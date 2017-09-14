<?php
require_once("decoder.php");
require_once("elecfat.php");
require_once("imgfixer.php");

$raw = fixImg($raw);

//create session folder
$sysTmpDir = sys_get_temp_dir();
$tmpName = tempnam($sysTmpDir,"elec_");
unlink($tmpName);
mkdir($tmpName);
$extractDir =$tmpName . "/files"; 
mkdir($extractDir);

$elecFat = new ElectoneFat12($raw);
$elecFat->extractFromRoot($extractDir);

?>