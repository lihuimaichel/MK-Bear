<?php
class ProductfieldchangestatisticsController extends UebController {
	public $_model = null;
	
	public function init() {
		$this->_model = new ProductFieldChangeStatistics();
	}
	
	/**
	 * @desc 插入数据
	 * @link /systems/productfieldchangestatistics/insertdata/type/1   加权平均价
	 * @link /systems/productfieldchangestatistics/insertdata/type/2   产品毛重
	 */
	public function actionInsertdata(){
		set_time_limit(1 * 3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);

		$productModel = new Product();
		$date = date("Y-m-d 00:00:00", strtotime('-1 day'));   //前一天日期
		$type = Yii::app()->request->getParam('type', ProductFieldChangeStatistics::AVG_PRICE_TYPE);
		if($type == ProductFieldChangeStatistics::AVG_PRICE_TYPE){
			$tableName = $productModel->tableNameAvgCostEffectiveUpdate();
		}else{
			$type == ProductFieldChangeStatistics::PRODUCT_WEIGHT_TYPE;
			$tableName = $productModel->tableNameWeightEffectiveUpdate();
		}

		//查询价格数据
		$command = $productModel->getDbConnection()->createCommand()
			->from($tableName)
			->select("sku,from_value,to_value,change_time")
			->Where("change_time = '{$date}'")
			->order('id desc');
		$dataList = $command->queryAll();
		if($dataList){
			foreach ($dataList as $priceInfo) {
				//判断平均价格变化日期或产品重量变化日期是否为空
				if(!$priceInfo['change_time']){
					continue;
				}

				$reportTime = date('Y-m-d', strtotime($priceInfo['change_time']));
				$sku = $priceInfo['sku'];
				
				$datas = array(
					'sku'         => $sku,
					'last_field'  => $priceInfo['from_value'],
					'new_field'   => $priceInfo['to_value'],
					'type'        => $type,
					'report_time' => $reportTime,
					'create_time' => date('Y-m-d H:i:s')
				);

				//查询数据是否存在
				$isExist = $this->_model->getInfoByCondition('sku', "sku = '{$sku}' AND type = {$type} AND report_time = '{$reportTime}'");
				if(!$isExist){
					$this->_model->insertData($datas);
				}
			}
		}
	}	
}