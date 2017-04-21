<?php
/**
 * @desc 下载订单
 * @author lihy
 * @since 2016-07-01
 */
class GetNewSalesRequest extends PriceministerApiAbstract{
    public $_action = "getnewsales";
	public $_version = "2016-03-16";
	public $_urlPath = "sales_ws";
	
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
               
        );
        $this->request = $request;
        return $this;
    }
}