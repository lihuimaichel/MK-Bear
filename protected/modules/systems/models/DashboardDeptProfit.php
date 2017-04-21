<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/23
 * Time: 14:50
 */
class DashboardDeptProfit extends SystemsModel
{

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
		$cdbCriteria->select = "SUM(sales_target) AS sales_target, SUM(profit_target) AS profit_target,
								SUM(sales_amount_rmb) AS sales_amount_rmb, SUM(retained_profits) AS retained_profits,
								department_id, department_name, date_time";
		$cdbCriteria->group =  "date_time";
		return $cdbCriteria;
	}


	public function search()
	{
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' =>  'date_time',
			'defaultDirection' => 'DESC',
		);
		$criteria = $this->_setCDbCriteria();
		$date_time = Yii::app()->request->getParam('date_time');
		$month = Yii::app()->request->getParam('month');
		$department_id = Yii::app()->user->department_id;

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
		$criteria->addCondition(" department_id = '{$department_id}'");
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
			$sales_rate = (0 < $v['sales_target']) ? round(($v['sales_amount_rmb'] / $v['sales_target'])*100, 2) : 0;
			$profit_rate = (0 < $v['profit_target']) ? round(($v['retained_profits'] / $v['profit_target'])*100, 2) : 0;

			$rows[$k]['date_time'] = date('Y-m', strtotime($v['date_time']));
			$rows[$k]['sales_rate'] =  $sales_rate.'%';
			$rows[$k]['profit_rate'] = $profit_rate.'%';
			$rows[$k]['sales_target'] = number_format($v['sales_target']);
			$rows[$k]['sales_amount_rmb'] = number_format($v['sales_amount_rmb']);
			$rows[$k]['retained_profits'] = number_format($v['retained_profits']);
			$rows[$k]['profit_target'] = number_format($v['profit_target']);
			if ($total == ($k+1)) {
				$data = clone $v;
			}
		}

		$data->date_time = Yii::t('task', 'Sum');
		$data->department_name = '-';
		$data->sales_target = number_format($sales_target);
		$data->sales_amount_rmb = number_format($sales_amount_rmb);
		$data->sales_rate = (0 < $sales_target) ? round(($sales_amount_rmb/$sales_target)*100, 2).'%' : '0.00%';
		$data->profit_target = number_format($profit_target);
		$data->retained_profits = number_format($retained_profits);
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
			'group_id' => Yii::t('task', 'Group Name'),
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

		$dept_id = Yii::app()->user->department_id;
        $group_rows = SellerToGroupName::model()->findAll(array(
            'select'=>array('id','group_name'),
            'order' => 'id DESC',
            'condition' => 'dept_id=:dept_id',
            'params' => array(':dept_id'=>$dept_id),
        ));
        $group_list = array();
        if(!empty($group_rows)) {
            foreach ($group_rows as $gk => $gv) {
                $group_list[$gv['id']] = $gv['group_name'];
            }
        };

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

		return $filterData;
	}

}