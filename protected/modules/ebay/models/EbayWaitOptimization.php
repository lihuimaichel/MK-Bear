<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/14
 * Time: 17:06
 */
class EbayWaitOptimization extends EbayModel
{
	const TABLE_NAME = 'ueb_ebay_task_wait_optimization';

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName()
	{
		return self::TABLE_NAME;
	}


	public function saveData($params)
	{
		$tableName = $this->tableName();
		$flag = $this->dbConnection->createCommand()->insert($tableName, $params);
		if ($flag) {
			return $this->dbConnection->getLastInsertID();
		}
		return false;
	}

	/**
	 * @desc 更新
	 * @param unknown $data
	 * @param unknown $id
	 * @return Ambigous <number, boolean>
	 */
	public function updateDataByID($data, $id)
	{
		if (!is_array($id)) $id = array($id);
		return $this->getDbConnection()
			->createCommand()
			->update($this->tableName(), $data, "id in(" . implode(",", $id) . ")");
	}

	/**
	 * @param $data
	 * @param $where
	 * @return int
	 *
	 * 按条件更新
	 */
	public function updateByWhere($data, $where)
	{
		return $this->getDbConnection()
				->createCommand()
				->update($this->tableName(), $data, $where);
	}

	/**
	 * @param string $where
	 * @return CDbDataReader|mixed
	 * 获取指定条件的数量
	 */
	public function getTotalByCondition($where = '1')
	{
		return $this->getDbConnection()
			->createCommand("SELECT COUNT(id) AS total FROM " . self::tableName() . " WHERE {$where}")
			->queryRow();
	}

	/**
	 * @param string $fields
	 * @param string $where
	 * @param array $params
	 * @param string $order
	 * @return CDbDataReader|mixed
	 *
	 * 根据条件获取一条记录
	 */
	public function getOneByCondition($fields = '*', $where = '1', $params = array(), $order = '')
	{
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where, $params);
		$order != '' && $cmd->order($order);
		$cmd->limit(1);
		return $cmd->queryRow();
	}


	/**
	 * @param string $fields
	 * @param string $where
	 * @param string $order
	 * @return array|CDbDataReader
	 *
	 * 根据条件获取数据
	 */
	public function getDataByCondition($fields = '*', $where = '1', $order = '')
	{
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from($this->tableName())
			->where($where);
		$order != '' && $cmd->order($order);
		return $cmd->queryAll();
	}


	protected function _setCDbCriteria()
	{
		$cdbCriteria = new CDbCriteria();
		$cdbCriteria->select = "*";
		return $cdbCriteria;
	}

	public function search()
	{
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' => 'id',
		);
		$criteria = $this->_setCDbCriteria();
		$group = $this->group();
		$date = Yii::app()->request->getParam('date_time');
		if (empty($date)) {
			$criteria->addCondition("date_time = '".date('Y-m-d')."'");
		} else {
			$date_arr = explode("-", $date);
			$total = count($date_arr);
			if (!in_array($total, array(2,3))) {
				exit('date error');
			}

			if (3 == $total) {
				$criteria->addCondition("date_time = '{$date}'");
			} else {
				$criteria->addBetweenCondition('date_time', $date.'-01', $date.'-'.date('t'));
			}
		}

		if ($group) {
			if (ProductsGroupModel::GROUP_LEADER == $group->job_id) {
				$seller_user_id = intval(Yii::app()->request->getParam('seller_user_id'));
				//如果是搜索，则单独查询满足条件的数据
				if (0 < $seller_user_id) {
					$criteria->addCondition('seller_user_id = ' . $seller_user_id);
				} else {
					//否则查询组内的数据
					$users_list = $this->groupUsers($group->group_id);
					$criteria->addInCondition('seller_user_id', $users_list);
				};
			} else {
				$criteria->addCondition('seller_user_id = ' . Yii::app()->user->id);
			}
		} else {
			$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_EBAY);
			if ($check_result && !$group) {
				//如果是搜索，则单独查询满足条件的数据
				$seller_user_id = intval(Yii::app()->request->getParam('seller_user_id'));
				if (0 < $seller_user_id) {
					$criteria->addCondition('seller_user_id = ' . $seller_user_id);
				} else {
					$department_id = User::model()->getDepIdById(Yii::app()->user->id);
					$users_arr = User::model()->getUserNameByDeptID(array($department_id), true);
					$users_list = array_keys($users_arr);
					$criteria->addInCondition('seller_user_id', $users_list);
				}
			} else {
				$criteria->addInCondition('seller_user_id', array());
			}
		}


		$dataProvider = parent::search(get_class($this), $sort, array(), $criteria);
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}

	private function addition($rows)
	{
		if (empty($rows)) return $rows;
		$status_arr = $this->optimization_status();
		$accounts = EbayAccount::getIdNamePairs();
		if ($this->group()) {
			$group_id = $this->group()->group_id;
			$group_users = $this->groupUsers($group_id);
			$users_list = $this->userList($group_users);
		} else {
			//部门主管，则取部门的所有数据
			$users_list = array();
			$uid = Yii::app()->user->id;
			$platform = SellerUserToAccountSite::model()->getPlatformByUid($uid);
			$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, $platform);
			if ($check_result) {
				$department_id = User::model()->getDepIdById(Yii::app()->user->id);
				$users_list = User::model()->getUserNameByDeptID(array($department_id), true);
			}
		}

		foreach ($rows as $k =>$v) {
			$rows[$k]['status'] = isset($status_arr[$v['status']]) ? $status_arr[$v['status']] : "unknow";
			$rows[$k]['seller_user_id'] = isset($users_list[$v['seller_user_id']]) ? $users_list[$v['seller_user_id']] : '';
			$rows[$k]['account_id'] = isset($accounts[$v['account_id']]) ? $accounts[$v['account_id']] : '';
		}
		return $rows;
	}


	//过滤显示标题
	public function attributeLabels()
	{
		return array(
			'listing_id' => Yii::t('task', 'Listingid'),
			'sku' => Yii::t('task', 'Sku'),
			'seller_user_id' => Yii::t('task', 'Seller'),
			'date_time' => Yii::t('task', 'Select Date Time'),
		);
	}

	/**
	 * @return array
	 *
	 * 下拉过滤选项
	 */
	public function filterOptions()
	{
		$group = $this->group();
		$filterData = array(
			array(
				'name' => 'sku',
				'search' => 'IN',
				'type' => 'text',
				'htmlOptions' => array(),
			),
			array(
				'name' => 'listing_id',
				'type' => 'text',
				'search' => 'IN',
				'htmlOptions' => array(),
			),
			array(
				'rel' => true,
				'name' 			=> 'date_time',
				'type' 			=> 'text',
				'search' 		=> 'IN',
				'alias'			=>	't',
				'value' => Yii::app()->request->getParam('date_time', date('Y-m-d')),
				'htmlOptions'	=> array(
					'size' => 4,
					'class'=>'date',
					'style'=>'width:80px;'
				),
			),
		);

		if ($group) {
			$job_id = $group->job_id;
			if ($job_id == ProductsGroupModel::GROUP_LEADER) {
				$group_users = $this->groupUsers($group->group_id);
				$user_list = array();
				if (!empty($group_users)) {
					$user_list = $this->userList($group_users);
				}
				$filterData = array_merge(
					$filterData,
					array(
						array(
							'name' => 'seller_user_id',
							'type' => 'dropDownList',
							'data' => $user_list,
							'search' => '=',
							'alis' => 'p',
							'value' => Yii::app()->request->getParam('seller_user_id'),
						)
					)
				);
			}
		}

		$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_EBAY);
		if ($check_result && !$group) {
			$department_id = User::model()->getDepIdById(Yii::app()->user->id);
			$users_arr = User::model()->getUserNameByDeptID(array($department_id), true);
			$filterData = array_merge(
				$filterData,
				array(
					array(
						'name' => 'seller_user_id',
						'type' => 'dropDownList',
						'data' => $users_arr,
						'search' => '=',
						'alis' => 'p',
						'value' => Yii::app()->request->getParam('seller_user_id'),
					)
				)
			);
		}

		return $filterData;
	}

	private function optimization_status()
	{
		return array(
			self::STATUS_WAIT => Yii::t('task', 'STATUS_WAIT'),
			self::STATUS_PROCESS => Yii::t('task', 'STATUS_PROCESS'),
			self::STATUS_SCUCESS => Yii::t('task', 'STATUS_SCUCESS'),
		);
	}

}