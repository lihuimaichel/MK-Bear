<?php
/**
 * @desc 产品临时表
 * @author yangsh
 * @since 2016-09-12
 *
 */
class ProducttempController extends UebController{

	/**
	 * @desc 模型
	 * @var Instance
	 */
	protected $_model = null;

        /**
	 * (non-PHPdoc)
	 * @see CommonModule::init()
	 */
	public function init() {
		$this->_model = new ProductTemp();
		parent::init();
	}

	/**
	 * @desc 同步产品信息及库存信息
	 * @link /common/producttemp/sync
	 */
	public function actionSync() {
		set_time_limit(0);
		ini_set('display_errors', true);
        error_reporting(E_ALL);

		$nowTime         = date("Y-m-d H:i:s");
		$product         = Product::model();
		$warehouseSkuMap = WarehouseSkuMap::model();
		$res             = $product->getDbConnection()->createCommand()
					    	->select("count(*) as total")
					    	->from($product->tableName())
					    	->queryRow();
		$total     = $res['total'];				    	
		$pageSize  = 2000;
		$pageCount = ceil($total/$pageSize);
		for ($page=1; $page <= $pageCount ; $page++) { 
			$offset = ($page - 1) * $pageSize;
			$res 	= $product->getDbConnection()->createCommand()
					    	->select("sku,product_status,product_is_multi")
					    	->from($product->tableName())
					    	->order("sku asc")
					    	->limit($pageSize,$offset)
					    	->queryAll();
			if ($res) {
				$skuArr = array();
				foreach ($res as $v) {
					if ($v['product_is_multi'] != 2) {
						$skuArr[] = $v['sku'];
					}
				}
				$avgArr = array();
				if ($skuArr) {
					//深圳光明仓可用库存
					$wmsRes = $warehouseSkuMap->getDbConnection()->createCommand()
					    	->select("sku,available_qty")
					    	->from($warehouseSkuMap->tableName())
					    	->where("sku in('".implode("','",$skuArr)."')")
					    	->andWhere("warehouse_id=41")
					    	->queryAll();
					if ($wmsRes) {
						foreach ($wmsRes as $v) {
							$avgArr[$v['sku']] = $v['available_qty'];
						}
					}
				}
				foreach ($res as $v) {
					$row                     = array();
					$row['sku']              = $v['sku'];
					$row['product_status']   = $v['product_status'];
					$row['product_is_multi'] = $v['product_is_multi'];
					$row['available_qty']    = 0;
					$row['updated_at']       = $nowTime;
					if (isset($avgArr[$v['sku']])) {
						$row['available_qty'] = $avgArr[$v['sku']];
					}
					$tempInfo = $this->_model->getOneByCondition('sku',"sku='{$v['sku']}'");
					if ( !empty($tempInfo) ) {
						$this->_model->getDbConnection()->createCommand()->update($this->_model->tableName(),$row,"sku='{$v['sku']}'");
					} else {
						$this->_model->getDbConnection()->createCommand()->insert($this->_model->tableName(),$row);
					}
				}
			}
		}
		Yii::app()->end('finish');
	}

}