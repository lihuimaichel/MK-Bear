<?php

class JdproductController extends UebController {
	private $_model = NULL;
	public function init(){
		parent::init();
		$this->_model = new JdProduct;
	}
	public function actionList(){
		$this->render('list', array('model'=>$this->_model));
	}
	
	public function actionOffline(){
		echo $this->failureJson(array(
				'message'=>'暂不支持'
		));
		Yii::app()->end();
		$id = Yii::app()->request->getParam('id');
		try{
			if(!$id){
				throw new Exception(Yii::t('jd_product', 'Invalide Param'));
			}
			$wareInfo = $this->_model->find('id=:id', array(':id'=>$id));
			if(!$wareInfo) 
				throw new Exception(Yii::t('jd_product', 'The Ware No Exists'));
			$result = $this->_model->unshelveWare($wareInfo->ware_id, $wareInfo->account_id);
			if(!$result['success']){
				throw new Exception(Yii::t('system', 'Operate failure'));
			}
			echo $this->successJson(array('message'=>Yii::t('system', 'Operate Successful')));
		}catch (Exception $e){
			echo $this->failureJson(array(
					'message'=>$e->getMessage()	
			));
		}
	}
	/**
	 * @desc 批量下架
	 * @throws Exception
	 */
	public function actionBatchoffline(){
		echo $this->failureJson(array(
				'message'=>'暂不支持'
		));
		Yii::app()->end();
		$id = Yii::app()->request->getParam('ids');
		try{
			if(!$id){
				throw new Exception(Yii::t('jd_product', 'Invalide Param'));
			}
			$wareInfo = $this->_model->findAll("id IN ({$id})");
			if(!$wareInfo)
				throw new Exception(Yii::t('jd_product', 'The Ware No Exists'));
			$newWareInfoList = array();
			foreach ($wareInfo as $ware){
				$newWareInfoList[$ware->account_id][] = $ware->ware_id;
			}
			$results = array('success'=>array(), 'fail'=>array(), 'errorMsg'=>array());
			foreach ($newWareInfoList as $accountID=>$wareids){
				$result = $this->_model->unshelveWare($wareids, $accountID);
				$results['success'] = array_merge($results['success'], $result['success']);
				$results['fail'] = array_merge($results['fail'], $result['fail']);
				$results['errorMsg'] = array_merge($results['errorMsg'], $result['errorMsg']);
			}
			
			if(!$results['success']){
				throw new Exception(Yii::t('system', 'Operate failure'));
			}
			echo $this->successJson(array('message'=>Yii::t('system', 'Operate Successful')));
		}catch (Exception $e){
			echo $this->failureJson(array(
					'message'=>$e->getMessage()
			));
		}
	}
}

?>