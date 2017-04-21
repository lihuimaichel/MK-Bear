<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/15
 * Time: 16:34
 */
class DashboardDepSiteStats extends SystemsModel
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
		return 'ueb_dashboard_dep_site_stats';
	}
}

