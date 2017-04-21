<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/29
 * Time: 15:35
 */
class EbaySettingDeleteLog extends EbayModel
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
		return 'ueb_ebay_task_setting_delete_log';
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