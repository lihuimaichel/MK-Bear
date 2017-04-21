<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/13
 * Time: 14:49
 */
class GroupExceptionOrder extends ReportModel
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
		return 'dm_dim_task_pane_com_exception_order_group';
	}
}