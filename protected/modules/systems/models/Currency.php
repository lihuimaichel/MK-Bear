<?php
/**
 * @desc 币种Model
 * @author Gordon
 */
class Currency extends SystemsModel {

    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @return string the associated database table name
     */
    public function tableName() {
    	return 'ueb_currency';
    }
      
   
    /**
     * @desc 获取货币信息
     */
    public function getCurrencyParis(){
    	return $this->findAll();
    }
    
    /**
     * 获取 code->currency_name形式的货币列表信息
     * $currencyCode:获取指定货币Code
     */
    public function getCurrencyList($currencyCode=''){
    	$currencyArr = array();
    	$objCurrency = $this->getCurrencyParis();
    	foreach($objCurrency as $val){
    		$currencyArr[$val->code] = $val->code.'-'.$val->currency_name;
    	}
    	if(isset($currencyCode) && !empty($currencyCode)){
    		return $currencyArr[$currencyCode];
    	}
    	return $currencyArr;
    }
    /**
     * 获取 id ->currency_name形式的货币列表信息
     * $currencyId:获取指定货币Code
    */
    public function getCurrencyIdList($currencyId=0){
    	$currencyArr = array();
    	$objCurrency = $this->getCurrencyParis();
    	foreach($objCurrency as $val){
    		$currencyArr[$val->id] = $val->code.'-'.$val->currency_name;
    	}
    	if(isset($currencyId) && $currencyId>0){
    		return $currencyArr[$currencyId];
    	}
    	return $currencyArr;
    }
    
    public function getByCode($code) {
    	return $this->find("code = :code", array(':code' => $code));
    }
    
}