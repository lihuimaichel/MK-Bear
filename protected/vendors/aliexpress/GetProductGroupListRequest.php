<?php
/**
 * @desc 获取分组
 * @since 2015-09-08
 */
class GetProductGroupListRequest extends AliexpressApiAbstract{ 
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.getProductGroupList';
    }
   
    public function setRequest(){
        $request = array();
        $this->request = $request;
        return $this;
    }
}