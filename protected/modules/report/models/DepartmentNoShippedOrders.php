<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/15
 * Time: 11:50
 */

class DepartmentNoShippedOrders extends ReportModel
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
		return 'dm_dim_task_pane_none_ship_order_num_dep';
	}

}
