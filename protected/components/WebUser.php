<?php
/**
 * @desc 用户部件
 * @author Gordon
 */
class WebUser extends CWebUser { 

	private $_model;
	public  $user_full_name='';
	public  $user_name='';
	

	/**
	 * @desc 获取用户名
	 * @return string
	 */
	function getFull_name(){ 
    	$user = $this->loadUser(Yii::app()->user->id); 
    	if($user){
    	    return $user->user_full_name;
    	}else{
    	    return '';
    	}
	}

	/**
	 * @desc根据ID获取用户对象
	 * @param string $id
	 * @return 
	 */ 
	protected function loadUser($id=null) { 
        if($this->_model===null) 
        { 
            if($id!==null) 
                $this->_model=User::model()->findByPk($id); 
        } 
        return $this->_model; 
    }
    
    
    public function __get($name)
    {
    	if ($this->hasState('__userInfo')) {
    		$user=$this->getState('__userInfo',array());
    		if (isset($user[$name])) {
    			return $user[$name];
    		}
    	}
    	 
    	return parent::__get($name);
    }
     
    public function login($identity, $duration=0) {
    	$this->setState('__userInfo', $identity->getUser());
    	parent::login($identity, $duration);
    }
    
}