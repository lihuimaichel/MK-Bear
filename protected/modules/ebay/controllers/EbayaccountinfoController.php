<?php
class EbayaccountinfoController extends UebController
{
	public function actionIndex(){
		$model = new EbayAccountInfo();
		$accountList = $model->getAccountInfoListJoinAccount();
		$this->render("index", 
						array('model'=>$model, 'accountList'=>$accountList)
					);	
	}
	
	public function actionSavedata(){
		try{
			$model = new EbayAccountInfo();
			$accounts = Yii::app()->request->getParam('accounts');
			$listData = Yii::app()->request->getParam('listdata');
			if(!$accounts){
				throw new Exception("没有选择项");
			}
			$datas = array();
			foreach ($accounts as $accountID){
				$data = $listData[$accountID];
				$data['account_id'] = $accountID;
				$data['update_time'] = date("Y-m-d H:i:s");
				$datas[] = $data;
			}
			$flag = $model->batchSavedata($datas);
			if(!$flag){
				throw new Exception(Yii::t('system', 'Save failure'));
			}
			echo $this->successJson(array('message'=>Yii::t('system', 'Save successful')));
		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}
	
	/**
	 * @desc 更新在线额度
	 */
	public function actionUpdatelimitremaining(){
		try{
			set_time_limit(3600);
			$accounts = Yii::app()->request->getParam('accounts');
			if(empty($accounts)) throw new Exception("账号没有指定");
			$accountNames = EbayAccount::getIdNamePairs();
			$model = new EbayAccountInfo();
			$successArr = $errorArr = array();
			foreach ($accounts as $accountID){
				$res = $model->updateLimitRemaining($accountID);
				if($res){
					$successArr[] = $accountNames[$accountID];
				}else{
					$errorArr[] = $accountNames[$accountID];
				}
			}
			if(empty($successArr)){
				//全部失败
				throw new Exception(Yii::t('system', 'Update failure'));
			}
			$msg = implode(",", $successArr) . ":" .Yii::t('system', 'Update successful');
			if($errorArr){
				$msg .= "<br/>".implode(",", $successArr) . ":" . Yii::t('system', 'Update failure');
			}
			echo $this->successJson(array('message' => $msg));
		}catch(Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}
}