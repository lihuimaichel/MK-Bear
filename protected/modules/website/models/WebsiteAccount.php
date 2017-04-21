<?php
/**
 * @desc website account 模型类
 * @author zhangF
 *
 */
class WebsiteAccount extends WebsiteModel {

	/** @var tinyint 账号状态开启*/
	const STATUS_OPEN = 1;
	 
	/** @var tinyint 账号状态关闭*/
	const STATUS_SHUTDOWN = 0;
	
	/** @var tinyint 账号状态锁定*/
	const STATUS_ISLOCK = 1;
	
	/** @var tinyint 账号状态未锁定*/
	const STATUS_NOTLOCK = 0;
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_website_account';
	}
	
	/**
	 * @desc 获取可用账号列表
	 * @author Gordon
	 */
	public static function getAbleAccountList(){
		return self::model()->dbConnection->createCommand()
		->select('*')
		->from(self::model()->tableName())
		->where('status = '.self::STATUS_OPEN)
		->queryAll();
	}
	
	/**
	 * @desc 根据账号ID获取账号信息
	 * @param int $id
	 */
	public static function getAccountInfoById($id){
		return self::model()->getDbConnection()->createCommand()->select('*')->from(self::model()->tableName())->where('id = '.$id)->queryRow();
	}
	
	/**
	 * 获取账号ID => ACCOUNT NAME对
	 * @return array
	 */
	public static function getIdNamePairs() {
		$pairs = array();
		$res = WebsiteAccount::model()
		->getDbConnection()
		->createCommand()
		->select("id, website_name")
		->from(self::tableName())
		->queryAll();
		if (!empty($res)) {
			foreach ($res as $row)
				$pairs[$row['id']] = $row['website_name'];
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
		->select("website_name")
		->from(self::tableName())
		->where("id = :id", array(':id' => $accountId))
		->queryScalar();
	}	
}