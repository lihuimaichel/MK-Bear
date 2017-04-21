<?php
/**
 * @desc Wish listing
 * @author Gordon
 * @since 2015-06-02
 */
class WishspecialordershipcodeController extends UebController{
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

    public function actionList(){
    	$model = new WishSpecialOrder();
    	$model->getAccessUser();
    	$model = new WishSpecialOrderShipCode();
    	$this->render("list", array("model"=>$model));
    }
    
    public function actionAdd(){
    	$model = new WishSpecialOrderShipCode();
    	$this->render('add', array('model' => $model));
    }
    
    public function actionUpdate(){
    	$id = Yii::app()->request->getParam('id');
    	$model = new WishSpecialOrderShipCode();
    	$model = $model->findByPk($id);
    	$this->render('add', array('model' => $model, 'id'=>$id));
    }
    
    public function actionSavedata(){
    	try{
    		$model = new WishSpecialOrderShipCode();
    		$id = Yii::app()->request->getParam('id');
    		$post = Yii::app()->request->getParam('WishSpecialOrderShipCode');

    		$shipName = $post['ship_name'];
    		$shipCode = $post['ship_code'];
    		$status = $post['status'];
    		$time = date("Y-m-d H:i:s");
    		$userId = Yii::app()->user->id;
    		if(empty($shipName) && empty($shipCode)){
    			throw new Exception("Ship Name AND Ship Code at least one required");
    		}
    		
    		$filterIds = array();
    		if($id){
    			$filterIds = array($id);
    		}
    		//检测同名
    		if($model->checkExistsByShipName($shipName, $filterIds)){
    			throw new Exception("Ship Name Exists");
    		}
    		if($model->checkExistsByShipCode($shipCode, $filterIds)){
    			throw new Exception("Ship Code Exists");
    		}
    		$data = array(
    				'ship_name'		=>	$shipName,
    				'ship_code'		=>	$shipCode,
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
    					'message' => Yii::t('system','Save successful'),
    					'forward' => '/wish/wishspecialordershipcode/list',
    					'navTabId' => 'page'.Menu::model()->getIdByUrl('/wish/wishspecialordershipcode/list'),
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
}