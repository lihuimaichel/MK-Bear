<?php 
/**
 * @desc paypal交易 Model
 * @author Gordon
 */
class WishSpecialOrderTraceNumber extends WishModel{

    public static function model($className = __CLASS__) {
    	return parent::model($className);
    
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
        return 'ueb_wish_order_trace_number';
    }
    
    /**
     * @desc 保存
     * @param string $transactionID
     * @param string $orderID
     * @param array $param
     */
    public function saveOrderTraceNumberRecord($data){
        return $this->dbConnection->createCommand()->replace($this->tableName(), $data);
    }
    
    /**
     * @desc 检查是否存在追踪号
     * @param unknown $traceNumber
     * @param unknown $shipCode
     * @return boolean
     */
    public function checkTraceNumberExists($traceNumber, $shipCode){
    	$info = $this->getDbConnection()->createCommand()
    					->from($this->tableName())
    					->select("id")
    					->where("trace_number=:trace_number and ship_code=:ship_code", array(":trace_number"=>$traceNumber, ":ship_code"=>$shipCode))
    					->queryRow();
    	if($info) return true;
    	return false;
    }
    
    /**
     * @desc 获取最大发货时间
     * @return mixed|string
     */
    public function getMaxShipDateField(){
    	$info = $this->getDbConnection()->createCommand()->from($this->tableName())->select("max(ship_date) max_ship_date")->queryRow();
    	if($info) return $info['max_ship_date'];
    	return '';
    }
    
    
    /**
     * @desc 获取物流名称
     * @param unknown $orderId
     * @return multitype:unknown mixed |boolean
     */
    public function getShipInfoByOrderId($orderId){
    	$info = $this->getDbConnection()->createCommand()
    					->from($this->tableName())
    					->select("*")
    					->where("order_id='{$orderId}'")
    					->queryRow();
    	if($info){
    		$shipName = Logistics::model()->getShipNameByShipCode($info['ship_code']);
    		$traceNumber = $info['trace_number'];
    		return array(
    			'ship_name'=>$shipName,
    			'ship_code'=>$info['ship_code'],
    			'trace_number'=>$traceNumber
    		);
    	}
    	return false;
    }
    
    
    public function getTraceNumberListByOrderDownTime($timestamp, $shipCountry){
    	$info = $this->getDbConnection()->createCommand()
    	->from($this->tableName() . " t")
    	->join(WishSpecialOrder::model()->tableName() . ' a', 'a.order_id=t.order_id')
    	->select("t.ship_code, t.trace_number")
    	->where("t.ship_date>'{$timestamp}' and a.ship_country='{$shipCountry}'")
    	->queryAll();
    	$newinfo = array();
    	if($info){
    		foreach ($info as $in){
    			$newinfo[$in['ship_code']][] = $in['trace_number'];
    		}
    	}
    	return $newinfo;
    }
}

?>