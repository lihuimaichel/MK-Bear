<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/15
 * Time: 15:56
 */
class CompanySalesDepSite extends ReportModel
{

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function getDbKey()
	{
		return 'db_report';
	}

	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName()
	{
		return 'dm_dim_task_pane_com_amt_dep_site';

	}
}