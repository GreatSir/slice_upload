# slice_upload
分片上传php
使用说明
  把FileUpload.php这个类引入到项目中
  例子：
include 'FileUpload.php';
$rootDir = 'upload';//上传根目录
$subDir  = '20161219'; //子目录
$up = new FileUpload($rootDir,$subDir);
$res = $up->upload();
$error = $up->getError();
