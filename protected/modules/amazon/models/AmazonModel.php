<?php

/**
 * @desc amazon 模型类
 * @author zhangf
 * @since 2015-7-7
 *
 */
class AmazonModel extends UebModel
{
    const STATUS_WAIT = 1;
    const STATUS_PROCESS = 2;
    const STATUS_SCUCESS = 3;

    public function getDbKey()
    {
        return 'db_amazon';
    }

	public function group($job_id = null)
    {
        if ('manager' == Yii::app()->session['login_role']) {
            return array();
        } else {
            if (null != $job_id) {
                $job_id = $job_id;
            } else {
            $job_id = isset(Yii::app()->session['role_job_id']) ? Yii::app()->session['role_job_id'] : 0;
            }
            $string = (0 < $job_id) ? " AND job_id = '{$job_id}'" : " ";
            return ProductsGroupModel::model()
                ->find("seller_user_id = :seller_user_id AND is_del =:is_del AND group_id >:group_id {$string}",
                    array(':seller_user_id' => Yii::app()->user->id, ':is_del' => 0, ':group_id' => 0)
                );
        }
    }

    /**
     * @param $users
     * @return array
     *
     * 根据指定的销售人员id，返回id及姓名
     */
    protected function userList($users)
    {
        $users_list = array();
        $rows = User::model()->getUserListByIDs($users);
        if (!empty($rows)) {
            foreach ($rows as $k=>$v) {
                $users_list[$v['id']] = $v['user_full_name'];
            }
        }
        return $users_list;
    }


    /**
     * @param $group_id
     * @return array
     *
     * 列出此组长下的所有组员
     */
    protected function groupUsers($group_id)
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
            ->findAll('job_id=:job_id AND is_del =:is_del',
                array(':job_id' => ProductsGroupModel::GROUP_LEADER, ':is_del' => 0)
            );
        if (!empty($rows)) {
            foreach ($rows as $k => $v) {
                $data[] = $v['seller_user_id'];
            }

            if (!isset(Yii::app()->user->id)) {
                $data = array_merge($data, array(Yii::app()->user->id));
            }
        }

        return $data;
    }


    /**
     * @return array
     * 返回产品在刊登状态
     */
    public function productStatus()
    {
        return array(
            self::STATUS_WAIT => Yii::t('product', 'Listing Waiting'),
            self::STATUS_PROCESS => Yii::t('product', 'Listing Processing'),
            self::STATUS_SCUCESS => Yii::t('product', 'Listing Sucess'),
        );
    }


    public function productOnlineStatus()
    {
        return Product::getProductStatusConfig();
    }
}