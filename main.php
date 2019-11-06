<!-- *************************************************

Copyright:Chandler_Lu

Date:2018-04-01 16:17:55

Description:
1. 限制上传文件大小及上传文件类型
2. 上传文件直接命名为"el_6位随机字符串.img"，防止后缀大小写问题导致后期传参错误
3. 部署时注意：部分php默认设置禁止scandir，需手动取消该设置

**************************************************/ -->

<?php
header("Content-Type: text/html;charset=utf-8");
date_default_timezone_set("Asia/Shanghai");
//以上传时间命名新文件夹
function getRandChar($length)
{
	$str = null;
	$strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
	$max = strlen($strPol) - 1;

	for ($i = 0; $i < $length; $i++) {
		$str .= $strPol[rand(0, $max)];
	}
	return $str;
}
$str0 = "el_";
$str1 = $str0 . getRandChar(6);
$str2 = date("md_His");

//上传、并将上传文件重名名于所属文件夹同名
if ($_FILES["file"]["size"] < 1600000 && (
	(strstr($_FILES["file"]["name"], ".") == ".img")  || (strstr($_FILES["file"]["name"], ".") == ".ImG")  || (strstr($_FILES["file"]["name"], ".") == ".IMg")  || (strstr($_FILES["file"]["name"], ".") == ".Img")  || (strstr($_FILES["file"]["name"], ".") == ".iMg")  || (strstr($_FILES["file"]["name"], ".") == ".IMG")  || (strstr($_FILES["file"]["name"], ".") == ".iMG")  || (strstr($_FILES["file"]["name"], ".") == ".imG"))) {
	if ($_FILES["file"]["error"] > 0) {
		echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
	} else {
		mkdir($str1);
		mkdir("$str1/img/");
		$uptype = explode(".", $_FILES["file"]["name"]);
		$newname = "$str1" . ".img";
		$_FILES["file"]["name"] = $newname;
		echo "Upload: " . $_FILES["file"]["name"] . "<br />";
		echo "Type: " . $_FILES["file"]["type"] . "<br />";
		echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
		echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";
		if (file_exists("$str1/img/" . $_FILES["file"]["name"])) {
			echo $_FILES["file"]["name"] . " already exists. " . "<br />";
		} else {
			move_uploaded_file($_FILES["file"]["tmp_name"], "$str1/img/" . $_FILES["file"]["name"]);
			echo "Stored in: " . "$str1/img/" . $_FILES["file"]["name"] . "<br />";
		}
	}
} else {
	echo "Invalid file</br>";
	exit("错误的文件类型（仅支持img）或超过文件大小限制（不大于1.44M），文件上传失败，请重试");
}

//解密调用
require_once("decoder.php");
decrypt_elimg("$str1/img/$str1.img", "$str1/");

function list_dir($dir)
{
	$result = array();
	if (is_dir($dir)) {
		$file_dir = scandir($dir);
		foreach ($file_dir as $file) {
			if ($file == '.' || $file == '..') {
				continue;
			} elseif (is_dir($dir . $file)) {
				$result = array_merge($result, list_dir($dir . $file . '/'));
			} else {
				array_push($result, $dir . $file);
			}
		}
	}
	return $result;
}

$datalist = list_dir("$str1/");
$filename = "$str1.zip";

if (!file_exists($filename)) {
	$zip = new ZipArchive();
	if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
		exit('None');
	}
	foreach ($datalist as $val) {
		if (file_exists($val)) {
			$zip->addFile($val, basename($val));
		}
	}
	$zip->close();
}
echo "</br><h1>HI久等了，这是一份新出炉的音色文件，出炉时间：$str2 , 祝练琴愉快～<h1>";
echo "<h1>点击下载吧 -> <a href=" . $str1 . ".zip>Download</a><h1>";

?>