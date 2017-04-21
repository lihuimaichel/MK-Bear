<?php 

class WishBuyerAccountSchedule extends WishModel{

	private $_exceptionMsg = '';
	
    public static function model($className = __CLASS__) {
    	return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
        return 'ueb_wish_buyer_account_schedule';
    }
    
    public function getList(){
    	return $this->getDbConnection()->createCommand()->from($this->tableName())->where("1")->queryAll();
    }
    
    public function getListPairsByBuyerID($buyerID){
    	$lists = $this->getList();
    	$newLists = array();
    	if($lists){
    		foreach ($lists as $val){
    			if($val['buyer_id'] == $buyerID){
    				$newLists[$val['account_name']] = $val;
    			}
    		}
    	}
    	return $newLists;
    }
    /**
     * @desc 保存
     * @param string $transactionID
     * @param string $orderID
     * @param array $param
     */
    public function saveRecord($data){
        return $this->dbConnection->createCommand()->replace($this->tableName(), $data);
    }
    
    public function addOrUpadateRecord($data){
    	$accountName = $data['account_name'];
    	$buyerId = $data['buyer_id'];
    	$self = new self();
    	$row = $self->find("account_name=:account_name AND buyer_id=:buyer_id", array(':account_name'=>$accountName, ':buyer_id'=>$buyerId));
    	if($row){
    		return $this->getDbConnection()->createCommand()->update($this->tableName(), $data, "account_name=:account_name AND buyer_id=:buyer_id", array(':account_name'=>$accountName, ':buyer_id'=>$buyerId));
    	}else{
    		return $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    	}
    }
    
    
    public function setExceptionMsg($msg){
    	$this->_exceptionMsg = $msg;
    }
    
    public function getExceptionMsg(){
    	return $this->_exceptionMsg;
    	
    }
}

?>