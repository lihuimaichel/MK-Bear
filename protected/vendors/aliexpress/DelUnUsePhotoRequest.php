<?php
/**
 * @desc 删除未被引用图片
 * @author liuj
 * @since 2016-05-25
 */
class DelUnUsePhotoRequest extends AliexpressApiAbstract{ 

    
    /** @var long 图片ID **/
    protected $_imageRepositoryId = null;
    
    
    /**
     * @desc 设置图片id
     * @param LONG $imageRepositoryId
     */
    public function setImageRepositoryId($imageRepositoryId){
        $this->_imageRepositoryId = $imageRepositoryId;
    }
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.delUnUsePhoto';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array();
        $request['imageRepositoryId'] = $this->_imageRepositoryId;
        $this->request = $request;
        return $this;
    }
}