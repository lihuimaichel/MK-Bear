<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/28
 * Time: 11:50
 */
class TaskBaseController extends UebController
{
	/**
	 * @return string
	 *
	 * 获取当前登录账号所属平台
	 */
	public function userPlatform()
	{
		$uid = Yii::app()->user->id;
		$row = User::model()->getUserNameById($uid);
		if (isset($row)) {
			$department = $row['department_id'];
			$platform_arr = Platform::model()->departmentPlatform();
			$platform = isset($platform_arr[$department]) ? $platform_arr[$department] : Platform::CODE_EBAY;
		} else {
			$platform = Platform::CODE_EBAY;
		}
		
		return $platform;
	}

	/**
	 * @param $param_key
	 * @param string $platform
	 * @return EbayListingOptimization|EbaySalesTarget
	 *
	 * 返回类对象实例
	 */
	protected function model($param_key, $platform = null)
	{
		$platform = (null == $platform) ? $this->userPlatform() : $platform;
		switch ($param_key) {
			case 'task':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbayListingOptimization();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = new AliexpressListingOptimization();
						break;
                    case Platform::CODE_WISH:
                        $model = new WishListingOptimization();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonListingOptimization();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaListingOptimization();
                        break;
					default:
						$model = new EbayListingOptimization();
						break;
				}
				break;
			case 'sales':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbaySalesTarget();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = new AliexpressSalesTarget();
						break;
                    case Platform::CODE_WISH:
                        $model = new WishSalesTarget();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonSalesTarget();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaSalesTarget();
                        break;
					default:
						$model = new EbaySalesTarget();
						break;
				}
				break;
			case 'sales_extend':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbaySalesExtend();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = new AliexpressSalesExtend();
						break;
                    case Platform::CODE_WISH:
                        $model = new WishSalesExtend();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonSalesExtend();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaSalesExtend();
                        break;
					default:
						$model = new EbaySalesExtend();
						break;
				}
				break;
			case 'site':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbaySite();
						break;
                    case Platform::CODE_WISH:
                        $model = new WishSite();
                        break;
                    case Platform::CODE_ALIEXPRESS:
                        $model = new AliexpressSite();
                        break;
					case Platform::CODE_AMAZON:
						$model = new AmazonSite();
						break;
					case Platform::CODE_LAZADA:
						$model = new LazadaSite();
						break;
					default:
						$model = new EbaySite();
						break;
				}
				break;
			case 'account':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbayAccount();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = new AliexpressAccount();
						break;
                    case Platform::CODE_WISH:
                        $model = new WishAccount();
                        break;
					case Platform::CODE_AMAZON:
						$model = new AmazonAccount();
						break;
					case Platform::CODE_LAZADA:
						$model = new LazadaAccount();
						break;
					default:
						$model = new EbayAccount();
						break;
				}
				break;
			case 'history':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbayHistoryListing();
						break;
                    case Platform::CODE_WISH:
                        $model = new WishHistoryListing();
                        break;
					case Platform::CODE_ALIEXPRESS:
						$model = new AliexpressHistoryListing();
						break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonHistoryListing();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaHistoryListing();
                        break;
					default:
						$model = new EbayHistoryListing();
						break;
				}
				break;
			case 'wait':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbayWaitListing();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = new AliexpressWaitListing();
						break;
                    case Platform::CODE_WISH:
                        $model = new WishWaitListing();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonWaitListing();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaWaitListing();
                        break;
					default:
						$model = new EbayWaitListing();
						break;
				}
				break;
            case 'exception':
                switch ($platform) {
                    case Platform::CODE_EBAY:
                        $model = new EbayException();
                        break;
                    case Platform::CODE_ALIEXPRESS:
                        $model = new AliexpressException();
                        break;
                    case Platform::CODE_WISH:
                        $model = new WishException();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonException();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaException();
                        break;
                    default:
                        $model = new EbayException();
                        break;
                }
                break;
			case 'optimization':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbayWaitOptimization();
						break;
					case Platform::CODE_WISH:
						$model = new WishWaitOptimization();
						break;
                    case Platform::CODE_ALIEXPRESS:
                        $model = new AliexpressWaitOptimization();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonWaitOptimization();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaWaitOptimization();
                        break;
					default:
						$model = new EbayWaitOptimization();
						break;
				}
				break;
			case 'optimization_history':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbayOptimizationHistory();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = new AliexpressOptimizationHistory();
						break;
                    case Platform::CODE_WISH:
                        $model = new WishOptimizationHistory();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonOptimizationHistory();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaOptimizationHistory();
                        break;
					default:
						$model = new EbayOptimizationHistory();
						break;
				}
				break;
			case 'record':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbayRecord();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = new AliexpressRecord();
						break;
                    case Platform::CODE_WISH:
                        $model = new WishRecord();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonRecord();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaRecord();
                        break;
					default:
						$model = new EbayRecord();
						break;
				}
				break;
            case 'month_record':
                switch ($platform) {
                    case Platform::CODE_EBAY:
                        $model = new EbayMonthRecord();
                        break;
                    case Platform::CODE_ALIEXPRESS:
                        $model = new AliexpressMonthRecord();
                        break;
                    case Platform::CODE_WISH:
                        $model = new WishMonthRecord();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonMonthRecord();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaMonthRecord();
                        break;
                    default:
                        $model = new EbayMonthRecord();
                        break;
                }
                break;
            case 'year_record':
                switch ($platform) {
                    case Platform::CODE_EBAY:
                        $model = new EbayYearRecord();
                        break;
                    case Platform::CODE_ALIEXPRESS:
                        $model = new AliexpressYearRecord();
                        break;
                    case Platform::CODE_WISH:
                        $model = new WishYearRecord();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonYearRecord();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaYearRecord();
                        break;
                    default:
                        $model = new EbayYearRecord();
                        break;
                }
                break;
			case 'report':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbayTaskReport();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = new AliexpressTaskReport();
						break;
                    case Platform::CODE_WISH:
                        $model = new WishTaskReport();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonTaskReport();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaTaskReport();
                        break;
					default:
						$model = new EbayTaskReport();
						break;
				}
				break;
			case 'seller':
				$model = new SellerUserToJob;
				break;
			case 'sync':
				$model = new TaskSyncModel;
				break;
			case 'delete_log':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbaySettingDeleteLog();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = new AliexpressSettingDeleteLog();
						break;
                    case Platform::CODE_WISH:
                        $model = new WishSettingDeleteLog();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonSettingDeleteLog();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaSettingDeleteLog();
                        break;
					default:
						$model = new EbaySettingDeleteLog();
						break;
				}
				break;
			case 'product_add':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = EbayProductAdd::model();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = AliexpressProductAdd::model();
						break;
                    case Platform::CODE_WISH:
                        $model = WishProductAdd::model();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = AmazonProductAdd::model();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = LazadaProductAdd::model();
                        break;
					default:
						$model = EbayProductAdd::model();
						break;
				}
				break;
			case 'listing_rank':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = EbayListingRank::model();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = AliexpressListingRank::model();
						break;
                    case Platform::CODE_WISH:
                        $model = WishListingRank::model();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = AmazonListingRank::model();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = LazadaListingRank::model();
                        break;
					default:
						$model = EbayListingRank::model();
						break;
				}
				break;
			case 'optimization_rank':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = EbayOptimizationRank::model();
						break;
                    case Platform::CODE_WISH:
                        $model = WishOptimizationRank::model();
                        break;
                    case Platform::CODE_AMAZON:
                        $model = AmazonOptimizationRank::model();
                        break;
					case Platform::CODE_ALIEXPRESS:
						$model = AliexpressOptimizationRank::model();
						break;
                    case Platform::CODE_LAZADA:
                        $model = LazadaOptimizationRank::model();
                        break;
					default:
						$model = EbayOptimizationRank::model();
						break;
				}
				break;
			case 'report_tmp':
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = EbayTaskTmp::model();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = AliexpressTaskTmp::model();
						break;
                    case Platform::CODE_AMAZON:
                        $model = AmazonTaskTmp::model();
                        break;
                    case Platform::CODE_WISH:
                        $model = WishTaskTmp::model();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = LazadaTaskTmp::model();
                        break;
					default:
						$model = EbayTaskTmp::model();
						break;
				}
				break;
			default:
				switch ($platform) {
					case Platform::CODE_EBAY:
						$model = new EbayListingOptimization();
						break;
					case Platform::CODE_ALIEXPRESS:
						$model = new AliexpressListingOptimization();
						break;
                    case Platform::CODE_AMAZON:
                        $model = new AmazonListingOptimization();
                        break;
                    case Platform::CODE_WISH:
                        $model = new WishListingOptimization();
                        break;
                    case Platform::CODE_LAZADA:
                        $model = new LazadaListingOptimization();
                        break;
					default:
						$model = new EbayListingOptimization();
						break;
				}
				break;
		}
		return $model;
	}


	/**
	 * @param $users
	 * @param $platform
     * @param $date
	 * @return mixed
	 *
	 * 获取部门，小组或者销售个人本月刊登成功的总数
	 */
	public function yesterdayListingOptimization($users, $platform = Platform::CODE_EBAY, $date = '')
	{
	    $date_time = ('' == $date) ?  date("Y-m-01") : $date;
		$users = !is_array($users) ? array($users) : $users;
		//获取昨天刊登的数量
		$historyModel = $this->historyModel($platform);
		$listing_count = $historyModel->countByAttributes(
			array(
				'seller_user_id' => $users
			),
			" date_time >=:date_time ",
			array(":date_time" => $date_time)
		);

		$optimizationModel = $this->optimizationModel($platform);
		$optimization_count = $optimizationModel->countByAttributes(
			array(
				'seller_user_id' => $users,
				'status' => 3, //状态为3表示已优化
			),
			" date_time >=:date_time ",
			array(":date_time" => $date_time)
		);
		$data['listing_count'] = $listing_count;
		$data['optimization_count'] = $optimization_count;
		return $data;
	}


	/**
	 * @return CActiveRecord
	 * 获取待刊登及待优化的设置model
	 */
	protected function listingOptimizationModel($platform = Platform::CODE_EBAY)
	{
		return $this->model('task', $platform);
	}

	/**
	 * @return CActiveRecord
	 */
	protected function historyModel($platform = Platform::CODE_EBAY)
	{
		return $this->model('history', $platform);
	}


	protected function optimizationModel($platform = Platform::CODE_EBAY)
	{
		return $this->model('optimization', $platform);
	}

    /**
     * @return CDbDataReader|mixed|string
     *
     * 返回当前登陆人员具有的角色数量
     */
	public function roleNum()
    {
        return ProductsGroupModel::model()->countByAttributes(
                    array(
                        'seller_user_id' => Yii::app()->user->id,
                        'is_del' => 0,
                    ),
                    " group_id >:group_id ",
                    array(":group_id" => 0)
                );

    }

	/**
	 * @return array|CActiveRecord|mixed|null
	 * 返回登录的会员所属的组
	 */
	public function group()
	{
        if ('manager' == Yii::app()->session['login_role']) {
            return array();
        } else {
            $job_id = isset(Yii::app()->session['role_job_id']) ? Yii::app()->session['role_job_id'] : 0;
            $string = (0 < $job_id) ? " AND job_id = '{$job_id}'" : " ";
            return ProductsGroupModel::model()
                ->find("seller_user_id = :seller_user_id AND is_del = :is_del AND group_id > 0 {$string}",
                    array(':seller_user_id' => Yii::app()->user->id, ':is_del' => 0)
                );
        }
	}

	/**
	 * @param $group_id
	 * @return array
	 *
	 * 列出此组长下的所有组员
	 */
	public function groupUsers($group_id)
	{
		//获取此组长下的所有组员id
		$teams = SellerUserToJob::model()
			->findAll('group_id=:group_id AND job_id=:job_id AND is_del=:is_del',
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
	 * @param null $platform
	 * @param bool $flip
	 * @return mixed
	 */
	public function siteList($platform = null, $flip = false)
	{
		$platform = (null == $platform) ? $this->userPlatform() : $platform;
		switch ($platform) {
			case Platform::CODE_EBAY:
				$model = $this->model('site', $platform);
				$data = (false == $flip) ? $model->getSiteList() : array_flip($model->getSiteList());
				break;
			case Platform::CODE_ALIEXPRESS:
				$data = (false == $flip) ? array(0 => 'ali') : array('ali' => 0);
				break;
            case Platform::CODE_WISH:
                $model = $this->model('site', $platform);
                $data = (false == $flip) ? $model::getSiteList() : array_flip($model::getSiteList());
                break;
            case Platform::CODE_AMAZON:
                $model = $this->model('site', $platform);
                $data = (false == $flip) ? $model::getSiteList() : array_flip($model::getSiteList());
                break;
            case Platform::CODE_LAZADA:
                $model = $this->model('site', $platform);
                if (false == $flip) {
                    $data = $model::getSiteList();
                } else {
                    foreach (array_flip($model::getSiteList()) as $k => $v) {
                        $data[strtolower($k)] = strtolower($v);
                    }
                }
                break;
			default:
				$model = $this->model('site', $platform);
				$data = (false == $flip) ? $model->getSiteList() : array_flip($model->getSiteList());
				break;
		}
		return $data;
	}


	/**
	 * @return array
	 *
	 * 排除的站点，不查询
	 */
	public function excludeSite()
	{
		return 'eBayMotors';
	}

	/**
	 * @return array
	 *
	 * 返回有站点列表的平台
	 */
	protected function platformSite()
	{
		return array(Platform::CODE_EBAY, Platform::CODE_AMAZON, Platform::CODE_LAZADA);
	}

	/**
	 * @param  $platform
	 * @return mixed
	 *
	 * 获取账号列表
	 */
	public function accountList($platform = null)
	{
		$model = $this->model('account', $platform);
		if (Platform::CODE_LAZADA != $platform) {
            return $model::getIdNamePairs();
        } else {
		    $account_list_ids = array_keys($model::getIdNamePairs());
		    $account_model = $this->model('account', $platform);
		    $account_rows = $account_model->getListByCondition("id,short_name","old_account_id IN('".join("','",$account_list_ids)."')");
		    $data = array();
		    if (!empty($account_rows)) {
		        foreach ($account_rows as $k=>$v) {
                    $data[$v['id']] = $v['short_name'];
                }
            }

            return $data;
        }
	}


	/**
	 * @return mixed
	 *
	 * 获取相应平账号列表
	 */
	public function sellerUsers()
	{
		$platform = $this->userPlatform();
		switch ($platform) {
			case Platform::CODE_EBAY:
				return User::model()->getEbayUserList(false, false);
				break;
			case Platform::CODE_ALIEXPRESS:
				return User::model()->getAliexpressUserList(false, true);
				break;
            case Platform::CODE_WISH:
                return User::model()->getWishUserList(false);
                break;
            case Platform::CODE_AMAZON:
                return User::model()->getAmazonUserList();
                break;
            case Platform::CODE_LAZADA:
                return User::model()->getLazadaUserList();
                break;
			default:
				return User::model()->getEbayUserList(false, false);
				break;
		}
	}


	/**
	 * @return string
	 * 返回角色
	 */
	public function user_role()
	{
		$group = $this->group();
		if (!empty($group)) {
			if (ProductsGroupModel::GROUP_SALE == $group->job_id) {
				$role = 'seller';
			} else {
				$role = 'leader';
			}
		} else {
			$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, $this->userPlatform());
			if ($check_result) {
				$role = 'manager';
			} else {
				$role = '';
			}
		}

		return $role;
	}


	/**
	 * @param $data
	 * @param string $type
	 * @return mixed
	 *
	 * 更新 api 接口中的数据
	 */
	protected function postApi($data, $type = 'listing')
	{
		$data_string = is_array($data) ? json_encode($data) : $data;
		$header = array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data_string)
		);
		$taskKeys = ConfigFactory::getConfig('taskKeys');
		$uri = ('listing' == $type) ? $taskKeys['api_sync_listing_url'] : $taskKeys['api_sync_optimization_url'];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		$result = curl_exec($ch);
		return $result;
		curl_close($ch);
	}


    /**
     * @param $data
     * @param string $type
     * @return mixed
     *
     * 重新拉取数据
     */
	protected function getApi($data, $type = 'listing')
    {
        $params = array();
        $taskKeys = ConfigFactory::getConfig('taskKeys');
        $server = $_SERVER['SERVER_NAME'];
        $uri = ('listing' == $type) ? $taskKeys['sync_local_listing_url'] : $taskKeys['sync_local_optimization_url'];
        $uri = $server.$uri;
        echo $uri; exit;
        foreach ($data as $key =>$val) {
            $params[] = $key."/".$val;
        }
        $uri = !empty($params) ? $uri."/".join("/", $params) : $uri;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$uri}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}