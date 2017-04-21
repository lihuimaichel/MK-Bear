<?php
/**
 * @desc 销售平台物流carrier
 * @author Gordon
 */
class LogisticsPlatformCarrier extends LogisticsModel { 
    
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
       	return 'ueb_logistics_platform_carrier';
    }
    
    /**
     * @desc 通过物流code和销售平台获取carrier
     * @param string $shipCode
     * @param string $platformCode
     * @return string
     */
    public function getCarrierByShipCode($shipCode, $platformCode){
    	$shipID = Logistics::model()->getLogisticsIdByShipCode($shipCode);
    	if(!$shipID) return '';
    	$carriers = $this->getCarrierByLogisticsIds(array($shipID));
    	if( isset($carriers[$shipID][$platformCode]) ){
    		return $carriers[$shipID][$platformCode];
    	}else{
    		return '';
    	}
    }
    

    /**
     * @desc 获取物流Carrier
     * @param logistics IDs $ids
     * @param string $returnAll 
     * @return array
     */
    public function getCarrierByLogisticsIds($ids,$returnAll=false){
    	$carrierArr = array();
        if($ids){
            $list = $this->findAll('logistics_id IN ('.implode(",", $ids).')');
        }
    	if($list){
	    	foreach($list as $carrier){
	    		$carrierArr[$carrier['logistics_id']][$carrier['platform_code']] = $returnAll ? $carrier : $carrier['carrier'];
	    	}
    	}
    	return $carrierArr;
    }
}