<?php
/**
 * @desc aliexpress 产品SKUs MODEL
 * @author zhangF
 *
 */
class AliexpressProductVariation extends AliexpressModel {
	
	/** @var string 错误信息 */
	protected $_errorMessage = '';

	/**
	 * @desc 生成model
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * @desc 设置错误信息
	 * @param sting $message
	 */
	public function setErrorMessage($message) {
		$this->_errorMessage .= $message;
	}
	
	/**
	 * @desc 获取错误信息
	 * @return string
	 */
	public function getErrorMessage() {
		return $this->_errorMessage;
	}		
	
	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_product_variation';
	}
	
	/**
	 * 获取所有列
	 */
	public function columnName() {
    	return MHelper::getColumnsArrByTableName(self::tableName());
    }
	
    /**
     * @return array relational rules.
     */
    public function relations(){
    	return array(
    			'order'   => array(self::BELONGS_TO, 'AliexpressEditPrice','product_id')
    	);
    }
    
    /**
     * 设置标题中文
     * @see CModel::attributeLabels()
     */
    public function attributeLabels(){
    	return array(
    			'id'                 		=>Yii::t('system','No.'),
    			'sku'                		=>Yii::t('aliexpress_product','SKU'),
    			'sku_price'                	=>Yii::t('aliexpress_product','Sku Price'),
    	);
    }
    
	/**
	 * 排序、以及数据准备
	 * @see UebModel::search()
	 */
	public  function search(){
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'id',
		);
		$criteria = $this->_setCDbCriteria();
		$dataProvider = parent::search(get_class($this), $sort, array(), $criteria);
// 		$data = $this->addition($dataProvider->data);
// 		$dataProvider->setData($data);
		return $dataProvider;
	}
    
	/**
	 * 设置查询条件
	 * filter search options
	 * @return type
	 */
	public function filterOptions() {
		$result = array();
		$this->addFilterOptions($result);
		return $result;
	}
	
	/**
	 * 处理数据
	 * @param unknown $data
	 * @return unknown
	 */
// 	public function addition($data) {
// 		return $data;
// 	}
	
	/**
	 * 设置查询条件的预设
	 * @return boolean
	 */
	public function addFilterOptions(&$result){
		return array();
	}
	
	/**
	 * 链表查询数据
	 * @param unknown $addCondition
	 * @return CDbCriteria
	 */
	protected function _setCDbCriteria($addCondition=array()){
		$criteria=new CDbCriteria();
// 		$criteria->select='t.order_id,sum(t.quantity) refund_num,t.platform_code,t.sku,t.modify_time,a.sale_num, if (a.sale_num, sum(t.quantity) / a.sale_num, 0) as refund_rate';
// 		$criteria->join='LEFT JOIN ueb_product.ueb_product_sales a ON t.sku = a.sku AND t.platform_code = a.platform_code';
// 		$criteria->addCondition('t.modify_time >= "'.date("Y-m-d H:i:s",strtotime("-3 month")).'" AND t.modify_time <= "'.date("Y-m-d H:i:s").'"');
// 		$criteria->addCondition('a.day_type=30');
// 		$criteria->group ='t.sku,t.platform_code';
		return $criteria;
	}
	
	public function getByProductId($id) {
		if (empty($id)) return false;
		return $this->dbConnection->createCommand()
						->select('id, sku, sku_price, sku_id, sku_property')
						->from(self::tableName())
						->where("product_id =:id", array(":id" => $id))
						->queryAll();
	}
	
	/**
	 * 更新库中产品价格
	 */
	public function updatePrice($id, $price) {
		if (!empty($id)) {
			$this->dbConnection->createCommand()
			->update(self::tableName(), array('sku_price'=>$price),'id=:id', array(':id'=>$id));
		}
		return true;
	}
	
	/**
	 * @desc 更新子sku数据
	 * @param unknown $id
	 * @param unknown $data
	 */
	public function updateVariationById($id, $data){
		return $this->dbConnection->createCommand()
							->update(self::tableName(), $data,'id=:id', array(':id'=>$id));
	}
	
	/**
	 * @desc 根据aliexpress product id 删除记录
	 * @param int $id
	 */
	public function deleteByProductID($id) {
		return $this->deleteAll("product_id = :id", array(':id' => $id));
	}
	
	/**
	 * @desc 根据 productId 和Sku 查出线上库存
	 * @param int $id
	 */
	public function getIpmStockBySkuAndPorductId($sku,$productId) {
        $data =  $this->dbConnection->createCommand()
	                  ->select('ipm_sku_stock')
	                  ->from(self::tableName())
	                  ->where('product_id = "'.$productId.'"')
	                  ->andWhere('sku = "'.$sku.'"')
	                  ->queryRow();
        return $data['ipm_sku_stock'];
	}
	
	/**
	 * @desc 通过productID 获取SKU
	 * @param int $productID
	 * @return array
	 */
	public function getSkuByproductID($productID){
		$data = $this->getDbConnection()->createCommand()
		->select('sku')
		->from($this->tableName())
		->where('product_id = "'.$productID.'"')
		->qureyRow();
		return $data['sku'];
	}

    /**
     * @desc filterByCondition
     * @param  string $fields 
     * @param  [type] $where 
     * @return [type]  
     */
    public function filterByCondition($fields="*",$where) {
        $res = $this->dbConnection->createCommand()
                    ->select($fields)
                    ->from($this->tableName().' as v')
                    ->leftJoin(AliexpressProduct::tableName().' as p', "v.product_id=p.id")
                    ->where($where)
                    ->queryAll();
        return $res;
    }

	/**
	 * [getOneByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where  [description]
	 * @param  mixed $order  [description]
	 * @return [type]         [description]
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
	 * @return [type]         [description]
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
	 * [saveAliProductVariation description]
	 * @param  [type] $productId   主表id
	 * @param  [type] $productInfo [description]
	 * @return [type]              [description]
	 */
	public function saveAliProductVariation($productId, $productInfo) {
		try {
			$aliexpressProduct  = new AliexpressProduct();
			$this->deleteByProductID($productId);//删除产品对应的多属性表
			$aliexpresProductID = $productInfo->productId;
			$aeopAeProductSKUs  = $productInfo->aeopAeProductSKUs;
			if (empty($aeopAeProductSKUs)) {
				return true;
			}
			$encryptSku 	= new encryptSku();
			$productStock 	= 0;
			foreach ($aeopAeProductSKUs as $aeopAeProductSKU) {
				$productVariation 	= array();
				$aliexpressSku 		= $aeopAeProductSKU->skuCode;
				$sku 				= $encryptSku->getAliRealSku($aliexpressSku);
				if (empty($sku)) {
					$sku 			= $aliexpressSku;
				}
				$productStock 		+= $aeopAeProductSKU->skuStock;
				$productVariation 	= array(
						'product_id'    			=> $productId,
						'aliexpress_product_id'		=> $aliexpresProductID,
						'ipm_sku_stock' 			=> $aeopAeProductSKU->ipmSkuStock,
						'sku_code'                  => $aliexpressSku,
						'sku'                       => $sku,
						'sku_price'                 => $aeopAeProductSKU->skuPrice,
						'sku_stock'                 => intval($aeopAeProductSKU->skuStock),
						'sku_id'                    => $aeopAeProductSKU->id =='<none>' ? '' : $aeopAeProductSKU->id,
						'sku_property'              => json_encode($aeopAeProductSKU->aeopSKUProperty),
						'profit_rate'               => round(floatval($aliexpressProduct->getAliexpressSkuProfitRate($sku, $aeopAeProductSKU->skuPrice)), 2),
				);
				$this->dbConnection->createCommand()->insert(self::tableName(), $productVariation);
			}
			unset($productVariation);
			return true;
		} catch (Exception $e) {
			$this->setErrorMessage($e->getMessage());
			return false;
		}
	}
	
}