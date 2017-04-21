<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/4/11
 * Time: 16:23
 */
class WishYearRecord extends WishModel
{
    public $listing_rate;
    public $optimization_rate;

    const TABLE_NAME = 'ueb_wish_task_year_record';

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

    protected function _setCDbCriteria()
    {
        $cdbCriteria = new CDbCriteria();
        $cdbCriteria->select = "*";
        return $cdbCriteria;
    }

    public function search()
    {
        $criteria = $this->_setCDbCriteria();
        $sort = new CSort($criteria);
        $sort->attributes = array(
            'defaultOrder' => 'id'
        );

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


    /***
     * @param $rows
     * @return mixed
     *
     */
    private function addition($rows)
    {
        if (empty($rows)) return $rows;
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

        $total = count($rows);
        $listing_num = 0;
        $finish_listing_num = 0;
        $optimization_num = 0;
        $finish_optimization_num = 0;
        foreach ($rows as $key => $row) {
            $rows[$key]['seller_user_id'] = isset($users_list[$row['seller_user_id']]) ? $users_list[$row['seller_user_id']] : '';
            $rows[$key]['listing_rate'] = (0 < $row['finish_listing_num'] && 0 < $row['listing_num']) ?
                round(($row['finish_listing_num'] / $row['listing_num'])*100, 2).'%' : ((0 == $row['listing_num'] && $row['finish_listing_num'] > 0) ? 100 : 0).'%';
            $rows[$key]['optimization_rate'] = (0 < $row['finish_optimization_num'] && 0 < $row['optimization_num']) ?
                round(($row['finish_optimization_num'] / $row['optimization_num'])*100, 2).'%' : ((0 == $row['optimization_num'] && $row['finish_optimization_num'] > 0) ? 100 : 0).'%';

            $listing_num += $row['listing_num'];
            $finish_listing_num += $row['finish_listing_num'];
            $optimization_num += $row['optimization_num'];
            $finish_optimization_num += $row['finish_optimization_num'];
            if ($total == ($key+1)) {
                $data = clone $row;
            }
        }

        $data->year = Yii::t('task', 'Sum');
        $data->listing_num = $listing_num;
        $data->finish_listing_num = $finish_listing_num;
        $data->listing_rate = (0 < $listing_num) ? round(($finish_listing_num/$listing_num)*100, 2).'%' : ((0 < $finish_listing_num) ? '100%' : '0%');
        $data->optimization_num = $optimization_num;
        $data->finish_optimization_num = $finish_optimization_num;
        $data->optimization_rate = (0 < $optimization_num) ? round(($finish_optimization_num/$optimization_num)*100, 2).'%' : ((0 < $finish_optimization_num) ? '100%' : '0%');
        if ($this->group()) {
            if (ProductsGroupModel::GROUP_SALE  == $this->group()->job_id) {
                $data->seller_user_id = '-';
            } else {
                $data->seller_user_id = '-';
            }
        } else {
            $data->seller_user_id = '-';
        }
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
     * @param $data
     * @param $id
     * @return int
     *
     * 更新
     */
    public function updateDataByID($data, $id)
    {
        if (!is_array($id)) $id = array($id);
        return $this->getDbConnection()
            ->createCommand()
            ->update($this->tableName(), $data, "id in(" . implode(",", $id) . ")");
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


    //过滤显示标题
    public function attributeLabels()
    {
        return array(
            'seller_user_id' => Yii::t('task', 'Seller'),
            'year' => Yii::t('task', 'Year'),
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

        $check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_WISH);
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