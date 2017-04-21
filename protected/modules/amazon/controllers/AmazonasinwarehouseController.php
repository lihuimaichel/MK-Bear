<?php
/**
 * @desc amazon海外仓（ASIN）影射表
 * @author Liz
 * @since 2016-09-13
 */
class AmazonasinwarehouseController extends UebController{

	/**
	 * @desc 访问过滤配置
	 * @see CController::accessRules()
	 */
	public function accessRules() {
		return array(
				array('allow',
						'actions' => array('list','touploadfile','downloadtp','uploadfile','delete'),
						'users'=>array('*'),
				),
		);
	}
	
	public function actionList(){
		$this->render('list',array('model'=>new AmazonAsinWarehouse()));
	}
	
	public function actionTouploadfile(){
		$this->render('uploadfile',array('model'=>new AmazonAsinWarehouse()));
	}

	/**
	 * 下载模板
	 * @return file
	 */
	public function actionDownloadTP(){
	        ini_set('display_errors', false);
	        error_reporting(0);
	        set_time_limit(3600);	
            $excelData = new MyExcel();
            $column_width = array(20,20,20,20,20);
            $excelData->export_excel(array('在线SKU', '海外仓ID', 'ASIN', '销售人员', '账号名称'),array(),'AmazonOverseasWarehouse'.date('YmdHi').'.xls',$limit=10000,$output=1,$column_width);
            Yii::app()->end();
	}
	
	/**
	 * 导入EXCEL
	 * @return null
	 */
	public function actionUploadfile(){
		set_time_limit(3600);
		if(empty($_FILES['file1']['tmp_name'])){
			echo $this->failureJson(array( 'message' => Yii::t('amazon_product', 'Not Find Upload File')));
			Yii::app()->end();
		}
		if( !empty($_FILES) ){
			$models = new AmazonAsinWarehouse();
			$dir="uploads/";
			
			$path=time();
			if(isset($_FILES)){
				if($_FILES['file1']['size'] > 1024*1024*10){
					echo $this->failureJson(array( 'message' => Yii::t('amazon_product', 'Upload files can not be more than 10M')));
					Yii::app()->end();
				}
			}			
			$fileExtStr = end(explode('.',$_FILES['file1']['name']));
			$fileName = 'AmazonOverseasWarehouse'.date('YmdHi');			
			if($fileExtStr == 'xls' || $fileExtStr == 'xlsx'){
				$filePath = 'uploads/'.$fileName.'.'.$fileExtStr;
				$uploadFlag=move_uploaded_file($_FILES['file1']["tmp_name"],$filePath);
			}

			//保存
			$error = $models->saveAsinDataByExcel($filePath);
			unlink($filePath);	//删除临时上传的文件

			if($error == 'on_excel_data'){
				echo $this->failureJson(array( 'message' => Yii::t('amazon_product', 'Not Find Excel Data')));
			}elseif($error == 'excel_data_errors'){
				echo $this->failureJson(array( 'message' => Yii::t('amazon_product', 'Incorrect Excel Data')));
			}else{					
				$jsonData = array(
						'message' => Yii::t('amazon_product', 'Upload success'),
						'forward' => '/amazon/amazonasinwarehouse/list',
						'navTabId' => 'page'.Menu::model()->getIdByUrl('/amazon/amazonasinwarehouse/list'),
						'callbackType' => 'closeCurrent'
				);
				echo $this->successJson($jsonData);		
			}
		}else{
			echo $this->failureJson(array( 'message' => Yii::t('amazon_product', 'Not Find Excel Data')));	
		}
		Yii::app()->end();		
	}	


	/**
	 * 删除选中的记录
	 * @return bool
	 */
	public function actionDelete(){
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			$ids = $_REQUEST['ids'];
			$idsArr = explode(',', $ids);
			$ids = array_filter($idsArr);

			if (!empty($ids) && AmazonAsinWarehouse::model()->deleteAll("id in (" . implode(',', $ids) . ")")) {
				echo $this->successJson(array(
					'message' => Yii::t('system', 'Delete successful'),
					'navTabId' => 'page' . Menu::model()->getIdByUrl('/amazon/amazonasinwarehouse/list'),
				));
				Yii::app()->end();
			}
		}
		echo $this->failureJson(array(
			'message' => Yii::t('system', 'Delete failure'),
		));
		Yii::app()->end();		
	}	
}

?>