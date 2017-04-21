<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/15
 * Time: 14:20
 */
class DashboardDepGroupStats extends SystemsModel
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
		return 'ueb_dashboard_dep_group_stats';
	}
}