<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/03/07
 * Time: 14:46
 *
 * 刊登排名
 */
class LazadaOptimizationRank extends LazadaModel
{
	const TABLE_NAME = 'ueb_lazada_task_optimization_rank';

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
		return $this->getDbConnection()
			->createCommand()
			->update($this->tableName(), $data, "id={$id}");
	}

	public function calculate()
	{
		$date_time = date('Y-m-01', strtotime("-1 days"));
		$sql = "UPDATE ".$this->tableName()." SET optimization_rate = (was_optimization_num/optimization_num)*100 WHERE 1 AND date_time = '{$date_time}'";
		return $this->getDbConnection()->createCommand($sql)->execute();
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


	protected function _setCDbCriteria()
	{
		$cdbCriteria = new CDbCriteria();
		$cdbCriteria->select = "*";
		return $cdbCriteria;
	}


	public function search()
	{
		$sort = new CSort();
		$order = Yii::app()->request->getParam('rank_select', 'rank');
		$sort->attributes = array(
			'defaultOrder' => "{$order}",
			'defaultDirection' => ('rank' == $order) ? 'ASC' : 'DESC',
		);
		$criteria = $this->_setCDbCriteria();

		$date = date('Y-m-01');
		$date_time = Yii::app()->request->getParam('date_time', $date);
		$criteria->addCondition("date_time ='{$date_time}'");

		$seller_user_id = intval(Yii::app()->request->getParam('seller_user_id', 0));
		if (0 < $seller_user_id) {
			$criteria->addCondition('seller_user_id = ' . $seller_user_id);
		}
		$dataProvider = parent::search(get_class($this), $sort, array(), $criteria);
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}

	private function addition($rows)
	{
		return $rows;
	}


	//过滤显示标题
	public function attributeLabels()
	{
		return array(
			'date_time' => Yii::t('task', 'Year Month'),
			'seller_user_id' => Yii::t('task', 'Seller'),
			'rank_select' => Yii::t('task', 'Order Way'),
		);
	}

	/**
	 * @return array
	 *
	 * 下拉过滤选项
	 */
	public function filterOptions()
	{
		$department_id = User::model()->getDepIdById(Yii::app()->user->id);
		$users_arr = User::model()->getUserNameByDeptID(array($department_id), true);
		$filterData = array(
			array(
				'rel' => true,
				'name' => 'date_time',
				'type' => 'dropDownList',
				'search' => '=',
				'data' => $this->filterDate(),
				'value' => date('Y-m-01'),
			),
			array(
				'rel' => true,
				'name' => 'rank_select',
				'type' => 'dropDownList',
				'data' => $this->rank(),
				'search' => '=',
				'value' => 'rank',
			),
			array(
				'name' => 'seller_user_id',
				'type' => 'dropDownList',
				'data' => $users_arr,
				'search' => '=',
				'value' => Yii::app()->request->getParam('seller_user_id'),
			),
		);
		return $filterData;
	}

	/**
	 * @return array
	 *
	 * 按排名排序
	 */
	private function rank()
	{
		return array(
			'rank' => Yii::t('task', 'Optimization Rank'),
			'optimization_num' => Yii::t('task', 'Order Optimization Num'),
			'was_optimization_num' => Yii::t('task', 'Order Was Optimization')
		);
	}

	/**
	 * @return array
	 *
	 * 返回年月
	 */
	private function filterDate()
	{
		$data = array();
		for ($i = 0; $i < 12; $i++) {
			$data[date('Y-m-01', strtotime("-{$i} months"))] = date('Y' . Yii::t('task', 'Y') . 'm' . Yii::t('task', 'M'), strtotime("-{$i} months"));
		}

		return $data;
	}

}