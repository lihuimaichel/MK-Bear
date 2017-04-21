<?php
/**
 * @desc 产品下线请求
 * @author tony
 * @since 2015-09-14
 */
class GetOfflineProductRequest extends AliexpressApiAbstract{ 

    /**@var integer 模板ID*/
    public $_productIds = null;
    
    /**
     * @desc 设置产品id
     * @param integer $cateId
     */
    public function setPrdouctID($productIds){
        $this->_productIds = $productIds;
    }
    
    
    public function setApiMethod(){
        $this->_apiMethod = 'api.offlineAeProduct';
    }
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
        		'productIds'=>$this->_productIds,
        );
       
        $this->request = $request;
        return $this;
    }
}