<?php
include 'FileUpload.php';
$rootDir = 'upload';
$subDir  = '20161219'; 
$up = new FileUpload($rootDir,$subDir);
$res = $up->upload();
$error = $up->getError();
var_dump($res);
var_dump($error);