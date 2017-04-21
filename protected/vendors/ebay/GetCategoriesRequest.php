<?php
/**
 * @desc 获取站点分类信息
 * @author Gordon
 * @since 2015-06-02
 */
class GetCategoriesRequest extends EbayApiAbstract{
    
    /**@var 接口名*/
    public $_verb = 'GetCategories';
    
    /**@var 返回数据级别*/
    public $_DetailLevel = 'ReturnAll';
    
    /**
     * @desc 设置站点
     * @see EbayApiAbstract::setSiteID()
     */
    public function setSiteID($siteID){
        $this->_siteID = $siteID;
    }
    
    /**
     * @desc 设置请求
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $this->setSiteID($this->_siteID);
        $request = array(
            'RequesterCredentials' => array(
                'eBayAuthToken' => $this->getToken(),
            ),
            'DetailLevel'           => $this->_DetailLevel,
            'CategorySiteID'        => $this->_siteID,//这里需要拓展分类型siteID,如ebay motor  TODO
        );
        $this->request = $request;
        return $this;
    }
}