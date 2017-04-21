<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/03/07
 * Time: 11:45
 */
class AmazonSettingDeleteLog extends AmazonModel
{
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * 查询的表名
	 */
	public function tableName()
	{
		return 'ueb_amazon_task_setting_delete_log';
	}

	/**
	 * @param $params
	 * @return bool
	 *
	 * 新增数据
	 */
	public function saveData($params)
	{
		$tableName = $this->tableName();
		$flag = $this->dbConnection
			->createCommand()
			->insert($tableName, $params);
		if ($flag) {
			return $this->dbConnection->getLastInsertID();
		}
		return false;
	}
}