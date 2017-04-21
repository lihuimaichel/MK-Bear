<?php
/**
 * @desc 导入sku用来更改库存为0
 * @author lihy
 *
 */
class ProductimportskuController extends UebController{
	public function actionList(){
		$model = UebModel::model('ProductImportSku');
		$this->render('list',array('model'=>$model));
	}
	
	public function actionTouploadfile(){
		/* if(empty($_FILES['file1']['tmp_name'])){
		 echo $this->failureJson(array( 'message' => Yii::t('purchases', '上传文件不存在，请重新上传！')));
		Yii::app()->end();
		} */
		$model = UebModel::model('ProductImportSku');
		$this->render('uploadfile',array('model'=>$model));
	}
	
	public function actionUploadfile(){
		set_time_limit(0);
		if(empty($_FILES['file1']['tmp_name'])){
			echo $this->failureJson(array( 'message' => Yii::t('purchases', '上传文件不存在，请重新上传！')));
			Yii::app()->end();
		}
	
	
	
		if( !empty($_FILES) ){
			$models = new ProductImportSku();
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
			$fileName = 'import_sku_'.date('YmdHi');
				
			if($fileExtStr == 'xls' || $fileExtStr == 'xlsx'){
				$filePath = 'uploads/'.$fileName.'.'.$fileExtStr;
				$uploadFlag=move_uploaded_file($_FILES['file1']["tmp_name"],$filePath);
			}
			//保存包裹数据
			$error = UebModel::model('ProductImportSku')->saveDataByExcel($filePath);
				
			if($error == 'on_excel_data'){
				echo $this->failureJson(array( 'message' => Yii::t('order', 'Not Find Excel Data')));
				Yii::app()->end();
			}
			unlink($filePath);
	
			$jsonData = array(
					'message' => $error,
					'forward' => '/common/productimportsku/list',
					'navTabId' => 'page'.Menu::model()->getIdByUrl('/common/productimportsku/list'),
					'callbackType' => 'closeCurrent'
			);
			echo $this->successJson($jsonData);
			Yii::app()->end();
		}
	
	}
}