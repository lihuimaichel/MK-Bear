<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/03/06
 * Time: 21:44
 */

class WishHistoryListing extends WishModel
{
	const TABLE_NAME = 'ueb_wish_task_history_listing';

	//public $cost_price;
	public $seller_user_id;

	const STATUS_WAIT = 1;
	const STATUS_PROCESS = 2;
	const STATUS_SCUCESS = 3;

	const IS_SYSTEM = 1;
	const NO_SYSTEM = 0;

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
     * @param $values
     * @return mixed
     *
     */
	public function saveData($values)
	{
		$filed = array(
			'add_id',
			'seller_user_id',
			'account_id',
			'account_short_name',
			'site_name',
			'site_id',
            'warehouse_id',
			'platform_code',
			'sku',
			'sku_category_id',
			'category_name',
			'sku_title',
			'currency',
			'status',
			'created_at',
			'added_at',
			'is_system',
			'is_sync',
			'product_status',
			'date_time',
		);
		$connection = Yii::app()->db_wish;
		/**--------------------韩翔宇  2017-03-31 修改 开始----------------------------------**/

		//未修改前程序
		// $sql = "INSERT INTO ".$this->tableName()."(".implode(",", $filed).") VALUES (".implode("),(", $values).")";

		//修改后程序
		$sql = "INSERT INTO ".$this->tableName()."(".implode(",", $filed).") VALUES (".implode("),(", $values).") ON DUPLICATE KEY UPDATE 
                seller_user_id = VALUES(seller_user_id), 
                account_id = VALUES(account_id), 
                site_id = VALUES(site_id),
                sku = VALUES(sku),
                date_time = VALUES(date_time)";

		/**--------------------韩翔宇  2017-03-31 修改 结束----------------------------------**/
		$command = $connection->createCommand($sql);
		$result = $command->execute();
		return $result;
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


	public function updateDataByFields($data, $field, $value)
	{
		return $this->getDbConnection()
			->createCommand()
			->update($this->tableName(), $data, "$field =:$field", array(":$field" => $value));
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
                " AND sku = '" . $params['sku'] . "'" .
                " AND warehouse_id = '" . $params['warehouse_id'] ."'"
			);
	}


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
	 * @param $seller_user_id
	 * @param $date_time
	 * @return CDbDataReader|mixed
	 */
	public function fetchListingNum($seller_user_id, $date_time)
	{
		$table = $this->tableName();
		$sql = "SELECT COUNT(id) AS total FROM {$table} WHERE 1 AND seller_user_id = {$seller_user_id} AND date_time = '{$date_time}'";
		$row = $this->getDbConnection()->createCommand($sql)->queryRow();
		return $row['total'];
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
			'defaultOrder' =>  'id',
            'defaultOrderDirection' => 'DESC'
		);
		$criteria = $this->_setCDbCriteria();
		$group = $this->group();
		//默认是显示全部数据
		$date_time = Yii::app()->request->getParam('date_time');
		if (!empty($date_time)) {
			$start = $date_time[0];
			$end = $date_time[1];
			if ($end < $start) {
			    list($start, $end) = array($end, $start);
            }
			$criteria->addBetweenCondition("date_time", $start, $end);
		}

        $added_at = Yii::app()->request->getParam('added_at');
        if (!empty($added_at)) {
            $add_start = $added_at[0];
            $add_end = $added_at[1];
            if ($add_end < $add_start) {
                list($add_start, $add_end) = array($add_end, $add_start);
            }
            //转成具体的时间
            $add_start = date('Y-m-d H:i:s', strtotime($add_start));
            $add_end = date('Y-m-d H:i:s', (strtotime("$add_end +1 day") -1));
            $criteria->addBetweenCondition("added_at", $add_start, $add_end);
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
		}

		$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_WISH);
		if ($check_result && !$group) {
			//如果是搜索，则单独查询满足条件的数据
			$seller_user_id = intval(Yii::app()->request->getParam('seller_user_id'));
			if (0 < $seller_user_id) {
				$criteria->addCondition('seller_user_id = ' . $seller_user_id);
			} else {
				$department_id = User::model()->getDepIdById(Yii::app()->user->id);
				$users_arr =  User::model()->getUserNameByDeptID(array($department_id), true);
				$users_list = array_keys($users_arr);
				$criteria->addInCondition('seller_user_id', $users_list);
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
		$group = $this->group();
		$users_list = array();
		if ($group) {
			$group_id = $group->group_id;
			$group_users = $this->groupUsers($group_id);
			$users_list = $this->userList($group_users);
		} else {
			$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_WISH);
			if ($check_result) {
				$department_id = User::model()->getDepIdById(Yii::app()->user->id);
				$users_list = User::model()->getUserNameByDeptID(array($department_id), true);
			}
		}


		foreach ($rows as &$row) {
			$sku_category_id = $row['sku_category_id'];
			$row['sku_status'] = isset($product_status[$row['sku_status']]) ? $product_status[$row['sku_status']] : '';
			//$row['cost_price'] = $row['currency'] . "：" . $row['cost_price'];
			$class_name = ProductClass::model()->getClassNameByOnlineId($sku_category_id);
			$row['sku_category_id'] = !empty($class_name) ? $class_name : Yii::t('product', 'Unknow');
			$sku_category_name = ProductCategoryOnline::model()->getCat($sku_category_id);
			$row['category_name'] = !empty($sku_category_name) ? (isset($sku_category_name[$sku_category_id]) ?
				$sku_category_name[$sku_category_id] : Yii::t('product', 'Unknow')) : Yii::t('product', 'Unknow');
			$row['status'] = isset($list_status[$row['status']]) ? $list_status[$row['status']] : Yii::t('product', 'Unknow');
			$row['seller_user_id'] = isset($users_list[$row['seller_user_id']]) ? $users_list[$row['seller_user_id']] : '';
			$row['is_system'] = (1 == $row['is_system']) ? Yii::t('task', 'Yes') : Yii::t('task', 'No');
		}
		return $rows;
	}


	/**
	 * @param $date_time
	 * @param string $platform
	 * @return mixed
	 *
	 * 根据日期，平台更新历史记录中任务不系统的记录
	 */
	public function syncSystemListing($date_time)
	{
		$wait_table = WishWaitListing::TABLE_NAME;
		$sql = "UPDATE ".self::TABLE_NAME." AS h
				LEFT JOIN ".$wait_table." AS w
				ON h.seller_user_id = w.seller_user_id 
				AND h.account_id = w.account_id 
				AND h.site_name = w.site_name 
				AND h.sku = w.sku 
				AND h.date_time = w.date_time
				SET h.is_system = 1
				WHERE 1 AND w.date_time = '{$date_time}';
				";
		$connection = Yii::app()->db_wish;
		$command = $connection->createCommand($sql);
		$result = $command->execute();
		return $result;
	}


	/**
	 * @param $seller_user_id
	 * @return mixed
	 *
	 * 把销售对应的产品状态已经是上传成功的数据，置为刊登成功
	 */
	public function syncHistoryStatus($seller_user_id)
	{
		$product_table = UebModel::model('WishProductAdd')->tableName();
		$wait_table = $this->waitTable();
		$connection = Yii::app()->db_wish;

		//更新刊登历史表中的状态，把产品表中状态为已上传，历史表中状态不是刊登成功的状态修改为已刊登成功
		$sql = "UPDATE ".self::TABLE_NAME." AS h
				LEFT JOIN ".$product_table." AS p
				ON h.seller_user_id = p.create_user_id
				AND h.account_id = p.account_id
				AND h.site_id = p.site_id
				AND h.sku = p.sku
				SET h.status = 3
				WHERE 1 AND h.status <> 3 AND p.status = 4 AND p.create_user_id = '{$seller_user_id}'
				";

		$command = $connection->createCommand($sql);
		$result = $command->execute();

		//把产品状态为已上传成功的，还处于待刊登的全部设置为刊登成功的
		$wait_sql = "UPDATE {$wait_table} AS w
					 LEFT JOIN $product_table AS p
					 ON w.seller_user_id = p.create_user_id
					 AND w.account_id = p.account_id
					 AND w.site_id = p.site_id
					 AND w.sku = p.sku
					 SET w.status = 3
					 WHERE 1 AND w.status <> 3 AND p.status = 4 AND p.create_user_id = '{$seller_user_id}'
					";

		$command = $connection->createCommand($wait_sql);
		$wait_result = $command->execute();

		return ($result && $wait_result);
	}

    /**
     * @return string
     * 根据平台，返回待优化列表中的表名
     */
	private function waitTable()
	{
		return WishWaitListing::TABLE_NAME;
	}


	//过滤显示标题
	public function attributeLabels()
	{
		return array(
			//'cost_price' => Yii::t('task', 'Cost Price'),
			'sku' => Yii::t('task', 'Sku'),
			'seller_user_id' => Yii::t('task', 'Seller'),
			'status' => Yii::t('task', 'Listing Status'),
			'is_system' => Yii::t('task', 'System Task'),
			'date_time' => Yii::t('task', 'Listing Date'),
            'added_at' => Yii::t('task', 'Listing Online Date'),
			'product_status' => Yii::t('task', 'Status'),
            'account_id' => Yii::t('Task', 'Accounts'),
            'site_name'      => Yii::t('Task', 'Site'),
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
				'name' => 'sku',
				'search' => 'IN',
				'type' => 'text',
				'htmlOptions' => array(

				),
			),
			array(
				'name' => 'status',
				'type' => 'dropDownList',
				'data' => $this->productStatus(),
				'search' => '=',
				'alis' => 'p',
				'value' => Yii::app()->request->getParam('status'),
			),
			array(
				'name' => 'is_system',
				'type' => 'dropDownList',
				'data' => array('1' => Yii::t('task', 'Yes'), '0' => Yii::t('task', 'No')),
				'search' => '=',
				'alis' => 'p',
				'value' => Yii::app()->request->getParam('is_system'),
			),
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
            array(
                'rel' 			=> true,
                'name' 			=> 'added_at',
                'type' 			=> 'text',
                'search' 		=> 'RANGE',
                'alias'			=>	'dt',
                'htmlOptions'	=> array(
                    'size' => 4,
                    'class'=>'date',
                    'style'=>'width:80px;'
                ),
            ),
			/*
			array(
				'name' => 'cost_price',
				'type' => 'text',
				'search' => 'RANGE',
				'htmlOptions' => array(
					'size' => 8
				),
			),
			*/
		);
		$group = $this->group();
        $account_list = WishAccount::getIdNamePairs();
        $account_dropdown_list = array();
        $site_dropdown_list = array();
		if ($group) {
			$job_id = $group->job_id;
            if ($job_id == ProductsGroupModel::GROUP_SALE) {
                //销售
                $account_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_WISH, Yii::app()->user->id);
                if (!empty($account_rows)) {
                    foreach ($account_rows as $ak => $av) {
                        $account_dropdown_list[$av['account_id']] = isset($account_list[$av['account_id']]) ? $account_list[$av['account_id']] : 'unkonw';
                    }
                }

                $site_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_WISH, Yii::app()->user->id, 'site');
                if (!empty($site_rows)) {
                    foreach ($site_rows as $sk => $sv) {
                        $site_dropdown_list[$sv['site']] = $sv['site'];
                    }
                }

            } else {
                //组长
                $users_rows = $this->groupUsers($group->group_id);
                $account_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_WISH, $users_rows);
                if (!empty($account_rows)) {
                    foreach ($account_rows as $ak => $av) {
                        $account_dropdown_list[$av['account_id']] = isset($account_list[$av['account_id']]) ? $account_list[$av['account_id']] : 'unkonw';
                    }
                }

                $site_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_WISH, $users_rows, 'site');
                if (!empty($site_rows)) {
                    foreach ($site_rows as $sk => $sv) {
                        $site_dropdown_list[$sv['site']] = $sv['site'];
                    }
                }

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
		} else {
            $check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_WISH);
            if ($check_result) {
                $rows = User::model()->getUserNameByDeptID(array(Yii::app()->user->department_id), true);
                $users = array_keys($rows); //返回销售的数组
                $account_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_WISH, $users);
                if (!empty($account_rows)) {
                    foreach ($account_rows as $ak => $av) {
                        $account_dropdown_list[$av['account_id']] = isset($account_list[$av['account_id']]) ? $account_list[$av['account_id']] : 'unkonw';
                    }
                }

                $site_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_WISH, $users, 'site');
                if (!empty($site_rows)) {
                    foreach ($site_rows as $sk => $sv) {
                        $site_dropdown_list[$sv['site']] = $sv['site'];
                    }
                }

                $department_id = User::model()->getDepIdById(Yii::app()->user->id);
                $users_arr =  User::model()->getUserNameByDeptID(array($department_id), true);
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
                        )
                    )
                );
            } else {
                //其它账号
            }
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
            )
        );


		return $filterData;
	}


}