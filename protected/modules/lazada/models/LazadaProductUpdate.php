<?php
/**
 * 
 * @author Liutf
 * @date 2015-09-21
 */
class LazadaProductUpdate extends LazadaModel {
	
    const EVENT_NAME = 'product_update';
    
    /** @var string 异常信息*/ 
    protected $_exception = null;
    
    public $detail = array();
    
    public $opration = '';
    
    public $changeway = '';
    
    public $variable_price = '';
    
    const EVENT_NAME_UPDATE_QUANTITY = 'product_update_quantity';
    const EVENT_NAME_RECORD_ZERO_STOCK = 'product_record_zero_stock_sku';
    
    public static function model($className=__CLASS__){
    	return parent::model($className);
    }
    
    public  function tableName(){
    	return 'ueb_lazada_product';
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
    			'id' =>Yii::t('system','No.'),
    			'sku' => Yii::t('lazada_product', 'Sku'),
    			'site_id' => Yii::t('lazada_product', 'Site'),
    			'account_id' => Yii::t('lazada_product', 'Account'),
    			'seller_sku' => Yii::t('lazada_product', 'Seller Sku'),
    			'price' => Yii::t('lazada_product', 'price'),
    			'sale_price' => Yii::t('lazada_product', 'Special Price'),
    			'listing_id' => Yii::t('lazada_product', 'Listing Tab Header Text'),
    			'save_price' => Yii::t('lazada_product', 'Change The Way The Price'),
    			'variable_price' => Yii::t('lazada_product', 'Variable Price'),
    			'changeway' => Yii::t('lazada_product', 'Change Way'),
    			'opration'	=> Yii::t('system', 'Oprator'),
    	);
    }
    
    /**
     * 排序、以及数据准备
     * @see LazadaModel::search()
     */
    public  function search(){
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'  => 'seller_sku',
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
    	$result	= array(
			array(
				'name' => 'sku',
				'type' => 'text',
				'search' => 'LIKE',
				'htmlOption' => array(
					'size' => '22',
				),
			),
			array(
				'name' => 'site_id',
				'type' => 'dropDownList',
				'data' => LazadaSite::getSiteList(),
				'search' => '=',
				'htmlOptions' => array('onchange' => 'getAccountList(this)'),
				'alias' => 't',
			),
			array(
				'name' => 'account_id',
				'type' => 'dropDownList',
				'data' => LazadaAccount::model()->getAccountList(Yii::app()->request->getParam('site_id')),
				'search' => '=',
			),				
		);
//     	$this->addFilterOptions($result);
    	return $result;
    }
    
    /**
     * 处理数据
     * @param unknown $data
     * @return unknown
     */
    public function addition($datas) {
		foreach ($datas as $key => $data) {
			//查找每个SKU刊登的所有listing记录
			$listings =  UebModel::model('LazadaProduct')->getSkuListingBySearchCondition($data->sku);
			$datas[$key]->detail = array();
			if (!empty($listings)) {
				foreach ($listings as $k => $listing) {
					$currencySymbol = LazadaSite::getSiteCurrencyList($listing['site_id']) . ' ';
					$price = '<font color="red">' . $currencySymbol . $listing['price'] . '</font>';
					$sku = $listing['seller_sku'].'';
					$datas[$key]->seller_sku = $sku;
					$datas[$key]->price		 = $price;
					$datas[$key]->variable_price = $price;
					$datas[$key]->changeway  = $this->getOprationList($listing['id']);
				}
			}
		}
		return $datas;
    }
    
    /**
     * 操作多选项
     * @param unknown $status
     * @return string
     */
    public function getOprationList($id){
    	$str = "<select style='width:75px;' onchange = 'saveChangePrice(this.value,".$id.")' ><option value=''>".Yii::t('system', 'Please Select')."</option>";
    	$str .= "<option value='common'>".Yii::t('lazada_product', 'Common')."</option>";
    	$str .= "<option value='calculate'>".Yii::t('lazada_product', 'Calculate')."</option>";
    	$str .="</select>";
    	return $str;
    }
    
    /**
     * 获取计算方式
     */
    public function getPriceWay() {
    	return array('+'=>'+','-'=>'-','*'=>'x');
    }
    
    /**
     * 根据传值计算出要更改价格
     * @param float $changePrice
     * @param string $changeWay
     * @param folat $price
     * @return float
     */
    public static function getPrice($changePrice, $changeWay, $price) {
    	switch ($changeWay) {
    		case '+':
    			$price	= $price + $changePrice;
    			break;
    		case '-':
    			$price	= $price - $changePrice;;
    			break;
    		case '*':
    			$price	= $price * $changePrice;
    			break;
    		default:
    			break;
    	}
    	return $price;
    }
    
    /**
     * 列表排序
     * @return multitype:string
     */
    public function orderFieldOptions() {
    	return array();
    }
    
    /**
     * 设置查询条件的预设
     * @return boolean
     */
//     public function addFilterOptions(&$result){
//     	$common	= strtotime($_REQUEST['modify_time'][1]) - strtotime($_REQUEST['modify_time'][0]);
//     	$year	= floor($common/86400/360);    //整数年
//     	$month	= floor($common/86400/30) - $year*12; //整数月
//     	if ($month>5) {
//     		$_REQUEST['search']['modify_time'] = array();
//     		return false;
//     	}
//     	return array();
//     }
    
    /**
     * 链表查询数据
     * @param unknown $addCondition
     * @return CDbCriteria
     */
    protected function _setCDbCriteria($addCondition=array()){
    	$criteria=new CDbCriteria();
    	$criteria->select = "t.*";   	
    	$criteria->group = "t.sku";
    	return $criteria;
    }
    
    /**
     * @desc更新指定lazada账号指定seller sku的产品的状态
     * @param integer $accountID
     * @param array $skuList
     * @return boolean
     */
    public function updateAccountProducts($siteID, $accountID, $sku, $status) {
    	try {
    		$request = new ProductUpdateRequest();
    		$request->setSellerSku($sku);
    		$request->setStatus($status);
    		$response = $request->setSiteID($siteID)->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
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
     * @desc更新指定lazada账号指定seller sku的产品的价格
     * @param integer $accountID
     * @param array $skuList
     * @return boolean
     */
    public function updateProductsPrice($siteID, $accountID, $sku, $price) {
    	try {
    		$request = new ProductUpdateRequest();
    		$request->setSellerSku($sku);
    		$request->setPrice($price);
    		$response = $request->setSiteID($siteID)->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
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
     * 更新产品线上价格
     */
    public function updatePrice($id, $price) {
    	return $this->dbConnection->createCommand()->update(self::tableName(), array('price'=>$price),'id=:id', array(':id'=>$id));
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message) {
    	$this->_exception = $message;
    }
    
    /**
     * @desc 获取异常信息
     */
    public function getExceptionMessage() {
    	return $this->_exception;
    }
    
    /**
     * @desc 获取菜单对应ID
     * @return integer
     */
    public static function getIndexNavTabId() {
    	return UebModel::model('Menu')->getIdByUrl('/lazada/lazadaproductupdate/list');
    }


    /**
     * @desc更新指定lazada账号指定seller sku的产品的库存和价格
     * @param integer $autoAccountID
     * @param array $paramsArr
     * @return boolean
     */
    public function updatePriceQuantity($autoAccountID, $paramsArr) {
        try {
            $request = new UpdatePriceQuantityRequestNew;
            $request->setSkus($paramsArr);
            $request->push();
            $response = $request->setApiAccount($autoAccountID)->setRequest()->sendRequest()->getResponse();
            if (!$request->getIfSuccess()) {
                $messages = $request->getErrorMsg();
                if(isset($response->Body->Errors->ErrorDetail->Message)){
                    $messages = $response->Body->Errors->ErrorDetail->Message;
                }
                $this->setExceptionMessage($messages);
                return false;
            }
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }


    /**
     * 排除不执行的账号ID
     */
    public static function excludeAccount(){
        return array('35');
    }
}