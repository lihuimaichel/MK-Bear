<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/4/12
 * Time: 9:34
 */
class DashboardfetchreportController extends TaskBaseController
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
     * 获取排行数据
     */
    public function actionFetchrank()
    {
        set_time_limit(3600);
        ini_set('memory_limit', '2000M');
        ini_set("display_errors", true);
        error_reporting(E_ALL);
        $date_time = date('Y-m-d');
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
        echo 'Start: ' . date('Y-m-d H:i:s');
        try {
            //获取销售排名
            $rows = CompanySalesRankPerson::model()->getDataByCondition("sales_id, rank, rank_flag, inc_rank, department_id", " del_flag = 1 ");
            $company_amount = CompanySalesRankPerson::model()->totalAmount(2);
            if (!empty($rows)) {
                $data = array();
                foreach ($rows as $k => $v) {
                    if (1 == $v['rank_flag']) {
                        //按部门排
                        $data[$v['sales_id']]['dep_rank'] = $v['rank'];
                        $data[$v['sales_id']]['dep_rank_inc'] = $v['inc_rank'];
                        $data[$v['sales_id']]['dep_id'] = $v['department_id'];
                        $data[$v['sales_id']]['dep_rank_amount'] = CompanySalesRankPerson::model()->totalAmount(1, $v['department_id']);
                    } else {
                        //按公司排
                        $data[$v['sales_id']]['90_rank'] = $v['rank'];
                        $data[$v['sales_id']]['90_rank_inc'] = $v['inc_rank'];
                        $data[$v['sales_id']]['90_rank_amount'] = $company_amount;
                    }
                }

                //获取新人排行及组排行信息
                $new_rows = CompanyRankNewperson::model()->getDataByCondition("sales_id, rank, inc_rank", " del_flag = 1");
                $new_person_amount = CompanyRankNewperson::model()->totalAmount();
                $new_data = array();
                if (!empty($new_rows)) {
                    foreach ($new_rows as $ke => $va) {
                        $new_data[$va['sales_id']]['new_rank'] = $va['rank'];
                        $new_data[$va['sales_id']]['new_rank_inc'] = $va['inc_rank'];
                        $new_data[$va['sales_id']]['new_rank_amount'] = $new_person_amount;

                    }
                }

                //获取所属组的排行
                $group_rows = CompanyRankGroup::model()->getDataByCondition("rank, inc_rank, group_id", " del_flag = 1");
                $group_data = array();
                $team_rank_amount = CompanyRankGroup::model()->totalAmount();
                if (!empty($group_rows)) {
                    foreach ($group_rows as $key => $val) {
                        $group_data[$val['group_id']]['team_rank'] = $val['rank'];
                        $group_data[$val['group_id']]['team_rank_inc'] = $val['inc_rank'];
                        $group_data[$val['group_id']]['team_rank_amount'] = $team_rank_amount;
                    }
                }

                //获取销售所属的组
                $team_rows = ProductsGroupModel::model()->findAll("group_id>:group_id AND is_del=:is_del", array(":group_id" => 0, ":is_del" => 0));
                $team_data = array();
                if (!empty($team_rows)) {
                    foreach ($team_rows as $tk => $tv) {
                        $team_data[$tv['seller_user_id']] = $tv['group_id'];
                    }
                }

                foreach ($data as $seller_user_id => $dv) {
                    $params = array();
                    $group_id = isset($team_data[$seller_user_id]) ? $team_data[$seller_user_id] : 0;
                    $params['seller_user_id'] = $seller_user_id;
                    $params['group_id'] = $group_id;
                    $params['dep_id'] = $dv['dep_id'];
                    $params['90_rank'] = $dv['90_rank'];
                    $params['90_rank_inc'] = $dv['90_rank_inc'];
                    $params['90_rank_amount'] = $dv['90_rank_amount'];
                    $params['dep_rank'] = $dv['dep_rank'];
                    $params['dep_rank_inc'] = $dv['dep_rank_inc'];
                    $params['dep_rank_amount'] = $dv['dep_rank_amount'];
                    $params['team_rank'] = isset($group_data[$group_id]) ? $group_data[$group_id]['team_rank'] : 0;
                    $params['team_rank_inc'] = isset($group_data[$group_id]) ? $group_data[$group_id]['team_rank_inc'] : 0;
                    $params['team_rank_amount'] = isset($group_data[$group_id]) ? $group_data[$group_id]['team_rank_amount'] : 0;
                    $params['new_rank'] = isset($new_data[$seller_user_id]) ? $new_data[$seller_user_id]['new_rank'] : 0;
                    $params['new_rank_inc'] = isset($new_data[$seller_user_id]) ? $new_data[$seller_user_id]['new_rank_inc'] : 0;
                    $params['new_rank_amount'] = isset($new_data[$seller_user_id]) ? $new_data[$seller_user_id]['new_rank_amount'] : 0;
                    $params['date_time'] = $date_time;

                    //排行汇总存储的表
                    $rank_row = DashboardSalesRank::model()->getOneByCondition("id", "seller_user_id = '{$seller_user_id}' AND date_time = '{$date_time}'");
                    if (empty($rank_row)) {
                        //插入
                        DashboardSalesRank::model()->saveData($params);
                    } else {
                        //更新
                        DashboardSalesRank::model()->update($params, $rank_row['id']);
                    }
                }
            }

        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $e->getMessage();
        }
        echo '<br />End: ' . date('Y-m-d H:i:s');
        echo '<br />Done!!!';
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

    public function actionFetchorders()
    {
        set_time_limit(3600);
        ini_set('memory_limit', '2000M');
        ini_set("display_errors", true);
        error_reporting(E_ALL);
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
        echo 'Start: ' . date('Y-m-d H:i:s');
        try {
            $date_time = date('Y-m-01', strtotime("-1 days"));
            $now_date = date('Y-m-d');
            $departmentPlatforms = Department::departmentPlatform();
            //获取销售当月累计订单数
            $orders = TaskPanUserOrder::model()->getDataByCondition("sales_id, department_id, order_num", " del_flag = 1 AND paytime = '{$date_time}'");
            $orders_data = array();
            if (!empty($orders)) {
                foreach ($orders as $ko => $vo) {
                    $orders_data[$vo['sales_id']]['seller_user_id'] = $vo['sales_id'];
                    $orders_data[$vo['sales_id']]['dep_id'] = $vo['department_id'];
                    $orders_data[$vo['sales_id']]['orders_num'] = $vo['order_num'];
                }
            }
            //获取当月取消订单数
            $cancel_orders = OrdersCancelUsers::model()->getDataByCondition("sales_id, cancel_num", " del_flag = 1 AND paytime = '{$date_time}'");
            $cancel_orders_data = array();
            if (!empty($cancel_orders)) {
                foreach ($cancel_orders as $kc => $vc) {
                    $cancel_orders_data[$vc['sales_id']]['orders_cancel'] = $vc['cancel_num'];
                }
            }

            //获取包裹重发数
            $repeat_orders = TaskPanUserRepeat::model()->getDataByCondition("sales_id, repaeat_qty", " del_flag = 1 AND ship_date = '{$date_time}'");
            $repeat_orders_data = array();
            if (!empty($repeat_orders)) {
                foreach ($repeat_orders as $rk => $rc) {
                    $repeat_orders_data[$rc['sales_id']]['orders_repeat'] = $rc['repaeat_qty'];
                }
            }

            //获取昨日订单数
            $yesterday_orders = SellerOrderQtyGroup::model()->getDataByCondition("sales_id, order_quantity", " day_flag = 1 AND del_flag = 1");
            $yesterday_orders_data = array();
            if (!empty($yesterday_orders)) {
                foreach ($yesterday_orders as $yk => $yv) {
                    $yesterday_orders_data[$yv['sales_id']]['orders_yesterday_num'] = $yv['order_quantity'];
                }
            }

            //待处理亏损订单
            $exception_orders = SellerExceptionOrder::model()->getDataByCondition("sales_id, exp_qty", " del_flag = 1");
            $exception_orders_data = array();
            if (!empty($exception_orders)) {
                foreach ($exception_orders as $ek => $ev) {
                    $exception_orders_data[$ev['sales_id']]['orders_loss_num'] = $ev['exp_qty'];
                }
            }

            //待发货订单
            $wait_shipped = SellerNoShippedOrders::model()->getDataByCondition("sales_id, order_num", "del_flag = 1 AND nowdate = '{$now_date}'");
            $wait_shipped_orders = array();
            if (!empty($wait_shipped)) {
                foreach ($wait_shipped as $wk => $wv) {
                    $wait_shipped_orders[$wv['sales_id']]['orders_wait_send'] = $wv['order_num'];
                }
            }

            //7日动销率
            $moving_rows = TaskPaneMovingRateUser::model()->getDataByCondition("sales_id, moving_rate_7", " del_flag = 1 AND cal_date = '{$now_date}'");
            $moving_rows_data = array();
            if (!empty($moving_rows)) {
                foreach ($moving_rows as $mk => $mv) {
                    $moving_rows_data[$mv]['orders_sales_rate'] = $mv['moving_rate_7'];
                }
            }

            $wait_rows = WaitOptSale::model()->getDataByCondition("sale_id, mov_rate", " del_flag =1 AND stc_date = '{$now_date}'");
            $wait_rows_data = array();
            if (!empty($wait_rows)) {
                foreach ($wait_rows as $wak => $wav) {
                    $wait_rows_data[$wav['sale_id']]['orders_optimization_rate'] = $wav['mov_rate'];
                }
            }

            //获取当前有效销售的数据信息
            $rows = ProductsGroupModel::model()->findAll("group_id>:group_id AND is_del=:is_del", array(":group_id" => 0, ":is_del" => 0));
            if (!empty($rows)) {
                foreach ($rows as $k => $v) {
                    $params = array();
                    $seller_user_id = $v['seller_user_id'];
                    $group_id = $v['group_id'];
                    $params['seller_user_id'] = $seller_user_id;
                    $params['group_id'] = $group_id;
                    $params['dep_id'] = isset($orders_data[$seller_user_id]) ? $orders_data[$seller_user_id]['dep_id'] : 0;
                    $params['platform'] = isset($departmentPlatforms[$params['dep_id']]) ? $departmentPlatforms[$params['dep_id']] : '';
                    $params['orders_num'] = isset($orders_data[$seller_user_id]) ? $orders_data[$seller_user_id]['orders_num'] : 0;
                    $params['orders_cancel'] = isset($cancel_orders_data[$seller_user_id]) ? $cancel_orders_data[$seller_user_id]['orders_cancel'] : 0;
                    $params['orders_repeat'] = isset($repeat_orders_data[$seller_user_id]) ? $repeat_orders_data[$seller_user_id]['orders_repeat'] : 0;
                    $params['orders_yesterday_num'] = isset($yesterday_orders_data[$seller_user_id]) ? $yesterday_orders_data[$seller_user_id]['orders_yesterday_num'] : 0;
                    $params['orders_loss_num'] = isset($exception_orders_data[$seller_user_id]) ? $exception_orders_data[$seller_user_id]['orders_loss_num'] : 0;
                    $params['orders_wait_send'] = isset($wait_shipped_orders[$seller_user_id]) ? $wait_shipped_orders[$seller_user_id]['orders_wait_send'] : 0;
                    $params['orders_sales_rate'] = isset($moving_rows_data[$seller_user_id]) ? round($moving_rows_data[$seller_user_id]['orders_sales_rate'] * 100, 2) : 0;
                    $params['orders_optimization_rate'] = isset($wait_rows_data[$seller_user_id]) ? round($wait_rows_data[$seller_user_id]['orders_optimization_rate'] * 100, 2) : 0;
                    $params['date_time'] = $now_date;

                    $check_row = DashboardOrders::model()->getOneByCondition("id", "date_time = '{$now_date}' AND seller_user_id = '{$seller_user_id}'");
                    if (empty($check_row)) {
                        //插入
                        DashboardOrders::model()->saveData($params);
                    } else {
                        //更新
                        DashboardOrders::model()->update($params, $check_row['id']);
                    }
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $e->getMessage();
        }
        echo '<br />End: ' . date('Y-m-d H:i:s');
        echo '<br />Done!!!';
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
     * 获取每日销售明细，利润明细
     */
    public function actionFetchdetail()
    {
        set_time_limit(3600);
        ini_set('memory_limit', '2000M');
        ini_set("display_errors", true);
        error_reporting(E_ALL);
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
        echo 'Start: ' . date('Y-m-d H:i:s');
        try {
            $platforms = array(
                Platform::CODE_EBAY,
                Platform::CODE_ALIEXPRESS,
                Platform::CODE_LAZADA,
                Platform::CODE_WISH,
                Platform::CODE_AMAZON
            );

            //查询的月份
            $month_date = date('Y-m-01', strtotime("-1 days"));
            $start_month_date = date("Y-01-01", strtotime("-1 days"));
            $end_month_date = date('Y-m-01', strtotime("-1 days"));
            $now_date = date('Y-m-d');
            $departmentPlatforms = Department::departmentPlatform();

            $users_data = User::model()->getAllUsers();
            $users_array = array();
            if (!empty($users_data)) {
                foreach ($users_data as $uk=>$uv) {
                    $users_array[$uv['id']] = $uv['department_id'];
                }
            }

            $group_rows = ProductsGroupModel::model()->findAll("group_id>:group_id AND is_del=:is_del", array(":group_id" => 0, ":is_del" => 0));
            $group_data = array();
            if (!empty($group_rows)) {
                foreach ($group_rows as $gk => $gv) {
                    $group_data[$gv['seller_user_id']] = $gv['group_id'];
                }
            }

            //获取当月销售额数据
            $sales_rows = TaskPanUserSale::model()->getDataByCondition("sales_id, total_rmb", " del_flag = 1 AND paytime = '{$month_date}'");
            $sales_data = array();
            if (!empty($sales_rows)) {
                foreach ($sales_rows as $sak=>$sav) {
                    $sales_data[$sav['sales_id']] = $sav['total_rmb'];
                }
            }

            //获取整年的销售额数据
            $year_sales_rows = TaskPanUserSale::model()->getDataByCondition("sales_id, SUM(total_rmb) AS total_rmb", " del_flag = 1 AND paytime BETWEEN '{$start_month_date}' AND '{$end_month_date}'", " ", "sales_id");
            $year_sales_data = array();
            if (!empty($year_sales_rows)) {
                foreach ($year_sales_rows as $yak=>$yav) {
                    $year_sales_data[$yav['sales_id']] = $yav['total_rmb'];
                }
            }

            //获取当月净利额
            $profit_rows = TaskPanProfit::model()->getDataByCondition("sales_id, retained_profits", " del_flag = 1 AND paytime = '{$month_date}'");
            $profit_data = array();
            if (!empty($profit_rows)) {
                foreach ($profit_rows as $pak=>$pav) {
                    $profit_data[$pav['sales_id']] = $pav['retained_profits'];
                }
            }

            //获取整年净利额
            $year_profit_rows = TaskPanProfit::model()->getDataByCondition("sales_id, SUM(retained_profits) AS retained_profits", " del_flag = 1 AND paytime BETWEEN '{$start_month_date}' AND '{$end_month_date}'", " ", "sales_id");
            $year_profit_data = array();
            if (!empty($year_profit_rows)) {
                foreach ($year_profit_rows as $ypak=>$ypav) {
                    $year_profit_data[$ypav['sales_id']] = $ypav['retained_profits'];
                }
            }


            //获取昨日销售数据
            $yesterday_rows = CompanySalesGroup::model()->getDataByCondition("sales_id, total_rmb", " del_flag = 1 AND day_flag = 1");
            $yesterday_rows_data = array();
            if (!empty($yesterday_rows)) {
                foreach ($yesterday_rows as $yk => $yv) {
                    $yesterday_rows_data[$yv['sales_id']] = $yv['total_rmb'];
                }
            }

            //获取昨日净利额
            $yesterday_profit_rows = TaskPaneProfitGroupSale::model()->getDataByCondition("sales_id, retained_profits", " del_flag=1 AND day_flag = 1");
            $yesterday_profit_rows_data = array();
            if (!empty($yesterday_profit_rows)) {
                foreach ($yesterday_profit_rows as $ypk=>$ypv) {
                    $yesterday_profit_rows_data[$ypv['sales_id']] = $ypv['retained_profits'];
                }
            }

            //当前月的天数(取脚本运行的前一天为准）
            $days = date('t', strtotime('-1 days'));
            //循环平台
            $all_array = array();
            foreach ($platforms as $pk => $platform) {
                $sales_extend_model = $this->model('sales_extend', $platform);
                //获取年销售额及年利润目标
                $year_array = $sales_extend_model->getYearAllSumData();
                if (!empty($year_array)) {
                    foreach ($year_array as $yk => $yv) {
                        $sale_amount = isset($year_sales_data[$yv['seller_user_id']]) ? $year_sales_data[$yv['seller_user_id']] : 0;
                        $profit_amount = isset($year_profit_data[$yv['seller_user_id']]) ? $year_profit_data[$yv['seller_user_id']] : 0;
                        $all_array[$yv['seller_user_id']]['seller_user_id'] = $yv['seller_user_id'];
                        $all_array[$yv['seller_user_id']]['group_id'] = isset($group_data[$yv['seller_user_id']]) ? $group_data[$yv['seller_user_id']] : 0;
                        $all_array[$yv['seller_user_id']]['dep_id'] = isset($users_array[$yv['seller_user_id']]) ? $users_array[$yv['seller_user_id']] : 0;
                        $all_array[$yv['seller_user_id']]['platform'] = isset($departmentPlatforms[$all_array[$yv['seller_user_id']]['dep_id']]) ? $departmentPlatforms[$all_array[$yv['seller_user_id']]['dep_id']] : '';
                        $all_array[$yv['seller_user_id']]['year_sale_amount'] = $yv['sales_target'];
                        $all_array[$yv['seller_user_id']]['year_profit_amount'] = $yv['profit_target'];
                        $all_array[$yv['seller_user_id']]['sale_amount'] = $sale_amount;
                        $all_array[$yv['seller_user_id']]['profit_amount'] = $profit_amount;
                        $all_array[$yv['seller_user_id']]['year_sale_rate'] = (0 < $yv['sales_target']) ? round(($sale_amount/$yv['sales_target'])*100, 2) : 0;
                        $all_array[$yv['seller_user_id']]['year_profit_rate'] = (0 < $yv['profit_target']) ? round(($profit_amount/$yv['profit_target'])*100, 2) : 0;
                    }
                }

                //获取当月销售额及月利润目标
                $month_array = $sales_extend_model->fetchAllSumData();
                if (!empty($month_array)) {
                    foreach ($month_array as $mk => $mv) {
                        $month_amount = isset($sales_data[$mv['seller_user_id']]) ? $sales_data[$mv['seller_user_id']] : 0;
                        $month_profit = isset($profit_data[$mv['seller_user_id']]) ?  $profit_data[$mv['seller_user_id']] : 0;
                        $day_amount = isset($yesterday_rows_data[$mv['seller_user_id']]) ? $yesterday_rows_data[$mv['seller_user_id']] : 0;
                        $day_profit = isset($yesterday_profit_rows_data[$mv['seller_user_id']]) ? $yesterday_profit_rows_data[$mv['seller_user_id']] : 0;
                        $day_sale_amount = round($mv['month_sale_amount']/$days, 2);;
                        $day_profit_amount = round($mv['month_profit_amount']/$days, 2);
                        $all_array[$mv['seller_user_id']]['month_sale_amount'] = $mv['month_sale_amount'];
                        $all_array[$mv['seller_user_id']]['month_profit_amount'] = $mv['month_profit_amount'];
                        $all_array[$mv['seller_user_id']]['month_amount'] = $month_amount;
                        $all_array[$mv['seller_user_id']]['month_profit'] = $month_profit;
                        $all_array[$mv['seller_user_id']]['month_sale_rate'] = (0 < $mv['month_sale_amount']) ? round(($month_amount/$mv['month_sale_amount'])*100, 2) : 0;
                        $all_array[$mv['seller_user_id']]['month_profit_rate'] = (0 < $mv['month_profit_amount']) ? round(($month_profit/$mv['month_profit_amount'])*100, 2) : 0;
                        $all_array[$mv['seller_user_id']]['day_sale_amount'] = $day_sale_amount;
                        $all_array[$mv['seller_user_id']]['day_profit_amount'] = $day_profit_amount;
                        $all_array[$mv['seller_user_id']]['day_amount'] = $day_amount;
                        $all_array[$mv['seller_user_id']]['day_profit'] = $day_profit;
                        $all_array[$mv['seller_user_id']]['day_sale_rate'] = (0 < $day_sale_amount) ? round(($day_amount/$day_sale_amount)*100, 2) : 0;
                        $all_array[$mv['seller_user_id']]['day_profit_rate'] = (0 < $day_profit_amount) ? round(($day_profit/$day_profit_amount)*100, 2) : 0;
                        $all_array[$mv['seller_user_id']]['date_time'] = date('Y-m-d');
                    }
                }
            }

            if (!empty($all_array)) {
                foreach ($all_array as $user_id => $params) {
                    $check_row = DashboardSalesProfitDetail::model()->getOneByCondition("id", "seller_user_id = '{$user_id}' AND date_time = '{$now_date}'");
                    if (empty($check_row)) {
                        DashboardSalesProfitDetail::model()->saveData($params);
                    } else {
                        DashboardSalesProfitDetail::model()->update($params, $check_row['id']);
                    }
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $e->getMessage();
        }
        echo '<br />End: ' . date('Y-m-d H:i:s');
        echo '<br />Done!!!';
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
}
