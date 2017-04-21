<?php
/**
 * ULogRouter class file, file save log
 *
 * @author Bob <zunfengke@gmail.com>
 * 
 */
class UFileLogRoute extends ULogRoute
{
	/**
	 * @var integer maximum log file size
	 */
	private $_maxFileSize=1024; // in KB
	/**
	 * @var integer number of log files used for rotation
	 */
	private $_maxLogFiles=5;
	/**
	 * @var string directory storing log files
	 */
	private $_logPath;
	/**
	 * @var string log file name
	 */
	private $_logFile='api.log';


	/**
	 * Initializes the route.
	 * This method is invoked after the route is created by the route manager.
	 */
	public function init()
	{
		parent::init();
		if($this->getLogPath()===null)
			$this->setLogPath(Yii::app()->getRuntimePath());
	}

	/**
	 * @return string directory storing log files. Defaults to application runtime path.
	 */
	public function getLogPath()
	{
		return $this->_logPath;
	}

	/**
	 * @param string $value directory for storing log files.
	 * @throws CException if the path is invalid
	 */
	public function setLogPath($value)
	{
		$this->_logPath=realpath($value);
		if($this->_logPath===false || !is_dir($this->_logPath) || !is_writable($this->_logPath))
			throw new CException(Yii::t('yii','CFileLogRoute.logPath "{path}" does not point to a valid directory. Make sure the directory exists and is writable by the Web server process.',
				array('{path}'=>$value)));
	}

	/**
	 * @return string log file name. Defaults to 'application.log'.
	 */
	public function getLogFile()
	{
		return $this->_logFile;
	}

	/**
	 * @param string $value log file name
	 */
	public function setLogFile($value)
	{
		$this->_logFile=$value;
	}

	/**
	 * @return integer maximum log file size in kilo-bytes (KB). Defaults to 1024 (1MB).
	 */
	public function getMaxFileSize()
	{
		return $this->_maxFileSize;
	}

	/**
	 * @param integer $value maximum log file size in kilo-bytes (KB).
	 */
	public function setMaxFileSize($value)
	{
		if(($this->_maxFileSize=(int)$value)<1)
			$this->_maxFileSize=1;
	}

	/**
	 * @return integer number of files used for rotation. Defaults to 5.
	 */
	public function getMaxLogFiles()
	{
		return $this->_maxLogFiles;
	}

	/**
	 * @param integer $value number of files used for rotation.
	 */
	public function setMaxLogFiles($value)
	{
		if(($this->_maxLogFiles=(int)$value)<1)
			$this->_maxLogFiles=1;
	}

	/**
	 * Saves log messages in files.
	 * @param array $logs list of log messages
	 */
	protected function processLogs($logs)
	{      
        foreach ($logs as $key => $val) {
            $dirPath = $this->getLogPath(). DS . $key. DS. date('Ymd'). DS . substr(date('Y-m-d H:i:s'), 11, 2);           
            if (!is_dir( $dirPath) ) {
                @mkdir($dirPath, 0777, true);
            }           
            $logFile = $dirPath . DS . $this->getLogFile();
            if(@filesize($logFile)>$this->getMaxFileSize()*1024)
                $this->rotateFiles($key);
            $fp=@fopen($logFile,'a');
            @flock($fp,LOCK_EX);
            foreach($val as $log) {               
                 @fwrite($fp,$this->formatLogMessage($log[0],$log[1],$log[2],$log[3],$log[4],$log[5]));
            }              
            @flock($fp,LOCK_UN);
            @fclose($fp);
        }		
	}

	/**
	 * Rotates log files.
	 */
	protected function rotateFiles($type)
	{
		$file = $this->getLogPath().DS.$type. DS. date('Ymd') .DS. $this->getLogFile();
		$max=$this->getMaxLogFiles();
		for($i=$max;$i>0;--$i)
		{
			$rotateFile=$file.'.'.$i;
			if(is_file($rotateFile))
			{
				// suppress errors because it's possible multiple processes enter into this section
				if($i===$max)
					@unlink($rotateFile);
				else
					@rename($rotateFile,$file.'.'.($i+1));
			}
		}
		if(is_file($file))
			@rename($file,$file.'.1'); // suppress errors because it's possible multiple processes enter into this section
	}
}
