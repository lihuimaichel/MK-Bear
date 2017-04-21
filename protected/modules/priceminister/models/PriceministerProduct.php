<?php
/**
 * @desc pm产品
 * @since 2016-03-24
 */
class PriceministerProduct extends PriceministerModel{

	const EVENT_NAME = 'get_product';

	public $detail = null;
	public $account_name = null;
	public $_accountID = null;

	/**@var 消息提示*/
	private $_errorMessage = null;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_pm_product';
    }

    
    // ======================== Start:Search =============================
    public function search(){
    	$csort = new CSort();
    	$csort->attributes = array(
    		'defaultOrder'=>'id'
    	);
    	$cdbCriteria = $this->setCdbCriteria();
    	$dataProvider = parent::search($this, $csort, '', $cdbCriteria);
    	$data = $this->additions($dataProvider->getData());
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
    /**
     * @desc  处理额外数据
     * @param unknown $datas
     * @return unknown
     */
    public function additions($datas){
    	if(empty($datas)) return $datas;
		$accountList = PriceministerAccount::getIdNamePairs();
    	foreach($datas as &$data){
			$data['account_name'] = isset($accountList[$data['account_id']]) ? $accountList[$data['account_id']] : '-';
			$variants = $this->getPmProductVariantList($data['id']);
			$data->detail = array();
			if(!empty($variants)){
				foreach ($variants as $variant){
					$variantData = array(
						'variant_id'=>$variant['id'],
						'son_sku'=>$variant['sku'],
						'inventory' => CHtml::link($variant['quantity_available'],"/priceminister/priceministerproduct/updateStock/variationID/".$variant['id'],array("title"=>$variant['sku'],"style"=>"color:blue","target"=>"dialog","width"=>400,"mask"=>true,"height"=>240)),
						'sale_price' => CHtml::link($variant['sale_price'],"/priceminister/priceministerproduct/updateprice/variationID/".$variant['id'],array("title"=>$variant['sku'],"style"=>"color:blue","target"=>"dialog","width"=>400,"mask"=>true,"height"=>240)),
						//'sale_price'=>$variant['sale_price'],
						'advert_id'=>$variant['advert_id'],
					);
					$data->detail[] = $variantData;
				}
			}

		}

    	return $datas;
    }
    
    public function setCdbCriteria(){
    	$cdbCriteria = new CDbCriteria();
    	$cdbCriteria->select = "*";
    	return $cdbCriteria;
    }

    public function filterOptions(){

    	return array(
			array(
				'name'		=>	'sku',
				'type'		=>	'text',
				'search'	=>	'=',
			),
			array(
				'name'		=>	'product_id',
				'type'		=>	'text',
				'search'	=>	'=',
			),
			array(
				'name'		=>	'account_id',
				'type'		=>	'dropDownList',
				'data'		=>	PriceministerAccount::getIdNamePairs(),
				'search'	=>	'='
			),
			array(
				'name' 			=> 'create_time',
				'type' 			=> 'text',
				'search' 		=> 'RANGE',
				'alias'			=>	't',
				'htmlOptions'	=> array(
						'size' => 4,
						'class'=>'date',
						'style'=>'width:80px;'
				),
			),
    	);
    }

    public function attributeLabels(){
    	return array(
			'sku'				=>	'SKU',
			'product_id'		=>	'product ID',
			'advert_id'			=>	'advert ID',
			'account_name'		=>	Yii::t('priceminister', 'Account Name'),
			'account_id'		=>	Yii::t('priceminister', 'Account Name'),
			'title'				=>	Yii::t('priceminister', 'Title'),
			'create_time'		=>	Yii::t('priceminister', 'Create Time'),
			'alias'				=>	Yii::t('priceminister', 'Alias'),
			'caption'			=>	Yii::t('priceminister', 'Caption'),
			'shipping_price'	=>	Yii::t('priceminister', 'Shipping Price'),
			'son_sku'			=>	Yii::t('priceminister', 'Son Sku'),
			'sale_price'		=>	Yii::t('priceminister', 'Sale Price'),
			'inventory'			=>	Yii::t('priceminister', 'Inventory'),
			'product_type'		=>	Yii::t('priceminister', 'Product Type'),

    	);
    }

	public function getPmProductVariantList($listingId){
		if(empty($listingId)) return array();
		$conditions = "listing_id={$listingId}";
		return PriceministerProductVariation::model()->findAll($conditions);
	}

    // ======================== End:Search ===============================

	/**
	 * @desc 页面的跳转链接地址
	 */
	public static function getIndexNavTabId() {
		return Menu::model()->getIdByUrl('/priceminister/priceministerproduct/list');
	}

	// ============ S:设置错误消息提示 =================
	public function getErrorMessage(){
		return $this->_errorMessage;
	}

	public function setErrorMessage($errorMsg){
		$this->_errorMessage = $errorMsg;
	}
	// ============ E:设置错误消息提示 =================

	private function throwE($message,$code=null){
		throw new Exception($message,$code);
	}

	/**
	 * @desc 设置账号ID
	 */
	public function setAccountID($accountID){
		$this->_accountID = $accountID;
		return $this;
	}
	/**
	 * @desc 获取账号ID
	 */
	public function getAccountID(){
		return $this->_accountID;
	}

	/**
	 * [getOneByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where  [description]
	 * @param  mixed $order  [description]
	 * @return array
	 * @author yangsh
	 */
	public function getOneByCondition($fields='*', $where='1',$order='') {
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where);
		$order != '' && $cmd->order($order);
		$cmd->limit(1);
		return $cmd->queryRow();
	}

	/**
	 * [getListByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where  [description]
	 * @param  mixed $order  [description]
	 * @return array
	 * @author yangsh
	 */
	public function getListByCondition($fields='*', $where='1',$order='') {
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where);
		$order != '' && $cmd->order($order);
		return $cmd->queryAll();
	}
	/**
	 * 下载listing
	 */
	public function getPmListing() {
		$accountID  = $this->_accountID;

		$errMsg = '';
		$nexttoken = false;
		do{
			$exportRequest = new ExportRequest();

			if($nexttoken != false){ //判断第一次
				$exportRequest->setNexttoken($nexttoken);
			}

			$response = $exportRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			//判断循环条件，接口根据有nexttoken返回调用下一页
			$nexttoken = isset($response->response->nexttoken) && !empty($response->response->nexttoken) ? trim(strval($response->response->nexttoken)) : false;
			//$nexttoken = false;
			//echo "<pre>";print_r($response);echo "<hr>";die;

			//返回的每页总数
			//$total = $response->response->nbresults;

			//具体数据
			$data = isset($response->response->advertlist->advert) ? $response->response->advertlist->advert : '';
			if($data == ''){
				break;
			}

			$isOk = $this->updateListingInfo($data);
			if (!$isOk) {
				$errMsg .= $accountID.'##########'.$this->getErrorMessage()."\r\n";
			}

		}while($nexttoken);

		$this->setErrorMessage($errMsg);
		return $errMsg =='' ? true : false;
	}

	/***
	 * @desc 更新产品listing信息 -- 保存主表信息
	 */
	public function updateListingInfo($datas){
		$errMsgs = '';
		$accountID  = $this->_accountID;
		foreach ($datas as $productData) {
			$dbTransaction = $this->getDbConnection()->beginTransaction();
			try {
				//$productData = $data->advert;
				$params = array();
				$params['product_id'] = strval($productData->productsummary->productid);
				$params['account_id'] = $accountID;
				$params['sku'] = strval($productData->sku);
				$params['title'] = strval($productData->productsummary->headline);
				$params['alias'] = strval($productData->productsummary->alias);
				$params['view_item_url'] = 'http://www.priceminister.com/'.strval($productData->productsummary->url);
				$params['product_type'] = strval($productData->productsummary->topic);
				$params['barcode'] = isset($productData->productsummary->barcode)?strval($productData->productsummary->barcode):0;
				$params['comment'] = isset($productData->comment)?strval($productData->comment):'';
				$params['quantity_available'] = $productData->stock;
				$params['current_price'] = $productData->price->amount;
				$params['current_price_currency'] = $productData->price->currency;
				$params['shipping_price'] = $productData->shippingcost->amount;
				$params['shipping_price_currency'] = $productData->shippingcost->currency;

				$params['caption'] = strval($productData->productsummary->caption);
				$params['complement'] = strval($productData->productsummary->complement);
				$params['shipping_type'] = $productData->shippingtype;
				$params['quality'] = $productData->quality;
				$params['advert_type'] = $productData->adverttype;
				$params['is_rsl'] = $productData->isrsl=='Y'? 1 :0;
				$params['is_negotiable'] = $productData->isnegotiable ? 1 : 0;
				$params['is_original'] = $productData->isoriginal ? 1 :0;

				//主表处理
				$checkExists = $this->getDbConnection()->createCommand()
						->from(self::model()->tableName())
						->select('id')
						->where("product_id = :id", array(':id' => $params['product_id']))
						->queryRow();
				if($checkExists){
					$listingID = $checkExists['id'];
					$flag = $this->getDbConnection()->createCommand()->update(self::model()->tableName(), $params, "id=:id AND update_time=:update_time", array(':id'=>$listingID,':update_time'=>date("Y-m-d H:i:s",time())));
					if (!$flag) {
						throw new Exception(Yii::t('wish', 'Update Product Info Failure'));
					}
				}else{
					$params['create_time'] = date("Y-m-d H:i:s",time());
					$flag = $this->getDbConnection()->createCommand()->insert(self::model()->tableName(), $params);
					if (!$flag) {
						throw new Exception(Yii::t('wish', 'Save Product Info Failure'));
					}
					$listingID = $this->getDbConnection()->getLastInsertID();
				}
				unset($params);

				$variantParams = array();
				$variantParams['listing_id'] = $listingID;
				$variantParams['account_id'] = $accountID;
				$variantParams['main_sku'] = strval($productData->sku);
				$variantParams['product_id'] = strval($productData->productsummary->productid);
				$variantParams['sku'] = strval($productData->sku);
				$variantParams['quantity_available'] = $productData->stock;
				$variantParams['sale_price'] = $productData->price->amount;
				$variantParams['currency'] = $productData->price->currency;
				$variantParams['advert_id'] = $productData->advertid;
				//子sku表处理
				$existsVariant = $this->getDbConnection()->createCommand()
						->from('ueb_pm_product_variation')
						->select('id')
						->where('advert_id=:advert_id AND listing_id=:listing_id',array(':advert_id'=>$variantParams['advert_id'], ':listing_id'=>$listingID))
						->queryRow();
				if($existsVariant){
					$flag = $this->getDbConnection()->createCommand()->update(
						'ueb_pm_product_variation',
						$variantParams,
						'advert_id=:advert_id AND listing_id=:listing_id',array(':advert_id'=>$variantParams['advert_id'], ':listing_id'=>$listingID));
					if (!$flag) {
						throw new Exception(Yii::t('wish', 'Update Product Variation Failure'));
					}
				}else{
					$flag = $this->getDbConnection()->createCommand()->insert('ueb_pm_product_variation', $variantParams);
					if (!$flag) {
						throw new Exception(Yii::t('wish', 'Save Product Variation Failure'));
					}
				}
				/*//删除已经下架的子sku
				if(isset($checkExists) && $checkExists){
					$this->getDbConnection()->createCommand()->delete('ueb_pm_product_variation', 'advert_id not in ('. MHelper::simplode($variantParams['advert_id']) .') AND listing_id=:listing_id',array(':listing_id'=>$listingID));
				}*/
				unset($variantParams);

				$dbTransaction->commit();
			} catch (Exception $e) {
				$errMsgs .= $e->getMessage();
				$dbTransaction->rollback();
			}
		}

		$this->setErrorMessage($errMsgs);
		return $errMsgs == '' ? true : false;
	}

	/*
	 * $listArr 二维数组
	 * 更新listing价格，库存
	 */
	public function updatePMListing($listArr){

		//组装数据
		$xmlGeneration = new XmlGenerator();

		foreach($listArr as $key=>$infoArr){
			if ( !isset($infoArr['listing_id']) || !isset($infoArr['sku']) || (!isset($infoArr['quantity']) && !isset($infoArr['price'])) ) {
				continue;
			}
			$listingInfo = $this->getOneByCondition('alias','id='.$infoArr['listing_id']);
			if(!$listingInfo){
				continue;
			}

			$advertData = array();
			if(isset($infoArr['sku'])){
				$advertData[] = array('key'=>'sellerReference','value'=>$infoArr['sku']);
			}
			if(isset($infoArr['quantity'])){
				$advertData[] = array('key'=>'qty','value'=>$infoArr['quantity']);
			}
			if(isset($infoArr['price'])){
				$advertData[] = array('key'=>'sellingPrice','value'=>$infoArr['price']);
			}
			$xmlGeneration->xml = "";
			$advertXml = $xmlGeneration->buildXMLFilterMulti($advertData, 'attribute', '')->getXml();

			$item = array(
				'alias' => $listingInfo['alias'],	//类型
				'attributes' => array(
					'advert' => $advertXml,			//listing
				),
			);
			$xmlGeneration->xml = "";
			$itemXml = $xmlGeneration->buildXMLFilterMulti($item)->getXml();
			$data['items']['item'][$key] = $itemXml;
		}
		$dataXml = $xmlGeneration->XmlWriter()->buildXMLFilterMulti($data)->pop()->getXml();

		//生成文件
		$path = './uploads/pm/';
		if(!file_exists($path) ){
			mkdir($path, 0777, true);
		}
		$file_name = date('YmdHis').'_updatelisting.xml';
		$filePath = $path.$file_name;
		file_put_contents($filePath,$dataXml);

		//调用接口
		$request = new GenericImportFileRequest();
		//本地测试
		$filePath="D:/wamp/www/codes".ltrim($filePath,'.');
		$request->setXmlFile($filePath);
		$response = $request->setRequest()->sendRequest()->getResponse();
		//正式环境
		/*$request->setXmlFile($filePath);
        $response = $request->setAccount($infoArr['account_id'])->setRequest()->sendRequest()->getResponse();*/

		$importID = 0;
		if(isset($response->response->status) && $response->response->status=='OK'){
			$importID = trim($response->response->importid);
		}else{
			$error = isset($response->error->details->detail) ? strval($response->error->details->detail): $request->getErrorMsg();
			$this->setErrorMessage($error);
		}
		@unlink($filePath);
		return $importID;
	}
}