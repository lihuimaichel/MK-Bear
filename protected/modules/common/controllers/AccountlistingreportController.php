<?php
/**
 * @desc 账号listing报告
 * @author zhangF
 *
 */
class AccountlistingReportController extends UebController {
	/**
	 * @desc 列表
	 */
	public function actionList() {
		try {
			$platformCode = Yii::app()->request->getParam('platform_code', Platform::CODE_AMAZON);
			$model = AccountListingReportFactory::factory($platformCode);
			$hasPrivilegesCoulumns = $model->getHasPrivilegesColumns();
			$accountColumnMaps = $model->getColumnAccountMaps();
		} catch (Exception $e) {
			echo $this->failureJson(array(
				'message' => $e->getMessage()
			));
			Yii::app()->end();
		}
		$this->render('list', array(
			'model' => $model,
			'hasPrivilegesColumns' => $hasPrivilegesCoulumns,
			'accountColumnMaps' => $accountColumnMaps,
		));
	}
}