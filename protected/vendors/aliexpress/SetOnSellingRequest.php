<?php
/**
 * @desc 设置速卖通产品上架请求接口
 * @author lihy
 *
 */
class SetOnSellingRequest extends AliexpressApiAbstract{
	private $_productIds = "";
	
	public function setRequest(){
		$this->request = array(
						'productIds'=>$this->_productIds
					);
		return $this;
	}
	public function setApiMethod(){
		$this->_apiMethod = "api.onlineAeProduct";
	}
	
	public function setPrdouctID($ids){
		if(is_array($ids))
			$ids = implode(";", $ids);
		$this->_productIds = $ids;
		return $this;
	}
}

?>