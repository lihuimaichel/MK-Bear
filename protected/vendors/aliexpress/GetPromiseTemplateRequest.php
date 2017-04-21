<?php
/**
 * @desc 服务模板
 * @author tony
 * @since 2015-09-14
 */
class GetPromiseTemplateRequest extends AliexpressApiAbstract{ 

    
    /**@var integer 模板ID*/
    public $_templateId = null;
    
    
    /**
     * @desc 设置分类id
     * @param integer $cateId
     */
    public function setTemplateID($templateID){
        $this->_templateId = $templateID;
    }
    
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.queryPromiseTemplateById';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
        		'templateId'=>$this->_templateId,
        );
       
        $this->request = $request;
        return $this;
    }
}