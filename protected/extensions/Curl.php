<?php
/**
 * @desc Curl Wrapper
 * @since 2015-06-22
 */
class Curl extends CComponent {

    private $_ch;
    
    public $options;

    /*curl返回结果info及error、errno*/
    private $curlResponse;
    
    private $_config = array(
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_FOLLOWLOCATION  => true,
        CURLOPT_AUTOREFERER     => true,
        CURLOPT_CONNECTTIMEOUT  => 60,
        CURLOPT_TIMEOUT         => 10,
        CURLOPT_SSL_VERIFYPEER  => false,
        CURLOPT_USERAGENT       => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:5.0) Gecko/20110619 Firefox/5.0'
    );
    
    /**
     * @desc Curl Get
     * @param string $url
     * @param array $params
     */
    public function get($url, $params = array()) {
        $this->setOption(CURLOPT_HTTPGET, true);
        return $this->_exec($this->buildUrl($url, $params));
    }

    /**
     * @desc Curl Post
     * @param string $url
     * @param array $data
     */
    public function post($url, $data = array()) {
    	$postMultipart = false;
        if( is_array($data) && count($data) ){
            $postBodyString = '';
            foreach ($data as $k => $v) {  
                if("@" != substr($v, 0, 1)) {
                    $postBodyString .= "$k=" . urlencode($v) . "&"; 
                }else {
                    $postMultipart = true;
                }
            }
            $data = substr($postBodyString,0,-1);
        }
        $this->setOption(CURLOPT_POST, true);
        $this->setOption(CURLOPT_POSTFIELDS, $data);
        return $this->_exec($url);
    }

    /**
     * @desc Curl Post 整合文件上传
     * @param string $url
     * @param array $data
     */
    public function postFile($url, $data = array()) {
        $postMultipart = false;
        if( is_array($data) && count($data) ){
            $postBodyString = '';
            foreach ($data as $k => $v) {
                if("@" != substr($v, 0, 1)) {
                    $postBodyString .= "$k=" . urlencode($v) . "&";
                }else {
                    $postMultipart = true;
                }
            }
            if($postMultipart == false){//文件上传不需要截取
                $data = substr($postBodyString,0,-1);
            }
        }
        $this->setOption(CURLOPT_POST, true);
        $this->setOption(CURLOPT_POSTFIELDS, $data);
        return $this->_exec($url);
    }

    /**
     * @desc Curl by Get
     * @param string $url
     * @param array $params
     * @return string
     */
    public function getByRestful($url, $params = array(), $headers=array()) {
        $path = '';
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $path .= rawurlencode($key).'/'.rawurlencode($value);
            }
            $url .= '/' . $path;
        }
        //$headers = array('Content-Type' => 'application/json') + $headers;
        //$this->setHeaders($headers);
        $this->setOption(CURLOPT_HTTPGET, true);
        return $this->_exec($url);
    }    

    /**
     * @desc Curl by Post
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    public function postByJson($url, $data = array(), $headers=array()) {
        $headers = array('Content-Type' => 'application/json') + $headers;
        $this->setHeaders($headers);
        $this->setOption(CURLOPT_POST, true);
        $this->setOption(CURLOPT_POSTFIELDS, json_encode($data));
        return $this->_exec($url);
    }

    /**
     * @desc Curl by put
     * @param  string $url    
     * @param  string $data   
     * @param  array  $params 
     * @param  array  $headers
     * @return string        
     */
    public function putByJson($url, $data, $params = array(), $headers=array()) {
        if($data) {
            $f = fopen('php://temp', 'rw+');
            fwrite($f, $data);
            rewind($f);

            $this->setOption(CURLOPT_INFILE, $f);
            $this->setOption(CURLOPT_INFILESIZE, strlen($data));            
        }
        $headers = array('Content-Type' => 'application/json') + $headers;
        $this->setHeaders($headers);
        $this->setOption(CURLOPT_PUT, true);
        $this->setOption(CURLOPT_POSTFIELDS, json_encode($params));
        return $this->_exec($url);
    }

    /**
     * @desc Curl by delete
     * @param  string $url    
     * @param  array  $params 
     * @param  array  $headers
     * @return string        
     */
    public function deleteByJson($url, $params = array(), $headers=array()) {
        $headers = array('Content-Type' => 'application/json') + $headers;
        $this->setHeaders($headers);
        $this->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->setOption(CURLOPT_POSTFIELDS, json_encode($params));
        return $this->_exec($url);
    }

    /**
     * @desc Curl 传输文件
     * @param string $url
     * @param array $data
     * @param array $params
     */
    public function put($url, $data, $params = array()) {
        $f = fopen('php://temp', 'rw+');
        fwrite($f, $data);
        rewind($f);

        $this->setOption(CURLOPT_PUT, true);
        $this->setOption(CURLOPT_INFILE, $f);
        $this->setOption(CURLOPT_INFILESIZE, strlen($data));
        return $this->_exec($this->buildUrl($url, $params));
    }

    /**
     * @desc HTTP DELETE操作
     * @param string $url
     * @param array $params
     */
    public function delete($url, $params = array()) {

        $this->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');

        return $this->_exec($this->buildUrl($url, $params));
    }

    /**
     * @desc 组建交互地址
     * @param unknown $url
     * @param unknown $data
     */
    public function buildUrl($url, $data = array()) {
        $parsed = parse_url($url);
        isset($parsed['query']) ? parse_str($parsed['query'], $parsed['query']) : $parsed['query'] = array();
        $params = isset($parsed['query']) ? array_merge($parsed['query'], $data) : $data;
        $parsed['query'] = ($params) ? '?' . http_build_query($params) : '';
        if (!isset($parsed['path']))
            $parsed['path'] = '/';

        return $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'] . $parsed['query'];
    }

    /**
     * @desc 设置Curl交互参数
     * @param array $options
     * @return Curl
     */
    public function setOptions($options = array()){
        curl_setopt_array($this->_ch, $options);
        return $this;
    }

    /**
     * @desc 设置Curl交互参数
     * @param string $option
     * @param string $value
     */
    public function setOption($option, $value) {
        curl_setopt($this->_ch, $option, $value);
        return $this;
    }

    /**
     * @desc 设置头信息
     * @param array $header
     */
    public function setHeaders($header = array()) {
        if ($this->_isAssoc($header)) {
            $out = array();
            foreach ($header as $k => $v) {
                $out[] = $k . ': ' . $v;
            }
            $header = $out;
        }
        $this->setOption(CURLOPT_HTTPHEADER, $header);
        return $this;
    }
    
    /**
     * @desc Curl交互
     * @param string $url
     * @throws CException
     * @return mixed|boolean
     */
    private function _exec($url) {
        $this->setOption(CURLOPT_URL, $url);
        $c = curl_exec($this->_ch);

        //存放响应结果
        $obj = new stdClass();
        $obj->errno = curl_errno($this->_ch);
        $obj->error = curl_error($this->_ch);
        $obj->info = curl_getinfo($this->_ch);
        $this->curlResponse = $obj;

        if (!curl_errno($this->_ch)){
            return $c;
        }else{
            throw new CException(curl_error($this->_ch));
        }
        return false;
    }
    
    /**
     * @desc 添加证书
     */
    public function addCertificate(){
        $this->setOption(CURLOPT_CAINFO, Yii::app()->basePath.'/extensions/cacert.pem');
        return $this;
    }

    private function _isAssoc($arr) {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * @desc 获取Curl报错
     */
    public function getError() {
        return curl_error($this->_ch);
    }

    /**
     * @desc 获取Curl报错编号
     */
    public function getErrorNo() {
        return curl_errno($this->_ch);
    }

    /**
     * @desc 获取Curl交互信息
     * @return mixed
     */
    public function getInfo($param = null) {
        return curl_getinfo($this->_ch, $param);
    }

    /**
     * @desc 获取curl响应结果
     * @return array
     */
    public function getCurlResponse() {
        return $this->curlResponse;
    }
    
    /**
     * @desc 初始化Curl
     * @throws CException
     */
    public function init() {
        try {
            $this->curlResponse = null;
            $this->_ch = curl_init();
            $options = is_array($this->options) ? ($this->options + $this->_config) : $this->_config;
            $this->setOptions($options);

            $ch = $this->_ch;

            Yii::app()->onEndRequest = function() use(&$ch) {
                curl_close($ch);
            };
        } catch (Exception $e) {
            throw new CException('Curl not installed');
        }
    }
}