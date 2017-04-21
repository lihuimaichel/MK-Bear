<?php
/**
 * @desc Wish listing
 * @author Gordon
 * @since 2015-06-02
 */
class WishspecialorderController extends UebController{
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
		$this->render("index", array("model"=>$model));
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
     * @desc 订单统计
     */
    public function actionOrderstatistics(){
    	//通过buyerid
    	$model = new WishSpecialOrderStatistic();
    	WishSpecialOrder::model()->getAccessUser();
    	$this->render("statisticlist", array("model"=>$model));
    }
    
    public function actionShowbuyerid(){
    	$buyerId = Yii::app()->request->getParam('buyer_id');
    	$model = new WishSpecialOrderBuyerIdStatistic();
    	WishSpecialOrder::model()->getAccessUser();
    	$this->render("buyerstatisticlist", array("model"=>$model, 'buyerId'=>$buyerId));
    }
}