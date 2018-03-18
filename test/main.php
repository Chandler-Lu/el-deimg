<?php
	date_default_timezone_set("Asia/Shanghai");
	$str1=date("md_His");
	mkdir('/Users/chandler/text/'.$str1);
	mkdir("/Users/chandler/text/$str1/img/");
	

	if($_FILES["file"]["size"] < 12079595)
  {
  if ($_FILES["file"]["error"] > 0)
    {
    echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
    }
  else
    {
   $uptype = explode(".", $_FILES["file"]["name"]); 
   $newname = "$str1".".".$uptype[1]; 
   //echo($newname); 
   $_FILES["file"]["name"] = $newname; 
    echo "Upload: " . $_FILES["file"]["name"] . "<br />";
    echo "Type: " . $_FILES["file"]["type"] . "<br />";
    echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
    echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";


    if (file_exists("/Users/chandler/text/$str1/img/" . $_FILES["file"]["name"]))
      {
      echo $_FILES["file"]["name"] . " already exists. ". "<br />";
      }
    else
      {
      move_uploaded_file($_FILES["file"]["tmp_name"],
      "/Users/chandler/text/$str1/img/" . $_FILES["file"]["name"]);
      echo "Stored in: " . "/Users/chandler/text/$str1/img/" . $_FILES["file"]["name"] . "<br />";
      }
    }
  }
else
  {
  echo "Invalid file";
  }

require_once("decoder.php");
decrypt_elimg("/Users/chandler/text/$str1/img/$str1.img","/Users/chandler/text/$str1/");

    
function list_dir($dir){
    	$result = array();
    	if (is_dir($dir)){
    		$file_dir = scandir($dir);
    		foreach($file_dir as $file){
    			if ($file == '.' || $file == '..'){
    				continue;
    			}
    			elseif (is_dir($dir.$file)){
    				$result = array_merge($result, list_dir($dir.$file.'/'));
    			}
    			else{
    				array_push($result, $dir.$file);
    			}
    		}
    	}
    	return $result;
    }

$datalist = list_dir("/Users/chandler/text/$str1/");
$filename = "/Users/chandler/text/$str1.zip";    

if(!file_exists($filename)){      
    $zip = new ZipArchive();   
    if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {   
        exit('None');
    }   
    foreach( $datalist as $val){   
        if(file_exists($val)){   
            $zip->addFile( $val, basename($val));   
        }   
    }   
    $zip->close();   
}

$pathToFile = '/Users/chandler/text/0318_183302.zip';//文件绝对路径
$downloadName = '0318_183302.zip';//下载后的文件名
 //输入文件标签
 Header("Cache-Control: must-revalidate, post-check = 0, pre-check = 0");
 Header("Content-type: application/octet-stream");
 Header("Content-type: application/foece-download");
 Header("Content-type: application/download");
 Header("Accept-Ranges: bytes");
 Header("Accept-Length: " . filesize($pathToFile));
 Header("Content-Disposition: filename=" . $downloadName);

 //输出文件内容
 $file = fopen($pathToFile, "r");
 echo fread($file, filesize($pathToFile));
 fclose($file);
 ?>
