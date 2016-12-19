#分片上传

##使用说明
\<br>
  把FileUpload.php这个类引入到项目中即可
  例子：\<br>
  ~~~ php
include 'FileUpload.php';
$rootDir = 'upload';//上传根目录
$subDir  = '20161219'; //子目录
$up = new FileUpload($rootDir,$subDir);
$res = $up->upload();
$error = $up->getError();
~~~ php
