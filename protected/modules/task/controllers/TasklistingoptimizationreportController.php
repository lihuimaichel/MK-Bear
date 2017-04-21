<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/1/24
 * Time: 9:14
 */
class TasklistingoptimizationreportController extends TaskBaseController
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

	public function actionReport()
	{
		set_time_limit(3600);
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		try {
			$platform_arr = array(Platform::CODE_EBAY, Platform::CODE_ALIEXPRESS);
			$debug = Yii::app()->request->getParam("debug", false);
			$days = date('t');
			$date_time = date('Y-m-01', strtotime("-1 days"));
			foreach ($platform_arr as $kp => $platform) {
				if (Platform::CODE_EBAY == $platform) {
					$seller_user_arr = User::model()->getEbayUserList(false, false);
				} else {
					$seller_user_arr = User::model()->getAliexpressUserList(false, false);
				}

				$model = $this->model('task', $platform);
				$rows = $model->sumByCondition();
				if ($debug) {
					echo "<br />==============Platform: {$platform}=======================<br /><pre>";
					print_r($rows);
					echo "</pre>";
				}
				if (!empty($rows)) {
					$listing_rank_model = $this->model('listing_rank', $platform);
					$optimization_rank_model = $this->model('optimization_rank', $platform);
					foreach ($rows as $uk => $uv) {
						$seller_user_id = $uv['seller_user_id'];
						//获取已刊登的数量以及已优化的数量
						$data = $this->yesterdayListingOptimization(array($seller_user_id), $platform, $date_time);
						$listing_rank_arr = array(
							'seller_user_id' => $seller_user_id,
							'seller_name' => isset($seller_user_arr[$seller_user_id]) ? $seller_user_arr[$seller_user_id] : '',
							'listing_num' => $uv['listing_num'] * $days,
							'was_listing_num' => $data['listing_count'],
							'date_time' => $date_time,
						);
						$rank_result = $listing_rank_model->getoneByCondition("id", " seller_user_id = '{$seller_user_id}' AND date_time = '{$date_time}'");
						if (!empty($rank_result)) {
							$listing_rank_model->updateData($listing_rank_arr, $rank_result['id']);
						} else {
							$listing_rank_model->saveData($listing_rank_arr);
						}

						$optimization_rank_arr = array(
							'seller_user_id' => $seller_user_id,
							'seller_name' => isset($seller_user_arr[$seller_user_id]) ? $seller_user_arr[$seller_user_id] : '',
							'optimization_num' => $uv['optimization_num'] * $days,
							'was_optimization_num' => $data['optimization_count'],
							'date_time' => $date_time,
						);
						$optimization_result = $optimization_rank_model->getoneByCondition("id", " seller_user_id = '{$seller_user_id}' AND date_time = '{$date_time}'");
						if (!empty($optimization_result)) {
							$optimization_rank_model->updateData($optimization_rank_arr, $optimization_result['id']);
						} else {
							$optimization_rank_model->saveData($optimization_rank_arr);
						}
					}
					//刷新比例
					$listing_rank_model->calculate();
					$optimization_rank_model->calculate();

					//重新更新排名
					$rank_arr_listing = $listing_rank_model->getDataByCondition("id", " date_time = '{$date_time}'", "listing_rate DESC, listing_num DESC");
					if (!empty($rank_arr_listing)) {
						foreach ($rank_arr_listing as $lrk => $lrv) {
							$listing_rank_model->updateData(array('rank' => ($lrk+1)), $lrv['id']);
						}
					}
					$rank_arr_optimization = $optimization_rank_model->getDataByCondition("id", " date_time = '{$date_time}'", "optimization_rate DESC, optimization_num DESC");
					if (!empty($rank_arr_optimization)) {
						foreach ($rank_arr_optimization as $olk => $olv) {
							$optimization_rank_model->updateData(array('rank' => ($olk+1)), $olv['id']);
						}
					}
				}
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
}