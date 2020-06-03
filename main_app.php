<html lang="zh">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/cover.css">
    <meta charset="UTF-8">
    <title>IMG - B00</title>
    <style type="text/css">
        #browse{
            order: 1px solid #ccc;
            padding: 4px;
            border-radius: 4px;
            background-color: #2c9a8a;
            color: #fff;
        }
    </style>
</head>

  <body class="text-center">
    <div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
  <header class="masthead mb-auto">
    <div class="inner">
      <h3 class="masthead-brand">EL双排键解密工具</h3>
      <nav class="nav nav-masthead justify-content-center">
	  <a class="nav-link active" href="http://www.cndzq.com/bbs/xcx/">返回<span style="color:#d74c3f">第一键盘</span></a>
      </nav>
    </div>
  </header>


        
  <main role="main" class="inner cover">

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
		//echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
	} else {
		mkdir($str1);
		mkdir("$str1/img/");
		$uptype = explode(".", $_FILES["file"]["name"]);
		$newname = "$str1" . ".img";
		$_FILES["file"]["name"] = $newname;
		echo "Upload: " . $_FILES["file"]["name"] . "<br />";
		echo "Type: " . $_FILES["file"]["type"] . "<br />";
		echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
		//echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";
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
echo "</br><p>HI久等了，这是一份新出炉的音色文件，出炉时间：$str2 , 祝练琴愉快～</p>";
echo "<p>点击下载吧 -></p><p> <a class='btn btn-lg btn-secondary' href=" . $str1 . ".zip>Download</a></p>";

?>

</main>
	<div style="text-align:center;">
        <p><img src="img/icon.png" width="100" height="100" /></p>
  </div>

  <footer class="mastfoot mt-auto">
    <div class="inner">
      <p>项目最新修改时间：2020-03-29 10:52:04</p>
      <p>2019 cndzq.com·第一键盘</p>
    </div>
  </footer>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script
<script src="js/bootstrap.min.js"></script>
<script type="text/javascript">
 //触发隐藏的file表单
 function makeThisfile(){
        $('#thisfile').click();
  }
 
   //file表单选中文件时,让file表单的val展示到showname这个展示框
  $('#thisfile').change(function(){
        $('#showname').val($(this).val())
    })
</script> 
</body>

</html>
