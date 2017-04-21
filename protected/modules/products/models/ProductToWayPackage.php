<?php
class ProductToWayPackage extends ProductsModel{
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	const PACKAGE_TYPE_GOODS=1; // 来货包装方式
	const PACKAGE_TYPE_INCOMING=2; // 贴标加工包装
	const PACKAGE_TYPE_BAOCAI=3;	//包材
	const PACKAGE_TYPE_BAOZHUANG=4;//包装
	
	public function tableName(){
		return 'ueb_product_package';
	}
	public function attributeLabels() {
		return array(
				'pack_name'  => Yii::t('system', '来货包装方式'),
		);
	}
	/**
	 *  获取来货方式包装
	 */
	public function getProductPackageById($id) {
		$model=$this->findByPk($id);
		return $model->code.' : '.$model->pack_name;
	}

	public function getBaoCaiByCode($code){
		$data = $this->getDbConnection()->createCommand()
			->select('*')
			->from(self::tableName())
			->where("code='".$code."'")
			->queryRow();
		return $data['pack_name'];
		
		
	}
	/**
	 *  获取来来料加工包装方式
	 */
	public function getProductAllPackage() {
		static $info=array();
		$dataObj = $this->getDbConnection()->createCommand()
		->select('*')
		->from(self::tableName())
		->queryAll();
		if($dataObj){
			foreach ($dataObj as $val){
				if($val['type']==self::PACKAGE_TYPE_GOODS){
					$info['purincome'][$val['id']]=$val['code'].':'.$val['pack_name'];
				}
				if($val['type']==self::PACKAGE_TYPE_INCOMING){
					$info['labelproces'][$val['id']]=$val['code'].':'.$val['pack_name'];
				}
				if($val['type']==self::PACKAGE_TYPE_BAOCAI){
					$info['baocai'][$val['id']]=$val['code'].':'.$val['pack_name'];
				}
				if($val['type']==self::PACKAGE_TYPE_BAOZHUANG){
					$info['baozhuang'][$val['id']]=$val['code'].':'.$val['pack_name'];
				}
			}
		}
		return $info;
	}
	
	public function getProductPackageCost($sku){
		$dataObj = $this->getDbConnection()->createCommand()
		->select('B.*')
		->from(UebModel::model('Product')->tableName().' AS A')
		->join(self::tableName().' AS B', 'B.id=A.product_pack_code OR B.id=A.product_package_code OR B.id=A.product_label_proces')
		->where("A.sku='".$sku."'")
		->queryAll();
		$cost=array();
		if(!empty($dataObj)){
			foreach ($dataObj as $obj){
				$cost['machin_cost']+=$obj['machin_cost'];
				$cost['material_cost']+=$obj['material_cost'];
			}
			return $cost;	
		}else{
			return 0;
		}
	}
	
	/**
	 *  获取所有来来料加工包装方式
	 */
	public function getAllPack() {
		static $info=array();
		$dataObj = $this->getDbConnection()->createCommand()
		->select('*')
		->from(self::tableName())
		->queryAll();
		if($dataObj){
			foreach ($dataObj as $val){
				$info[$val['id']] = $val['product_sku'];
			}
		}
		return $info;
	}
	
}
?>