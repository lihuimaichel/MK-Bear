<?php
/**
 * @desc 刊登 csv格式
 * @author qzz
 * @since 2016-12-26
 */
class genericImportFileRequest extends PriceministerApiAbstract{

    public $_action = "genericimportfile";
	public $_version = "2015-02-02";
	public $_urlPath = "stock_ws";
	
	public $_xmlFile = null;
    
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array(
			'file'	=>	$this->_xmlFile,
        );
        $this->_isPost = true;
        $this->request = $request;
        return $this;
    }
    
    
    public function setXmlFile($file){
    	$this->_xmlFile = '@'.$file;
    	return $this;
    }

}