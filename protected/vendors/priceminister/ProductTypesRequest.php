<?php
/**
 * @desc xml刊登分类
 * @author qzz
 * @since 2016-12-27
 */
class ProductTypesRequest extends PriceministerApiAbstract{
    public $_action = "producttypes";
	public $_version = "2011-11-29";
	public $_urlPath = "stock_ws";
	
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array();
        $this->request = $request;
        return $this;
    }
}