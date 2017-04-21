<?php
/**
 * @desc 获取分类
 * @author wx
 * @since 2015-09-12
 */
class GetCategoryRequest extends AliexpressApiAbstract{ 
    
    /**@var integer 分类ID*/
    protected $_cateId = null;
    
    /**
     * @desc 设置分类id
     * @param integer $cateId
     */
    public function setCateId($cateId){
        $this->_cateId = $cateId;
    }
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.getChildrenPostCategoryById';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
               'cateId' => $this->_cateId,
        );
       
        $this->request = $request;
        return $this;
    }
}