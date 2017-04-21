<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/13
 * Time: 10:25
 *
 * 组长昨日或者最近30日利润
 */

class TaskPaneProfitGroup extends ReportModel
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
		return 'dm_dim_task_pane_com_profit_group';
	}
}