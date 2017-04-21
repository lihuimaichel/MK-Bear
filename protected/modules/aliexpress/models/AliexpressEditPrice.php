<?php
/**
 * @desc Aliexpress单个sku改价相关
 * @author Liutf
 * @since 2015-09-23
 */
class AliexpressEditPrice extends AliexpressModel{
    
    const EVENT_NAME = 'editprice';
    
    /** @var object sku改价返回信息*/
    public $editPriceResponse = null;
    
    /** @var string 异常信息*/
    public $exception = null;
    
    /** @var int 账号ID*/
    public $_accountId	= null;
    
    public $changeway = null;
    
    public $detail = null;
    
    public $opration = null;

    public $discount;
    
//     public $sku	= null;
    
    public $variable_price = '';
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
	public function columnName() {
    	return MHelper::getColumnsArrByTableName(self::tableName());
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_product';
    }
    
    /**
     * @return array relational rules.
     */
//     public function relations(){
    	
//     	return array(
//     				'detail'   => array(self::HAS_MANY, 'AliexpressProductVariation',array('id'=>'id'))
//     	);
//     }
    
	/**
	 * @desc 设置账号ID
	 * @param int $accountId
	 */
	public function setAccountId($accountId){
		$this->_accountId = $accountId;
	}
	
	public function getAccountId() {
		return $this->_accountId;
	}
	
	/**
	 * @desc 设置异常信息
	 * @param string $message
	 */
	public function setExceptionMessage($message){
		$this->exception = $message;
	}
	
	/**
	 * @desc 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage(){
		return $this->exception;
	}
	
	public static function getIndexNavTabId() {
		return Menu::model()->getIdByUrl('/aliexpress/aliexpresseditprice/list');
	}
	
	public function rules(){
		return array(
				array('price,variable_price,changeway','required')
		);
	}
	/**
	 * 设置标题中文
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels(){
		return array(
				'id' 					=> Yii::t('system','No.'),
				'sku'					=> Yii::t('aliexpress', 'Sku'),
				'aliexpress_product_id'	=> Yii::t('aliexpress', 'Product ID'),
				'product_price'			=> Yii::t('aliexpress', 'Product Price'),
				'changeway'				=> Yii::t('aliexpress_product', 'Change Way'),
				'opration'              => Yii::t('system', 'Opration'),
				'variable_price'		=> Yii::t('lazada_product', 'Variable Price'),
				'account_id'			=> Yii::t('aliexpress', 'Account Id'),
				'discount'				=> '价格折扣',
		);
	}
	
	/**
	 * 排序、以及数据准备
	 * @see LazadaModel::search()
	 */
	public  function search(){
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'id',
		);
		$criteria = $this->_setCDbCriteria();
		$dataProvider = parent::search(get_class($this), $sort, array(), $criteria);
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	/**
	 * 设置查询条件
	 * filter search options
	 * @return type
	 */
	public function filterOptions() {
		
		if(isset($_REQUEST['sku'])) {
			$_REQUEST['sku'] = $_REQUEST['sku']=='null' ? '' : $_REQUEST['sku'];
		}
		
		$result	= array(
				array(
						'name' => 'sku',
						'type' => 'text',
						'search' => 'LIKE',
						'htmlOption' => array(
								'size' => '22',
						),
// 						'alias' => 'a',
				),
				array(
						'name' => 'account_id',
						'type' => 'dropDownList',
						'data' => CHtml::listData(UebModel::model('AliexpressAccount')->findAll(), 'id', 'short_name'),
						'search' => '=',
				),
				array(
						'name' => 'discount',
						'type' => 'text',
						'search' => '=',
						'htmlOption' => array(
								'size' => '22',
						),
						'rel' 	=> true,
				),
		);
    	
		return $result;
	}
	
	/**
	 * 处理数据
	 * @param unknown $data
	 * @return unknown
	 */
	public function addition($data) {
		$accountList	= AliexpressAccount::getIdNamePairs();
		foreach ($data as $key => $value) {
			$data[$key]->account_id 	= $accountList[$value->account_id];
			$data[$key]->product_price 	= '<font color="red">$' . $value->product_price . '</font>';
			$data[$key]->variable_price = '';
			$data[$key]->opration  		= $this->getOprationList($value->id);
			$data[$key]->opration  		.= CHtml::link('','/aliexpress/aliexpresseditprice/update/id/'.$value->id,array('class' => 'update','rel'=>'orderOption',
					'target'=> 'dialog','width'	=> '450','height'=> '300','style'	=> 'display:none','title' =>Yii::t('aliexpress_product', 'Change Way')));
			
		}
		return $data;
	}
	
	/**
	 * 操作多选项
	 * @param unknown $status
	 * @return string
	 */
	public function getOprationList($id){
		$str = "<select style='width:75px;' onchange = 'check(this)' ><option value=''>".Yii::t('system', 'Please Select')."</option>";
		$str .= "<option value='update'>".Yii::t('aliexpress_product', 'Change Way')."</option>";
		$str .="</select>";
		return $str;
	}
	
	/**
	 * @param unknown $sku
	 * @return mixed
	 */
	public function checkSku($sku) {
		$productObj	= $this->getDbConnection()->createCommand()
							->select($this->columnName())
							->from(self::tableName())
							->where('t.sku = :sku', array(':sku'=>$sku))
							->queryRow();
		return $productObj;
	}
	
	/**
	 * 列表排序
	 * order field options
	 * @return $array
	 */
	public function orderFieldOptions() {
		return array(
				'sku',
		);
	}
	
	/**
	 * 链表查询数据
	 * @param unknown $addCondition
	 * @return CDbCriteria
	 */
	protected function _setCDbCriteria($addCondition=array()){
		$criteria = new CDbCriteria();
		$criteria->addCondition("t.product_status_type = 'onSelling'");
		return $criteria;
	}
	
	/**
	 * @desc更新指定aliexpress账号指定sku的产品的价格
	 * @param integer $accountID
	 * @param string $skuList
	 * @param string $productID
	 * @param string $SkuPrice
	 * @return boolean
	 */
	public function updateProductsPrice($accountID, $productID, $SkuPrice, $skuID = null) {
		try {
			$request = new EditSingleSkuPriceRequest();
			$request->setSkuID($skuID);
			$request->setSkuPrice($SkuPrice);
			$request->setProductID($productID);
			$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			if (!$request->getIfSuccess()) {
				$this->setExceptionMessage($request->getErrorMsg());
				return false;
			}
			return true;
		} catch (Exception $e) {
			$this->setExceptionMessage($e->getMessage());
			return false;
		}
	}
	
	/**
	 * 更新库中产品价格
	 */
	public function updatePrice($id, $price) {
		if (!empty($id)) {
			$this->dbConnection->createCommand()
				 ->update(self::tableName(), array('product_price'=>$price),'id=:id', array(':id'=>$id));
		}
		return true;
	}
	
	
	
	
	
	
}