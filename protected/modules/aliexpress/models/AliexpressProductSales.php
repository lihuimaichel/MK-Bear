<?php
/**
 * @desc aliexpress Listing销售表
 * @author Liz
 *
 */
class AliexpressProductSales extends AliexpressModel {
	
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
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_product_sales';
	}
	
	/**
	 * @desc 根据产品ID和SKU获取信息
	 * @param unknown $productID
	 */
	public function getInfoByProductIDAndSku($productID,$sku) {
		return $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->where("aliexpress_product_id = '{$productID}' AND sku = '{$sku}'")
			->queryRow();
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
			->from($this->tableName())
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
			->from($this->tableName())
			->where($where);
		$order != '' && $cmd->order($order);
		return $cmd->queryAll();
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

}