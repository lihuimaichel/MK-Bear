<?php

class AmazonasinimportController extends UebController{
	
	
	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules() {
		return array(
				array('allow',
						'actions' => array('touploadfile','uploadfile'),
						'users'=>array('*'),
				),
		);
	}
	
	
	public function actionList(){
		$model = UebModel::model('AmazonAsinImport');
		$this->render('list',array('model'=>$model));
	}
	
	public function actionTouploadfile(){
		/* if(empty($_FILES['file1']['tmp_name'])){
			echo $this->failureJson(array( 'message' => Yii::t('purchases', '上传文件不存在，请重新上传！')));
			Yii::app()->end();
		} */
		$model = UebModel::model('AmazonAsinImport');
		$this->render('uploadfile',array('model'=>$model));
	}
	
	public function actionUploadfile(){
		set_time_limit(2*3600);
		if(empty($_FILES['file1']['tmp_name'])){
			echo $this->failureJson(array( 'message' => Yii::t('purchases', '上传文件不存在，请重新上传！')));
			Yii::app()->end();
		}
		
		
		
		if( !empty($_FILES) ){
			$models = new AmazonAsinImport();
			/***************************************************/
			$dir="uploads/";
			
			/* if (!file_exists($dir)){
				$a = $models->createFolder(dirname($dir));
				
			}
			if(!is_dir($dir)){
				$models->createFolder($dir);
			} */
			
			
			$path=time();
			if(isset($_FILES)){
				if($_FILES['file1']['size']>1024*1024*100){
					echo $this->failureJson(array( 'message' => Yii::t('purchases', '文件大于10M，上传失败！请上传小于10M的文件！')));
					Yii::app()->end();
				}
			}
			
			$fileExtStr = end(explode('.',$_FILES['file1']['name']));
			$fileName = 'amazon_asin_'.date('YmdHi');
			
			if($fileExtStr == 'xls' || $fileExtStr == 'xlsx'){
				$filePath = 'uploads/'.$fileName.'.'.$fileExtStr;
				$uploadFlag=move_uploaded_file($_FILES['file1']["tmp_name"],$filePath);
			}
			//保存包裹数据
			$error = UebModel::model('AmazonAsinImport')->saveAsinDataByExcel($filePath);
			
			if($error == 'on_excel_data'){
				echo $this->failureJson(array( 'message' => Yii::t('order', 'Not Find Excel Data')));
				Yii::app()->end();
			}			 
 			unlink($filePath);
 			
			$jsonData = array(
					'message' => $error,
					'forward' => '/amazon/amazonasinimport/list',
					'navTabId' => 'page'.Menu::model()->getIdByUrl('/amazon/amazonasinimport/list'),
					'callbackType' => 'closeCurrent'
			);
			echo $this->successJson($jsonData);
			Yii::app()->end();
		}
		
	}

	/**
	 * @desc 通过Excel文件解码SKU
	 * @link /amazon/Amazonasinimport/DecryptSKUFromExcel
	 */
	public function actionDecryptSKUFromExcel() {
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		set_time_limit(3600);

		$filePath = 'uploads/FBAInvtory.xlsx';
		UebModel::model('AmazonAsinImport')->DecryptSKUByExcel($filePath);
		exit('finish!');
	}	
	
}

?>