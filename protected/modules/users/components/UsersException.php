<?php
/**
 * UsersException represents an exception caused by invalid operations of end-users.
 * Error handlers may use this status code to decide how to format the error page.
 * @author @author Bob <zunfengke@gmail.com>
 * @package users.components
 */
class UsersException extends UebException
{	

	/**
	 * Constructor.
	 * @param integer $status
	 * @param string $message error message
	 * @param integer $code error code
	 */
	public function __construct($message = null, $status, $code=0)
	{	
        $this->statusCode = $status;
		parent::__construct($message,$code);
	}
}
