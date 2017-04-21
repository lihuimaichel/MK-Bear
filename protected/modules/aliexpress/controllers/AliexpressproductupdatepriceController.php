<?php
/**
 * @desc 速卖通产品改价记录
 * @author hanxy
 *
 */
class AliexpressproductupdatepriceController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new AliexpressLogUpdatePrice();
		parent::init();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$this->render("list", array("model"=>$this->_model));
	}


	/**
	 * 批量更新log记录的价格
	 */
	public function actionUpdateprice(){
		$ids = Yii::app()->request->getParam('ids');
		if(!$ids){
			echo $this->failureJson(array('message'=> '请选择'));
			Yii::app()->end();
		}

		$wheres = "t.id IN(".$ids.")";
		$aliAccountModel          = new AliexpressAccount();
		$aliProductVariationModel = new AliexpressProductVariation();
		$aliEditPriceModel        = new AliexpressEditPrice();

		$command = $this->_model->getDbConnection()->createCommand()
	        ->from($this->_model->tableName() . " as t")
	        ->leftJoin($aliAccountModel->tableName()." as a", "a.id=t.account_id")
	        ->leftJoin($aliProductVariationModel->tableName()." as v", "v.aliexpress_product_id=t.product_id AND v.sku = t.sku")
	        ->select("t.id,t.sku,t.product_id,t.update_price,t.account_id,v.sku_id,v.id as v_id,v.product_id as p_id")
	        ->where($wheres)
	    	->queryAll();
	    if(!$command){
	    	echo $this->failureJson(array('message'=> '无数据要更改'));
			Yii::app()->end();
	    }

	    foreach ($command as $val) {
	    	$flag = $aliEditPriceModel->updateProductsPrice($val['account_id'], $val['product_id'], $val['update_price'], $val['sku_id']);
            if( $flag ){
                AliexpressEditPrice::model()->updatePrice($val['p_id'], $val['update_price']);
                AliexpressProductVariation::model()->updatePrice($val['v_id'], $val['update_price']);
                $data = array(
                	'status' => 1,
                	'message' => '改价成功'
                );
            }else{
            	$data = array(
                	'status' => 0,
                	'message' => '改价失败:'.$aliEditPriceModel->getExceptionMessage()
                );
            }

            $data['start_time'] = date('Y-m-d H:i:s');
            $conditions = 'id = :id';
            $params = array(':id'=>$val['id']);
            $this->_model->getDbConnection()->createCommand()->update($this->_model->tableName(), $data, $conditions, $params);
	    }

		echo $this->successJson(array('message'=>'操作成功'));
		Yii::app()->end();
	}
}