<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/1/20
 * Time: 13:54
 */
class DashboardreportController extends TaskBaseController
{
	public function accessRules()
	{
		return array(
			array(
				'allow',
				'users' => array(
					'*'
				),
				'actions' => array(
					'index',
				)
			)
		);
	}


	public function actionIndex()
	{
		set_time_limit(3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		echo 'Start: '.date('Y-m-d H:i:s');
        $execute_start = date('Y-m-d H:i:s');
        $message = '';
		try {
			//输入参数
			$platform = strtoupper(Yii::app()->request->getParam('platform', Platform::CODE_EBAY));
			$debug = Yii::app()->request->getParam('debug', false);
			$task_keys = ConfigFactory::getConfig('taskKeys');
            $default_date = date('Y-m-01', strtotime("-1 days"));
			$date_time = Yii::app()->request->getParam('date', $default_date);
			$platform_arr = Platform::model()->getPlatformCodes();
			if (in_array($platform, $platform_arr)) {
				$rows = SellerUserToAccountSite::model()->getDataByPlatform($platform);
				$total = count($rows);
				if ($debug) {
				    echo "<pre>Seller Account:";
				    print_r($rows);
				    echo "</pre>";
                }
				if (0 < $total) {
					$page_size = isset($task_keys['page_size']) ? $task_keys['page_size'] : 50;
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
								$seller_user_id = $rows[$k]['seller_user_id'];
								//获取此销售人员的月销售额目标，月实际销售额，月净利润目标，月实际净利润
								$targetInfo = $this->targetInfo($seller_user_id, $platform, $date_time);
								$salesInfo = $this->salesInfo($seller_user_id, $date_time);
								$profitInfo = $this->profitInfo($seller_user_id, $date_time);
								$data = array(
									'sales_target' => $targetInfo['sales_amount'],
									'profit_target' => $targetInfo['profit_amount'],
									'seller_user_id' => $seller_user_id,
									'seller_name' => isset($salesInfo['user_cn_name']) ? $salesInfo['user_cn_name'] : '',
									'sales_amount_rmb' => isset($salesInfo['total_rmb']) ? $salesInfo['total_rmb'] : 0,
									'sales_amount_usd' => isset($salesInfo['total_usd']) ? $salesInfo['total_usd'] : 0,
									'final_profit' => isset($profitInfo['final_profit']) ? $profitInfo['final_profit'] : 0,
									'retained_profits' => isset($profitInfo['retained_profits']) ? $profitInfo['retained_profits'] : 0,
									'group_id' => isset($profitInfo['group_id']) ? $profitInfo['group_id'] : 0,
									'group_name' => isset($profitInfo['group_name']) ? $profitInfo['group_name'] : 0,
									'department_id' => isset($profitInfo['department_id']) ? $profitInfo['department_id'] : 0,
									'department_name' => isset($profitInfo['department_name']) ? $profitInfo['department_name'] : 0,
									'date_time' => $date_time,
								);
                                echo "<pre>";
                                print_r($data);
                                echo "</pre>";
								$result = DashboardSalesProfit::model()->getOneByCondition(
									"id",
									" seller_user_id = '{$seller_user_id}' AND date_time = '{$date_time}'"
								);
								if (!empty($result)) {
									$this->updateData($data, $result['id']);
								} else {
									$this->saveData($data);
								}
							}
						}
					} while ($flag);

					DashboardSalesProfit::model()->calculate();
				}
			} else {
				throw new Exception("{$platform} was not exist");
			}
		} catch (Exception $e) {
            $message = $e->getMessage();
			echo $e->getMessage();
		}
        echo 'End: '.date('Y-m-d H:i:s');

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
	 * @param $uid
	 * @param $platform
	 * @return array
	 *
	 * 获取销售额，净利润
	 */
	private function targetInfo($uid, $platform, $date_time)
	{
		$model = $this->model('sales_extend', $platform);
		$sales_amount = $model->getSum(array($uid), 'sales_amount', $date_time);
		$profit_amount = $model->getSum(array($uid), 'profit_amount', $date_time);

		return array('sales_amount' => $sales_amount, 'profit_amount' => $profit_amount);
	}

	/**
	 * @param $uid
	 * @return mixed
	 * 获取实际销售额
	 */
	private function salesInfo($uid, $paytime)
	{
		$row = TaskPanUserSale::model()->getOneByCondition("user_cn_name, total_rmb, total_usd", " sales_id = '{$uid}' AND paytime = '{$paytime}'");
		return $row;
	}

	/**
	 * @param $uid
	 * @return mixed
	 *
	 * 获取实际净利润
	 */
	private function profitInfo($uid, $paytime)
	{
		$row = TaskPanProfit::model()->getOneByCondition("retained_profits, final_profit, department_id, department_name, group_id, group_name", " sales_id = '{$uid}' AND paytime = '{$paytime}'");
		return $row;
	}


	private function saveData($data)
	{
		return DashboardSalesProfit::model()->saveData($data);
	}


	private function updateData($data, $id)
	{
		return DashboardSalesProfit::model()->update($data, $id);
	}
}
