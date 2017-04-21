<?php
/**
 * @desc 声明发货
 * @author Gordon
 * @since 2015-08-04
 */
class QueryPromiseTemplateByIdRequest extends AliexpressApiAbstract{ 
    protected $_templateId = null;
    
    public function setTemplateId($id) {
    	$this->_templateId = $id;
    }
    
    public function setRequest(){
		$request['templateId'] = $this->_templateId;
        $this->request = $request;
        return $this;
    }

    public function setApiMethod(){
    	$this->_apiMethod = 'api.queryPromiseTemplateById';
    }    
}