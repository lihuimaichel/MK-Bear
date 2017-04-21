<?php
class ProductRiskRecord extends CommonModel{
	
	/**@var String 产品卖价*/
	protected $_sale_price = null;
	
	/**@var String 销售平台*/
	protected $_platform_code = null;
	
	/**@var String SKU*/
	protected $_sku = null;
	
	/**@var String 币种*/
	protected $_currency = null;
	
	/**@var String 利润率*/
	protected $_profit_rate = null;
	
	/**@var 上传状态*/
	const STATUS_NOTMARK    = 0;//未标记
	const STATUS_MARKED     = 1;//已标记
	const STATUS_FINISHED   = 2;//已刷价
	
	public $status_desc = NULL; 
	
	/**
	 * @desc 设置产品卖价
	 * @param string $keyword
	 */
	public function setSalePrice($salePrice){
		$this->_product_sale_price = $salePrice;
	}
	
	/**
	 * @desc 设置SKU
	 * @param string $keyword
	 */
	public function setSku($sku){
		$this->_sku = $sku;
	}
	
	/**
	 * @desc 设置币种
	 * @param string $keyword
	 */
	public function setCurrency($currency){
		$this->_currency = $currency;
	}
	
	/**
	 * @desc 设置利润率
	 * @param string $keyword
	 */
	public function setProfitRate($profitrate){
		$this->_profit_rate = $profitrate;
	}
	
	/**
	 * @desc 设置利润率
	 * @param string $keyword
	 */
	public function setPlatformCode($platformCode){
		$this->_platform_code = $platformCode;
	}
	
	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'ueb_product_risk_record';
	}
	
	/**
	 * @desc 获取状态列表
	 * @param string $status
	 */
	public static function getStatusList($status = null){
		$statusArr = array(
				self::STATUS_NOTMARK     => Yii::t('common', 'STATUS NOTMARK'),
				self::STATUS_MARKED      => Yii::t('common', 'STATUS MARKED'),
				self::STATUS_FINISHED    => Yii::t('common', 'STATUS FINISHED'),
		);
		if($status===null){
			return $statusArr;
		}else{
			return $statusArr[$status];
		}
	}
	
	/**
	 * @desc 属性翻译
	 */
	public function attributeLabels() {
		return array(
				'sku'					=> Yii::t('common', 'SKU'),
				'product_id'            => Yii::t('common', 'Product Id'),
				'platform_code'		    => Yii::t('common', 'Platform Code'),
				'sku'		            => Yii::t('common', 'Sku'),
				'status'		        => Yii::t('common', 'Status'),
				'modify_time'		    => Yii::t('common', 'Modify Time'),
				'modify_user_id'		=> Yii::t('common', 'Modify User'),
				'note'		    		=> Yii::t('common', 'Note'),
		);
	}
	
	/**
	 * @return array search filter (name=>label)
	 */
	public function filterOptions() {
		$result = array(
				array(
    					'name'      => 'sku',
    					'type'      => 'text',
    					'search'    => 'LIKE',
    					'alias'     => 't',
    				),
    			array(
    					'name'      => 'product_id',
    					'type'      => 'text',
    					'search'    => '=',
    					'alias'     => 't',
    			),
    			array(
    					'name'		 => 'platform_code',
    					'type'		 => 'dropDownList',
    					'search'	 => '=',
    					'data'		 => UebModel::model('Platform')->getPlatformList(),
    					'alias'	     => 't',
    			),
		);
	
		return $result;
	
	}
	/**
	 * search SQL
	 * @return $array
	 */
	protected function _setCDbCriteria() {
	
		return NULL;
	}
	/**
	 * @return $array
	 */
	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'id',
		);
		$criteria = null;
		$criteria = $this->_setCDbCriteria();
		$dataProvider = parent::search(get_class($this), $sort,array(),$criteria);
	
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	/**
	 * @return $array
	 */
	public function addition($data){
		foreach ($data as $key => $val){
			$data[$key]->status_desc = self::getStatusList($val->status);
		}
		return $data;
	}
	
	/**
	 * @desc 检查当前listing卖价是否存在风险
	 * @param string $keyword
	 */
	public function isRisk(){
		$salePrice		  =  $this->_sale_price;
		$sku 			  =  $this->_sku;
		$currency 		  =  $this->_currency;
		$profitRate 	  =  $this->_profit_rate;
		$platformCode 	  =  $this->_platform_code;
		
		$priceCal = new CurrencyCalculate();
		$priceCal ->setSalePrice($salePrice);//设置利润率
		$priceCal ->setCurrency($currency);//币种
		$priceCal ->setPlatform($platformCode);//设置销售平台
		$priceCal ->setSku($sku);//设置sku
		
		$lowestProfit = $priceCal->getProfitRate();//获取卖价
		
		if($lowestProfit > $profitRate){
			return true;
		}
		else {
			return false;
		}
		
	}
	
	/**
	 * @desc 表中是否已经存在数据
	 * @param string $keyword
	 */
	public function checkIsExist($productId){
		$platformCode = $this->_platform_code;
		$data = $this->getDbConnection()->createCommand()
				->select('id')->from($this->tableName())
				->where('product_id = "'.$productId.'" AND platform_code = "'.$platformCode.'"')
				->queryRow();
		if (empty($data)){
			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * @desc 对存在风险的listing保存记录
	 * @param string $keyword
	 */
	public function saveRiskRecord($param){
		if ($this->checkIsExist($param['product_id'])){
			$this->dbConnection->createCommand()->delete($this->tableName(), 'product_id = "'.$param['product_id'].'" AND platform_code = "'.$param['platform_code'].'"');
			$this->dbConnection->createCommand()->insert($this->tableName(), $param);
		}
		else{
			$this->dbConnection->createCommand()->insert($this->tableName(), $param);
		}
	}
	
}