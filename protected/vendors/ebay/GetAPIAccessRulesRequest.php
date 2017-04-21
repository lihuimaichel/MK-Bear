<?php
/**
 * @desc 获取账号的API交互次数信息
 * @author Gordon
 * @since 2015-06-02
 */
class GetAPIAccessRulesRequest extends EbayApiAbstract{
    public $_verb = 'GetApiAccessRules';
    public function setRequest(){
    	$request = array(
            'RequesterCredentials' => array(
                'eBayAuthToken' => $this->getToken(),
            )
    	);
    	$this->request = $request;
    	return $this;
    }
}