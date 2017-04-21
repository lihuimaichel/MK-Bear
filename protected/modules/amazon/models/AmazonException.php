<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/14
 * Time: 17:03
 */
class AmazonException extends AmazonModel
{
    const TABLE_NAME = 'ueb_amazon_task_wait_listing';

    public $cost_price;
    public $seller_user_id;
    public $status_value;
    public $appeal_status;
    public $seller_name;


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
            'defaultOrder' => 'appeal_status, sku',
            'defaultDirection' => 'ASC'
        );

        $criteria->addCondition("appeal_status > 0");
        $appeal_time = Yii::app()->request->getParam('appeal_time');
        if (!empty($appeal_time)) {
            $start = $appeal_time[0];
            $end = $appeal_time[1];
            if (!empty($start) && !empty($end)) {
                if ($end < $start) {
                    list ($start, $end) = array($end, $start);
                }
                $start = $start." 00:00:00";
                $end = $end." 23:59:59";
                $criteria->addBetweenCondition("appeal_time", $start, $end);
            } elseif(!empty($start)) {
                $start = $start." 23:59:59";
                $criteria->addCondition("appeal_time < '{$start}'");
            } elseif(!empty($end)) {
                $end = $end." 23:59:59";
                $criteria->addCondition("appeal_time < '{$end}'");
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
            $check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_AMAZON);
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
        $appeal_status_data = $this->appeal_status_data();
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
            //申诉中，并且状态为未刊登的才可以操作
            if (in_array($row['appeal_status'], array(1))) {
                $rows[$key]['status_value'] = (1 == $row['status']) ? true : false;
            } else {
                $rows[$key]['status_value'] = false;
            }

            //$rows[$key]['appeal_status'] = (1 == $row['appeal_status']) ? true : false;
            $rows[$key]['sku_status'] = $product_status[$row['sku_status']];
            $rows[$key]['cost_price'] = $row['currency'] . "：" . $row['cost_price'];
            $rows[$key]['sku_category_id'] = !empty($class_name) ? $class_name : Yii::t('product', 'Unknow');
            $rows[$key]['category_name'] = !empty($sku_category_name) ? (isset($sku_category_name[$sku_category_id]) ?
                $sku_category_name[$sku_category_id] : Yii::t('product', 'Unknow')) : Yii::t('product', 'Unknow');
            $rows[$key]['status'] = isset($list_status[$row['status']]) ? $list_status[$row['status']] : Yii::t('product', 'Unknow');
            $rows[$key]['seller_name'] = isset($users_list[$row['seller_user_id']]) ? $users_list[$row['seller_user_id']] : '';
            $rows[$key]['appeal_status'] = isset($appeal_status_data[$row['appeal_status']]) ? $appeal_status_data[$row['appeal_status']] : 'Unknow';
            $rows[$key]['seller_user_id'] = isset($users_list[$row['seller_user_id']]) ? $users_list[$row['seller_user_id']] : '';
        }
        return $rows;
    }


    private function appeal_status_data()
    {
        return array(
            '1' => Yii::t('Task','Appeal Processing'),
            '2'=>Yii::t('Task', 'Appeal Sucessful'),
            '3' => Yii::t('Task', 'Appeal Reject')
        );
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
            'appeal_status' => Yii::t('Task', 'Appeal Status'),
            'appeal_time' => Yii::t('Task', 'Appeal Date Time'),
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
        );
        $account_list = AmazonAccount::getIdNamePairs();
        $account_dropdown_list = array();
        $site_dropdown_list = array();
        if ($group) {
            $job_id = $group->job_id;
            if ($job_id == ProductsGroupModel::GROUP_SALE) {
                //销售
                $account_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_AMAZON, Yii::app()->user->id);
                if (!empty($account_rows)) {
                    foreach ($account_rows as $ak => $av) {
                        $account_dropdown_list[$av['account_id']] = isset($account_list[$av['account_id']]) ? $account_list[$av['account_id']] : 'unkonw';
                    }
                }
                $site_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_AMAZON, Yii::app()->user->id, 'site');
                if (!empty($site_rows)) {
                    foreach ($site_rows as $sk => $sv) {
                        $site_dropdown_list[$sv['site']] = $sv['site'];
                    }
                }
            } else {
                //组长
                $users_rows = $this->groupUsers($group->group_id);
                $account_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_AMAZON, $users_rows);
                if (!empty($account_rows)) {
                    foreach ($account_rows as $ak => $av) {
                        $account_dropdown_list[$av['account_id']] = isset($account_list[$av['account_id']]) ? $account_list[$av['account_id']] : 'unkonw';
                    }
                }

                $site_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_AMAZON, $users_rows, 'site');
                if (!empty($site_rows)) {
                    foreach ($site_rows as $sk => $sv) {
                        $site_dropdown_list[$sv['site']] = $sv['site'];
                    }
                }
            }
        } else {
            //列出所有账号
            $check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_AMAZON);
            if ($check_result) {
                $rows = User::model()->getUserNameByDeptID(array(Yii::app()->user->department_id), true);
                $users = array_keys($rows); //返回销售的数组
                $account_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_AMAZON, $users);
                if (!empty($account_rows)) {
                    foreach ($account_rows as $ak => $av) {
                        $account_dropdown_list[$av['account_id']] = isset($account_list[$av['account_id']]) ? $account_list[$av['account_id']] : 'unkonw';
                    }
                }

                $site_rows = SellerUserToAccountSite::model()->getDistinctData(Platform::CODE_AMAZON, $users, 'site');
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
                    'name' => 'appeal_status',
                    'type' => 'dropDownList',
                    'data' => $this->appeal_status_data(),
                    'search' => '=',
                    'alis' => 'st',
                    'value' => Yii::app()->request->getParam('status'),
                )
            )
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

        $check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, Platform::CODE_AMAZON);
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

        $filterData = array_merge($filterData, array(
                array(
                    'rel' 			=> true,
                    'name' 			=> 'appeal_time',
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

        return $filterData;
    }
}