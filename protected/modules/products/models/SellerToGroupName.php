<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/9
 * Time: 16:50
 */

class SellerToGroupName extends ProductsModel
{
    public $dept_id;

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return "seller_group_name";
	}


	public function groupName($group_id = null)
	{
		if (null != $group_id) {
			$group_id = is_array($group_id) ? join(",", $group_id) : $group_id;
		}

		$data = array();
		$rows = $this->getDbConnection()->createCommand()
					->select('id, group_name')
					->from('seller_group_name')
					->where((null == $group_id) ? 1 : " id IN ({$group_id})")
					->order('id DESC')
					->queryAll();

		if (!empty($rows)) {
			foreach ($rows as $k => $v) {
				$data[$v['id']] = $v['group_name'];
			}
		}
		return $data;
	}


	public function getGroupNameByDepId($dep_id = null)
    {
        if (null != $dep_id) {
            $dep_id = is_array($dep_id) ? join(",", $dep_id) : $dep_id;
        }
        $data = array();
        $rows = $this->getDbConnection()->createCommand()
            ->select('id, group_name')
            ->from('seller_group_name')
            ->where((null == $dep_id) ? 1 : " dept_id IN ({$dep_id})")
            ->order('id DESC')
            ->queryAll();

        if (!empty($rows)) {
            foreach ($rows as $k => $v) {
                $data[$v['id']] = $v['group_name'];
            }
        }
        return $data;
    }

}