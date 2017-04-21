<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/13
 * Time: 14:35
 */

class TaskPanGroupRepeat extends ReportModel
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
		return 'dm_dim_task_pane_repeat_pack_group';
	}
}