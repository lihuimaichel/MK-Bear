<?php
/**
 * @desc 复制刊登权限
 * @author hanxy
 *
 */
class WishcopylistingsellerController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new WishCopyListingSeller();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$sellerList = User::model()->getWishUserList();
		$this->render("index", array("model"=>$this->_model, 'sellerList'=>$sellerList));
	}


	/**
	 * 设置账号
	 */
	public function actionAdd(){
		if($_POST){
			$sellerList     = Yii::app()->request->getParam('WishCopyListingSeller');
			$seller_user_id = isset($sellerList['seller_user_id'])?$sellerList['seller_user_id']:'';

			if(!$seller_user_id){
				echo $this->failureJson(array('message'=>'没有选择销售人员'));
				exit;
			}
			
			//添加新的用户
			if($seller_user_id){
				foreach ($seller_user_id as $valueId) {
					//判断是否已经存在的用户
					$userList = $this->_model->getOneByCondition('seller_user_id',"seller_user_id = ".$valueId);
					if($userList){
						continue;
					}

					$paramArr = array(
						'seller_user_id' => $valueId,
						'create_user_id' => isset(Yii::app()->user->id)?Yii::app()->user->id:0,
						'create_time'    => date('Y-m-d H:i:s')
					);
					$this->_model->getDbConnection()->createCommand()->insert($this->_model->tableName(), $paramArr);
				}
			}

			$jsonData = array(
				'message' => '保存成功',
				'forward' =>'/wish/wishcopylistingseller/list',
				'navTabId'=> 'page' . WishCopyListingSeller::getIndexNavTabId(),
				'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);
			
		}else{
			$sellerList = User::model()->getWishUserList();
			$selectList = $this->_model->getListByCondition('seller_user_id','id > 0');
			$this->render("add", array("model"=>$this->_model, 'sellerList'=>$sellerList, 'selectList'=>$selectList));
		}
	}


	/**
	 * 批量删除
	 */
	public function actionBatchdel(){
		$ids = Yii::app()->request->getParam('ids');
		$info = $this->_model->getDbConnection()->createCommand()->delete($this->_model->tableName(), "id IN(".$ids.")");
		if($info){
			echo $this->successJson(array('message'=>'Delete successful'));
			Yii::app()->end();
		}
		echo $this->failureJson(array('message'=>'Delete failure'));			
	}
}