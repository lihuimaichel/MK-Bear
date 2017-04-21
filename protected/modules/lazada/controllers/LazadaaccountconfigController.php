<?php
class LazadaaccountController extends UebController {

	/**
	 * @desc 编辑账号基本信息
	 */
	public function actionView($id){
		$accountID = $id;
		$model = UebModel::model('LazadaAccountConfig')->loadModelByaccountID($accountID);
		$data = $_REQUEST['LazadaAccountConfig'];
		var_dump($model);
		var_dump($data);
		// 		if(Yii::app()->request->isAjaxRequest && isset($data)){
		// 			$tag = $lazadaAccountConfigModel->validate();
		// 			if(isset($data['publish_count'])){
		// 				$lazadaAccountConfigModel = new LazadaAccountConfig();
		// 				$lazadaAccountConfigModel->setAttribute('account_id',$accountID);
		// 				$lazadaAccountConfigModel->setAttribute('config_type', $lazadaAccountConfigModle::publish_count);
		// 				$lazadaAccountConfigModel->setAttribute('config_value',$data['publish_count']);
		// 				if($tag){
		//	try {
		//$publish_Account_Flag = $lazadaAccountConfigModel->save();
		//}	catch (Exception $e) {
		//		$publish_Flag = false;
		//	}
		// 				}
		// 			}
			
		// 			if(isset($data['adjust_count'])){
		// 				$lazadaAccountConfigModel->setAttribute('account_id',$accountID);
		// 				$lazadaAccountConfigModel->setAttribute('config_type', $lazadaAccountConfigModle::ifadjust_count);
		// 				$lazadaAccountConfigModel->setAttribute('config_value',$data['adjust_count']);
		// 				if($tag){
		// 					try {
		// 						$adjust_Flag = $lazadaAccountConfigModel->save();
		// 					}	catch (Exception $e) {
		// 						$adjust_Flag = false;
		// 					}
		// 				}
		// 			}
		// 			if(isset($data['publish_site_id'])){
		// 				$lazadaAccountConfigModel->setAttribute('account_id',$accountID);
		// 				$lazadaAccountConfigModel->setAttribute('config_type', $lazadaAccountConfigModle::publish_site_Id);
		// 				$lazadaAccountConfigModel->setAttribute('config_value',$data['publish_site_id']);
		// 				if($tag){
		// 					try {
		// 						$publish_Site_Flag = $lazadaAccountConfigModel->save();
		// 					}	catch (Exception $e) {
		// 						$publish_Site_Flag = false;
		// 					}
		// 				}
		// 			}
			
		// 		Yii::app()->end();
		// 		}
		$this->render('view',array('model' => $model));
	}
	
	
	
	/**
	 * @desc 获取账号基本信息
	 */
	public function loadModelByaccountID($accountID){
		$model =$this->findByAttributes(array('account_id' => $accountID));
		if($model===false){
			throw new CHttpException ( 404, Yii::t ( 'app', 'The requested page does not exist.' ) );
		}else{
			return $model;
		}
	}
	
	
	
	
	
	
	
	
}