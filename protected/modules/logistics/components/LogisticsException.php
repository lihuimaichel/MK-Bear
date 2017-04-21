<?php
/**
 * LogisticsException class file.
 *
 * @author Gordon
 */

/**
 * WarehosesException represents an exception caused by invalid operations of end-users.
 *
 * Error handlers may use this status code to decide how to format the error page.
 *
 * @author Gordon
 * @package logs.components
 */
class LogisticsException extends UebException
{	

	/**
	 * Constructor.
	 * @param integer $status
	 * @param string $message error message
	 * @param integer $code error code
	 */
	public function __construct($message = null, $status = null, $code=0)
	{	
        $this->statusCode = $status;
		parent::__construct($message,$code);
	}
}
