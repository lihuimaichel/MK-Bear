<?php
/**
 * @desc Wish listing
 * @author Gordon
 * @since 2015-06-02
 */
class WishspecialorderaccountController extends UebController{
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

    public function actionUserlist(){
    	$model = new WishSpecialOrder();
    	$model->getAccessUser();
    	$model = new WishSpecialOrderAccount();
    	$this->render("userlist", array("model"=>$model));
    }
    
    public function actionAddspuser(){
    	$model = new WishSpecialOrderAccount();
    	$this->render('addspuser',array('model' => $model));
    }
    
    public function actionUpdatespuser(){
    	$id = Yii::app()->request->getParam('id');
    	$model = new WishSpecialOrderAccount();
    	$model = $model->findByPk($id);
    	$this->render('addspuser',array('model' => $model, 'id'=>$id));
    }
    
    public function actionSavedata(){
    	try{
    		$model = new WishSpecialOrderAccount();
    		$id = Yii::app()->request->getParam('id');
    		$post = Yii::app()->request->getParam('WishSpecialOrderAccount');
    		$buyerId = $post['buyer_id'];
    		$buyerEmail = $post['buyer_email'];
    		$buyerPhone = $post['buyer_phone'];
    		$paypalId = $post['paypal_id'];
    		$status = $post['status'];
    		$time = date("Y-m-d H:i:s");
    		$userId = Yii::app()->user->id;
    		if(empty($buyerId)){
    			throw new Exception("Buyer_id required");
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
    				'update_id'		=>	$userId,
    				'order_num'		=>	rand(0, 10)
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
    					'forward' => '/wish/wishspecialorderaccount/userlist',
    					'navTabId' => 'page'.Menu::model()->getIdByUrl('/wish/wishspecialorderaccount/userlist'),
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
    
    /**
     * @desc 删除
     * @throws Exception
     */
    public function actionDelete(){
    	try{
    		$ids = Yii::app()->request->getParam("ids");
    		if(empty($ids)) throw new Exception("没有指定数据");
    		$model = new WishSpecialOrderAccount();
    		if(!$model->deleteAll("id in({$ids})")){
    			throw new Exception("删除操作失败");
    		}
    		$jsonData = array(
    				'message' => "删除成功",
    				'forward' => '/wish/wishspecialorderaccount/userlist',
    		);
    		echo $this->successJson($jsonData);
    	}catch (Exception $e){
    		echo $this->failureJson(array('message'=>$e->getMessage()));
    	}
    }
    
    //batchstopbuyer batchstartbuyer batchstopbuyerschedule batchstartbuyerschedule
    
    public function actionBatchstopbuyer(){
    	try{
    		$ids = Yii::app()->request->getParam("ids");
    		if(empty($ids)) throw new Exception("没有指定数据");
    		$model = new WishSpecialOrderAccount();
    		$idarr = explode(",", $ids);
    		$updateData = array('status'=>2, 'update_id'=>(int)Yii::app()->user->id, 'update_time'=>date("Y-m-d H:m:s"));
    		if(!$model->updateDataByIDs($idarr, $updateData)){
    			throw new Exception("操作失败");
    		}
    		$jsonData = array(
    				'message' => "操作成功",
    				'forward' => '/wish/wishspecialorderaccount/userlist',
    		);
    		echo $this->successJson($jsonData);
    	}catch (Exception $e){
    		echo $this->failureJson(array('message'=>$e->getMessage()));
    	}
    }
    
    public function actionBatchstartbuyer(){
    	try{
    		$ids = Yii::app()->request->getParam("ids");
    		if(empty($ids)) throw new Exception("没有指定数据");
    		$model = new WishSpecialOrderAccount();
    		$idarr = explode(",", $ids);
    		$updateData = array('status'=>1, 'update_id'=>(int)Yii::app()->user->id, 'update_time'=>date("Y-m-d H:m:s"));
    		if(!$model->updateDataByIDs($idarr, $updateData)){
    			throw new Exception("操作失败");
    		}
    		$jsonData = array(
    				'message' => "操作成功",
    				'forward' => '/wish/wishspecialorderaccount/userlist',
    		);
    		echo $this->successJson($jsonData);
    	}catch (Exception $e){
    		echo $this->failureJson(array('message'=>$e->getMessage()));
    	}
    }
    
    public function actionBatchstopbuyerschedule(){
    	try{
    		$ids = Yii::app()->request->getParam("ids");
    		if(empty($ids)) throw new Exception("没有指定数据");
    		$model = new WishSpecialOrderAccount();
    		$idarr = explode(",", $ids);
    		$updateData = array('schedule_status'=>0, 'update_id'=>(int)Yii::app()->user->id, 'update_time'=>date("Y-m-d H:m:s"));
    		if(!$model->updateDataByIDs($idarr, $updateData)){
    			throw new Exception("操作失败");
    		}
    		$jsonData = array(
    				'message' => "操作成功",
    				'forward' => '/wish/wishspecialorderaccount/userlist',
    		);
    		echo $this->successJson($jsonData);
    	}catch (Exception $e){
    		echo $this->failureJson(array('message'=>$e->getMessage()));
    	}
    }
    
    public function actionBatchstartbuyerschedule(){
    	try{
    		$ids = Yii::app()->request->getParam("ids");
    		if(empty($ids)) throw new Exception("没有指定数据");
    		$model = new WishSpecialOrderAccount();
    		$idarr = explode(",", $ids);
    		$updateData = array('schedule_status'=>1, 'update_id'=>(int)Yii::app()->user->id, 'update_time'=>date("Y-m-d H:m:s"));
    		if(!$model->updateDataByIDs($idarr, $updateData)){
    			throw new Exception("操作失败");
    		}
    		$jsonData = array(
    				'message' => "操作成功",
    				'forward' => '/wish/wishspecialorderaccount/userlist',
    		);
    		echo $this->successJson($jsonData);
    	}catch (Exception $e){
    		echo $this->failureJson(array('message'=>$e->getMessage()));
    	}
    }
}