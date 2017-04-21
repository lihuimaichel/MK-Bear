<?php
/**
 * @desc Wish海外仓（产品ID）影射表
 * @author Liz
 * @since 2016-09-14
 */
class WishoverseaswarehouseController extends UebController{
	/**
	 * @desc 访问过滤配置
	 * @see CController::accessRules()
	 */
	public function accessRules() {
		return array(
				array(
					'allow',
					'actions' => array('list','touploadfile','downloadtp','uploadfile','delete'),
					'users'=>array('*'),
				),
		);
	}
	
	public function actionList(){
		//传递搜索的参数给批量下载作为查询条件
		$request = http_build_query($_POST);
		$request .= '&random=';		//过滤自动添加的随机时间参数
		$this->render('list',array('model'=>new WishOverseasWarehouse(),'request'=>$request));
	}

	public function actionTouploadfile(){
		$this->render('uploadfile',array('model'=>new WishOverseasWarehouse()));
	}	

	/**
	 * 下载模板
	 * @return file
	 */
	public function actionDownloadTP(){
	        ini_set('display_errors', false);
	        error_reporting(0);
	        set_time_limit(2*3600);	
            $excelData = new MyExcel();
            $column_width = array(20,30,20,20,20);
            $excelData->export_excel(array('系统SKU', '产品ID', '海外仓ID', '销售人员', '账号名称'),array(),'WishOverseasWarehouse'.date('YmdHi').'.xls',$limit=10000,$output=1,$column_width);
            Yii::app()->end();
	}
	
	/**
	 * 上传excel文件
	 */
	public function actionUploadfile(){
		set_time_limit(2*3600);
		if(empty($_FILES['file1']['tmp_name'])){
			echo $this->failureJson(array( 'message' => Yii::t('wish_product_statistic', 'Not Find Upload File')));
			Yii::app()->end();
		}
		if( !empty($_FILES) ){
			$models = new WishOverseasWarehouse();
			$dir="uploads/";
			
			$path=time();
			if(isset($_FILES)){
				if($_FILES['file1']['size'] > 1024*1024*10){
					echo $this->failureJson(array( 'message' => Yii::t('wish_product_statistic', 'Upload files can not be more than 10M')));
					Yii::app()->end();
				}
			}			
			$fileExtStr = end(explode('.',$_FILES['file1']['name']));
			$fileName = 'WishOverseasWarehouse'.date('YmdHi');			
			if($fileExtStr == 'xls' || $fileExtStr == 'xlsx'){
				$filePath = 'uploads/'.$fileName.'.'.$fileExtStr;
				$uploadFlag=move_uploaded_file($_FILES['file1']["tmp_name"],$filePath);
			}

			//保存
			$error = $models->saveDataByExcel($filePath);
			unlink($filePath);	//删除临时上传的文件

			if($error == 'on_excel_data'){
				echo $this->failureJson(array( 'message' => Yii::t('wish_product_statistic', 'Not Find Excel Data')));
			}elseif($error == 'excel_data_errors'){
				echo $this->failureJson(array( 'message' => Yii::t('wish_product_statistic', 'Incorrect Excel Data')));
			}else{					
				$jsonData = array(
						'message' => Yii::t('wish_product_statistic', 'Upload success'),
						'forward' => '/wish/wishoverseaswarehouse/list',
						'navTabId' => 'page'.Menu::model()->getIdByUrl('/wish/wishoverseaswarehouse/list'),
						'callbackType' => 'closeCurrent'
				);				
				echo $this->successJson($jsonData);		
			}
		}else{
			echo $this->failureJson(array( 'message' => Yii::t('wish_product_statistic', 'Not Find Excel Data')));	
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

			if (!empty($ids) && WishOverseasWarehouse::model()->deleteAll("id in (" . implode(',', $ids) . ")")) {
				echo $this->successJson(array(
					'message' => Yii::t('system', 'Delete successful'),
					'navTabId' => 'page' . Menu::model()->getIdByUrl('/wish/wishoverseaswarehouse/list'),
				));
				Yii::app()->end();
			}
		}
		echo $this->failureJson(array(
			'message' => Yii::t('system', 'Delete failure'),
		));
		Yii::app()->end();		
	}
	
	/**
	 * 批量导出CSV
	 * @return file
	 */
	public function actionOutFile(){
        ini_set('display_errors', false);
        error_reporting(0);
        set_time_limit(3600);	

        $params = array();
        $params = $_GET;
        $conditions = '1';
        if ($params){
        	if (isset($params['sku']) && !empty($params['sku'])){
        		$conditions .= " AND sku = :sku";
        		$data[':sku'] = trim(addslashes($params['sku']));
        	}
        	if (isset($params['product_id']) && !empty($params['product_id'])){
        		$conditions .= " AND product_id = :product_id";
        		$data[':product_id'] = trim(addslashes($params['product_id']));
        	}
        	if (isset($params['overseas_warehouse_id']) && !empty($params['overseas_warehouse_id'])){
        		$conditions .= " AND overseas_warehouse_id = :overseas_warehouse_id";
        		$data[':overseas_warehouse_id'] = trim(addslashes($params['overseas_warehouse_id']));
        	}
        	if (isset($params['account_id']) && !empty($params['account_id'])){
        		$conditions .= " AND account_id = :account_id";
        		$data[':account_id'] = trim(addslashes($params['account_id']));
        	}	 
        	if (isset($params['seller_id']) && !empty($params['seller_id'])){
        		$conditions .= " AND seller_id = :seller_id";
        		$data[':seller_id'] = trim(addslashes($params['seller_id']));
        	}

        }
        $wishOverseasWarehouseModel = new WishOverseasWarehouse();
        $ret = $wishOverseasWarehouseModel->getListByCondition('*',$conditions,$data);

		$str = "系统SKU,产品ID,海外仓ID,销售人员,账号名称\n";
		if ($ret){
			foreach ($ret as $val){
				//销售人员ID转为销售名称
				$seller = '';
				if ($val['seller_id'] > 0){
					$userInfo = User::model()->getUserNameById($val['seller_id']);
					if($userInfo) $seller = $userInfo['user_name'];
				}

				$accountName = '';
				if ($val['account_id'] > 0){
					$accountName = WishAccount::model()->getAccountNameById($val['account_id']);
				}
				$str .= "\t".$val['sku'].",\t".$val['product_id'].",\t".$val['overseas_warehouse_id'].",\t".$seller.",\t".$accountName."\n";
			}
		}else{
			$str .='无相关数据可导出'."\n";
		}
		$exportName = 'WishOverseasWarehouse'.date('Ymd').'.csv';
		$this->export_csv($exportName,$str);
        Yii::app()->end();
	}		

	
	/**
	 * @desc 弹窗修改内容
	 * @throws Exception
	 */
	public function actionUpdate(){
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		try{
			$id = Yii::app()->request->getParam("id");
			if(empty($id)) throw new Exception(Yii::t('wish_product_statistic', 'Abnormal Parameter'));
			$info = WishOverseasWarehouse::model()->findByPk($id);
			if(empty($info)){
				throw new Exception(Yii::t('wish_product_statistic', 'Record does not exist'));
			}
			$this->render("update", array("model"=>$info, 'sellerList'=>User::model()->getWishUserList(), 'accountList'=>WishAccount::model()->getIdNamePairs(), 'warehouseList'=>WishOverseasWarehouse::model()->getWarehouseList()));
		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}
	
	/**
	 * @desc 保存更新
	 * @return unkown
	 */
	public function actionSavedata(){
		try{
			$id                  = Yii::app()->request->getParam("id");
			$formData          = Yii::app()->request->getParam("WishOverseasWarehouse");
			$sku                 = $formData['sku'];
			$sellerId            = $formData['seller_id'];
			$overseasWarehouseID = $formData['overseas_warehouse_id'];			
			$accountID           = $formData['account_id'];

			if(empty($sku)){
				throw new Exception(Yii::t('wish_product_statistic', 'SKU does not empty'));
			}
			if(empty($overseasWarehouseID)){
				throw new Exception(Yii::t('wish_product_statistic', 'Overseas warehouse does not empty'));
			}
			$updateData = array(
				'sku'                   => $sku,
				'seller_id'             => $sellerId,
				'overseas_warehouse_id' => $overseasWarehouseID,
				'account_id'            => $accountID,
			);
			$ret = WishOverseasWarehouse::model()->updateListByCondition("id = {$id}", $updateData);
			if(!$ret){
				throw new Exception(Yii::t('system', 'Update failure'));
			}

			$jsonData = array(
					'message' => '更新成功',
					'navTabId' => 'page'.Menu::model()->getIdByUrl('/wish/wishoverseaswarehouse/list'),
					'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);
		}catch(Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}


	
}

?>