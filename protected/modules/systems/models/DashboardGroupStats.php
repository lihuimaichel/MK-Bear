<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/13
 * Time: 16:39
 */
class DashboardGroupStats extends SystemsModel
{

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}


	public function getDbKey()
	{
		return 'db_dashboard';
	}


	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName()
	{
		return 'ueb_dashboard_group_stats';
	}
}