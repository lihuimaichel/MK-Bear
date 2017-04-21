<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/1/23
 * Time: 10:21
 *
 * 销售人员每月销售报告
 */
class SalesprofitController extends TaskBaseController
{
	public function accessRules()
	{
		return array(
			array(
				'allow',
				'users' => array(
					'*'
				),
				'actions' => array(
					'index',//new
					'tasksetting', //任务设置
				)
			)
		);
	}


	/**
	 * 获取销售的月销售的报告
	 */
	public function actionIndex()
	{
		$model = DashboardSalesProfit::model();
		$this->render("index", array('model' => $model));
	}

	/**
	 * 小组月汇总
	 */
	public function actionGroup()
	{
		$model = DashboardGroupProfit::model();
		$this->render("index_group", array('model' => $model));
	}


    /**
     * 按部门汇总
     */
	public function actionDepartment()
	{
		$model = DashboardDeptProfit::model();
        $this->render("index_dept", array('model' => $model));
	}
}