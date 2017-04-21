<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/1
 * Time: 14:22
 **/
class TaskdashboardController extends TaskBaseController
{
	public function accessRules()
	{
		return array();
	}

	/**
	 * 任务控制面板
	 */
	public function actionIndex()
	{
		//获取当前登录人员所属组别
        $role_num = $this->roleNum();
        $role = Yii::app()->request->getParam('role', 'seller');
        if (in_array($role, array('manager', 'leader'))) {
            Yii::app()->session['login_role'] = $role;
        }
        $role = Yii::app()->session['login_role'];
        //当前月天数
        $days = date("t");
        //报表类相关的要显示延迟一天的数据
        $report_days = date("t", strtotime("-1 days"));

        $config = ConfigFactory::getConfig('serverKeys');
        $groupRank_amount = CompanyRankGroup::model()->totalAmount();
        $platform = $this->userPlatform();
        //面板指定的Id
        $page_id = Menu::model()->find('menu_url =:menu_url', array(':menu_url' => '/task/taskdashboard/index'))->id;
        if (empty(Yii::app()->user->id)) {
            echo "登录超时，请重新登录";
            exit;
        }
        //销售，组长，销售+组长，组长+主管
        if ((0 < $role_num) && ('manager' != $role)) {
            //如果是有两个角色（销售+组长)
            if (2 == $role_num) {
                $default_job_id = isset(Yii::app()->session['role_job_id']) ? Yii::app()->session['role_job_id'] : ProductsGroupModel::GROUP_SALE;
                $req_job_id = Yii::app()->request->getParam('job_id', $default_job_id);
            } else {
                //组长，销售间转换
                $req_job_id = ProductsGroupModel::model()->find("group_id > 0 AND seller_user_id =:seller_user_id AND is_del = 0",
                                array(":seller_user_id" => Yii::app()->user->id)
                )->job_id;
            }

            Yii::app()->session['role_job_id'] = $req_job_id;
            $group = $this->group();
            //获取小组排名
            $groupRank = $this->groupRank();

            //获取人员的基本信息（获取照片，入职时间）
            $user_info = $this->userInfo(Yii::app()->user->full_name, Yii::app()->user->department_id);
            if (ProductsGroupModel::GROUP_SALE == $group->job_id) {
                try {
                    //取得负责的SKU总数
                    $sale_stats = $this->saleStats();

                    $seller_user_id = Yii::app()->user->id;
                    //获取人员已刊登及已优化的任务量
                    $yesterdayData = $this->yesterdayListingOptimization(array($seller_user_id), $platform);
                    //每日需刊登优化的数量
                    $data = $this->waitingListingOptimization(array($seller_user_id));

                    //获取所属组长
                    $leaderName = SellerUserToJob::model()
                        ->find('group_id=:group_id AND job_id = 1 AND is_del = 0',
                            array(':group_id' => $group->group_id)
                        )
                        ->seller
                        ->user_full_name;


                    //获取部分负责人
                    $manager = '';
                    $department_id = User::model()->getDepIdById($seller_user_id);
                    $person_liable = Department::model()->find('id=:id', array(':id' => $department_id))->person_liable;
                    if (!empty($person_liable)) {
                        $manager = User::model()->find('id=:id', array(':id' => $person_liable))->user_full_name;
                    }

                    //获取排名情况
                    $rank_info = $this->dashboard_rank();
                    //获取销售目标，利润
                    $sales_info = $this->dashboard_sales_detail();
                    //获取订单数，刊登动销率，优化动销率
                    $orders_info = $this->dashboard_order();


/*

                    //年度销售额
                    $sales_target = $this->salesTarget(array($seller_user_id));
                    //年度净利润
                    $profit_target = $this->profitTarget(array($seller_user_id));

                    //昨日销售额
                    $yesterdaySales = $this->yesterdaySales();
                    $yesterdayProfit = $this->yesterdayProfit();

                    //本月净利润
                    $monthInfo = $this->monthInfo();

                    //获取当月销售目标额
                    $sales_amount = $this->salesAmount(array($seller_user_id));

                    //获取当月净利润目标额
                    $profit_amount = $this->profitAmount(array($seller_user_id));

                    //计算当月利润的要延迟一天
                    $avg_sales_amount = sprintf("%d", ($sales_amount / $report_days));

                    $avg_profit_amount = sprintf("%d", ($profit_amount / $report_days));

                    //年销售额汇总
                    $yearSalesInfo = $this->yearSalesInfo();
 */



                    //取消的订单
                    $cancelOrdersInfo = $this->cancelOrdersInfo();

                    //累计订单数理
                    $ordersInfo = $this->ordersInfo();

                    //重发数
                    $repeatInfo = $this->repeatInfo();

                    //昨日订单数
                    $yesterdayOrders = $this->yesterdayOrders();

                    //异常订单数
                    $exceptionOrders = $this->exceptionOrders();

                    //待发货订单数
                    $noShippedOrders = $this->noShippedOrders();

                    $optimizationRate = $this->optimizationRate();

                    $listingRateData = $this->listingRate();

                    $this->render('seller', array(
                        'header_icon' => array('exists' => !empty($user_info['emp_pic']) ? 1 : 0, 'url' => $config['oms']['host'] . $user_info['emp_pic']),
                        'service_days' => $this->service_days($user_info['emp_joinDate']),
                        'leader' => $leaderName,
                        'manager' => $manager,
                        'data' => array_merge($data, $yesterdayData),
                        'sales_info' => $sales_info,
                        'days' => $days, //当月天数
                        'report_days' => $report_days, //报表相关的数据要延迟一天
                        'sale_stats' => $sale_stats,
                        'rank_info' => $rank_info,
                        'orders_info' => $orders_info,

                        'cancelOrdersInfo' => $cancelOrdersInfo,
                        'ordersInfo' => $ordersInfo,
                        'repeatInfo' => $repeatInfo,
                        'yesterdayOrders' => $yesterdayOrders,
                        'exceptionOrders' => $exceptionOrders,
                        'noShippedOrders' => $noShippedOrders,

                        'page_id' => $page_id,
                        'role_num' => $role_num,
                        'optimizationRate' => $optimizationRate,
                        'listingRateData' => $listingRateData
                    ));
                } catch (Exception $e) {
                    echo $e->getMessage();
                    exit;
                }
            } else {
                //获取组长的信息
                try {
                    $group_data = SellerUserToJob::model()
                        ->find('seller_user_id=:seller_user_id AND is_del=:is_del AND job_id =:job_id AND group_id >:group_id',
                            array(':seller_user_id' => Yii::app()->user->id, ':is_del' => 0, ':job_id' => 1, ':group_id'=>0)
                        );
                    $group_id = $group_data->group_id;
                    $leader_user_id = $group_data->seller_user_id;

                    //获取组别名称
                    $group_name = SellerToGroupName::model()
                                ->find('id =:id',
                                    array(':id' => $group_id)
                                )->group_name;

                    //获取组长的名称
                    $leaderName = User::model()->find('id =:id', array(':id' => $leader_user_id))->user_full_name;

                    //获取此组长下的所有组员id
                    $teams_arr = $this->groupUsers($group_id);
                    //根据组员id获取组员的联系人信息
                    $teamer = array();
                    if (!empty($teams_arr)) {
                        $teamer = User::model()->getUserListByIDs($teams_arr);
                    }

                    //获取部门负责人
                    $manager = '';
                    $department_id = User::model()->getDepIdById(Yii::app()->user->id);
                    $person_liable = Department::model()->find('id=:id', array(':id' => $department_id))->person_liable;
                    if (!empty($person_liable)) {
                        $manager = User::model()->find('id=:id', array(':id' => $person_liable))->user_full_name;
                    }

                    //获取昨日整个组员的刊登、优化信息汇总（组长+组员）
                    $users = !empty($teams_arr) ? array_merge($teams_arr, array(Yii::app()->user->id)) : array(Yii::app()->user->id);

                    //获取本月已刊登，已优化的数量
                    $yesterdayData = $this->yesterdayListingOptimization($users, $platform);
                    //销售目标（年）
                    $sales_target = $this->salesTarget($users);
                    //利润目标（年）
                    $profit_target = $this->profitTarget($users);

                    //获取当月销售目标额
                    $sales_amount = $this->salesAmount($users);

                    //获取当月净利润目标额
                    $profit_amount = $this->profitAmount($users);

                    //每日目标销售额
                    $avg_sales_amount = sprintf("%d", ($sales_amount / $report_days));

                    //每日目标利润额
                    $avg_profit_amount = sprintf("%d", ($profit_amount / $report_days));

                    $monthInfo = $this->monthInfo('leader', $group_id);

                    $yearSalesInfo = $this->yearSalesInfo('leader', $group_id);

                    //昨日销售
                    $yesterdaySales = $this->yesterdaySales('leader', $group_id);

                    //昨日利润
                    $yesterdayProfit = $this->yesterdayProfit('leader', $group_id);

                    //累计订单数理
                    $ordersInfo = $this->ordersInfo('leader', $group_id);

                    //昨日订单数
                    $yesterdayOrders = $this->yesterdayOrders('leader', $group_id);

                    //取消的订单
                    $cancelOrdersInfo = $this->cancelOrdersInfo('leader', $group_id);

                    //异常订单数
                    $exceptionOrders = $this->exceptionOrders('leader', $group_id);

                    //重发数
                    $repeatInfo = $this->repeatInfo('leader', $group_id);

                    //待发货订单数
                    $noShippedOrders = $this->noShippedOrders('leader', $group_id);


                    $sale_stats = $this->saleStats('leader', $group_id);


                    $exception_num = $this->wiatException($group_id);

                    $optimizationRate = $this->optimizationRate('leader', $group_id);

                    $listingRateData = $this->listingRate('leader', $group_id);
                    //获取今日待刊登、待优化信息汇总
                    $data = $this->waitingListingOptimization($users);
                    $this->render('leader', array(
                            'header_icon' => array('exists' => !empty($user_info['emp_pic']) ? 1 : 0, 'url' => $config['oms']['host'] . $user_info['emp_pic']),
                            'service_days' => $this->service_days($user_info['emp_joinDate']),
                            'manager' => $manager,
                            'data' => array_merge($data, $yesterdayData),
                            'group_name' => $group_name,
                            'leaderName' => $leaderName,
                            'teamer' => $teamer,
                            'sales_target' => $sales_target,
                            'profit_target' => $profit_target,
                            'sales_amount' => $sales_amount,
                            'profit_amount' => $profit_amount,
                            'avg_sales_amount' => $avg_sales_amount,
                            'avg_profit_amount' => $avg_profit_amount,
                            'days' => $days, //当月天数
                            'report_days' => $report_days, //报表相关的数据要延迟一天
                            'monthInfo' => $monthInfo,
                            'yearSalesInfo' => $yearSalesInfo,
                            'yesterdaySales' => $yesterdaySales,
                            'ordersInfo' => $ordersInfo,
                            'yesterdayProfit' => $yesterdayProfit,
                            'groupRank' => $groupRank,
                            'groupRank_amount' => $groupRank_amount,
                            'yesterdayOrders' => $yesterdayOrders,
                            'cancelOrdersInfo' => $cancelOrdersInfo,
                            'repeatInfo' => $repeatInfo,
                            'exceptionOrders' => $exceptionOrders,
                            'noShippedOrders' => $noShippedOrders,
                            'data_list' => $this->dataList($group_id),
                            'sale_stats' => $sale_stats,
                            'page_id' => $page_id,
                            'role_num' => $role_num,
                            'role' => $role,
                            'exception_num' => $exception_num,
                            'optimizationRate' => $optimizationRate,
                            'listingRateData' => $listingRateData,
                        )
                    );
                } catch (Exception $e) {
                    echo $e->getMessage();
                    exit;
                }
            }
        }else {
			//判断是否为主管，仅仅只有主管身份
			$user_info = $this->userInfo(Yii::app()->user->full_name, Yii::app()->user->department_id);
			//获取部分负责人
			$manager = '';
			$person_liable = Department::model()->find('id=:id', array(':id' => Yii::app()->user->department_id))->person_liable;
			if (!empty($person_liable)) {
				$manager = User::model()->find('id=:id', array(':id' => $person_liable))->user_full_name;
			}
			$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, $this->userPlatform());
			if ($check_result) {
				//取得当前登的主管所在的部门
				$department_id = User::model()->getDepIdById(Yii::app()->user->id);

				//根据部门Id获取此部门下所有销售人员的Id
				$rows = User::model()->getUserNameByDeptID(array($department_id), true);
				$users = array_keys($rows); //返回销售的数组

				//目标销售额
				$sales_target = $this->salesTarget($users);
				//利润目标（年）
				$profit_target = $this->profitTarget($users);

				//部分年销售额
				$yearSalesInfo = $this->yearSalesInfo('manager', $department_id);

				//获取当月销售目标额
				$sales_amount = $this->salesAmount($users);

				//获取当月净利润目标额
				$profit_amount = $this->profitAmount($users);

				//每日目标销售额
				$avg_sales_amount = sprintf("%d", ($sales_amount / $report_days));

				//每日目标利润额
				$avg_profit_amount = sprintf("%d", ($profit_amount / $report_days));

				//当月数据
				$monthInfo = $this->monthInfo('manager', $department_id);

				//昨日数据
				$yesterdaySales = $this->yesterdaySales('manager', $department_id);

				//昨日利润
				$yesterdayProfit = $this->yesterdayProfit('manager', $department_id);

				//累计订单数理
				$ordersInfo = $this->ordersInfo('manager', $department_id);

				//取消的订单
				$cancelOrdersInfo = $this->cancelOrdersInfo('manager', $department_id);

				//重发数
				$repeatInfo = $this->repeatInfo('manager', $department_id);

				//昨日订单数
				$yesterdayOrders = $this->yesterdayOrders('manager', $department_id);

				//重发订单数
				$exceptionOrders = $this->exceptionOrders('manager', $department_id);

				//待发货订单数
				$noShippedOrders = $this->noShippedOrders('manager', $department_id);

				$yesterdayData = $this->yesterdayListingOptimization($users, $platform);
				$data = $this->waitingListingOptimization($users);

				//概览
				$sale_stats = $this->saleStats('manager', $department_id);

                $optimizationRate = $this->optimizationRate('manager', $department_id);

                $listingRateData = $this->listingRate('manager', $department_id);

				$this->render('manager', array(
					'header_icon' => array('exists' => !empty($user_info['emp_pic']) ? 1 : 0, 'url' => $config['oms']['host'] . $user_info['emp_pic']),
					'groupRank_amount' => $groupRank_amount,
					'group_list' => $this->group_list($department_id),
					'data' => array_merge($data, $yesterdayData),
					'sales_target' => $sales_target,
					'profit_target' => $profit_target,
					'department' => Department::model()->getDepartment($department_id),
					'manager' => $manager,
					'service_days' => $this->service_days($user_info['emp_joinDate']),
					'yearSalesInfo' => $yearSalesInfo,
					'sales_amount' => $sales_amount,
					'profit_amount' => $profit_amount,
					'avg_sales_amount' => $avg_sales_amount,
					'avg_profit_amount' => $avg_profit_amount,
					'days' => $days, //当月天数
                    'report_days' => $report_days, //报表相关的数据要延迟一天
					'monthInfo' => $monthInfo,
					'yesterdaySales' => $yesterdaySales,
					'yesterdayProfit' => $yesterdayProfit,
					'ordersInfo' => $ordersInfo,
					'cancelOrdersInfo' => $cancelOrdersInfo,
					'repeatInfo' => $repeatInfo,
					'yesterdayOrders' => $yesterdayOrders,
					'exceptionOrders' => $exceptionOrders,
					'noShippedOrders' => $noShippedOrders,
					'sale_stats' => $sale_stats,
					'data_group_list' => $this->dataGroupList($department_id),
					'data_site_list' => $this->dataSiteList($department_id),
                    'page_id' => $page_id,
                    'role_num' => $role_num,
                    'role' => $role,
                    'optimizationRate' => $optimizationRate,
                    'listingRateData' => $listingRateData,
				));
			} else {
				echo '您所属的组暂未开放';
			}
		}
	}

	/**
	 * @param $name
	 * @param $dep_id
	 * @return array|CActiveRecord|mixed|null
	 */
	private function userInfo($name, $dep_id)
	{
		$row = Hrms::model()->find('emp_name =:emp_name AND emp_dept =:emp_dept', array(':emp_name' => $name, ':emp_dept' => $dep_id));
		return $row;
	}


	/**
	 * @param $join_date
	 * @return float|int
	 *
	 * 计算为公司工作日
	 */
	private function service_days($join_date)
	{
		if (empty($join_date)) {
			return 0;
		}

		$start = strtotime($join_date);
		$end = strtotime(date("Y-m-d"));
		$days = ceil(abs($end - $start) / 86400);

		return $days;
	}

	/**
	 * @param $users
	 * @return mixed
	 *
	 * 取得小组或者销售个人每天需刊登及优化的总数
	 */
	private function waitingListingOptimization($users)
	{
		//获取每天需要刊登及优化的数量总量
        $platform = $this->userPlatform();
		$data = $this->listingOptimizationModel($platform)->getListOptimizationNum($users);
		return $data;
	}


	/**
	 * @param array $users
	 * @return int
	 */
	private function salesTarget($users = array())
	{
		$model = $this->model('sales');
		return (empty($users)) ? 0 : $model->getSum($users);
	}


	private function profitTarget($users = array())
	{
		$model = $this->model('sales');
		return (empty($users)) ? 0 : $model->getSum($users, 'profit_target');
	}


	private function salesAmount($users = array())
	{
		$model = $this->model('sales_extend');
		return (empty($users)) ? 0 : $model->getSum($users);
	}


	private function profitAmount($users = array())
	{
		$model = $this->model('sales_extend');
		return (empty($users)) ? 0 : $model->getSum($users, 'profit_amount');
	}

	/**
	 * @return array|CActiveRecord|mixed|null
	 *
	 * @param $type
	 * @param $id
	 *
	 * 获取当前登录SKU数
	 */
	private function saleStats($type = 'sales', $id = 0)
	{
		$model = $this->model('report');
		$date_time = date('Y-m-d');
		$row = array();
		if ('sales' == $type) {
			$user_id = Yii::app()->user->id;
			$row = $model->getOneByCondition("*", "seller_user_id = '{$user_id}' AND date_time = '{$date_time}'");
		} elseif ('leader' == $type && 0 < $id) {
			$row = $model->getOneByCondition("SUM(sku_count) AS sku_count, SUM(sku_main_count) AS sku_main_count, SUM(pre_count) AS pre_count, SUM(pre_main_count) AS pre_main_count, 
											  SUM(sales_count) AS sales_count, SUM(sales_main_count) AS sales_main_count, SUM(clean_count) AS clean_count, SUM(clean_main_count) AS clean_main_count, 
											  SUM(was_listing) AS was_listing, SUM(was_main_listing) AS was_main_listing, SUM(pre_listing) AS pre_listing, SUM(pre_main_listing) AS pre_main_listing",
				"group_id = '{$id}' AND date_time = '{$date_time}'");
		} elseif ('manager' == $type && 0 < $id) {
			$row = $model->getOneByCondition("SUM(sku_count) AS sku_count, SUM(sku_main_count) AS sku_main_count, SUM(pre_count) AS pre_count, SUM(pre_main_count) AS pre_main_count, 
											  SUM(sales_count) AS sales_count, SUM(sales_main_count) AS sales_main_count, SUM(clean_count) AS clean_count, SUM(clean_main_count) AS clean_main_count, 
											  SUM(was_listing) AS was_listing, SUM(was_main_listing) AS was_main_listing, SUM(pre_listing) AS pre_listing, SUM(pre_main_listing) AS pre_main_listing",
				"department_id = '{$id}' AND date_time = '{$date_time}'");
		}
		return $row;
	}


	private function personRank()
	{
		$data = array();
		$user_id = Yii::app()->user->id;
		$row = CompanySalesRankPerson::model()->getDataByCondition("rank, rank_flag", " del_flag = 1 AND sales_id = '{$user_id}'");
		if (!empty($row)) {
			foreach ($row as $k => $v) {
				$data[$v['rank_flag']] = $v['rank'];
			}
		}
		return $data;
	}


	private function groupRank()
	{
		$group = $this->group();
		$group_id = $group->group_id;
		$row = CompanyRankGroup::model()->getOneByCondition("group_name, rank", "group_id = '{$group_id}' AND del_flag = 1");
		return $row;
	}

	private function newPersonRank()
	{
		$user_id = Yii::app()->user->id;
		$row = CompanyRankNewperson::model()->getOneByCondition("rank", "sales_id = '{$user_id}' AND del_flag = 1");
		return $row;
	}

	/**
	 * @param $type
	 * @param $id
	 * @param $day_flag
	 * @return array
	 *
	 * 获取销售昨日的销售额
	 */
	private function yesterdaySales($type = 'sales', $id = 0, $day_flag = 1)
	{
		$row = array();
		if ('sales' == $type) {
			$user_id = Yii::app()->user->id;
			$row = CompanySales::model()->getOneByCondition("SUM(total_rmb) AS total_rmb", "sales_id = '{$user_id}' AND day_flag = '{$day_flag}'");
		} elseif ('leader' == $type && 0 < $id) {
			$row = CompanyGroup::model()->getOneByCondition("SUM(total_rmb) AS total_rmb", "group_id = '{$id}' AND day_flag = '{$day_flag}'");
		} elseif ('manager' == $type && 0 < $id) {
			$row = CompanyDepartment::model()->getOneByCondition("SUM(total_rmb) AS total_rmb", "department_id = '{$id}' AND day_flag = '{$day_flag}'");
		}
		return $row;
	}


	/**
	 * @param $type
	 * @param $id
	 * @param $day
	 * @return array
	 *
	 * 获取销售昨日的净利额
	 */
	private function yesterdayProfit($type = 'sales', $id = 0, $day = 1)
	{
		$row = array();
		if ('sales' == $type) {
			$user_id = Yii::app()->user->id;
			$row = TaskPaneProfitSales::model()->getOneByCondition("SUM(retained_profits) AS retained_profits", "sales_id = '{$user_id}' AND day_flag = '{$day}'");
		} elseif ('leader' == $type && 0 < $id) {
			$row = TaskPaneProfitGroup::model()->getOneByCondition("retained_profits", "group_id = '{$id}' AND day_flag = '{$day}'");
		} elseif ('manager' == $type && 0 < $id) {
			$row = TaskPaneProfitDepartment::model()->getOneByCondition("retained_profits", "department_id = '{$id}' AND day_flag = '{$day}'");
		}
		return $row;
	}


	private function monthInfo($type = 'sales', $id = 0)
	{
	    //面板上显示的月份应该要延迟一天显示
		$date_time = date("Y-m-01", strtotime("-1 days"));
		$data = array();
		if ('sales' == $type) {
			$user_id = Yii::app()->user->id;
			$data = DashboardSalesProfit::model()->getOneByCondition("*", "seller_user_id = '{$user_id}' AND date_time = '{$date_time}'");
		} elseif ('leader' == $type && 0 < $id) {
			$data = DashboardSalesProfit::model()->getOneByCondition("SUM(sales_amount_rmb) AS sales_amount_rmb, SUM(retained_profits) AS retained_profits", "group_id = '{$id}' AND date_time = '{$date_time}'");
		} elseif ('manager' == $type && 0 < $id) {
			$data = DashboardSalesProfit::model()->getOneByCondition("SUM(sales_amount_rmb) AS sales_amount_rmb, SUM(retained_profits) AS retained_profits", "department_id = '{$id}' AND date_time = '{$date_time}'");
		}
		return $data;
	}

	private function yearSalesInfo($type = 'sales', $id = 0)
	{
		$user_id = Yii::app()->user->id;
		$start = date('Y-01-01');
		$end = date('Y-12-01');
		$data = array();
		$condition = "";
		if ('sales' == $type) {
			$condition = " seller_user_id = '{$user_id}'";
		} elseif ('leader' == $type && 0 < $id) {
			$condition = " group_id = '{$id}'";
		} elseif ('manager' == $type && 0 < $id) {
			$condition = "  department_id = '{$id}'";
		}
		if ('' != $condition) {
			$data = DashboardSalesProfit::model()->sumData("SUM(sales_amount_rmb) AS sales_total, SUM(retained_profits) AS profit_total",
				" {$condition} AND '{$start}' <= date_time AND date_time <= '{$end}'");
		}

		return $data;
	}

	/**
	 * @param $type
	 * @param $id
	 * @param $day_flag
	 * @return mixed
	 * 人员当月取消订单量
	 */
	private function cancelOrdersInfo($type = 'sales', $id = 0)
	{
		$date_time = date('Y-m-01');
		$row = array();
		if ('sales' == $type) {
			$user_id = Yii::app()->user->id;
			$row = OrdersCancelUsers::model()->getOneByCondition('cancel_num', "sales_id = '{$user_id}' AND paytime = '{$date_time}'");
		} elseif ('leader' == $type && 0 < $id) {
			$row = OrdersCancelGroup::model()->getOneByCondition('cancel_num', "group_id = '{$id}' AND paytime = '{$date_time}'");
		} elseif ('manager' == $type && 0 < $id) {
			$row = OrdersCancelDepartment::model()->getOneByCondition('cancel_num', "department_id = '{$id}' AND paytime = '{$date_time}'");
		}

		return $row;
	}


	/**
	 * @param $type
	 * @param $id
	 * @return mixed
	 * 人员当月（自然月）订单量
	 */
	private function ordersInfo($type = 'sales', $id = 0)
	{
		$row = array();
		$date_time = date('Y-m-01');
		if ('sales' == $type) {
			$user_id = Yii::app()->user->id;
			$row = TaskPanUserOrder::model()->getOneByCondition('order_num', "sales_id = '{$user_id}' AND paytime = '{$date_time}'");
		} elseif ('leader' == $type && 0 < $id) {
			$row = TaskPanGroupOrder::model()->getOneByCondition('order_num', "group_id = '{$id}' AND paytime = '{$date_time}'");
		} elseif ('manager' == $type && 0 < $id) {
			$row = TaskPaneDepartmentOrder::model()->getOneByCondition('order_num', "department_id = '{$id}' AND paytime = '{$date_time}'");
		}

		return $row;
	}


    /**
     * @param $type
     * @param $id
     * @return mixed
     * 优化动销率
     */
    private function optimizationRate($type = 'sales', $id = 0)
    {
        $row = array();
        $date_time = date('Y-m-d');
        if ('sales' == $type) {
            $user_id = Yii::app()->user->id;
            $row = WaitOptSale::model()->getOneByCondition('mov_rate', "sale_id = '{$user_id}' AND stc_date = '{$date_time}'");
        } elseif ('leader' == $type && 0 < $id) {
            $row = WaitOptGroup::model()->getOneByCondition('mov_rate', "group_id = '{$id}' AND stc_date = '{$date_time}'");
        } elseif ('manager' == $type && 0 < $id) {
            $row = WaitOptDept::model()->getOneByCondition('mov_rate', "department_id = '{$id}' AND stc_date = '{$date_time}'");
        }

        return $row;
    }


    private function listingRate($type = 'sales', $id = 0)
    {
        $row = array();
        $date_time = date('Y-m-d');
        if ('sales' == $type) {
            $user_id = Yii::app()->user->id;
            $row = TaskPaneMovingRateUser::model()->getOneByCondition('moving_rate_1, moving_rate_7, moving_rate_30', "sales_id = '{$user_id}' AND cal_date = '{$date_time}'");
        } elseif ('leader' == $type && 0 < $id) {
            $row = TaskPaneMovingRateGroup::model()->getOneByCondition('moving_rate_1, moving_rate_7, moving_rate_30', "group_id = '{$id}' AND cal_date = '{$date_time}'");
        } elseif ('manager' == $type && 0 < $id) {
            $row = TaskPaneMovingRateDep::model()->getOneByCondition('moving_rate_1, moving_rate_7, moving_rate_30', "department_id = '{$id}' AND cal_date = '{$date_time}'");
        }
        return $row;
    }


	/***
	 * @param $type
	 * @param $id
	 * @return mixed
	 * 人员当月（自然月）包裹重发数
	 */
	private function repeatInfo($type = 'sales', $id = 0)
	{
		$date_time = date('Y-m-01');
		$row = array();
		if ('sales' == $type) {
			$user_id = Yii::app()->user->id;
			$row = TaskPanUserRepeat::model()->getOneByCondition('repaeat_qty AS repeat_qty', "sales_id = '{$user_id}' AND ship_date = '{$date_time}'");
		} elseif ('leader' == $type && 0 < $id) {
			$row = TaskPanGroupRepeat::model()->getOneByCondition('repaeat_qty AS repeat_qty', "group_id = '{$id}' AND ship_date = '{$date_time}'");
		} elseif ('manager' == $type && 0 < $id) {
			$row = TaskPaneDepartmentRepeat::model()->getOneByCondition('repaeat_qty AS repeat_qty', "department_id = '{$id}' AND ship_date = '{$date_time}'");
		}

		return $row;
	}

	/**
	 * @param $type
	 * @param $id
	 * @param $day_flag
	 * @return mixed
	 *
	 *
	 * 昨日或者最近30日订单数
	 */
	private function yesterdayOrders($type = 'sales', $id = 0, $day_flag = 1)
	{
		$row = array();
		if ('sales' == $type) {
			$user_id = Yii::app()->user->id;
			$row = SellerOrderQty::model()->sumAmount('order_quantity', $user_id);
		} elseif ('leader' == $type && 0 < $id) {
			$row = GroupOrderQty::model()->getOneByCondition('order_quantity', "group_id = '{$id}' AND day_flag = '{$day_flag}'");
		} elseif ('manager' == $type && 0 < $id) {
			$row = DepartmentOrderQty::model()->getOneByCondition('order_quantity', "department_id = '{$id}' AND day_flag = '{$day_flag}'");
		}

		return $row;
	}

	/**
	 * @param $type
	 * @param $id
	 * @return mixed
	 * 待处理亏损订单
	 */
	private function exceptionOrders($type = 'sales', $id = 0)
	{
		$row = array();
		if ('sales' == $type) {
			$user_id = Yii::app()->user->id;
			$row = SellerExceptionOrder::model()->getOneByCondition('exp_qty', "sales_id = '{$user_id}' AND del_flag = 1");
		} elseif ('leader' == $type && 0 < $id) {
			$row = GroupExceptionOrder::model()->getOneByCondition('exp_qty', "group_id = '{$id}' AND del_flag = 1");
		} elseif ('manager' == $type && 0 < $id) {
			$row = DepartmentExceptionOrder::model()->getOneByCondition('exp_qty', "department_id = '{$id}' AND del_flag = 1");
		}
		return $row;
	}

	/**
	 * @param $type
	 * @param $id
	 * @return mixed
	 * 待发货订单
	 */
	private function noShippedOrders($type = 'sales', $id = 0)
	{
		$row = array();
		if ('sales' == $type) {
			$user_id = Yii::app()->user->id;
			$row = SellerNoShippedOrders::model()->getOneByCondition('order_num', " del_flag = 1 AND sales_id = '{$user_id}'");
		} elseif ('leader' == $type && 0 < $id) {
			$row = GroupNoShippedOrders::model()->getOneByCondition('order_num', " del_flag = 1 AND group_id = '{$id}'");
		} elseif ('manager' == $type && 0 < $id) {
			$row = DepartmentNoShippedOrders::model()->getOneByCondition('order_num', " del_flag = 1 AND department_id = '{$id}'");
		}

		return $row;
	}

	/**
	 * @param $group_id
	 *
	 * 获取组长底部的数据列表
	 */
	private function dataList($group_id)
	{
		$date_time = date('Y-m-d');
		$rows = DashboardGroupStats::model()->getDataByCondition("*", " group_id = '{$group_id}' AND date_time = '{$date_time}'");
		return $rows;
	}


	private function dataGroupList($department_id)
	{
		$date_time = date('Y-m-d');
		$rows = DashboardDepGroupStats::model()->getDataByCondition("*", " department_id = '{$department_id}' AND date_time = '{$date_time}'");
		return $rows;
	}


	private function dataSiteList($department_id)
	{
		$date_time = date('Y-m-d');
		$rows = DashboardDepSiteStats::model()->getDataByCondition("*", " department_id = '{$department_id}' AND date_time = '{$date_time}'");
		return $rows;

	}

	private function wiatException($group_id)
    {
        $user_list = $this->groupUsers($group_id);
        $model = $this->model('exception', $this->userPlatform());
        $data = $model->getOneByCondition('count(id) AS appeal_total', " appeal_status = 1 AND seller_user_id IN('".join("','", $user_list)."')");
        return $data['appeal_total'];
    }

	/**
	 * @param $department_id
	 * @return array|mixed|null
	 *
	 * 返回当前部门所有组排名
	 */
	private function group_list($department_id)
	{
		$rows = CompanyRankGroup::model()->findAll(
			array(
				'select'=>array('group_name','rank'),
				'order' => 'rank ASC',
				'condition' => 'department_id=:department_id AND del_flag=:del_flag',
				'params' => array(":department_id" => $department_id, ":del_flag"=>1),
			)
		);

		return $rows;
	}


    /**
     * @return mixed
     *
     * 销售人员面板排行数据
     */
	private function dashboard_rank()
    {
        $seller_user_id = isset(Yii::app()->user->id) ? Yii::app()->user->id : 0;
        $date_time = date('Y-m-d');
        $rank_info = DashboardSalesRank::model()->getOneByCondition("*", "seller_user_id = '{$seller_user_id}' AND date_time = '{$date_time}'");

        return $rank_info;
    }

    /**
     * @return mixed
     *
     * 销售人员销售及利润数据
     */
    private function dashboard_sales_detail()
    {
        $seller_user_id = isset(Yii::app()->user->id) ? Yii::app()->user->id : 0;
        $date_time = date('Y-m-d');
        $sales_detail = DashboardSalesProfitDetail::model()->getOneByCondition("*", "seller_user_id = '{$seller_user_id}' AND date_time = '{$date_time}'");

        return $sales_detail;
    }


    private function dashboard_order()
    {
        $seller_user_id = isset(Yii::app()->user->id) ? Yii::app()->user->id : 0;
        $date_time = date('Y-m-d');
        $dashboard_order = DashboardOrders::model()->getOneByCondition("*", "seller_user_id = '{$seller_user_id}' AND date_time = '{$date_time}'");

        return $dashboard_order;
    }

}

