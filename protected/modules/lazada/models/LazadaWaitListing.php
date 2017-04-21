<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/03/06
 * Time: 20:54
 */
class LazadaWaitListing extends LazadaModel
{
	const TABLE_NAME = 'ueb_lazada_task_wait_listing';

	public $cost_price;
	public $seller_user_id;
	public $status_value;
    public $appeal_status;


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

	/**
	 * @param $params
	 * @return bool|string
	 *
	 * 保存采集到的数据
	 */
	public function saveData($params)
	{
		$tableName = $this->tableName();
		$flag = $this->dbConnection->createCommand()->insert($tableName, $params);
		if ($flag) {
			return $this->dbConnection->getLastInsertID();
		}
		return false;
	}


	protected function _setCDbCriteria()
	{
		$cdbCriteria = new CDbCriteria();
		$cdbCriteria->select = "*";

		return $cdbCriteria;
	}


	/**
	 * @param $params
	 * @param int $status
	 * @return int
	 */
	public function updateWaitingListingStatus($params, $status = 2)
	{
        $site_id = isset($params['site_id']) ? $params['site_id'] : 0;
		return $this->getDbConnection()->createCommand()
			->update(
				$this->tableName(),
				array('status' => $status),
				" seller_user_id = '" . $params['create_user_id'] . "'" .
				" AND account_id = '" . $params['account_id'] . "'" .
				" AND site_id = '" . $site_id . "'" .
				" AND sku = '" . $params['sku'] . "'"
			);
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
	 * @param array $params
	 * @param string $order
	 * @return array|CDbDataReader
	 *
	 * 根据查询条件，返回符合条件的记录
	 */
	public function getDataByCondition($fields = '*', $where = '1', $params = array(), $order = '')
	{
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where, $params);
		$order != '' && $cmd->order($order);

		return $cmd->queryAll();
	}

	/**
	 * @param string $where
	 * @return CDbDataReader|mixed
	 *
	 * 获取记录数
	 */
	public function getTotalByCondition($where = '1')
	{
		return $this->getDbConnection()
			->createCommand("SELECT COUNT(id) AS total FROM " . self::tableName() . " WHERE {$where}")
			->queryRow();
	}


	public function search()
	{
		$criteria = $this->_setCDbCriteria();
		$sort = new CSort($criteria);
		$sort->attributes = array(
			'defaultOrder' => 'status, sku',
			'defaultDirection' => 'ASC'
		);
	
        //0正常，3驳回
        //$criteria->addInCondition('appeal_status', array(0, 3));

        $param = Yii::app()->request->getParam('param');
        if ('history' != $param) {
            //默认是显示当前月的数据
            $day = Yii::app()->request->getParam('day');
            if (empty($day)) {
                $start = date('Y-m-01');
                $end = date('Y-m-d');
                $criteria->addBetweenCondition("date_time", $start, $end);
            } else {
                $date = date("Y-m-").trim($day);
                $criteria->addCondition("date_time = '{$date}'");
            }
        } else {
            $date_time = Yii::app()->request->getParam('date_time');
            if (empty($date_time)) {
                $date = date("Y-m-01");
                $criteria->addCondition("date_time < '{$date}'");
            } else {
                $current_month = date('Y-m-01');
                $start = $date_time[0];
                $end = $date_time[1];
                if ($start < $current_month && $end < $current_month) {
                    $criteria->addBetweenCondition("date_time", $start, $end);
                } else {
                    if (!empty($start) && !empty($end)) {
                        if ($end < $start) {
                            list ($start, $end) = array($end, $start);
                        }
                        $start = ($start < $current_month) ? $start : "2017-01-01";
                        $end = ($end < $current_month) ? $end :  date('Y-m-t', (strtotime("$current_month -1 day")));
                        $criteria->addBetweenCondition("date_time", $start, $end);
                    } elseif(!empty($start)) {
                        $start = ($start < $current_month) ? $start : $current_month;
                        $criteria->addCondition("date_time < '{$start}'");
                    } else {
                        $end = ($end < $current_month) ? $end : $current_month;
                        $criteria->addCondition("date_time < '{$end}'");
                    }
                }
            }
        }

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
			$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_LAZADA);
			if ($check_result && !$group) {
                $group_id = Yii::app()->request->getParam('group_id');
				$seller_user_id = intval(Yii::app()->request->getParam('seller_user_id'));
                if (!empty($group_id)) {
				if (0 < $seller_user_id) {
					$criteria->addCondition('seller_user_id = ' . $seller_user_id);
                    }
                    $users_list = $this->groupUsers($group_id);
                    $criteria->addInCondition('seller_user_id', $users_list);
                } else {
                    //如果是搜索，则单独查询满足条件的数据
                    if (0 < $seller_user_id) {
                        $criteria->addCondition('seller_user_id = ' . $seller_user_id);
                    } else {
                        $department_id = User::model()->getDepIdById(Yii::app()->user->id);
                        $users_arr = User::model()->getUserNameByDeptID(array($department_id), true);
                        $users_list = array_keys($users_arr);
                        $criteria->addInCondition('seller_user_id', $users_list);
                    }
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


	/***
	 * @param $rows
	 * @return mixed
	 *
	 */
	private function addition($rows)
	{
		if (empty($rows)) return $rows;
		$product_status = $this->productOnlineStatus();
		$list_status = $this->productStatus();
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

		foreach ($rows as $key => $row) {
			$sku_category_id = $row['sku_category_id'];
			$class_name = ProductClass::model()->getClassNameByOnlineId($sku_category_id);
			$sku_category_name = ProductCategoryOnline::model()->getCat($sku_category_id);
            if (in_array($row['appeal_status'], array(0, 3))) {
			$rows[$key]['status_value'] = (1 == $row['status']) ? true : false;
            } else {
                $rows[$key]['status_value'] = false;
            }

            $rows[$key]['appeal_status'] = (1 == $row['appeal_status']) ? true : false;
			$rows[$key]['sku_status'] = $product_status[$row['sku_status']];
			$rows[$key]['cost_price'] = $row['currency'] . "：" . $row['cost_price'];
			$rows[$key]['sku_category_id'] = !empty($class_name) ? $class_name : Yii::t('product', 'Unknow');
			$rows[$key]['category_name'] = !empty($sku_category_name) ? (isset($sku_category_name[$sku_category_id]) ?
				$sku_category_name[$sku_category_id] : Yii::t('product', 'Unknow')) : Yii::t('product', 'Unknow');
			$rows[$key]['status'] = isset($list_status[$row['status']]) ? $list_status[$row['status']] : Yii::t('product', 'Unknow');
			$rows[$key]['seller_user_id'] = isset($users_list[$row['seller_user_id']]) ? $users_list[$row['seller_user_id']] : '';
		}
		return $rows;
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

	//过滤显示标题
	public function attributeLabels()
	{
		return array(
			'cost_price' => Yii::t('task', 'Cost Price'),
			'sku' => Yii::t('task', 'Sku'),
			'seller_user_id' => Yii::t('task', 'Seller'),
			'status' => Yii::t('task', 'Listing Status'),
	            	'date_time' => Yii::t('task', 'Task Date Time'),
	            	'account_id' => Yii::t('Task', 'Accounts'),
	            	'site_name'      => Yii::t('Task', 'Site'),
	            	'day' => Yii::t('Task', 'Days'),
	            	'group_id' => Yii::t('Task', 'Group Name'),
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
				'name' => 'cost_price',
				'type' => 'text',
				'search' => 'RANGE',
				'htmlOptions' => array(
					'size' => 8,
                    'style' => 'width:60px;',
                ),
                'headerOptions' => array(
                    'style' => 'width:60px;',
                ),
            )
        );
        $account_list = LazadaAccount::getIdNamePairs();
        $account_dropdown_list = array();
        $site_dropdown_list = array();
        if ($group) {
            $job_id = $group->job_id;
            if ($job_id == ProductsGroupModel::GROUP_SALE) {
                //销售
                $account_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_LAZADA, Yii::app()->user->id);
                if (!empty($account_rows)) {
                    foreach ($account_rows as $ak => $av) {
                        $account_dropdown_list[$av['account_id']] = isset($account_list[$av['account_id']]) ? $account_list[$av['account_id']] : 'unkonw';
                    }
                }

                $site_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_LAZADA, Yii::app()->user->id, 'site');
                if (!empty($site_rows)) {
                    foreach ($site_rows as $sk => $sv) {
                        $site_dropdown_list[$sv['site']] = $sv['site'];
                    }
                }

            } else {
                //组长
                $users_rows = $this->groupUsers($group->group_id);
                $account_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_LAZADA, $users_rows);
                if (!empty($account_rows)) {
                    foreach ($account_rows as $ak => $av) {
                        $account_dropdown_list[$av['account_id']] = isset($account_list[$av['account_id']]) ? $account_list[$av['account_id']] : 'unkonw';
                    }
                }

                $site_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_LAZADA, $users_rows, 'site');
                if (!empty($site_rows)) {
                    foreach ($site_rows as $sk => $sv) {
                        $site_dropdown_list[$sv['site']] = $sv['site'];
                    }
                }
            }
        } else {
            //列出所有账号
            $check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_LAZADA);
            if ($check_result) {
                $rows = User::model()->getUserNameByDeptID(array(Yii::app()->user->department_id), true);
                $users = array_keys($rows); //返回销售的数组
                $account_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_LAZADA, $users);
                if (!empty($account_rows)) {
                    foreach ($account_rows as $ak => $av) {
                        $account_dropdown_list[$av['account_id']] = isset($account_list[$av['account_id']]) ? $account_list[$av['account_id']] : 'unkonw';
                    }
                }

                $site_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_LAZADA, $users, 'site');
                if (!empty($site_rows)) {
                    foreach ($site_rows as $sk => $sv) {
                        $site_dropdown_list[$sv['site']] = $sv['site'];
                    }
                }
            } else {
                //其它账号
            }
        }

        $param = Yii::app()->request->getParam('param');
        $days = date('t');
        $data_arr = array();
        for ($i=1; $i<=$days; $i++) {
            $day_key = ($i < 10) ? "0{$i}" : $i;
            $data_arr[$day_key] = $i.Yii::t('Task', 'Day');
        }

        $filterData = array_merge($filterData, array(
                array(
                    'name' => 'account_id',
                    'type' => 'dropDownList',
                    'data' => $account_dropdown_list,
                    'search' => '=',
                    'alis' => 'a',
                    'value' => Yii::app()->request->getParam('account_id'),
                ),
                array(
                    'name' => 'site_name',
                    'type' => 'dropDownList',
                    'data' => $site_dropdown_list,
                    'search' => '=',
                    'alis' => 's',
                    'value' => Yii::app()->request->getParam('site_name'),
                ),
                array(
                    'name' => 'status',
                    'type' => 'dropDownList',
                    'data' => $this->productStatus(),
                    'search' => '=',
                    'alis' => 'st',
                    'value' => Yii::app()->request->getParam('status'),
                )
            )
        );

        if ('history' != $param) {
            $filterData = array_merge($filterData, array(
                    array(
                        'rel'  => true,
                        'name' => 'day',
                        'type' => 'dropDownList',
                        'data' => $data_arr,
                        'search' => '=',
                        'alis' => 'dy',
                        'value' => Yii::app()->request->getParam('day'),
                    ),
                )
            );
        } else {
            $filterData = array_merge($filterData, array(
                    array(
                        'rel' 			=> true,
                        'name' 			=> 'date_time',
                        'type' 			=> 'text',
                        'search' 		=> 'RANGE',
                        'alias'			=>	'dt',
                        'htmlOptions'	=> array(
                            'size' => 4,
                            'class'=>'date',
                            'style'=>'width:80px;'
                        ),
                    ),
                )
            );
        }

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

        $check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_LAZADA);
        if ($check_result && !$group) {
            $department_id = User::model()->getDepIdById(Yii::app()->user->id);
            $users_arr = User::model()->getUserNameByDeptID(array($department_id), true);
            if (!empty($users_arr)) {
                foreach ($users_arr as $uk => $uv) {
                    if ($uk == Yii::app()->user->id) {
                        unset($users_arr[$uk]);
                    }
                }
            }
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
                    ),
                    array(
                        'rel'  => true,
                        'name' => 'group_id',
                        'type' => 'dropDownList',
                        'data' => SellerToGroupName::model()->getGroupNameByDepId(array(Yii::app()->user->department_id)),
                        'search' => '=',
                        'alis' => 'gp',
                        'value' => Yii::app()->request->getParam('group_id'),
                    ),
                )
            );
        }

		return $filterData;
	}
}