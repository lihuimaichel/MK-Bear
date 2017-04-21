<?php
/**
 * @desc Wish listing
 * @author Gordon
 * @since 2015-06-02
 */
class WishspecialorderimporttracenumberController extends UebController{
	private $_model = null;
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('getlisting', 'list')
			),
		);
    }  

    public function actionIndex(){
    	$model = new WishSpecialOrder();
    	$model->getAccessUser();
    	$model = new WishSpecialOrderImportTraceNumber();
    	$this->render("index", array("model"=>$model));
    }
    
    public function actionAdd(){
    	$model = new WishSpecialOrderImportTraceNumber();
    	//获取物流
    	//上传文件
    	if($_POST){
    		$shipCode = Yii::app()->request->getParam("ship_code");//账号表的主键id
   
    		if(empty($shipCode)){
    			echo $this->failureJson(array('message'=>"选择物流方式"));
    			Yii::app()->end();
    		}
    		if(empty($_FILES['csvfilename']['tmp_name'])){
    			echo $this->failureJson(array('message'=>"文件上传失败"));
    			Yii::app()->end();
    		}
    		$filename = $_FILES['csvfilename']['name'];
    		$filename = substr($filename, 0, strrpos($filename, "."));
    		$outputFile = UPLOAD_DIR . date("Y-m-dHis") . '-' . $filename .'-upload_image_result.csv';
    		
    		$file = $_FILES['csvfilename']['tmp_name'];
  
    		Yii::import('application.vendors.MyExcel');
    		$PHPExcel = new MyExcel();
    		$datas = $PHPExcel->get_excel_con($file);
    		if($datas){
    			$nowtime = date("Y-m-d H:i:s");
    			foreach ($datas as $key=>$data){
    				if($key == 1) continue;
    				$traceNumber = trim($data['A']);
    				$shipCountry = trim($data['D']);
    				$shipDate = str_replace("/", "-", trim($data['C']));
    				if(empty($traceNumber) || empty($shipCountry) || empty($shipDate)){
    					echo $this->failureJson(array('message'=>"内容里面有空值，请修改后再次上传"));
    					Yii::app()->end();
    				}
    				if($model->checkExistsByShipCodeAndTraceNumber($shipCode, $traceNumber)){
    					continue;
    				}
    				$addData = array(
    					'ship_code'		=>	$shipCode,
    					'ship_date'		=>	$shipDate,
    					'trace_number'	=>	$traceNumber,
    					'ship_country'	=>	$shipCountry,
    					'ship_country_name'	=>	Country::model()->getEnNameByAbbr($shipCountry),
    					'status'		=>	WishSpecialOrderImportTraceNumber::STATUS_NO,
    					'create_time'	=>	$nowtime,
    					'update_time'	=>	$nowtime
    				);
    				$model->saveData($addData);
    			}
    		}
    		echo $this->successJson(array('message'=>'执行完成x！'));
    		Yii::app()->end();
    	}
    	$this->render('add',array('model' => $model));
    }
    
    public function actionUpdate(){
    	$id = Yii::app()->request->getParam('id');
    	$model = new WishSpecialOrderImportTraceNumber();
    	$model = $model->findByPk($id);
    	$this->render('addspuser',array('model' => $model, 'id'=>$id));
    }
    
    public function actionSavedata(){
    	try{
    		$model = new WishSpecialOrderImportTraceNumber();
    		$id = Yii::app()->request->getParam('id');
    		$post = Yii::app()->request->getParam('WishSpecialOrderAccount');
    		$buyerId = $post['buyer_id'];
    		$buyerEmail = $post['buyer_email'];
    		$buyerPhone = $post['buyer_phone'];
    		$paypalId = $post['paypal_id'];
    		$status = $post['status'];
    		$time = date("Y-m-d H:i:s");
    		$userId = Yii::app()->user->id;
    		if(empty($buyerId) && empty($buyerPhone)){
    			throw new Exception("Buyer_id AND Buyer_phone at least one required");
    		}
    		/* if($model->checkUniqueByPhone($buyerPhone, $id)){
    			throw new Exception("Has Exists the Same Phone");
    		}
    			
    		if($model->checkUniqueByBuyerId($buyerId, $id)){
    			throw new Exception("Has Exists the Same Buyer Id");
    		} */
    		
    		$data = array(
    				'buyer_id'		=>	$buyerId,
    				'buyer_email'	=>	$buyerEmail,
    				'buyer_phone'	=>	$buyerPhone,
    				'paypal_id'		=>	$paypalId,
    				'status'		=>	$status,
    				'create_time'	=>	$time,
    				'update_time'	=>	$time,
    				'create_id'		=>	$userId,
    				'update_id'		=>	$userId
    		);
    		if($id){//update
    			unset($data['create_id'], $data['create_time']);
    			$res= $model->updateDataByID($id, $data);
    		}else{
    			$res = $model->saveData($data);
    		}
    		if($res){
    			$jsonData = array(
    					'message' => Yii::t('system','Add successful'),
    					'forward' => '/wish/wishspecialorderimporttracenumber/index',
    					'navTabId' => 'page'.Menu::model()->getIdByUrl('/wish/wishspecialorderimporttracenumber/index'),
    					'callbackType' => 'closeCurrent'
    			);
    			echo $this->successJson($jsonData);
    		}else{
    			throw new Exception("Save Failure");
    		}
    	}catch (Exception $e){
    		echo $this->failureJson(array('message'=>$e->getMessage()));
    	}
    }
    
    
    public function actionBatchdel(){
    	try{
    		$ids = Yii::app()->request->getParam('ids');
    		
    		if(empty($ids)){
    			throw new Exception("没有选择项");
    		}
    		$idarr = explode(",", $ids);
    		$model = new WishSpecialOrderImportTraceNumber();
    		$res = $model->deleteById($idarr);
    		if($res){
    			echo $this->successJson(array('message'=>'操作成功'));
    		}else{
    			throw new Exception("操作失败");
    		}
    	}catch (Exception $e){
    		echo $this->failureJson(array('message'=>$e->getMessage()));
    	}
    }
}