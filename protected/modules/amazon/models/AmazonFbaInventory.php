<?php
/**
 * Amazon Inventory Model
 * @author 	Rex
 * @since 	2016-07-05
 */

class AmazonFbaInventory extends AmazonModel {

	const EVENT_NAME = 'get_fba_inventory';

	public $_sellerSkus = null;		#sku列表
	public $_queryStartDateTime = null;	#指定查询开始日期

	public $_accountID = null;		#账号
	public $_accountSite = null;	#站点

	public $_logID = 0;		#日志编号

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * 设置数据库
	 */
	public function getDbKey() {
		return 'db_oms_amazon';
	}

	/**
	 * 设置数据库表名
	 */
	public function tableName() {
		return 'ueb_amazon_fba_inventory';
	}

	/**
	 * 设置sku列表
	 */
	public function setSellerSkus($sellerSkus) {
		$this->_sellerSkus = $sellerSkus;
	}

	/**
	 * 设置查询开始日期
	 */
	public function setQueryStartDateTime($startDateTime) {
		$this->_queryStartDateTime = $startDateTime;
	}

	/**
	 * 设置日志编号
	 * @param 	string 	$logID
	 */
	public function setLogID($logID) {
		$this->_logID = $logID;
	}

	/**
	 * 设置账号ID
	 * @param 	string 	$accountID
	 */
	public function setAccountID($accountID) {
		$accountInfo = AmazonAccount::model()->findByPk($accountID);
		if ($accountInfo) {
			$this->_accountSite = strtoupper($accountInfo->country_code);
		}
		$this->_accountID = $accountID;
	}

	/**
	 * 得到 Fba inventory
	 */
	public function getFbaInventoryList() {
		$request = new ListInventorySupply();
		$request->setCaller(self::EVENT_NAME);
		$request->setQueryStartDateTime($this->_queryStartDateTime);
		$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
		//var_dump($response);

		if (!empty($response)) {
			$this->saveFbaInventory($response);
		} else {
			echo 'Eoror!';
		}

	}

	/**
	 * 保存库存数据入中间库
	 */
	private function saveFbaInventory($datas) {
		$encryptSku = new encryptSku();
		foreach ($datas as $key => $data) {
			$date = date('Y-m-d H:i:s');
			$insertData = array(
				'account_id'		=> $this->_accountID,
				'account_name'		=> '',
				'site'				=> '',
				'sku'				=> $encryptSku->getAmazonRealSku2($data['SellerSKU']),
				'seller_sku'		=> $data['SellerSKU'],
				'FN_SKU'			=> $data['FNSKU'],
				'ASIN'				=> $data['ASIN'],
				'condition'			=> $data['Condition'],
				'total_supply_quantity'	=> $data['TotalSupplyQuantity'],
				'in_stock_supply_quantity'	=> $data['InStockSupplyQuantity'],
				'earliest_availability'	=> $data['EarliestAvailability']['TimepointType'],
				'supply_detail'		=> !empty($data['SupplyDetail']) ? json_encode($data['SupplyDetail']) : '',
				'create_time'		=> $date,
				'update_time'		=> $date,
			);
			$row = $this->find("account_id = '{$this->_accountID}' and seller_sku='{$data['SellerSKU']}' and is_check=0 ");
			if ($row) {
				unset($insertData['seller_sku']);
				unset($insertData['create_time']);
				$ret = $this->updateByPk($row->id, $insertData);
			} else {
				$ret = $this->saveData($insertData);
			}
		}
		return true;
	}

	public function saveData($data) {
		$model = new self();
		foreach ($data as $key => $value) {
			$model->setAttribute($key, $value);
		}
		return $model->save();
	}

}

?>