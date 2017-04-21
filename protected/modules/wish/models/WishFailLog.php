<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/2
 * Time: 16:25
 *
 * 操作失败记录的日志信息
 */
class WishFailLog extends WishModel
{
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName()
	{
		return 'ueb_wish_task_fail_log';
	}

	public function saveData($params)
	{
		$tableName = $this->tableName();
		$flag = $this->getDbConnection()->createCommand()->insert($tableName, $params);
		if ($flag) {
			return $this->dbConnection->getLastInsertID();
		}
		return false;
	}
}