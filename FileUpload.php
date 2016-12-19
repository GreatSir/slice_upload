<?php
/**
* 
*/
class FileUpload
{
	//上传根目录
	private $rootDir='upload';
	//临时目录
	private $targetDir = 'upload_tmp';
	//上传文件子目录
	private $subDir = '';
	//是否清除旧缓存文件
	private $cleanupTargetDir = true; // Remove old files
	//缓存文件保存时间
    private $maxFileAge = 5 * 3600; // Temp file age in seconds
  	/**
     * 本地上传错误信息
     * @var string
     */
    private $error = ''; //上传错误信息
	
	function __construct($rootDir,$subDir)
	{
		# code...
		$this->rootDir = $rootDir;
		$this->subDir  = $subDir;
	}
	/**
	 * 检测上传目录
	 * @param   string $dir
	 * @return   boolean          检测结果，true-通过，false-失败 
	 */
	public function checkDir($dir)
	{
		if (!$this->makeDir($dir)) {
			return false;
		} else {
			/* 检测目录是否可写 */
			if (!is_writable($dir)) {
			    $this->error = '上传目录 ' . $dir. ' 不可写！';
			    return false;
			} else {
			    return true;
			}
		}
	}
	/**
	 * 上传文件
	 * @return [type] [description]
	 */
	public function upload()
	{
		
		$files  =   $_FILES;
		if(empty($files)){
			$this->error = '没有上传的文件！';
			return false;
		}
		//检测上传根目录
		if(!$this->checkDir($this->rootDir)){
			$this->error = $this->getError();
			return false;
		}
		

		//检测子目录
		if(!$this->checkDir($this->rootDir . DIRECTORY_SEPARATOR . $this->subDir)){
			$this->error = $this->getError();
			return false;
		}

		
		//$savePath = '123';
		//return $savePath;die;
		$fileName = $this->setName($files);
		
		$filePath = $this->targetDir . DIRECTORY_SEPARATOR . $fileName;
		$savePath = $this->rootDir . DIRECTORY_SEPARATOR . $this->subDir . DIRECTORY_SEPARATOR . $fileName;
		$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;
		if($this->save($filePath,$chunk,$files)){
			/*if($chunks>1)
			{
				if($this->mergeFile($filePath,$chunks,$savePath)){
					$res = array(
						'savePath'=> $savePath,
						'msg'     => 'sucess',
						);
					return $res;
				}else{
					$this->error = $this->getError();
					return false;
				}
			}else{
				
			}*/
			if($this->mergeFile($filePath,$chunks,$savePath)){
					$res = array(
						'savePath'=> $savePath,
						'msg'     => 'sucess',
						);
					return $res;
				}else{
					$this->error = $this->getError();
					return false;
				}
		}else{
			$this->error = $this->getError();
			return false;
		}
		

	}
	/**
	 * /
	 * @param  [array] $files [文件对象]
	 * @return [sting]        [保存文件名]
	 */
	public function setName($file)
	{
		
		if (isset($_REQUEST["guid"])) {
			
			$extension = pathinfo($file["file"]["name"], PATHINFO_EXTENSION); 
			$fileName = $_REQUEST["guid"].'.'.$extension;
		} else {
			$fileName = $file["file"]["name"];
		}
		return $fileName;
	}
	/**
	 * 保存文件
	 */
	public function save($filePath,$chunk,$file)
	{
		if (!$out = @fopen("{$filePath}_{$chunk}.parttmp", "wb")) {
			$this->error = 'Failed to open output stream 139';
			return false;
		    //die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
		}
		if (!empty($file)) {
		    if ($file["file"]["error"] || !is_uploaded_file($file["file"]["tmp_name"])) {
		    	$this->error = 'Failed to move uploaded file';
		    	return false;
		        //die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
		    }

		    // Read binary input stream and append it to temp file
		    if (!$in = @fopen($file["file"]["tmp_name"], "rb")) {
		    	$this->error = 'Failed to open input stream';
		    	return false;
		        //die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
		    }
		} else {
		    if (!$in = @fopen("php://input", "rb")) {
		    	$this->error = 'Failed to open input stream';
		    	return false;
		        //die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
		    }
		}

		while ($buff = fread($in, 4096)) {
		    fwrite($out, $buff);
		}

		@fclose($out);
		@fclose($in);

		rename("{$filePath}_{$chunk}.parttmp", "{$filePath}_{$chunk}.part");
		return true;

	}
	/**
	 * 合并文件
	 * @return [type] [description]
	 */
	public function mergeFile($filePath,$chunks,$savePath)
	{
		$index = 0;
		$done = true;
		for( $index = 0; $index < $chunks; $index++ ) {
		    if ( !file_exists("{$filePath}_{$index}.part") ) {
		        $done = false;
		        break;
		    }
		}
		if ( $done ) {
		    if (!$out = @fopen($savePath, "wb")) {
		    	$this->error = 'merge：Failed to open output stream';
		    	return false;
		        //die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
		    }

		    if ( flock($out, LOCK_EX) ) {
		        for( $index = 0; $index < $chunks; $index++ ) {
		            if (!$in = @fopen("{$filePath}_{$index}.part", "rb")) {
		                break;
		            }

		            while ($buff = fread($in, 4096)) {
		                fwrite($out, $buff);
		            }

		            @fclose($in);
		            @unlink("{$filePath}_{$index}.part");
		        }

		        flock($out, LOCK_UN);
		    }
		    @fclose($out);
		}
		return true;
	}
	/**
	 * 创建目录
	 * @param  string $path 要创建的目录
     * @return boolean      创建状态，true-成功，false-失败
	 */
	private function makeDir($path)
	{
		//$dir = $this->rootPath . $savepath;
        if(is_dir($path)){
            return true;
        }

        if(mkdir($path, 0777, true)){
            return true;
        } else {
            $this->error = "目录 {$path} 创建失败！";
            return false;
        }
	}
	 /**
     * 获取最后一次上传错误信息
     * @return string 错误信息
     */
    public function getError(){
        return $this->error;
    }
}