<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/9
 * Time: 11:33
 *
 * 任务设置模块基类
 **/

class TaskModel extends UebModel
{
	public function getDbKey()
	{
		return 'db';
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
}