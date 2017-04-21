<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/13
 * Time: 17:38
 */

class TasksettingController extends TaskCommonController
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
		$this->index('task');
	}

	/**
	 * 编辑销售额目标或者任务
	 */
	public function actionEdit()
	{
		$this->edit('task');
	}


	/**
	 * 保存编辑，新增，修改
	 */
	public function actionUpdate()
	{
		$this->update('task');
	}
}