<?php
/**
Release under MIT License
Copyright (C) 2013 Jeff Cai

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
**/

require_once("util.php");

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

?>