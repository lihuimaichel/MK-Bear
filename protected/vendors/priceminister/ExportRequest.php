<?php
/**
 * @desc 刊登 csv格式
 * @author qzz
 * @since 2016-12-06
 */
class ExportRequest extends PriceministerApiAbstract{

    public $_action = "export";
	public $_version = "2014-11-04";
	public $_urlPath = "stock_ws";
	
	public $_nexttoken = null;
	public $_scope = null;

    /**
     * @desc 设置请求参数
     * @see PlatformApiInterface::setRequest()
     */
    public function setRequest(){
        $request = array();
        if($this->_nexttoken){
            $request['nexttoken'] = $this->_nexttoken;
        }
        if($this->_scope){
            $request['scope'] = $this->_scope;
        }
        //等待数据过来的时间长
        $this->_timeout = 1800;
        //$this->_connecttimeout = 60;
        $this->request = $request;
        return $this;
    }

    public function setNexttoken($nexttoken){
    	$this->_nexttoken = $nexttoken;
    	return $this;
    }

    public function setScope($scope){
        $this->_scope = $scope;
        return $this;
    }

}