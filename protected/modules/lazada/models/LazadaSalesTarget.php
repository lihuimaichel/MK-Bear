<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/03/06
 * Time: 21:16
 *
 * 设置销售人员销售额任务
 */
class LazadaSalesTarget extends LazadaModel
{
	const TARGET = 50000;

	public $seller_name;
	public $department_name;
	public $platform_name;
	public $site_name;
	public $account_name;
	public $create_name;
	public $update_name;
	public $sales_target;
	public $profit_target;
	public $site_id;
	public $detail;

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
		
	}

	/**
	 * 查询的表名
	 */
	public function tableName()
	{
		return 'ueb_lazada_task_sales_target';
	}


	public function deleteOne($uid, $account_id, $site)
	{
		$result = $this->dbConnection->createCommand()
			->delete(
				$this->tableName(),
				"seller_user_id = '{$uid}' AND account_id = '{$account_id}' AND site_id = '{$site}'"
			);
		return $result;
	}

	/**
	 * @param $params
	 * @return bool
	 *
	 * 新增数据
	 */
	public function saveData($params)
	{
		$tableName = $this->tableName();
		$flag = $this->dbConnection
			->createCommand()
			->insert($tableName, $params);
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
	 *
	 * 保存数据
	 */
	public function updateData($data, $id)
	{
		return $this->dbConnection
			->createCommand()
			->update($this->tableName(), $data, "id={$id}");
	}


	protected function _setCDbCriteria()
	{
		$department_id = Yii::app()->user->department_id;
		$department_id = !empty($department_id) ? $department_id : 3;

		$cdbCriteria = new CDbCriteria();
		$cdbCriteria->select = "*";
		$cdbCriteria->group = "seller_user_id, platform_code, department_id, account_id";
		$cdbCriteria->addCondition("department_id = '{$department_id}'");
		$target = Yii::app()->request->getParam('sales_target');
		list($min, $max) = $target;
		$min_target = ('' != $min) ? $min : '';
		$max_target = ('' != $max) ? $max : '';
		if ('' != $min && '' != $max) {
			$min_target = ($min > $max) ? $max : $min;
			$max_target = ($min > $max) ? $min : $max;
		}

		//不能使用empty,因为值有可能为0
		if ('' != $min_target) {
			$cdbCriteria->addCondition("sales_target >= {$min_target}");
		}

		if ('' != $max_target) {
			$cdbCriteria->addCondition("sales_target <= {$max_target}");
		}

		return $cdbCriteria;
	}

	/**
	 * @param string $fields
	 * @param string $where
	 * @param string $order
	 * @return mixed
	 *
	 * 获取一条记录
	 */
	public function getoneByCondition($fields = '*', $where = '1', $order = '')
	{
		$cmd = $this->getDbConnection()->createCommand();
		$cmd->select($fields)
			->from($this->tableName())
			->where($where);
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
		$cmd = $this->getDbConnection()->createCommand();
		$cmd->select($fields)
			->from($this->tableName())
			->where($where);
		$order != '' && $cmd->order($order);
		return $cmd->queryAll();
	}

	/**
	 * @param array $sellers
	 * @return mixed
	 */
	public function sellerAccountSiteList($sellers = array())
	{
		$list = $this->dbConnection->createCommand()
			->select('id,seller_user_id,account_id,site_id,sales_target')
			->from(self::tableName())
			->where(!empty($sellers) ? "seller_user_id IN (" . implode(",", $sellers) . ")" : "1")
			->order("id DESC")
			->queryAll();
		return $list;
	}

	public function search()
	{
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder'      =>'seller_user_id',
			'defaultDirection'  =>  'ASC',
		);
		$criteria = $this->_setCDbCriteria();
        //$criteria->addCondition('status = 1');
		$group = $this->group();
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
			//主管查询整个部门
			$platform = SellerUserToAccountSite::model()->getPlatformByUid(Yii::app()->user->id);
			$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, $platform);
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

	/**
	 * @return mixed
	 *
	 * 从任务设置表中取得销售账号，去重，用于编辑时自动勾选
	 */
	public function distinct_seller_user()
	{
		$list = $this->dbConnection->createCommand()
			->selectDistinct('seller_user_id')
			->from(self::tableName())
			->query();
		return $list;
	}

	/**
	 * @param $id
	 * @return mixed
	 *
	 * 根据ID查询信息明细
	 */
	public function detail($id)
	{
		$row = $this->dbConnection
			->createCommand()
			->select('*')
			->from(self::tableName())
			->where('id=' . $id)
			->queryRow();
		return $row;
	}

	public function lists($ids = array())
	{
		if (empty($ids)) {
			return null;
		}
		$ids = is_array($ids) ? join(",", $ids) : $ids;
		$rows = $this->dbConnection
			->createCommand()
			->select('*')
			->from(self::tableName())
			->where('id IN(' . $ids . ")")
			->queryAll();
		return $rows;
	}


	public function getSum($ids = array(), $field = 'sales_target')
	{
		if (empty($ids)) {
			return null;
		}
		$row = $this->getDbConnection()
			->createCommand(" SELECT SUM({$field}) AS total FROM " . self::tableName() .
				" WHERE 1 AND seller_user_id IN(" . implode(",", $ids) . ")")
			->queryRow();
		return isset($row['total']) ? $row['total'] : 0;
	}

	/**
	 * @param $uid
	 * @param $ids
	 * @return bool
	 *
	 * 移除原来有设置，修改之后没有此项设置的要删除掉
	 */
	public static function remove($uid, $ids)
	{
		!is_array($ids) ? array($ids) : $ids;
		if (empty($ids)) return false;
		$result = Yii::app()->db_lazada->createCommand()
			->delete(
				self::tableName(),
				"seller_user_id = '{$uid}' AND id NOT IN (" . implode(",", $ids) . ")"
			);
		return $result;
	}


    /**
     * @return mixed
     * 返回更新的SQL
     */
    public function executeUpdate()
    {
        $sql = "UPDATE ".$this->tableName()." SET status = 0 WHERE 1 AND site_id <> 1";
        $result = $this->getDbConnection()->createCommand($sql)->execute();
        return $result;
    }


	private function addition($datas)
	{
		if (empty($datas)) return $datas;
		$siteList = LazadaSite::getSiteList();
		$platformList = Platform::getPlatformCodesAndNames();
		$marketDepartment = Department::model()->getMarketsDepartmentInfo();
		$accountList = LazadaAccount::getIdNamePairs();
		foreach ($datas as $key => $data) {
			$seller_name = isset($data['seller_user_id']) ? User::model()->getUserNameArrById($data['seller_user_id']) : '';
			$datas[$key]['seller_name'] = !empty($seller_name) ? $seller_name[$data['seller_user_id']] : '-';
			$datas[$key]['platform_name'] = isset($platformList[$data['platform_code']]) ? $platformList[$data['platform_code']] : '-';
			$datas[$key]['department_name'] = isset($marketDepartment[$data['department_id']]) ? $marketDepartment[$data['department_id']] : '-';
			$datas[$key]['account_name'] = isset($accountList[$data['account_id']]) ? $accountList[$data['account_id']] : '-';

			$datas[$key]->detail = array();
			//根据销售Id，账号找到子数据
			$rows = $this->sellerDetail($data['seller_user_id'], $data['account_id']);
			foreach ($rows as $nk => $nv) {
				$variant['sales_target'] = "<ul>";
				$variant['profit_target'] = "<ul>";
				$mData = array();
				$pData = array();
				$monthRows = $this->monthDetail($nv['id']);
				$create_name = isset($nv['created_uid']) ? User::model()->getUserNameArrById($nv['created_uid']) : '';
				$update_name = isset($nv['updated_uid']) ? User::model()->getUserNameArrById($nv['updated_uid']) : '';
				$variant['site_name'] = isset($siteList[$nv['site_id']]) ? $siteList[$nv['site_id']] : '-';
				$variant['create_name'] = !empty($create_name) ? $create_name[$nv['created_uid']] : '-';
				$variant['update_name'] = !empty($update_name) ? $update_name[$nv['updated_uid']] : '-';
				if (!empty($monthRows)) {
					foreach ($monthRows as $mk => $mv) {
						$mData[] = "<li style='width: 80px; float: left'>". $mv['month'].'（'.round($mv['sales_amount']).'）'."</li>";
						$pData[] = "<li style='width: 80px; float: left'>". $mv['month'].'（'.round($mv['profit_amount']).'）'."</li>";
					}
				}
				$variant['sales_target'] .= join("", $mData);
				$variant['profit_target'] .= join("", $pData);
				//$variant['sales_target'] = $nv['sales_target'];
				$variant['created_at'] = $nv['created_at'];
				$variant['updated_at'] = $nv['updated_at'];
				$variant['operate'] = $this->getSettingVariantOprator($nv['id']);
				$variant['variants_id'] = $nv['id'];
				$datas[$key]->detail[] = $variant;
				$variant['sales_target'] .="</ul>";
				$variant['profit_target'] .= "</ul>";
			}

		}
		return $datas;
	}

	private function sellerDetail($seller_user_id, $account_id)
	{
		$rows = $this->getDataByCondition('id, site_id, created_uid, updated_uid, sales_target, created_at, updated_at',
			" seller_user_id = '{$seller_user_id}' AND account_id = '{$account_id}'"
		);

		return $rows;
	}


	/**
	 * @param $id
	 * @return array|CDbDataReader
	 *
	 * 返回每月销售额明细
	 */
	private function monthDetail($id)
	{
		$rows = LazadaSalesExtend::model()->getDataByCondition('*', " sales_id = '{$id}'");
		return $rows;
	}


	private function getSettingVariantOprator($id)
	{
		$str = CHtml::link(Yii::t('task', 'Modify'),"/task/tasksalesetting/edit/mode/0/id/".$id,
			array("title"=>Yii::t('task', 'Edit Sales Target'),"mask"=>true, "target"=>"dialog","rel"=>"dialog","width"=>1100,"height"=>600));
		return $str;
	}


	public function attributeLabels()
	{
		return array(
			'variants_id' => '',
			'seller_user_id' => Yii::t('task', 'Seller'),
			'department_id' => Yii::t('task', 'Department'),
			'sales_target' => Yii::t('task', 'Sales Target'),
			'profit_target' => Yii::t('task', 'Profit Target'),
			'account_id' => Yii::t('task', 'Accounts'),
			'site_name' => Yii::t('task', 'Site'),
			'create_name' => Yii::t('task', 'Creator'),
			'created_at' => Yii::t('task', 'Create Time'),
			'update_name' => Yii::t('task', 'Updater'),
			'updated_at' => Yii::t('task', 'Update Time'),
			'operate' => Yii::t('task', 'Operate'),
		);
	}

	/**
	 * @return array
	 *
	 * 下拉过滤选项
	 */
	public function filterOptions()
	{
		$filterData = array(
			array(
				'name' => 'sales_target',
				'type' => 'text',
				'search' => 'RANGE',
				'htmlOptions' => array(
					'size' => 8
				),
			),
			/*
			array(
				'name' => 'department_id',
				'type' => 'dropDownList',
				'data' => Department::model()->getDepartment(),
				'search' => '=',
				'alis' => 'p',
				'value' => Yii::app()->request->getParam('department_id'),
			),
			*/
		);

		$group = $this->group();
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
							'alis' => 't',
							'value' => Yii::app()->request->getParam('seller_user_id'),
						)
					)
				);
			}
		}

		$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_LAZADA);
		if ($check_result && !$group) {
			$user_list = array();
			$department_id = User::model()->getDepIdById(Yii::app()->user->id);
			$users_arr = User::model()->getUserNameByDeptID(array($department_id), true);
			//把组长排除
			$group_user_list = $this->groupLeader();
			if (!empty($users_arr)) {
				foreach ($users_arr as $uk => $uv) {
					if (!in_array($uk, $group_user_list)) {
						$user_list[$uk] = $uv;
					}
				}
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

		return $filterData;
	}

}