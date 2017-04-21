<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/1/5
 * Time: 15:36
 */
class DashboardstatsController extends TaskBaseController
{
	public function accessRules()
	{
		return array(
			array(
				'allow',
				'users' => array(
					'*'
				),
				'actions' => array()
			)
		);
	}


	/**
	 * 采集销售人员底部面板中每日汇总报表数据到临时表
	 */
	public function actionEverydaystatistics()
	{
		set_time_limit(3600);
		ini_set('memory_limit', '2000M');
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		$platform = strtoupper(trim(Yii::app()->request->getParam('platform', Platform::CODE_EBAY)));
		$user_id = Yii::app()->request->getParam("user_id", 0);
		echo " Start: ".date('Y-m-d H:i:s');
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
		//从临时表中汇总
        try {
            $tmp_model = $this->model('report_tmp', $platform);
            $user_arr = (0 < $user_id) ? array($user_id) : array();
            ProductPlatformPublishReportMain::model()->createStatistics($platform, $tmp_model, $user_arr);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $e->getMessage();
        }
		echo "<br /> End :".date('Y-m-d H:i:s');
		echo "<br />Done !!!";
        $execute_end = date('Y-m-d H:i:s');
        TaskLogModel::model()->saveData(
            array(
                'file_name' => $_SERVER['REQUEST_URI'],
                'start_time' => $execute_start,
                'end_time' => $execute_end,
                'message' => $message,
            )
        );
	}

	/**
	 * 从临时表把数据更新到面板显示的表中
	 */
	public function actionFetchlisting()
	{
		//根据平台，获取销售人员绑定好的数据
		$configs = ConfigFactory::getConfig('taskKeys');
		$platform = strtoupper(trim(Yii::app()->request->getParam('platform', Platform::CODE_EBAY)));
		$debug = Yii::app()->request->getParam('debug', false);
		$user_id = Yii::app()->request->getParam('user_id', 0);
		$page_size = isset($configs['page_size']) ? $configs['page_size'] : 50;
		$platforms = Platform::model()->getPlatformCodes();
		//运行的时候应该取昨天的数据，不要取当天的
		$current = date('Y-m-d', strtotime("-1 days"));
		//执行的时候是取执行时的时间
		$date_time = date('Y-m-d');

		$excludeSite = $this->excludeSite();
		if (!in_array($platform, $platforms)) {
			echo "Platform: {$platform} was unknown";
			exit;
		}
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
        try {
            $platform_arr = array($platform);
            echo "Start:" . date("Y-m-d H:i:s") . "<br />";
            foreach ($platform_arr as $pk => $platform) {
                $omsAccountArr = array();
                if (in_array($platform, array(Platform::CODE_LAZADA, Platform::CODE_ALIEXPRESS))) {
                    if (Platform::CODE_LAZADA == $platform) {
                        $omsAccountArr = array_flip(LazadaAccount::getAccountToAccountId());
                    } else {
                        $omsAccountArr = array_flip(AliexpressAccount::getAccountToAccountId());
                    }
                }

                $lazada_data = (Platform::CODE_LAZADA == $platform) ? LazadaAccount::getAccountToAccountId() : array();
                $site_list = $this->siteList($platform, true);
                $account_list = $this->accountList($platform);
                //根据平台信息，采集已刊登（6），预刊登（8），分配的SKU（7子）的数据，分配的SKU（9主）的数据
                $rows = ProductPlatformPublishReportMain::model()->getSumDataByParams(array(6, 8, 7, 9), $excludeSite, $platform);
                $total = count($rows);
                if (0 < $total) {
                    $total_pages = ceil($total / $page_size);
                    $flag = true;
                    do {
                        for ($page = 1; $page <= $total_pages; $page++) {
                            $min = ($page - 1) * $page_size;
                            $max = ($page * $page_size - 1);
                            $arr = array();
                            for ($k = $min; $k <= $max; $k++) {
                                if ($total <= $k) {
                                    $flag = false;
                                    break;
                                }

                                if (6 == $rows[$k]['type']) {
                                    $key_field = 'was_listing';
                                } elseif (8 == $rows[$k]['type']) {
                                    $key_field = 'pre_listing';
                                } elseif (7 == $rows[$k]['type']) {
                                    $key_field = 'sku_amount';
                                } else {
                                    $key_field = 'sku_main_amount';
                                }

                                $var_array = array($key_field => $rows[$k]['sku_count']);
                                $group_row = SellerUserToJob::model()->sellerInfo('group_id', $rows[$k]['seller_user_id']);
                                //因为Lazada存的是OMS的账号Id,所以需要转换成market对应的账号Id
                                $account_id = (Platform::CODE_LAZADA == $platform) ? $lazada_data[$rows[$k]['account_id']] : $rows[$k]['account_id'];

                                $array = array(
                                    'department_id' => User::model()->getDepIdById($rows[$k]['seller_user_id']),
                                    'group_id' => !empty($group_row) ? $group_row['group_id'] : 0,
                                    'seller_user_id' => $rows[$k]['seller_user_id'],
                                    'platform' => $platform,
                                    'account_id' => $account_id,
                                    'account_name' => isset($account_list[$account_id]) ? $account_list[$account_id] : 'unknown',
                                    'site_id' => isset($site_list[$rows[$k]['site']]) ? $site_list[$rows[$k]['site']] : 0,
                                    'site_name' => $rows[$k]['site'],
                                    'product_status' => $rows[$k]['product_status'],
                                );
                                $arr[] = array_merge($var_array, $array);
                            }

                            if ($debug) {
                                echo "<br />============= fetch page: {$page} data: ================<br /><pre>";
                                print_r($arr);
                                echo "</pre>";
                            }

                            if (!empty($arr)) {
                                foreach ($arr as $sak => $sav) {
                                    $seller_uid = $sav['seller_user_id'];
                                    $account_id = $sav['account_id'];
                                    $site_id = $sav['site_id'];
                                    $product_status = $sav['product_status'];
                                    $site_name = $sav['site_name'];
                                    $result = DashboardSellerStats::model()->getOneByCondition(
                                        "id",
                                        " seller_user_id = '{$seller_uid}' 
									  AND account_id = '{$account_id}'
									  AND site_id = '{$site_id}'
									  AND date_time = '{$date_time}'
									  AND product_status = '{$product_status}'"
                                    );

                                    //如果是ali或者Lazada，则需要把自动id的转成OMS的
                                    $report_account_id = in_array($platform, array(Platform::CODE_LAZADA, Platform::CODE_ALIEXPRESS)) ? $omsAccountArr[$account_id] : $account_id;
                                    //账号7日优化动销率
                                    $row_optimization = WaitOptAccount::model()->getOneByCondition('mov_rate', "sale_id = '{$seller_uid}' AND stc_date = '{$date_time}' AND account_id = '{$report_account_id}' AND site = '{$site_name}'");
                                    if (!empty($row_optimization)) {
                                        $sav['optimization_sale_rate'] = $row_optimization['mov_rate'];
                                    }

                                    //人员7日刊登动销率，昨日动销率，30天动销率
                                    $row_listing = TaskPaneMovingRateAccount::model()->getOneByCondition('moving_rate_1, moving_rate_7, moving_rate_30', "sales_id = '{$seller_uid}' AND cal_date = '{$date_time}' AND account_id = '{$report_account_id}' AND site = '{$site_name}'");
                                    if (!empty($row_listing)) {
                                        $sav['listing_sale_rate'] = $row_listing['moving_rate_7'];
                                        $sav['y_sales_rate'] = $row_listing['moving_rate_1'];
                                        $sav['t_sales_rate'] = $row_listing['moving_rate_30'];
                                    }

                                    if (!empty($result)) {
                                        DashboardSellerStats::model()->update($sav, $result['id']);
                                    } else {
                                        $va = array_merge($sav, array('date_time' => $date_time, 'created_at' => date('Y-m-d H:i:s')));
                                        DashboardSellerStats::model()->saveData($va);
                                    }
                                }
                            }
                        }
                    } while ($flag);
                    //重新计算待刊登，刊登率
                    DashboardSellerStats::model()->calculate($platform);
                }

                $account_array = array();
                if (in_array($platform, array(Platform::CODE_ALIEXPRESS, Platform::CODE_LAZADA))) {
                    if ($platform == Platform::CODE_LAZADA) {
                        $account_array = LazadaAccount::getAccountToAccountId();
                    } else {
                        $account_array = AliexpressAccount::getAccountToAccountId();
                    }
                }

                //人员每日（30日）销售额数据
                $rows_one = CompanySales::model()->getDataByCondition("account_id, site, sales_id, total_rmb, day_flag", "platform_code = '{$platform}' AND del_flag = 1");
                if (!empty($rows_one)) {
                    foreach ($rows_one as $k => $v) {
                        //如果是Ali, Lazada，则账号还要转换
                        if (in_array($platform, array(Platform::CODE_ALIEXPRESS, Platform::CODE_LAZADA))) {
                            $accountid = isset($account_array[$v['account_id']]) ? $account_array[$v['account_id']] : 0;
                        } else {
                            $accountid = $v['account_id'];
                        }
                        $params = (1 == $v['day_flag']) ? array('y_sales' => $v['total_rmb']) : array('t_sales' => $v['total_rmb']);
                        DashboardSellerStats::model()->updateByCondition($params, " platform = '{$platform}' AND seller_user_id = '{$v['sales_id']}' AND account_id = '{$accountid}' AND date_time = '{$date_time}' AND UPPER(site_name) = '{$v['site']}'");
                    }
                }

                //人员每日（30日）净利润
                $rows_profit = TaskPaneProfitSales::model()->getDataByCondition("account_id, site, sales_id, retained_profits, day_flag", "platform_code = '{$platform}' AND del_flag = 1");
                if (!empty($rows_profit)) {
                    foreach ($rows_profit as $ke => $va) {
                        //如果是Ali，则账号还要转换
                        if (in_array($platform, array(Platform::CODE_ALIEXPRESS, Platform::CODE_LAZADA))) {
                            $accountid = isset($account_array[$va['account_id']]) ? $account_array[$va['account_id']] : 0;
                        } else {
                            $accountid = $va['account_id'];
                        }
                        $params = (1 == $va['day_flag']) ? array('y_earn' => $va['retained_profits']) : array('t_earn' => $va['retained_profits']);
                        DashboardSellerStats::model()->updateByCondition($params, " platform = '{$platform}' AND seller_user_id = '{$va['sales_id']}' AND account_id = '{$accountid}' AND date_time = '{$date_time}' AND UPPER(site_name) = '{$va['site']}'");
                    }
                }

                //人员每日（30日）订单数量
                $rows_orders = SellerOrderQty::model()->getDataByCondition("account_id, site, sales_id, order_quantity, day_flag", "platform_code = '{$platform}' AND del_flag = 1");
                if (!empty($rows_orders)) {
                    foreach ($rows_orders as $key => $val) {
                        //如果是Ali，则账号还要转换
                        if (in_array($platform, array(Platform::CODE_ALIEXPRESS, Platform::CODE_LAZADA))) {
                            $accountid = isset($account_array[$val['account_id']]) ? $account_array[$val['account_id']] : 0;
                        } else {
                            $accountid = $val['account_id'];
                        }
                        $params = (1 == $val['day_flag']) ? array('y_orders' => $val['order_quantity']) : array('t_orders' => $val['order_quantity']);
                        DashboardSellerStats::model()->updateByCondition($params, " platform = '{$platform}' AND seller_user_id = '{$val['sales_id']}' AND account_id = '{$accountid}' AND date_time = '{$date_time}' AND UPPER(site_name) = '{$val['site']}'");
                    }
                }

            }// end foreach

            //把销售人员的数据汇总到组长
            $this->Groupstats();
            //按组为纬度汇总数据
            $this->DepGroupstats();
            //按站点为纬度汇总数据
            $this->DepSitestats();
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $e->getMessage();
        }

        $execute_end = date('Y-m-d H:i:s');
        TaskLogModel::model()->saveData(
            array(
                'file_name' => $_SERVER['REQUEST_URI'],
                'start_time' => $execute_start,
                'end_time' => $execute_end,
                'message' => $message,
            )
        );
		echo "<br />End:".date("Y-m-d H:i:s")."<br />Done!!";
	}


	//把组长当日数据更新过来
	private function Groupstats()
	{
		$rows = DashboardSellerStats::model()->fetchDataBySeller();
		if (!empty($rows)) {
			foreach ($rows as $k => $v) {
				$seller_user_id = $v['seller_user_id'];
				$platform = $v['platform'];
				$date_time = date('Y-m-d');
				$params = array();
				if (0 < $seller_user_id) {
					//人员昨日（30日）销售额数据
					$rows_one = CompanySalesGroup::model()->getDataByCondition("sales_id, user_cn_name, total_rmb, department_id, department_name, group_id, group_name, day_flag", "platform_code = '{$platform}' AND sales_id = '{$seller_user_id}' AND del_flag = 1");
					if (!empty($rows_one)) {
						foreach ($rows_one as $ki => $vi) {
							$key = (1 == $vi['day_flag']) ? 'y_sales' : 't_sales';
							$params[$key] = $vi['total_rmb'];
							$params['seller_user_id'] = $vi['sales_id'];
							$params['seller_name'] = $vi['user_cn_name'];
							$params['group_id'] = $vi['group_id'];
							$params['group_name'] = $vi['group_name'];
							$params['department_id'] = $vi['department_id'];
							$params['department_name'] = $vi['department_name'];
						}
					}

					//人员每日（30日）净利润
					$rows_profit = TaskPaneProfitGroupSale::model()->getDataByCondition("retained_profits, day_flag", "platform_code = '{$platform}' AND del_flag = 1 AND sales_id = '{$seller_user_id}'");
					if (!empty($rows_profit)) {
						foreach ($rows_profit as $ke => $va) {
							$pkey = (1 == $va['day_flag']) ? 'y_profit' : 't_profit';
							$params[$pkey] = $va['retained_profits'];
						}
					}

                    //人员七日优化动销率
					$rows_optimization =  WaitOptSale::model()->getOneByCondition("mov_rate", "sale_id = '{$seller_user_id}' AND stc_date = '{$date_time}'");
                    if (!empty($rows_optimization)) {
                        $params['optimization_sale_rate'] = $rows_optimization['mov_rate'];
                    }

                    //人员7日刊登动销率，昨日动销率，30天动销率
                    $row_listing = TaskPaneMovingRateUser::model()->getOneByCondition('moving_rate_1, moving_rate_7, moving_rate_30', "sales_id = '{$seller_user_id}' AND cal_date = '{$date_time}'");
                    if (!empty($row_listing)) {
                        $params['listing_sale_rate'] = $row_listing['moving_rate_7'];
                        $params['y_sales_rate'] = $row_listing['moving_rate_1'];
                        $params['t_sales_rate'] = $row_listing['moving_rate_30'];
                    }

					//人员每日（30日）订单数量
					$rows_orders = SellerOrderQtyGroup::model()->getDataByCondition("order_quantity, day_flag", "platform_code = '{$platform}' AND del_flag = 1 AND sales_id = '{$seller_user_id}'");
					if (!empty($rows_orders)) {
						foreach ($rows_orders as $key => $val) {
							$okey = (1 == $val['day_flag']) ? 'y_orders' : 't_orders';
							$params[$okey] = $val['order_quantity'];
						}
					}
					$params['was_listing'] = $v['was_listing'];
					$params['pre_listing'] = $v['pre_listing'];
					$params['wait_listing'] = $v['wait_listing'];
					$params['sku_amount'] = $v['sku_amount'];
					$params['sku_main_amount'] = $v['sku_main_amount'];
					$params['collect_amount'] = $v['collect_amount'];
					$params['view_amount'] = $v['view_amount'];
					$params['platform'] = $platform;
					$params['date_time'] = date('Y-m-d');
					if (isset($params['seller_user_id'])) {
						$data = DashboardGroupStats::model()->getOneByCondition("id", "seller_user_id = '{$seller_user_id}' AND platform = '{$platform}' AND date_time = '{$date_time}'");
						//如果有数据，则更新，否则新增一条记录
						if (!empty($data)) {
							$result = DashboardGroupStats::model()->update($params, $data['id']);
						} else {
							$result = DashboardGroupStats::model()->saveData($params);
						}
					}
				}
			}
		}
	}


	/**
	 * 按部门，小组为纬度汇总数据
	 */
	private function DepGroupstats()
	{
		$rows = DashboardSellerStats::model()->fetchDataByDepGroup();
		$group_arr = SellerToGroupName::model()->groupName();
		$dep_arr = Department::model()->getDepartment();
		if (!empty($rows)) {
			foreach ($rows as $k => $v) {
				$group_id = $v['group_id'];
				$platform = $v['platform'];
				$date_time = date('Y-m-d');
				$params = array();

				if (0 < $group_id) {

					/*
					//人员昨日（30日）销售额数据
					$rows_one = CompanySalesGroup::model()->getDataByCondition("sales_id, user_cn_name, total_rmb, department_id, department_name, group_id, group_name, day_flag", "platform_code = '{$platform}' AND sales_id = '{$seller_user_id}' AND del_flag = 1");
					if (!empty($rows_one)) {
						foreach ($rows_one as $ki => $vi) {
							$key = (1 == $vi['day_flag']) ? 'y_sales' : 't_sales';
							$params[$key] = $vi['total_rmb'];
							$params['seller_user_id'] = $vi['sales_id'];
							$params['seller_name'] = $vi['user_cn_name'];
							$params['group_id'] = $vi['group_id'];
							$params['group_name'] = $vi['group_name'];
							$params['department_id'] = $vi['department_id'];
							$params['department_name'] = $vi['department_name'];
						}
					}

					//人员每日（30日）净利润
					$rows_profit = TaskPaneProfitGroupSale::model()->getDataByCondition("retained_profits, day_flag", "platform_code = '{$platform}' AND del_flag = 1 AND sales_id = '{$seller_user_id}'");
					if (!empty($rows_profit)) {
						foreach ($rows_profit as $ke => $va) {
							$pkey = (1 == $va['day_flag']) ? 'y_profit' : 't_profit';
							$params[$pkey] = $va['retained_profits'];
						}
					}

					//人员每日（30日）订单数量
					$rows_orders = SellerOrderQtyGroup::model()->getDataByCondition("order_quantity, day_flag", "platform_code = '{$platform}' AND del_flag = 1 AND sales_id = '{$seller_user_id}'");
					if (!empty($rows_orders)) {
						foreach ($rows_orders as $key => $val) {
							$okey = (1 == $val['day_flag']) ? 'y_orders' : 't_orders';
							$params[$okey] = $val['order_quantity'];
						}
					}
					*/

                    //人员七日优化动销率
                    $rows_optimization =  WaitOptGroup::model()->getOneByCondition("mov_rate", "group_id = '{$group_id}' AND stc_date = '{$date_time}'");
                    if (!empty($rows_optimization)) {
                        $params['optimization_sale_rate'] = $rows_optimization['mov_rate'];
                    }

                    //小组昨日，7日，30日动销率
                    $row_listing = TaskPaneMovingRateGroup::model()->getOneByCondition('moving_rate_1, moving_rate_7, moving_rate_30', "group_id = '{$group_id}' AND cal_date = '{$date_time}'");
                    if (!empty($row_listing)) {
                        $params['listing_sale_rate'] = $row_listing['moving_rate_7'];
                        $params['y_sales_rate'] = $row_listing['moving_rate_1'];
                        $params['t_sales_rate'] = $row_listing['moving_rate_30'];
                    }

					//获取组名
					$params['platform'] = $platform;
					$params['group_id'] = $v['group_id'];
					$params['group_name'] = isset($group_arr[$v['group_id']]) ? $group_arr[$v['group_id']] : '';
					$params['department_id'] = $v['department_id'];
					$params['department_name'] = isset($dep_arr[$v['department_id']]) ? $dep_arr[$v['department_id']] : '';
					$params['was_listing'] = $v['was_listing'];
					$params['pre_listing'] = $v['pre_listing'];
					$params['wait_listing'] = $v['wait_listing'];
					$params['sku_amount'] = $v['sku_amount'];
					$params['sku_main_amount'] = $v['sku_main_amount'];
					$params['collect_amount'] = $v['collect_amount'];
					$params['view_amount'] = $v['view_amount'];
					$params['date_time'] = date('Y-m-d');
					if (isset($params['group_id'])) {
						$data = DashboardDepGroupStats::model()->getOneByCondition("id", "group_id = '{$group_id}' AND platform = '{$platform}' AND date_time = '{$date_time}'");
						//如果有数据，则更新，否则新增一条记录
						if (!empty($data)) {
							$result = DashboardDepGroupStats::model()->update($params, $data['id']);
						} else {
							$result = DashboardDepGroupStats::model()->saveData($params);
						}
					}
				}
			}
		}
	}

	private function DepSitestats()
	{
		$rows = DashboardSellerStats::model()->fetchDataByDepSite();
		if (!empty($rows)) {
			foreach ($rows as $k => $v) {
				$site_name = $v['site_name'];
				$department_id = $v['department_id'];
				$platform = $v['platform'];
				$date_time = date('Y-m-d');
				$params = array();

				if (!empty($site_name)) {
					//人员昨日（30日）销售额数据
					$rows_one = CompanySalesDepSite::model()->getDataByCondition("total_rmb, department_id, department_name, day_flag", "platform_code = '{$platform}' AND site = '{$site_name}' AND department_id = '{$department_id}' AND del_flag = 1");
					if (!empty($rows_one)) {
						foreach ($rows_one as $ki => $vi) {
							$key = (1 == $vi['day_flag']) ? 'y_sales' : 't_sales';
							$params[$key] = $vi['total_rmb'];
							$params['department_name'] = $vi['department_name'];
						}
					}

					//人员每日（30日）净利润
					$rows_profit = TaskPaneProfitDepSiteSale::model()->getDataByCondition("retained_profits, day_flag", "platform_code = '{$platform}' AND del_flag = 1 AND site = '{$site_name}' AND department_id = '{$department_id}'");
					if (!empty($rows_profit)) {
						foreach ($rows_profit as $ke => $va) {
							$pkey = (1 == $va['day_flag']) ? 'y_profit' : 't_profit';
							$params[$pkey] = $va['retained_profits'];
						}
					}

					//人员每日（30日）订单数量
					$rows_orders = DepSiteOrderQty::model()->getDataByCondition("order_quantity, day_flag", "platform_code = '{$platform}' AND del_flag = 1 AND site = '{$site_name}' AND department_id = '{$department_id}'");
					if (!empty($rows_orders)) {
						foreach ($rows_orders as $key => $val) {
							$okey = (1 == $val['day_flag']) ? 'y_orders' : 't_orders';
							$params[$okey] = $val['order_quantity'];
						}
					}

                    //站点七日优化动销率
                    $rows_optimization =  WaitOptSite::model()->getOneByCondition("mov_rate", "platform_code = '{$platform}' AND  department_id = '{$department_id}' AND site = '{$site_name}' AND stc_date = '{$date_time}'");
                    if (!empty($rows_optimization)) {
                        $params['optimization_sale_rate'] = $rows_optimization['mov_rate'];
                    }

                    //站点昨日，7日，30日动销率
                    $row_listing = TaskPaneMovingRateSite::model()->getOneByCondition('moving_rate_1, moving_rate_7, moving_rate_30', "platform_code = '{$platform}' AND  department_id = '{$department_id}' AND site = '{$site_name}' AND cal_date = '{$date_time}'");
                    if (!empty($row_listing)) {
                        $params['listing_sale_rate'] = $row_listing['moving_rate_7'];
                        $params['y_sales_rate'] = $row_listing['moving_rate_1'];
                        $params['t_sales_rate'] = $row_listing['moving_rate_30'];
                    }

					//获取组名
					$params['platform'] = $platform;
					$params['site_id'] = $v['site_id'];
					$params['site_name'] = $site_name;
					$params['department_id'] = $v['department_id'];
					$params['department_name'] = isset($dep_arr[$v['department_id']]) ? $dep_arr[$v['department_id']] : '';
					$params['was_listing'] = $v['was_listing'];
					$params['pre_listing'] = $v['pre_listing'];
					$params['wait_listing'] = $v['wait_listing'];
					$params['sku_amount'] = $v['sku_amount'];
					$params['sku_main_amount'] = $v['sku_main_amount'];
					$params['collect_amount'] = $v['collect_amount'];
					$params['view_amount'] = $v['view_amount'];
					$params['date_time'] = date('Y-m-d');

					$data = DashboardDepSiteStats::model()->getOneByCondition("id", "department_id = '{$department_id}' AND site_name = '{$site_name}' AND platform = '{$platform}' AND date_time = '{$date_time}'");
					//如果有数据，则更新，否则新增一条记录
					if (!empty($data)) {
						$result = DashboardDepSiteStats::model()->update($params, $data['id']);
					} else {
						$result = DashboardDepSiteStats::model()->saveData($params);
					}
				}
			}
		}
	}


}