<?php
/**
 * @desc Ebay账号配置信息
 * @author Gordon
 * @since 2015-06-06
 */
class EbayAccountInfo extends EbayModel{

	private $_errorMsg = "";
	/**
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_account_info';
    }
    
    public function setErrorMsg($message){
    	$this->_errorMsg = $message;
    	return $this;
    }
    
    public function getErrorMsg(){
		return $this->_errorMsg;
    }
    
    /**
     * @desc 联账号主表获取账号配置信息列表
     * @return mixed
     */
    public function getAccountInfoListJoinAccount(){
    	$accountList = $this->getDbConnection()->createCommand()
    							->from($this->tableName() . ' as t')
    							->select('t.*, a.user_name,a.short_name')
    							->leftJoin(EbayAccount::model()->tableName() . ' AS a', "a.id=t.account_id")
    							->where("1")
    							->queryAll();
    	return $accountList;
    }
    
    /**
     * @desc 保存数据
     * @param unknown $data
     * @return Ambigous <number, boolean>
     */
    public function savedata($data){
    	return $this->getDbConnection()->createCommand()->replace($this->tableName(), $data);
    }
    /**
     * @desc 保存
     * @param unknown $datas
     * @return boolean
     */
    public function batchSavedata($datas){
    	$transaction = $this->getDbConnection()->beginTransaction();
    	$flag = true;
    	foreach ($datas as $data){
    		$data['update_time'] = date("Y-m-d H:i:s");
    		$flag = $flag && $this->savedata($data);
    	}
    	if($flag){
    		$transaction->commit();
    		return true;
    	}else{
    		$transaction->rollback();
    		return false;
    	}
    }
    
    public function updateDataByAccountID($accountID, $data){
    	return $this->updateByPk($accountID, $data);
    }
    
    public function getAccountInfoByAccountID($accountID){
    	return $this->getDbConnection()->createCommand()
    						->from($this->tableName())
    						->where("account_id={$accountID}")
    						->queryRow();
    }
    
    /**
     * @desc 更新账号额度
     * @param unknown $accountID
     * @return Ambigous <Ambigous, number, boolean>|boolean
     */
    public function updateLimitRemaining($accountID){
    	$request = new GetMyeBaySellingRequest();
    	$reponse = $request->setAccount($accountID)->setOutputSelectorSummary()->setSellingSummaryInclude(true)->setRequest()->sendRequest()->getResponse();
    	
    	if($request->getIfSuccess()){
    		$data = array(
    				'account_id' => $accountID,
    				'amount_limit_remaining' => $reponse->Summary->AmountLimitRemaining,
    				'quantity_limit_remaining' => $reponse->Summary->QuantityLimitRemaining,
    				'update_time' => date("Y-m-d H:i:s"),
    		);
    		if($this->getAccountInfoByAccountID($accountID)){
    			return $this->updateDataByAccountID($accountID, $data);
    		}else{
    			return $this->savedata($data);
    		}
    	}else{
    		return false;
    	}
    }
}