<?php

class JdProductPriceRecord extends JdModel {

	private static $_accountList = array();

	public function tableName(){
		
		return 'ueb_jd_price_record';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	/**
	 * @desc 查询价格调整记录表(单条)
	 * @author qzz
	 */
	public function getRevisePriceLogRow($condition, $param = null){
		return $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->select("*")
			->where($condition, $param)
			->order('id desc')
			->queryRow();
	}

	/**
	 * @desc 折后五折价添加到记录表
	 * @author qzz
	 */
	public function addRecord($data){
		$res = $this->getDbConnection()->createCommand()->insert('ueb_jd_price_record', $data);
		if($res){
			return $this->getDbConnection()->getLastInsertID();
		}
		return false;
	}

	/**
	 * @desc 查询价格调整记录表(列表)
	 * @author qzz
	 */
	public function getRestorePriceList($limit, $offset = 0){
		return $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->select("id,account_id,sku,online_sku,ware_id,sku_id,old_price")
			->where("restore_status=:restore_status and status=:status", array(':restore_status'=>0, ':status'=>1))
			->limit($limit, $offset)
			->queryAll();
	}

	/**
	 * @desc 恢复价格到记录表
	 * @author qzz
	 */
	public function updateRecordByID($id, $data){
		return $this->getDbConnection()->createCommand()->update(self::tableName(), $data, "id='{$id}'");
	}

	public function attributeLabels() {
		return array(
			'account_id'					=>	'账号',
			'ware_id'						=>	'商品id',
			'sku'							=>	'SKU',
			'online_sku'					=>	'线上SKU',
			'old_price'						=>	'原始价格',
			'change_price'					=>	'更改价格',
			'restore_price'					=>	'恢复价格',
			'status'						=>	'处理状态',
			'last_message'					=>	'提示',
			'sku_id'						=>	'sku_id',
			'restore_status'				=>	'是否恢复',
			'create_time'					=>	'创建时间',
			'update_time'					=>	'更新时间',
		);
	}

	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder'=>'update_time'
		);
		$dataProvider = parent::search(get_class($this), $sort);
		$data = $this->addtions($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}

	public function filterOptions() {
		$status = Yii::app()->request->getParam('status');
		$restoreStatus = Yii::app()->request->getParam('restore_status');
		$result = array(
			array(
				'name'=>'ware_id',
				'type'=>'text',
				'search'=>'LIKE',
				'htmlOption' => array(
					'size' => '22',
				)
			),

			array(
				'name'=>'sku',
				'type'=>'text',
				'search'=>'LIKE',
				'htmlOption' => array(
					'size' => '22',
				)
			),
			array(
				'name'=>'online_sku',
				'type'=>'text',
				'search'=>'LIKE',
				'htmlOption' => array(
					'size' => '22',
				)
			),

			array(
				'name'=>'account_id',
				'type'=>'dropDownList',
				'search'=>'=',
				'data'=>$this->getAccountList()
			),
			array(
				'name'=>'status',
				'type'=>'dropDownList',
				'search'=>'=',
				'data'=>$this->getStatusOptions(),
				'value'=>$status
			),

			array(
				'name'=>'restore_status',
				'type'=>'dropDownList',
				'search'=>'=',
				'data'=>$this->getRestoreStatusOptions(),
				'value'=>$restoreStatus
			),
		);
		return $result;
	}

	public function getAccountList(){
		if(!self::$_accountList){
			self::$_accountList = self::model("JdAccount")->getAccountPairs();
		}
		return self::$_accountList;
	}

	public function getStatusOptions($status = null){
		$statusOptions = array(
			1=>'改价成功',
			2=>'改价失败',
		);
		if($status !== null)
			return isset($statusOptions[$status])?$statusOptions[$status]:'';
		return $statusOptions;
	}

	public function getRestoreStatusOptions($restoreStatus = null){
		$restoreStatusOptions = array(
			0=>'待恢复',
			1=>'恢复成功',
			2=>'恢复失败',
		);
		if($restoreStatus !== null)
			return isset($restoreStatusOptions[$restoreStatus]) ? $restoreStatusOptions[$restoreStatus] : '';
		return $restoreStatusOptions;
	}

	public function addtions($datas){
		if(empty($datas)) return $datas;
		foreach ($datas as &$data){
			$data['account_id'] = self::$_accountList[$data['account_id']];
			$data['status'] = $this->getStatusOptions($data['status']);
			$data['restore_status'] = $this->getRestoreStatusOptions($data['restore_status']);
		}
		return $datas;
	}
}

?>