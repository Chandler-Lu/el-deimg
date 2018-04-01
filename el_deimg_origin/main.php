<?php
// Usage example
require_once("decoder.php");

// Assume we have a file name 'source.img', which is a HDCOPY image file of Electone disk
// We will decrypt it and extract all the files inside into current working directory.
decrypt_elimg("source.img",".");
