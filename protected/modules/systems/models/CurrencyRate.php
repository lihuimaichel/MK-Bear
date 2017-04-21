<?php
/**
 * @desc 货币汇率
 * @author Gordon
 */
class CurrencyRate extends SystemsModel {
    
	const RATE_TYPE_BASE = 'base';
	const CNY = 'CNY';

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
        return 'ueb_currency_rate';
    }
 
	/**
	 * @desc 汇率列表
	 * @return multitype:multitype:
	 */
    public function getRateList(){
    	$result = array();
    	$list = $this->findAll();
    	if($list){
    		foreach ($list as $val){
    			$result[] = $val->attributes;
    		}
    	}
    	return $result;
    }
    

    /**
     * @desc 汇率转换
     * @param char $fromCurrencyCode
     * @param char $toCurrencyCode
     * @param string $rateType
     * @return 
     */
    public function getRateByCondition($fromCurrencyCode,$toCurrencyCode,$rateType= self::RATE_TYPE_BASE) {  
		if($fromCurrencyCode == $toCurrencyCode){
			return 1;
		}
        $info = CurrencyRate::model()->dbConnection->createCommand() 
    			->select('rate')
    			->from(self::tableName())
    			->where("type = '{$rateType}'")
    			->andwhere("from_currency_code = '{$fromCurrencyCode}'")
    			->andwhere("to_currency_code = '{$toCurrencyCode}'")
    			->queryRow();
        if ( empty($info) ) {
             return false;
        }
        return $info['rate'];
    }
    
    /**
     * @desc 将指定币种转化为人民币(暂时只有美元和港币直接转化为人民币的汇率，所以其他币种要先转化为港币)
     * @author Gordon
     * @since 2014-07-31
     */
    public function getRateToCny($currency){
    	if( $currency=='USD' ){
    		$rate = $this->getRateByCondition($currency, 'CNY');	
    	}else{
    		$rate = $this->getRateByCondition($currency, 'HKD') * $this->getRateByCondition('HKD', 'CNY');
    	}
    	return $rate;
    }

    public function getRateToOther($currency, $target = "USD"){
        if( $currency== $target){
            $rate = 1;
        }else{
            $rate = $this->getRateByCondition($currency, $target);
        }
        return $rate;
    }

}