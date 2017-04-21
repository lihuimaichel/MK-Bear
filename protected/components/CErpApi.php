<?php
class CErpApi extends CComponent{
    
    /**@var 请求方*/
    public $_client = 'erp_market';
    
    /**@var 服务端*/
    public $server = null;
    
    /**@var 验证key*/
    public $key = null;
    
    /**@var 交互地址 */
    public $baseUrl = null;
    
    /**@var 调用的function*/
    public $function = null;
    
    /**@var 请求信息*/
    public $request = null;
    
    /**@var 相应数据*/
    public $response = null;
    
    public function init(){}
    
    /**
     * @desc 设置请求服务方
     */
    public function setServer($server){
        $config = ConfigFactory::getConfig('serverKeys');
        if( !isset($config[$server]) ){
            throw new CException(Yii::t('system', 'Server Does Not Exists'));
        }
        $this->server   = $server;
        $this->key      = $config[$server]['key'];
        $this->baseUrl  = $config[$server]['url'];
        return $this;
    }
    
    /**
     * @desc 设置调用function
     * @param string $function
     */
    public function setFunction($function){
        $this->function = $function;
        return $this;
    }
    
    /**
     * @desc 设置交互参数
     * @param unknown $params
     * @return CErpApi
     */
    public function setRequest($params=array()){
        $this->request = array(
                'col'   => json_encode(array(
						'client'	=> $this->_client,
						'key'		=> $this->key,
						'method'	=> $this->function,
						'data'		=> $params,
				)),
        );
        return $this;
    }
    
    /**
     * @desc 发送请求
     * @return CErpApi
     */
    public function sendRequest(){
        try {
            $response = Yii::app()->curl->post($this->baseUrl, $this->request);
            $this->response = json_decode($response);
        } catch (Exception $e ) {}
        return $this;
    }
    
    /**
     * @desc 检测是否成功
     * @return boolean
     */
    public function getIfSuccess(){
        if(isset($this->response->Ack) && $this->response->Ack == 'SUCCESS'){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * @desc 获取失败信息
     * @return string
     */
    public function getErrorMsg(){
        $errorMessage = '';
        if( isset($this->response->ErrorMsg) ){
            $errorMessage .= $this->response->ErrorMsg;
        }
        return $errorMessage;
    }
    
    /**
     * @desc 返回响应内容
     * @return mixed
     */
    public function getResponse(){
    	if(!isset($this->response->Data->data)) return array();
        return MHelper::objectToArray($this->response->Data->data);
    }
}