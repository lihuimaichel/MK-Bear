<?php
/**
* @desc Aliexpress PostCat Template (尺码模板)
* @author	tony
* @since	2015-09-19
*/
class GetPostCatTemplateRequest extends AliexpressApiAbstract{ 

    
    /**@var integer 模板ID*/
    public $_postCatId = null;
    
    
    /**
     * @desc 设置分类id
     * @param integer $cateId
     */
    public function setPostCatID($postcatID){
        $this->_postCatId = $postcatID;
    }
    
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.sizeModelsRequiredForPostCat';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
        		'postCatId'=>$this->_postCatId,
        );
       
        $this->request = $request;
        return $this;
    }
}