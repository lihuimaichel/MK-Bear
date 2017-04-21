<?php
/**
 * @desc xml刊登返回信息
 * @author qzz
 * @since 2017-1-3
 */
class GenericImportReportRequest extends PriceministerApiAbstract{
    public $_action = "genericimportreport";
	public $_version = "2011-11-29";
	public $_urlPath = "stock_ws";

    public $_fileId = null;
    public $_nextToken = null;
    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array();
        if($this->_fileId){
            $request['fileid'] = $this->_fileId;
        }
        if($this->_nextToken){
            $request['nexttoken'] = $this->_nextToken;
        }
        $this->request = $request;
        return $this;
    }

    public function setFileId($fileId){
        $this->_fileId = $fileId;
        return $this;
    }
    public function setNextToken($nextToken){
        $this->_nextToken = $nextToken;
        return $this;
    }
}