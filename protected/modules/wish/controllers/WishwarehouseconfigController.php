<?php
/**
 * @desc wish海外仓配置
 * @author lihy
 *
 */
class WishwarehouseconfigController extends UebController{
	public function accessRules(){
		return array(
				array(
					'allow', 
					'users'=>'*', 
					'actions'=>array('index', 'add', 'getableaccount', 'addinfo'))
		);
	}
	/**
	 * @desc 刊登产品时选择对应SKU
	 */
	public function actionIndex(){
		//获取所有非本地仓列表
		$warehouseModel = new Warehouse();
		$warehouseList = $warehouseModel->findAll();
		
		//获取本地配置文件列表
		$warehouseConfigModel = new WishWarehouseConfig();
		$localWarehouseList = $warehouseConfigModel->getListByCondition("*", "1", array());
		$newWarehouseList = array();
		foreach ($warehouseList as $warehouse){
			$newWarehouseList[$warehouse['id']] = array(
														'id'	=>	$warehouse['id'],
														'name'	=>	$warehouse['warehouse_name'],
														'flag'	=>	false
													);
		}

		if($localWarehouseList){
			foreach ($localWarehouseList as $warehouse){
				if(isset($newWarehouseList[$warehouse['warehouse_id']])){
					$newWarehouseList[$warehouse['warehouse_id']]['flag'] = true;
				}
			}
		}
		$this->render('index', array(
									'warehouseList'		=>	$newWarehouseList
								));
	}
	
	/**
	 * @desc 
	 * @throws Exception
	 */
	public function actionSavedata(){
		$warehouseConfigModel = new WishWarehouseConfig();
		$warehouseIdList = Yii::app()->request->getParam("warehouse_config");
		try{
			if(empty($warehouseIdList)) throw new Exception("没有选择任何仓库");
			//首先清空表
			$warehouseConfigModel->deleteWarehouseConfig("1");
			//其次插入数据
			$warehouseModel = new Warehouse();
			$warehouseList = $warehouseModel->findAll("id in(".MHelper::simplode($warehouseIdList).")");
			if(empty($warehouseList)) throw new Exception("没有匹配到仓库");
			if(count($warehouseList) != count($warehouseIdList)){
				throw new Exception("部分仓库没有匹配到");
			}
			foreach ($warehouseList as $warehouse){
				$warehouseConfigModel->addWarehouseAdd(array(
														'warehouse_id'=>$warehouse['id'],
														'warehouse_name'=>$warehouse['warehouse_name']));
			}
			
			//成功
			echo $this->successJson(array('message'=>'操作成功！'));
		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}
	
}