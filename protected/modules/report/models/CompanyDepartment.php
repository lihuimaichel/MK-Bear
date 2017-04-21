<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/15
 * Time: 9:46
 */

class CompanyDepartment extends ReportModel
{

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName()
	{
		return 'dm_dim_task_pane_com_amt_dep';
	}
}