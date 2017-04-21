<?php
/**
 * @desc 创建分组
 * @since 2015-09-08
 */
class CreateProductGroupRequest extends AliexpressApiAbstract{ 
    
	/**@var string 产品的分组名称*/
	public $_name	= '';
    /**@var Long 产品的分组ID*/
    public $_parentId = '';

    public function setName($name) {
    	$this->_name = $name;
    }
    
    public function setParentId($parentId) {
    	$this->_parentId = $parentId;
    }
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.createProductGroup';
    }
    
    public function setRequest(){
        $request = array();
		$request['parentId'] = $this->_parentId;
		$request['name']	 = $this->_name;
		$this->request = $request;
		return $this;
    }
}