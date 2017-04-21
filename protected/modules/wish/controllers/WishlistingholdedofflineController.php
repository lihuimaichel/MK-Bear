<?php
/**
 * @desc Wish
 * @author Liz
 *
 */
class WishlistingholdedofflineController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new WishListingHoldedOffline();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$request = http_build_query($_POST);

		//查询出搜索的总数
		$itemCount = $this->_model->search()->getTotalItemCount();

		$this->render("index", array("model"=>$this->_model, 'request'=>$request, 'itemCount'=>$itemCount));
	}

	
	/**
	 * @desc 更新
	 * @throws Exception
	 */
	public function actionUpdate(){
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		try{
			$id = Yii::app()->request->getParam("id");
			if(empty($id)) throw new Exception("参数不正确");
			$model = UebModel::model("WishProductSellerRelation")->findByPk($id);
			if(empty($model)){
				throw new Exception("不存在该数据");
			}
			$model->account_name = UebModel::model("WishAccount")->getAccountNameById($model->account_id);
			$this->render("update", array("model"=>$model, 'sellerList'=>User::model()->getWishUserList()));
		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}
	
	public function actionSavedata(){
		try{
			$id = Yii::app()->request->getParam("id");
			$wishProductSellerRelation = Yii::app()->request->getParam("WishProductSellerRelation");
			$sellerId = $wishProductSellerRelation['seller_id'];
			$sku = $wishProductSellerRelation['sku'];
			$onlineSku = $wishProductSellerRelation['online_sku'];
			if(empty($id) || empty($sellerId) || empty($sku) || empty($onlineSku)){
				throw new Exception("参数不对");
			}
			$res = UebModel::model("WishProductSellerRelation")->updateDataById($id, array('seller_id'=>$sellerId, 'sku'=>$sku, 'online_sku'=>$onlineSku));
			if(!$res){
				throw new Exception("操作失败");
			}

			$jsonData = array(
					'message' => '更改成功',
					'forward' =>'/lazada/lazadaproductsellerrelation/list',
					'navTabId'=> 'page' .WishProductSellerRelation::getIndexNavTabId(),
					'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);

			// echo $this->successJson(array('message'=>'更改成功'));
		}catch(Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}
	
	/**
	 * @desc 批量更新
	 * @throws Exception
	 */
	public function actionBatchupdate(){
		try{
			$ids = Yii::app()->request->getParam("ids");
			$wishProductSellerRelation = Yii::app()->request->getParam("WishProductSellerRelation");
			
			if(empty($ids)){
				throw new Exception("参数不对");
			}
			$idArr = explode(",", $ids);
			$res = WishListingHoldedOffline::model()->updateByIds($idArr);
			if(!$res){
				throw new Exception("操作失败");
			}
			echo $this->successJson(array('message'=>'操作成功'));
		}catch(Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}
	
}