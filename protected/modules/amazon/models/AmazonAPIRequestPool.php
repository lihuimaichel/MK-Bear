<?php
/**
 * @desc amazon 亚马逊接口请求池
 * @author zhangF
 *
 */
class AmazonAPIRequestPool extends AmazonModel {
	
	public $recoveryRate = 60;//恢复速率，单位秒
	public $maxRequestTotal = 0;//最大请求数量
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	
	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_amazon_api_request_frequence_pool';
	}
	/**
	 * @desc 获取请求访问权限, 返回true or false，返回false后可以调用$this->recoveryRate获取休眠时间
	 * @param unknown $accountID
	 * @param unknown $apiMethod
	 * @param unknown $maxReuestCount
	 * @param unknown $recoverRate
	 * @return boolean
	 */
	public function getRequestAccess($caller, $accountID, $apiMethod, $maxReuestCount, $recoverRate){
		//@TODO 可以根据caller进行做对应的其他逻辑操作
		
		if($this->createRequestData($accountID, $apiMethod, $maxReuestCount, $recoverRate)){
			return $this->checkAvailable($accountID, $apiMethod);
		}
		return false;
	}
	
	/**
	 * @desc 创建
	 * @param unknown $accountID
	 * @param unknown $apiMethod
	 * @param unknown $maxReuestCount
	 * @param unknown $recoverRate
	 * @return boolean
	 */
	private function createRequestData($accountID, $apiMethod, $maxReuestCount, $recoverRate){
		if(empty($accountID) || empty($apiMethod)) return false;
		$result = $this->getDbConnection()->createCommand()->from($this->tableName())
				->where("account_id=:account_id AND api_method=:api_method", array(":account_id"=>$accountID, ":api_method"=>$apiMethod))
				->queryRow();
		if(!$result){
			//add
			return $this->getDbConnection()->createCommand()->insert($this->tableName(),
					array(
							'account_id'	=>	$accountID,
							'api_method'	=>	$apiMethod,
							'max_request_total'			=>	$maxReuestCount,
							'avaliable_request_total'	=>	$maxReuestCount,
							'recovery_rate'			=>	$recoverRate,
							'last_recovery_time'	=>	time()
					));
		}
		return true;
	}
	
	/**
	 * @desc 检测是否可用请求次数
	 * @param unknown $accountID
	 * @param unknown $apiMethod
	 * @return boolean
	 */
	private function checkAvailable($accountID, $apiMethod){
		if(empty($accountID) || empty($apiMethod)) return false;
		$result = $this->getDbConnection()->createCommand()->from($this->tableName())
				->where("account_id=:account_id AND api_method=:api_method", 
						array(":account_id"=>$accountID, ":api_method"=>$apiMethod))
				->andWhere("avaliable_request_total>0")
				->queryRow();
		if($result){
			$this->recoveryRate = $result['recovery_rate'];
			$this->maxRequestTotal = $result['max_request_total'];
			$result2 = $this->getDbConnection()->createCommand()->update(
					$this->tableName(), 
					array(
							'avaliable_request_total'=>$result['avaliable_request_total']-1,
							'had_requested_total'=>$result['had_requested_total']+1,
							'last_request_time' => time()
					),
					"account_id=:account_id AND api_method=:api_method AND avaliable_request_total>0",
					array(":account_id"=>$accountID, ":api_method"=>$apiMethod)
			);
			if($result && $result2)
				return true;
		}
		return false;
	}
	
	/**
	 * @desc 恢复请求数量
	 */
	public function restoreRequestCount(){
		$result = $this->getDbConnection()->createCommand()
						->from($this->tableName())
						->limit(3000)	//限制一下
						->where("avaliable_request_total<max_request_total")
						->queryAll();
		
		//"UPDATE {$this->tableName()} SET ";
		if($result){
			foreach ($result as $row){
				if((time()-$row['last_recovery_time'])>$row['recovery_rate']){
					$sql = "UPDATE " . $this->tableName() ." SET `avaliable_request_total`=`avaliable_request_total`+1, last_recovery_time='".time()."' WHERE account_id={$row['account_id']} and api_method='{$row['api_method']}' AND avaliable_request_total<max_request_total";
					$result2 = $this->getDbConnection()->createCommand($sql)->execute();
				}
			}
		}
	}
	
}