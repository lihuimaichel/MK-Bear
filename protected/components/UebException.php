<?php
/**
 * UebException class file.
 * 
 * UebException represents an exception caused by invalid operations of end-users.
 * Error handlers may use this status code to decide how to format the error page.
 *
 * @author @author Bob <zunfengke@gmail.com>
 * @package application.components
 */
class UebException extends CException
{
	/**
	 * @var status code.
	 */
	public $statusCode = null;

	/**
	 * Constructor.	
	 * @param string $message error message
	 * @param integer $code error code
	 */
	public function __construct($message = null, $code=0)
	{		
        if (! empty($this->statusCode) ) {
            $message = '['. $this->statusCode.'] ' .$message;
        }
		parent::__construct($message,$code);
	}
}
