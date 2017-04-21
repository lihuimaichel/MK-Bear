<?php

class SystemsModel extends UebModel {
    
    public function getDbKey() {
        return 'db_oms_system';
    }

	public function group()
	{
        if ('manager' == Yii::app()->session['login_role']) {
            return array();
        } else {
            $job_id = isset(Yii::app()->session['role_job_id']) ? Yii::app()->session['role_job_id'] : 0;
            $string = (0 < $job_id) ? " AND job_id = '{$job_id}'" : " ";
            return ProductsGroupModel::model()
                ->find("seller_user_id = :seller_user_id AND is_del =:is_del AND group_id >:group_id {$string}",
                    array(':seller_user_id' => Yii::app()->user->id, ':is_del' => 0, ':group_id' => 0)
                );
        }
	}

	/**
	 * @param $group_id
	 * @return array
	 *
	 * 根据组别Id取得组员
	 */
	public function groupUsers($group_id)
	{
		//获取此组长下的所有组员id
		$teams = SellerUserToJob::model()
			->findAll('group_id=:group_id AND job_id=:job_id AND is_del =:is_del',
				array(':group_id' => $group_id, ':job_id' => ProductsGroupModel::GROUP_SALE, ':is_del' => 0)
			);
		$teams_arr = array();
		if (!empty($teams)) {
			foreach ($teams as $k => $v) {
				$teams_arr[] = $v->seller_user_id;
			}
		}
		return $teams_arr;
	}


	/**
	 * @return array
	 *
	 * 获取组长列表
	 */
	protected function groupLeader()
	{
		$data = array();
		$rows = ProductsGroupModel::model()
			->findAll('job_id=:job_id AND is_del =:is_del AND group_id >:group_id',
				array(':job_id' => ProductsGroupModel::GROUP_LEADER, ':is_del' => 0, ':group_id' => 0)
			);
		if (!empty($rows)) {
			foreach ($rows as $k => $v) {
			    //如果既是组长，又是组员的，把此id去掉
                $rw = ProductsGroupModel::model()
                        ->find("seller_user_id =:seller_user_id AND job_id=:job_id AND is_del =:is_del AND group_id >:group_id",
                            array(':seller_user_id' => $v['seller_user_id'],
                                  ':job_id' => ProductsGroupModel::GROUP_SALE,
                                  ':is_del' => 0,
                                  ':group_id' => 0)
                        );
                if (empty($rw)) {
                    $data[] = $v['seller_user_id'];
                }
			}
		}

		return $data;
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
			return $this->getDbConnection()->getLastInsertID();
		}
		return false;
	}

}