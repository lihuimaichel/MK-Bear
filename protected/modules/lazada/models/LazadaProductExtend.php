<?php
/**
 * @desc lazada product extend model
 * @author yangsh
 * @since  2016-06-23
 *
 */
class LazadaProductExtend extends LazadaModel {

	/** @var 主表id */
	protected $_productId  = null;

	/** @var string 异常信息*/
	protected $_exception = null;

	public static function model($className = __CLASS__) {
	    return parent::model($className);
	}
	
	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_lazada_product_extend';
	}

	/**
	 * @desc 设置主表id
	 * @param int $productId
	 */
	public function setProductId($productId) {
		$this->_productId = $productId;
		return $this;
	}

	/**
	 * @desc 获取主表id
	 * @return int
	 */
	public function getProductId() {
		return $this->_productId;
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
	 * @return string
	 */
	public function getExceptionMessage() {
		return $this->_exception;
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
	 * @desc 保存product extend信息
	 * @param  object $product
	 * @return boolean
	 * @author yangsh
	 * @since  2016-06-23
	 */
	public function saveProductExtendInfo($product) {
		try {
			$images = json_decode(json_encode($product->Images->Image),true);
			if (!isset($images[0])) {
				$images = array($images);
			}
			$description = trim($product->Description); 
			$productData = json_decode(json_encode($product->ProductData),true);
			$extendData = array(
				'product_id' 	=> $this->_productId,
				'images' 		=> json_encode($images),
				'description' 	=> $description,
				'product_data' 	=> json_encode($productData),
			);
			$info = $this->getOneByCondition('id', "product_id=".$this->_productId );
			if (!empty($info)) {
				$this->dbConnection->createCommand()->update(self::tableName(), $extendData, "id = ".$info['id'] );
			} else {
				$this->dbConnection->createCommand()->insert(self::tableName(), $extendData);
			}
			return true;
		} catch (Exception $e) {
			$this->setExceptionMessage($e->getMessage());
			return false;
		}
	}

	
	/**
	 * @desc 新接口保存product extend信息
	 * @param  object $product
	 * @return boolean
	 * @author hanxy
	 * @since  2016-12-29
	 */
	public function saveProductExtendInfoNew($product) {
		try {
			$imagesJson = '';
			$images = (array)$product->Skus->Sku->Images;
			if (isset($images['Image'])) {
				$images = array_filter($images['Image']);
				if(!empty($images)){
					$imagesJson = json_encode($images);
				}
			}
			$productData = '';
			$description = trim($product->Attributes->description);
			$replaceArr  = array('name'=>'','description'=>'','Images'=>'','quantity'=>'','Url'=>'','price'=>'','ShopSku'=>'','Available'=>'','special_price'=>'');
			$productData = array_replace((array)$product->Attributes,$replaceArr);
			$productData = array_filter($productData);
			$productSkus = array_replace((array)$product->Skus->Sku,$replaceArr);
			$productSkus = array_filter($productSkus);
			$productDataArr = array_merge_recursive($productData,$productSkus);
			$extendData = array(
				'product_id' 	=> $this->_productId,
				'images' 		=> $imagesJson,
				'description' 	=> $description,
				'product_data' 	=> json_encode($productDataArr)
			);
			$info = $this->getOneByCondition('id', "product_id=".$this->_productId );
			if (!empty($info)) {
				$this->dbConnection->createCommand()->update(self::tableName(), $extendData, "id = ".$info['id'] );
			} else {
				$this->dbConnection->createCommand()->insert(self::tableName(), $extendData);
			}
			return true;
		} catch (Exception $e) {
			$this->setExceptionMessage($e->getMessage());
			return false;
		}
	}
}