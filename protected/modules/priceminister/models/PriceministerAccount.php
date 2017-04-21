<?php
/**
 * @desc pm账号
 * @author lihy
 * @since 2016-07-1
 */
class PriceministerAccount extends PriceministerModel{
    
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
     
    /** @var tinyint 账号状态关闭*/
    const STATUS_SHUTDOWN = 0;


	public $num;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_pm_account';
    }
    
    /**
     * @desc 获取可用账号列表
     * @author Gordon
     */
    public static function getAbleAccountList(){
        return PriceministerAccount::model()->dbConnection->createCommand()
                       ->select('*')
                       ->from(self::tableName())
                       ->where('status = '.self::STATUS_OPEN)
                       ->queryAll();
    }
    
    /**
     * @desc 根据账号ID获取账号信息
     * @param int $id
     */
    public static function getAccountInfoById($id){
        return PriceministerAccount::model()->dbConnection->createCommand()->select('*')->from(self::tableName())->where('id = '.$id)->queryRow();
    }
    
    /**
     * 获取账号ID => ACCOUNT NAME对
     * @return array
     */
    public static function getIdNamePairs() {
    	$pairs = array();
    	$res = PriceministerAccount::model()
    	->getDbConnection()
    	->createCommand()
    	->select("id, user_name")
    	->from(self::tableName())
    	->order("user_name asc")
    	->queryAll();
    	if (!empty($res)) {
    		foreach ($res as $row)
    			$pairs[$row['id']] = $row['user_name'];
    	}
    	return $pairs;
    }
    
    /**
     * @desc 根据账号ID获取账号名称
     * @param string $accountId
     */
    public function getAccountNameById($accountId) {
    	return self::model()->getDbConnection()
    	->createCommand()
    	->select("user_name")
    	->from(self::tableName())
    	->where("id = :id", array(':id' => $accountId))
    	->queryScalar();
    }
    
    /**
     * @desc 根据账号名称获取账号信息
     * @param unknown $accountName
     */
    public static function getByAccountName($accountName) {
    	return PriceministerAccount::model()->getDbConnection()->createCommand()
		    	->select('*')
		    	->from(self::tableName())
		    	->where("user_name = '{$accountName}'")
		    	->queryRow();
    }
    /**
     * @desc 获取计划任务执行同一组账号列表
     */
    public function getCronGroupAccounts() {
    	$accountList = array();
    	$groupList = array();
    	$accountInfos = self::getAbleAccountList();
    	//根据账号ID最后一个数字分组
    	foreach ($accountInfos as $accountInfo) {
    		$key = substr($accountInfo['id'], -1, 1);
    		$groupList[$key][] = $accountInfo['id'];
    		$accountIDs[] = $accountInfo['id'];
    	}
    	return $accountIDs;
    	//获取当前时间小时对应的数组
    	$offset = 6;
    	$hour = date('H');
    	$index = ($hour + $offset) % 24;
    	if(isset($groupList[$index]))
    		return $groupList[$index];
    	else 
    		return array();
    }    
    /**
     * @desc 获取过滤掉的可用账户列表
     * @param unknown $ids
     * @return mixed
     */
    public static function getAbleAccountListByFilterId($ids){
    	if(is_array($ids) && $ids)
    		$ids = implode(",", $ids);
    	$command = PriceministerAccount::model()->dbConnection->createCommand()
					    	->select('*')
					    	->from(self::tableName())
					    	->order("user_name asc")
					    	->where('status = '.self::STATUS_OPEN );
    	if($ids){
    		$command->andWhere('id not in('.$ids.')');
    	}
		return $command->queryAll();
    }
    
    /**
     * @desc 根据账号ID获取账号信息
     * @param int $id
     */
    public static function getAccountInfoByIds($id){
    	$flag = false;
    	if( !is_array($id) ){
    		$flag = true;
    		$id = array($id);
    	}
    	$sql = PriceministerAccount::model()->dbConnection->createCommand()->select('*')->from(self::model()->tableName())->where('id IN ('.implode(',', $id).')');
    	if( $flag ){
    		return $sql->queryRow();
    	}else{
    		return $sql->queryAll();
    	}
    }

	
}