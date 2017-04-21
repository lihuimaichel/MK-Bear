<?php
/**
 * Wish 邮　Base类
 * @author	Rex
 * @since	2015-10-12
 */

class WishPostBase extends WishApiAbstract {
	
	public function setRequest() {
		return $this;
	}
	
	/**
	 * curl post
	 */
	public function _curlPost($data=array()) {
		$header[] = "Content-Type: text/xml; charset=utf-8";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$ret = curl_exec($ch);
		curl_close($ch);
	
		return $ret;
	}
	
	/**
	 * 生成xml格式数据
	 * @param	array	$data
	 * @param	string	$rootElement
	 * @return	xml
	 */
	public function getXmlData($data, $rootElement) {
		$data['api_key'] = $this->_sign;
		$strH = '<?xml version="1.0" encoding="UTF-8"?>';
		$strBody = '';
		foreach ($data as $key => $val) {
			if (is_array($val)) {
				$strBody2 = '';
				$strBody2 .= '<'.$key.'>';
				foreach ($val as $key2 => $val2) {
					$strBody2 .= '<'.$key2.'>'.$val2.'</'.$key2.'>';
				}
				$strBody2 .= '</'.$key.'>';
			}else {
				$strBody .= '<'.$key.'>'.$val.'</'.$key.'>';
			}
		}
	
		$retXml = $strH.'<'.$rootElement.'>'.$strBody.$strBody2.'</'.$rootElement.'>';
		return $retXml;
	}
	
}