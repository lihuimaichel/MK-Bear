<?php
class LazadaProductWarehouse extends UebModel{
	
	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_warehouse_sku_map';
	}
	
	/**
	 * @desc 设置连接的数据库名
	 * @return string
	 */
	public function getDbKey() {
		return 'db_oms_warehouse';
	}
	
	/**
	 * @desc 库存状态
	 * @param array $sku
	 * @return 
	 */
	public function getAvailableQtyBySku($sku)
	{
                //   应lazada杨英要求available_qty>0改成available_qty>2
		$result = $this->getDbConnection()->createCommand()->select('sku')
												           ->from('ueb_warehouse_sku_map')
												           ->where('available_qty>2')
												           ->queryColumn();
		return 	$result;
	}
}

?>