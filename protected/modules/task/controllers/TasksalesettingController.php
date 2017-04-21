<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/13
 * Time: 17:38
 */

class TasksalesettingController extends TaskCommonController
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

				)
			)
		);
	}

	public function actionIndex()
	{
		$this->index('sales');
	}

	/**
	 * 编辑销售额目标或者任务
	 */
	public function actionEdit()
	{
		$this->edit('sales');
	}


	/**
	 * 保存新增，编辑，修改
	 */
	public function actionUpdate()
	{
		$this->update('sales');
	}


}