<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 * @author Bob <zunfengke@gmail.com>
 */
class UserIdentity extends CUserIdentity {

    private $_id;
    public $user = null;//add by ethanhu 2013.11/11

    /**
     * Authenticates a user.
     * @return boolean whether authentication succeeds.
     */
    public function authenticate() {
//      $user = User::model()->find('LOWER(user_name)=?', array(strtolower($this->username)));
		//Support English name to log in，add by ethanhu 2014.1.10
        $user = User::model()->find('(LOWER(user_name)=? or LOWER(en_name)=? ) and user_status=1', array(strtolower($this->username),strtolower($this->username)));
        if ($user === null) {
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        } else if (!$user->validatePassword($this->password)) {
            $this->errorCode = self::ERROR_PASSWORD_INVALID;
        } else {
            $this->_id = $user->id;
            $this->username = $user->user_name;
			$this->errorCode = self::ERROR_NONE;
			$this->setUser($user);//add by ethanhu 2013.11/11,add other userinfo
        }
        return $this->errorCode == self::ERROR_NONE;
    }

    /**
     * @return integer the ID of the user record
     */
    public function getId() {
        return $this->_id;
    }
    /*
     * add by ethanhu 2013.11/11
     */
    public function getUser()
    {
    	return $this->user;
    }
    /*
     * add by ethanhu 2013.11/11
    */
    public function setUser(CActiveRecord $user)
    {
    	$this->user=$user->attributes;
    }
    
    

}