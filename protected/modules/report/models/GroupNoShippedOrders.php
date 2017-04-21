<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/13
 * Time: 15:02
 */
class GroupNoShippedOrders extends ReportModel
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
		return 'dm_dim_task_pane_none_ship_order_num_group';
	}

}