<?php
//ob_start();
date_default_timezone_set("Asia/Shanghai");
//以上传时间命名新文件夹
function getRandChar($length){
     $str = null;
     $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";//大小写字母以及数字
     $max = strlen($strPol)-1;
     
     for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)];
     }
     return $str;
}
$str0="el_";
$str1=$str0.getRandChar(6);
$str2=date("md_His");
mkdir($str1);
//在该文件夹下创建以img命名的新文件夹
mkdir("$str1/img/");
	
//上传、并将上传文件重名名于所属文件夹同名
if($_FILES["file"]["size"] < 12079595) {
  	if ($_FILES["file"]["error"] > 0) {
    echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
    } else {
    	$uptype = explode(".", $_FILES["file"]["name"]); 
    	$newname = "$str1".".".$uptype[1]; 
    	$_FILES["file"]["name"] = $newname; 
    	 echo "Upload: " . $_FILES["file"]["name"] . "<br />";
    	 echo "Type: " . $_FILES["file"]["type"] . "<br />";
    	 echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
    	 echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";
	if (file_exists("$str1/img/" . $_FILES["file"]["name"])) {
		echo $_FILES["file"]["name"] . " already exists. ". "<br />";
	} else {
		move_uploaded_file($_FILES["file"]["tmp_name"],"$str1/img/" . $_FILES["file"]["name"]);
		echo "Stored in: " . "$str1/img/" . $_FILES["file"]["name"] . "<br />";
	}
	}
}
else {
	echo "Invalid file";
	}

//解密调用
require_once("decoder.php");
decrypt_elimg("$str1/img/$str1.img","$str1/");

function list_dir($dir){
    	$result = array();
    	if (is_dir($dir)) {
    		$file_dir = scandir($dir);
    		foreach($file_dir as $file) {
    			if ($file == '.' || $file == '..'){
    				continue;
    			}
    			elseif (is_dir($dir.$file)) {
    				$result = array_merge($result, list_dir($dir.$file.'/'));
    			}
    			else {
    				array_push($result, $dir.$file);
    			}
    		}
    	}
    	return $result;
}

$datalist=list_dir("$str1/");
$filename = "$str1.zip";

if(!file_exists($filename)){      
    $zip = new ZipArchive();   
    if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {   
        exit('None');
    }   
    foreach($datalist as $val){   
        if (file_exists($val)) {   
            $zip->addFile( $val, basename($val));   
        }   
    }   
    $zip->close();   
}
echo "</br><h1>HI，这是一份新出炉的音色文件，出炉时间：$str2 , 祝练琴愉快～<h1>";
echo "<h1>点击下载吧 -> <a href=".$str1.".zip>Download</a><h1>";

?>
