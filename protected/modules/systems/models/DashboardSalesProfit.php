<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/1/20
 * Time: 14:50
 */
class DashboardSalesProfit extends SystemsModel
{
	public $seller_user_id;

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function getDbKey()
	{
		return 'db_dashboard';
	}

	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName()
	{
		return 'ueb_dashboard_sales_profit';
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
		);
		$criteria = $this->_setCDbCriteria();

		$group = $this->group();

		$date_time = Yii::app()->request->getParam('date_time', '');
		$month = Yii::app()->request->getParam('month');
		if ('' == $date_time) {
			//默认从今年的第一个月开始
			if ('' == $month) {
				$start = date("Y-01-01");
				$end = date("Y-12-01");
			} else {
				$start = date("Y-{$month}-01");
				$end = date("Y-{$month}-01");
			}
		} else {
			if ('' == $month) {
				$start = $date_time."-01-01";
				$end = $date_time.'-12-01';
			} else {
				$start = $date_time."-{$month}-01";
				$end = $date_time."-{$month}-01";
			}
		}

		$criteria->addBetweenCondition("date_time", $start, $end);
		if ($group) {
			if (ProductsGroupModel::GROUP_LEADER == $group->job_id) {
				//组长
				$seller_user_id = intval(Yii::app()->request->getParam('seller_user_id'));
				//如果是搜索，则单独查询满足条件的数据
				if (0 < $seller_user_id) {
					$criteria->addCondition('group_id = '. $group->group_id.' AND seller_user_id = ' . $seller_user_id);
				} else {
					//否则查询组内的数据
					$criteria->addCondition('group_id = '. $group->group_id);
				};
			} else {
				//普通销售人员
				$criteria->addCondition('seller_user_id = ' . Yii::app()->user->id);
			}
		}

		//主管
		$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_EBAY);
		if ($check_result && !$group) {
			//如果是搜索，则单独查询满足条件的数据
			$group_id = intval(Yii::app()->request->getParam('group_id'));
			$seller_user_id = intval(Yii::app()->request->getParam('seller_user_id'));
			if (0 < $group_id) {
				$criteria->addCondition('group_id = ' . $group_id);
			} elseif ($seller_user_id < 0) {
				$criteria->addCondition('seller_user_id = ' . $seller_user_id);
			}else {
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


	private function addition($rows)
	{
		if (empty($rows)) {
			return $rows;
		}

		$sales_target = 0;
		$sales_amount_rmb = 0;
		$profit_target = 0;
		$retained_profits = 0;
		$total = count($rows);
		foreach ($rows as $k => $v) {
			$sales_target += $v['sales_target'];
			$sales_amount_rmb += $v['sales_amount_rmb'];
			$retained_profits += $v['retained_profits'];
			$profit_target += $v['profit_target'];
			$rows[$k]['date_time'] = date('Y-m', strtotime($v['date_time']));
			$rows[$k]['sales_rate'] = !empty($v['sales_rate']) ? $v['sales_rate'].'%' : '0.00%';
			$rows[$k]['profit_rate'] = !empty($v['profit_rate']) ? $v['profit_rate'].'%' : '0.00%';
			$rows[$k]['sales_target'] = number_format($v['sales_target'], 2);
			$rows[$k]['sales_amount_rmb'] = number_format($v['sales_amount_rmb'], 2);
			$rows[$k]['retained_profits'] = number_format($v['retained_profits'], 2);
			$rows[$k]['profit_target'] = number_format($v['profit_target'], 2);
			if ($total == ($k+1)) {
				$data = clone $v;
			}
		}

		$data->date_time = Yii::t('task', 'Sum');
		$data->seller_name = '-';
		$data->sales_target = number_format($sales_target, 2);
		$data->sales_amount_rmb = number_format($sales_amount_rmb, 2);
		$data->sales_rate = (0 < $sales_target) ? round(($sales_amount_rmb/$sales_target)*100, 2).'%' : '0.00%';
		$data->profit_target = number_format($profit_target, 2);
		$data->retained_profits = number_format($retained_profits, 2);
		$data->profit_rate = (0 < $profit_target) ? round(($retained_profits/$profit_target)*100, 2).'%' : '0.00%';

		$rows = array_merge($rows, array($data));

		return $rows;
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
			->from($this->tableName())
			->where($where, $params);
		$order != '' && $cmd->order($order);
		$cmd->limit(1);
		return $cmd->queryRow();
	}


	/**
	 * 计算当前月的比例
	 */
	public function calculate()
	{
		$date = date('Y-m-01', strtotime("-1 days"));
		$sql = "UPDATE ".$this->tableName()." SET sales_rate = (sales_amount_rmb / sales_target)*100, 
				profit_rate = (retained_profits / profit_target)*100
				WHERE 1 AND date_time = '{$date}'";

		$this->getDbConnection()->createCommand("{$sql}")->execute();
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
		$flag = $this->getDbConnection()
			->createCommand()
			->insert($tableName, $params);
		if ($flag) {
			return $this->dbConnection->getLastInsertID();
		}
		return false;
	}


	/**
	 * @param $params
	 * @param $id
	 * @return int
	 */
	public function update($params, $id)
	{
		return $this->getDbConnection()->createCommand()
			->update(
				$this->tableName(),
				$params,
				" id = '{$id}'"
			);
	}

	public function sumData($fields = "SUM(sales_amount_rmb) AS sales_amount", $where = "1")
	{
		$row = $this->getOneByCondition($fields, $where);
		return $row;
	}

	//过滤显示标题
	public function attributeLabels()
	{
		return array(
			'date_time' => Yii::t('task', 'Year'),
			'seller_user_id' => Yii::t('task', 'Seller'),
			'month' => Yii::t('task', 'Month'),
		);
	}

	public function filterOptions()
	{
		$year_arr = array();
		$month_arr = array();
		for ($i = 2016; $i <= date('Y'); $i++) {
			$year_arr[$i] = $i.Yii::t('task', 'Y');
		}

		for ($k = 1; $k <=12; $k++) {
			$mk = ($k < 10) ? '0'.$k : $k;
			$month_arr[$mk] = (($k < 10) ? '0'.$k : $k).Yii::t('task', 'M');
		}

		$filterData = array(
			array(
				'rel' => true,
				'name' => 'date_time',
				'type' => 'dropDownList',
				'data' => $year_arr,
				'search' => '=',
				'alis' => 'p',
				'value' => Yii::app()->request->getParam('date_time'),
			),
			array(
				'rel' => true,
				'name' => 'month',
				'type' => 'dropDownList',
				'data' => $month_arr,
				'search' => '=',
				'alis' => 'm',
				'value' => Yii::app()->request->getParam('month'),
			),
		);

		$group = $this->group();
		if ($group) {
			if (ProductsGroupModel::GROUP_LEADER == $group->job_id) {
				//获取此组长下的所有组员id
				$teams_arr = $this->groupUsers($group->group_id);
				//根据组员id获取组员的联系人信息
				$user_list = array();
				if (!empty($teams_arr)) {
					$teams = User::model()->getUserListByIDs($teams_arr);
					if (!empty($teams)) {
						foreach ($teams as $uk => $uv) {
							$user_list[$uv['id']] = $uv['user_full_name'];
						}
					}
				}

				$filterData = array_merge($filterData,
					array(
						array(
							'name' => 'seller_user_id',
							'type' => 'dropDownList',
							'data' => $user_list,
							'search' => '=',
							'alis' => 'p'
						),
					)
				);
			}
		} else {
			$users_arr = User::model()->getUserNameByDeptID(array(Yii::app()->user->department_id), true);
			//把组长排除
			$group_user_list = $this->groupLeader();
			if (!empty($users_arr)) {
				foreach ($users_arr as $uk => $uv) {
					if (!in_array($uk, $group_user_list)) {
					    //还要检查是否为销售，如果不为销售，则也不要显示
                        $check_arr = ProductsGroupModel::model()->getOneDataByCondition("id", " seller_user_id = '{$uk}' 
                                        AND job_id = '".ProductsGroupModel::GROUP_SALE."' AND is_del = 0");
                        if (!empty($check_arr)) {
                            $user_list[$uk] = $uv;
                        }
					}
				}
			}

			$filterData = array_merge($filterData,
				array(
					array(
						'name' => 'seller_user_id',
						'type' => 'dropDownList',
						'data' => $user_list,
						'search' => '=',
						'alis' => 'p'
					)
				)
			);
		}
		return $filterData;
	}

}