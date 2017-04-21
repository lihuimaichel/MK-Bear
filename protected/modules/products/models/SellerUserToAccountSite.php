<?php

/**
 * @desc 销售人员与站点关联
 * @author lihy
 *
 */
class SellerUserToAccountSite extends ProductsModel
{

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return "ueb_seller_user_to_account_site";
	}

	/**
	 * @desc 获取产品与站点负责人的绑定数据
	 */

	public function getsiterByCondition($platformCode, $accountId, $site)
	{
		$data = $this->getDbConnection()->createCommand()
			->select('seller_user_id')
			->from(SellerUserToAccountSite::tableName())
			->where('is_del = 0')
			->andwhere("platform_code=:platform_code and account_id=:account_id and site=:site", array(':platform_code' => $platformCode, ':account_id' => $accountId, ':site' => $site))
			->queryRow();
		return $data;
	}

	/*
	 * 根据用户Id获取平台
	 */
	public function getPlatformByUid($seller_user_id)
	{
	    if (isset(Yii::app()->user->department_id)) {
            $department_id = Yii::app()->user->department_id;
        } else {
	        $row = User::model()->find('id=:id', array(":id"=>$seller_user_id));
	        if (isset($row)) {
                $department_id = $row->department_id;
            } else {
                $department_id = 0;
            }
        }

        if (0 < $department_id) {
            $platform_arr = Platform::departmentPlatform();
            return isset($platform_arr[$department_id]) ? $platform_arr[$department_id] : Platform::CODE_EBAY;
        } else {
	        return Platform::CODE_EBAY;
        }

	    /*
		$row = $this->getDbConnection()->createCommand()
			->select('*')
			->from(self::tableName())
			->where('is_del = 0')
			->andWhere("seller_user_id =:seller_user_id", array(':seller_user_id' => $seller_user_id))
			->queryRow();

		return isset($row['platform_code']) ? $row['platform_code'] : Platform::CODE_EBAY;
	    */
	}

    /**
     * @param $platform
     * @return array|CDbDataReader
     */
    public function getDataByPlatform($platform)
    {
        $rows = $this->getDbConnection()->createCommand()
            ->select('*')
            ->from(self::tableName())
            ->where("is_del = 0")
            ->andWhere("site <> 'eBayMotors'")
            ->andWhere("platform_code =:platform_code", array(':platform_code' => $platform))
            ->queryAll();
        return $rows;
    }


	public function checkSellerAccountSite($platformCode, $accountId, $site, $sellerId)
	{
		if (in_array($platformCode, array(Platform::CODE_EBAY, Platform::CODE_AMAZON, Platform::CODE_LAZADA))) {
			$data = $this->getDbConnection()->createCommand()
				->select('seller_user_id')
				->from(SellerUserToAccountSite::tableName())
				->where('is_del = 0')
				->andWhere("site <> 'eBayMotors'")
				->andwhere("seller_user_id =:seller_user_id AND platform_code=:platform_code and account_id=:account_id and site=:site",
					array(':seller_user_id' => $sellerId, ':platform_code' => $platformCode, ':account_id' => $accountId, ':site' => $site))
				->queryRow();
		} else {
			$data = $this->getDbConnection()->createCommand()
				->select('seller_user_id')
				->from(SellerUserToAccountSite::tableName())
				->where('is_del = 0')
				->andWhere("site <> 'eBayMotors'")
				->andwhere("seller_user_id =:seller_user_id AND platform_code=:platform_code and account_id=:account_id",
					array(':seller_user_id' => $sellerId, ':platform_code' => $platformCode, ':account_id' => $accountId))
				->queryRow();
		}
		return $data;
	}

	/**
	 * @desc 通过销售人员获取对应的帐号站点
	 */

	public function getAccountSiteByCondition($platformCode, $sellerId)
	{
        $sellerId = !is_array($sellerId) ? array($sellerId) : $sellerId;
		$data = $this->getDbConnection()->createCommand()
			->select('account_id,site')
			->from($this->tableName())
            ->where(array('in','seller_user_id', $sellerId))
            ->andWhere('is_del = 0')
			->andWhere("site <> 'eBayMotors'")
            ->andwhere("platform_code=:platform_code", array(':platform_code' => $platformCode))
			->queryAll();
		return $data;
	}


    /**
     * @param $platformCode
     * @param $sellerId
     * @param $field
     * @return array|CDbDataReader
     *
     * 获取销售人员负责的账号信息
     */
	public function getDistinctData($platformCode, $sellerId, $field = 'account_id')
    {
        $sellerId = !is_array($sellerId) ? array($sellerId) : $sellerId;
        $data = $this->getDbConnection()->createCommand()
            ->select("DISTINCT({$field})")
            ->from($this->tableName())
            ->where(array('in','seller_user_id', $sellerId))
            ->andWhere('is_del = 0')
            ->andWhere("site <> 'eBayMotors'")
            ->andwhere("platform_code=:platform_code", array(':platform_code' => $platformCode))
            ->queryAll();
        return $data;
    }

}