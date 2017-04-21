<?php
/**
 * Hash File Path
 * 
 * @package Application.components
 * @auther Bob <zunfengke@gmail.com>
 */
class HashFilePath extends CComponent
{
	/**
	 * @var string the directory to store the files. Defaults to null, meaning	
	 */
	public $baseFilePath;

	/**
	 * @var integer the level of sub-directories to store cache files. Defaults to 0,
	 * meaning no sub-directories. If the system has huge number of cache files (e.g. 10K+),
	 * you may want to set this value to be 1 or 2 so that the file system is not over burdened.
	 * The value of this property should not exceed 16 (less than 3 is recommended).
	 */
	public $directoryLevel = 0;  
    
    /**
     * set directory level
     * 
     * @param int $level
     * @return \HashFilePath
     */
    public function setDirectoryLevel($level) {
        $this->directoryLevel = $level;
        
        return $this;
    }

	/**
	 *  set base file path	
	 */
	public function setBaseFilePath($baseFilePath = null) {		
		if ( $baseFilePath === null)
			$this->baseFilePath = Yii::getPathOfAlias('webroot'). '/' .'download';
        else 
            $this->baseFilePath = $baseFilePath;
        
		if (! is_dir($this->baseFilePath))
			mkdir($this->baseFilePath, 0777, true);
        
        return $this;
	}       

    /**
	 * Returns the file path given the  key.
	 * @param string $key key
	 * @return string the file path
	 */
	public function getFilePath($key) {
		if( $this->directoryLevel > 0) {
			$base = $this->baseFilePath;
			for( $i=0; $i < $this->directoryLevel; ++$i ) {
				if(($prefix = substr($key,$i+$i,2))!==false)
					$base.= '/'.$prefix;
			}           
            if (! is_dir($base))
			mkdir($base, 0777, true);
            
			return $base.'/'.$key;
		}
		else
			return $this->baseFilePath.'/'.$key;
	}
	
}
