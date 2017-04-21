<?php
/**
 * @package Ueb.modules.AmazonModel.models
*
* @author wx
*/
class ProductImportSku extends CommonModel {

	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'ueb_import_sku_zero_stock';
	}
	/**
	 * @return array() column name
	 */
	public function columnName() {
		return MHelper::getColumnsArrByTableName(self::tableName());
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
				 
		);
	}

	/**
	 * Declares attribute labels.
	 * @return array
	 */
	public function attributeLabels() {
		return array(
				'id'                            => Yii::t('system', 'No.'),
				'sku'							=>	'SKU',
				'ebay_status'					=>	'Ebay',
				'amazon_status'					=>	'Amazon',
				'aliexpress_status'					=>	'Aliexpress',
				'wish_status'					=>	'Wish',
				'lazada_status'					=>	'Lazada',
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		return array();
	}

	/**
	 * get search info
	 */

	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'id',
		);
		return parent::search(get_class($this), $sort);
	}

	/**
	 * filter search options
	 * @return type
	 */
	public function filterOptions() {
		$amazonStatus = Yii::app()->request->getParam('amazon_status');
		
		$ebayStatus = Yii::app()->request->getParam('ebay_status');
		
		$aliexpressStatus = Yii::app()->request->getParam('aliexpress_status');
		
		$wishStatus = Yii::app()->request->getParam('wish_status');
		
		$lazadaStatus = Yii::app()->request->getParam('lazada_status');
		
		$result = array(
				array(
    					'name'=>'sku',
    					'type'=>'text',
    					'search'=>'LIKE',
    					'htmlOption' => array(
    							'size' => '22',
    					)
    			),
				array(
						'name'=>'amazon_status',
						'type'=>'dropDownList',
						'search'=>'=',
						'data'=>$this->getStatusOptions(),
						'value'=>$amazonStatus,
				),
				
				array(
						'name'=>'ebay_status',
						'type'=>'dropDownList',
						'search'=>'=',
						'data'=>$this->getStatusOptions(),
						'value'=>$ebayStatus
				),
				
				array(
						'name'=>'aliexpress_status',
						'type'=>'dropDownList',
						'search'=>'=',
						'data'=>$this->getStatusOptions(),
						'value'=>$aliexpressStatus
				),
				array(
						'name'=>'wish_status',
						'type'=>'dropDownList',
						'search'=>'=',
						'data'=>$this->getStatusOptions(),
						'value'=>$wishStatus
				),
				array(
						'name'=>'lazada_status',
						'type'=>'dropDownList',
						'search'=>'=',
						'data'=>$this->getStatusOptions(),
						'value'=>$lazadaStatus
				),
		);
		return $result;
	}

	
	public function getStatusOptions(){
		return array(
					0=>'未处理',
					1=>'已处理'
				);
	}
	/**
	 * order field options
	 * @return $array
	 */
	public function orderFieldOptions() {
		return array(
		);
	}

	public static function getIndexNavTabId() {
		return Menu::model()->getIdByUrl('/common/productimportsku/list');
	}

	/**
	 * @desc 特殊虚拟仓发货 导入出货后的包裹，更新包裹状态等信息。
	 * @param string $excelFile
	 * @return string
	 */
	public function saveDataByExcel($excelFile){  //$type 仓库id
		Yii::import('application.vendors.MyExcel.php');
		 
		$data = new MyExcel();
		 
		$excelData = $data->get_excel_con($excelFile);
		 
		//return count($excelData);
		//无数据 导入的数据$i <2
		if(count($excelData)<1){
			return 'no_excel_data';
		}
		 
		$result='';
		$success = 0; //成功数量
		$exist = 0;   //重复数量
		for($i=2;$i<= count($excelData);$i++){
			$result= $this->saveData($excelData[$i]);
			if($result == 'success' ){
				$success++;
			}
			if($result == 'exist' ){
				$exist++;
			}
		}
		return '成功导入'.$success.'条数据，其中剔除掉重复数据'.$exist.'条';
	}

	/**
	 * @desc 保存
	 * @author wx
	 */
	public function saveData( $packageData){
		 
		$sku = trim($packageData['A'], "'");
		if(is_float($sku)){
			$sku = round($sku, 2);
		}
		$newData = array(
				'sku' => $sku,
				'create_time' => date("Y-m-d H:i:s"),
		);
		
		 
		$flag = $this->saveNewData($newData);
		 
		return 'success';
	}




	public function saveNewData($data) {
		$model = new self();
		foreach($data as $key => $value){
			$model->setAttribute($key,$value);
		}
		$model->setIsNewRecord(true);
		if ($model->save()) {
			return $model->id;
		}
		return false;
	}

	/**
	 * @desc 查询一条数据
	 */
	public function getSkuInfo( $sku){
		$ret = $this->dbConnection->createCommand()
		->select('*')
		->from(self::tableName())
		->where('sku="'.$sku.'"')
		->queryRow();
		return $ret;
	}

	/**
	 * @desc 获取对应的sku列表
	 * @param unknown $conditions
	 * @param unknown $params
	 * @param unknown $limits
	 * @param unknown $select
	 * @return mixed
	 */
	public function getSkuListByCondition($conditions, $params, $limits, $select = "*"){
		$command = $this->getDbConnection()->createCommand()
		->from($this->tableName())
		->where($conditions, $params)
		->select($select);
		if($limits){
			$limitsarr = explode(",", $limits);
			$limit = isset($limitsarr[1]) ? trim($limitsarr[1]) : 0;
			$offset = isset($limitsarr[0]) ? trim($limitsarr[0]) : 0;
			$command->limit($limit, $offset);
		}
		return $command->queryAll();
	}
	
	public function updateDataByCondition($conditions, $data){
		 return $this->getDbConnection()->createCommand()
		 				->update($this->tableName(), $data, $conditions);
	}
}