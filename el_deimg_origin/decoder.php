<?php

/** Release under MIT License Copyright (C) 2013 Jeff Cai

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE. **/


// Predfine Section
define('MAX_TRACK', 84);
define('MAX_HEADER', 2);
define('MAX_SECTOR', 21);
define('BYTES_PER_SECTOR', 512);

function toInt16($strBytes,$offset = 0) {
        $low = ord($strBytes[$offset]);
        $high = ord($strBytes[$offset+1]);

        return $high*256 + $low;
}

function toInt32($strBytes,$offset = 0) {
        $low = toInt16($strBytes,$offset);
        $high = toInt16($strBytes,$offset+2);

        return $high*65536 + $low;
}

class Decryptor {
    public static function decodeBlock(&$strBytes,&$offset) {
    	$res = "";	
    	$blockDataLength = toInt16($strBytes,$offset);

    	$offset +=2;

    	$startIdx = $offset;
    	$specialByte = $strBytes[$offset++];

    	while($offset - $startIdx  < $blockDataLength) {
    		if ($strBytes[$offset] == $specialByte) {	
    			$res .= str_repeat($strBytes[$offset+1],ord($strBytes[$offset + 2]));
    			$offset+=3;
    		} else {	
    			$res .= $strBytes[$offset++];			
    		}
    	}

    	if ($offset - $startIdx != $blockDataLength) return false;
    	return $res;
    }

    public static function getRawImage($pathToHdImg) {
    	$name = "NO NAME";
    	$trackInfo = array();
    	$fh = fopen($pathToHdImg,"rb");
	if (!$fh) return false;
    	$buf = "";
    	while(!feof($fh)) {
    		$buf .= fread($fh,8192);
    	}
    	fclose($fh);

    	$parseIdx = 0;
    	$hd20Format = false;

    	if (ord($buf[0]) == 0xff && ord($buf[1]) == 0x18) {
    		$hd20Format = true;
    	}

    	if ($hd20Format) {
    		$labelLength = ord($buf[2]);
    		if ($labelLength > 11) return false;
    		if ($labelLength != 0 ) {
    			$header->name = substr($buf,3,$labelLength);
    		}
    		$parseIdx = 0x0e;
    		$headerLength = 2 + 1 + 11 + 2 + 84*2;
    	} else {
    		$parseIdx = 0x0e;
    		$headerLength = 2 + 1 + 11 + 2 + 84*2;
    	}

    	$trackNumber = ord($buf[$parseIdx++])+1;	// Seems HDCOPY remember the last tracker number here
    	$sectorNumber = ord($buf[$parseIdx++]);

    	if ($trackNumber == 0 || $trackNumber > MAX_TRACK ) return false;
    	if ($sectorNumber == 0 || $sectorNumber > MAX_SECTOR) return false;

    	for($j=0;$j<MAX_TRACK * MAX_HEADER;$j++) {
    		$trackInfo[$j] = ord($buf[$parseIdx+$j]);
    		if ($trackInfo[$j] > 1) return false;
    	}

    	$parseIdx += MAX_TRACK * MAX_HEADER;

    	$outbuf = "";

    	for($i=0;$i<$trackNumber * MAX_HEADER;$i++) {
    		if ($trackInfo[$i] == 0) {
    			$outbuf .= str_repeat(chr(0),BYTES_PER_SECTOR * $sectorNumber);
    		} else {
    			$blockOut = self::decodeBlock($buf,$parseIdx);
    			//if (!$blockOut) return false;
    			//if (strlen($blockOut) != BYTES_PER_SECTOR * $sectorNumber) return false;

    			$outbuf .=$blockOut;
    		}
    	}

    	return $outbuf;
    }

    public static function fixImg($raw) {
        $mediaDesc = ord($raw[512]);
        $imgLength = strlen($raw);
     
        $newRaw = "";
     
        if ($imgLength == 1474560 && $mediaDesc == 0x04) {
            // only 8 sector is okay for every track
            for($sidetrack=0;$sidetrack<80 * 2;$sidetrack++) {
                $newRaw .= substr($raw,$sidetrack * 18 * 512 , 8 * 512);            
            }
     
            return $newRaw;
        } else {
            return $raw;
        }
     
    }
}

class ElectoneFat12 {
    public function __construct($_diskContent) {
        $this->diskContent = $_diskContent;

        $this->bytesPerSector = 512; //BitConverter.ToInt16(diskContent,0x0b);

        $this->mediaDesc = ord($this->diskContent[$this->bytesPerSector]);

        switch ($this->mediaDesc)    {
            case 0x0f:    {
                $this->sectorPerCluster = 1;
                $this->reservedSectorCount = 1;
                $this->sectorsPerFAT = 9;
                $this->rootDirMaxEntries = 224;
                break;
            }
            case 0x04: {
                $this->sectorPerCluster = 2;
                $this->reservedSectorCount = 1;
                $this->sectorsPerFAT = 2;
                $this->rootDirMaxEntries = 112;
                break;
            }
            default: {
                $this->sectorPerCluster = 1;
                $this->reservedSectorCount = 1;
                $this->sectorsPerFAT = 9;
                $this->rootDirMaxEntries = 224;
                break;
            }

        }

        $this->dirOffset = $this->reservedSectorCount * $this->bytesPerSector + $this->sectorsPerFAT * 2 * $this->bytesPerSector;

        $this->dataOffset = $this->dirOffset + 32 * $this->rootDirMaxEntries;

        //Build FAT
        $this->endOfCluster = $this->getNextCluster(1);
    }

    private function extraAll($contentOfDirTable, $targetDir) {
        $offset = 0;
        $numOfRecord = strlen($contentOfDirTable)/32;

        for($i=0;$i<$numOfRecord;$i++) {
            $offset = $i * 32;
            $firstByte = ord($contentOfDirTable[$offset]);

            // 0xf6，seems undocumented. http://www.c-jump.com/CIS24/Slides/FAT/lecture.html
            if ($firstByte == 0x0 || $firstByte == 0x05 || $firstByte == 0x2e || $firstByte == 0xe5 || $firstByte == 0xf6) continue;

            $name = chop(substr($contentOfDirTable,$offset,8)) . '.' . chop(substr($contentOfDirTable,$offset+8,3));

            if (strpos($name, "\0") !== FALSE) continue;

            $firstCluter = toInt16($contentOfDirTable,$offset+0x1a);
            $size = toInt32($contentOfDirTable,$offset + 0x1c);

            $attr = ord($contentOfDirTable[$offset+0x0b]);

            if ($attr == 0x08) {
            	continue;
            }

            if ( ($attr & 0x10) == 0x10) {
                $this->extraAll($this->getDirTable($firstCluster),$targetDir);
            } else {
                // Extract file to targetdir
                echo "$targetDir/$name\n";
		$wfh = fopen("$targetDir/$name","wb");
                $decContent=$this->getFileContent($firstCluter,$size);
                fwrite($wfh,$decContent);
                fclose($wfh);
            }
        }
    }

    public function extractFromRoot($targetDir) {
        return $this->extraAll($this->getRootDirTable(),$targetDir);
    }

    private function getRootDirTable() {
        return substr($this->diskContent,$this->dirOffset,$this->rootDirMaxEntries * 32);
    }

    private function getDirTable($firstCluster) {
        $final = "";

        $nextCluster = $firstCluster;
        $culSize = 0;
        $writeSize = 0;
        $endFound = false;

        do {
            $writeSize = $sectorPerCluster * $bytesPerSector;
            $culSize+= $writeSize;
            $final .= $this->getClusterContent($nextCluster,$writeSize);
        } while($nextCluster != $endOfCluster && !$endFound);

        return $final;
    }

    private function getFileContent($firstCluster,$size) {
        $final = "";

        $nextCluster = $firstCluster;
        $culSize = 0;
        $writeSize = 0;
        $endFound = false;

        do {
            if ($culSize + $this->sectorPerCluster * $this->bytesPerSector > $size) {
                $writeSize = $size - $culSize;
                $endFound = true;
            } else {
                $writeSize = $this->sectorPerCluster * $this->bytesPerSector;
            }

            $culSize+= $writeSize;

            $final .= $this->getClusterContent($nextCluster,$writeSize);
            $nextCluster = $this->getNextCluster($nextCluster);
        } while($nextCluster != $this->endOfCluster && !$endFound);

        return $final;
    }

    private function getNextCluster($clusterNum) {
        $b=floor($clusterNum/2);

        $fatStart = $this->reservedSectorCount*512;
        $first = ord($this->diskContent[$fatStart + $b*3]);
        $second = ord($this->diskContent[$fatStart + $b*3 +1]);
        $thrid = ord($this->diskContent[$fatStart + $b*3 +2]);

        if ($clusterNum%2 == 0)    {
            return (($second &0x0f) << 8) + $first;
        }
        else {
            return ($thrid << 4) + ($second >> 4);
        }
    }

    private function getClusterContent($clusterNum,$size) {
        $offset = $this->dataOffset + ($clusterNum-2)*$this->bytesPerSector*$this->sectorPerCluster;
        return substr($this->diskContent,$offset,$size);
    }

    private $diskContent;
    private $bytesPerSector;
    private $sectorPerCluster;
    private $reservedSectorCount;
    private $sectorsPerFAT;
    private $rootDirMaxEntries;
    private $dirOffset;
    private $dataOffset;
    private $endOfCluster;
}

// Use this method to do all the tricks
// $imgfile -- Path of the IMG file (HDCOPY format)
// $extractDir -- Path to store extracted files.
 
function decrypt_elimg($imgfile,$extractDir) {
    
    $raw = Decryptor::getRawImage($imgfile);
    if (!$raw)
        return FALSE;

    $raw = Decryptor::fixImg($raw);

    $elecFat = new ElectoneFat12($raw);
    $elecFat->extractFromRoot($extractDir);
}

?>
