<?php
/**
 * LogsException class file.
 *
 * @author Bob <zunfengke@gmail.com>
 */

/**
 * LogsException represents an exception caused by invalid operations of end-users.
 *
 * Error handlers may use this status code to decide how to format the error page.
 *
 * @author @author Bob <zunfengke@gmail.com>
 * @package logs.components
 */
class LogsException extends UebException
{	
	/**
	 * Constructor.
	 * @param integer $status HTTP status code, such as 404, 500, etc.
	 * @param string $message error message
	 * @param integer $code error code
	 */
	public function __construct($status, $message=null, $code=0)
	{
		$this->statusCode = $status;
		parent::__construct($message,$code);
	}
}
