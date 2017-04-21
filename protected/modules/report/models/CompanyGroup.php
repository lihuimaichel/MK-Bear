<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/11
 * Time: 17:33
 */
class CompanyGroup extends ReportModel
{

	public $user_id;

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
		return 'dm_dim_task_pane_com_amt_group';

	}
}