<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/13
 * Time: 18:36
 */
class TaskCommonController extends TaskBaseController
{
	/**
	 * @param string $param_key
	 *
	 * 查看设定任务或者销售额列表页
	 */
	public function index($param_key = 'task')
	{
		$model = $this->model($param_key);
		$this->render("index", array('model' => $model));
	}

	/**
	 * @param string $param_key
	 *
	 * 编辑页
	 */
	public function edit($param_key = 'task')
	{
		$mode = Yii::app()->request->getParam('mode', 0);//0为修改 1为新增
		$id = Yii::app()->request->getParam('id', ''); //修改的Id
		$taskKeys = ConfigFactory::getConfig('taskKeys');
		$model = $this->model($param_key);
		$ids = Yii::app()->request->getParam('ids', ''); //销售人员Id

		//账号列表
		$account_model = $this->model('account');
		$account_list = $account_model::getIdNamePairs();

		//站点列表
		$sites_arr = $this->siteList();

		//组长列表
		$team_leader_model = $this->model('seller');
		$teamleaders = $team_leader_model->teamLeaderIds();

		$seller_list = $this->sellerUsers();

		//如果是单一的卖家，则以此卖家为纬度，取得所属的账号，站点信息，以便在前端显示时自动勾选上
		$checkSite = array();
		$checkAccount = array();
		$checkSeller = array();
		$detail_row = array();
		$rows = array();

		//mode 0 为编辑 1 为新增批量任务
		if ((0 == $mode)) {
			//查询当前对应销售的任务量情况
			if (!empty($id)) {
				$detail_row = $model->detail($id);
				//返回具体的销售人员姓名信息
				$seller_user_list = array($detail_row['seller_user_id'] => User::model()->getUserFullNameScalarById($detail_row['seller_user_id']));
				//$seller_user_id = $detail_row['seller_user_id'];
				$checkSite = array($detail_row['site_id']);
				$checkAccount = array($detail_row['account_id']);
			} else {
				//批量修改
				$seller_user_list = $this->sellerUsers(); //自动获取相应平台的销售人员列表
				//勾选上已经设置的销售账号
				if (!empty($ids)) {
					$check_user_list = $model->lists($ids);
					if (null != $check_user_list) {
						foreach ($check_user_list as $uk => $uv) {
							$checkSeller[] = $uv['seller_user_id'];
							$checkAccount[] = $uv['account_id'];
							$checkSite[] = $uv['site_id'];
						}
					}
				}
			}

			$edit_ids = !empty($id) ? array($id) : (!empty($ids) ? explode(",", trim($ids)) : array());
			$data = $model->lists($edit_ids);
			if (!empty($data)) {
				foreach ($data as $ke => $va) {
					$seller_name = isset($seller_list[$va['seller_user_id']]) ? $seller_list[$va['seller_user_id']] : 'unknown';
					$account_name = isset($account_list[$va['account_id']]) ? $account_list[$va['account_id']] : 'unknown';
					$site_name = isset($sites_arr[$va['site_id']]) ? $sites_arr[$va['site_id']] : 0;
					$rows[$seller_name][$account_name][] = $site_name;
				}
			}
		} else {
			//获取所有ebay的销售人员列表
			$seller_user_list = $this->sellerUsers(); //自动获取相应平同的销售人员列表
		}

		$user_list = array();
		if (!empty($teamleaders)) {
			if (!empty($seller_user_list)) {
				foreach ($seller_user_list as $uid => $uval) {
				    /*
					if (!in_array($uid, $teamleaders)) {
						$user_list[$uid] = $uval;
					}
				    */
                    //任何人都可以
                    $user_list[$uid] = $uval;
				}
			}
		}

		$sales_profit_detail = array();
		if ('sales' == $param_key) {
			if (!empty($id)) {
				$extend_model = $this->model('sales_extend');
				$sales_profit_detail = $extend_model->getDataByCondition("*", "sales_id = '{$id}'");
				if (empty($sales_profit_detail)) {
					$configs = ConfigFactory::getConfig('taskKeys');
					//默认值
					for($i=1; $i<=12; $i++) {
						$sales_profit_detail[] = array(
							'year' => date('Y'),
							'month' => ($i < 10) ? '0'.$i : $i,
							'sales_amount' => $configs['sales_amount'],
							'profit_amount' => $configs['profit_amount'],
						);
					}
				}
			} else {
				$configs = ConfigFactory::getConfig('taskKeys');
				//默认值
				for($i=1; $i<=12; $i++) {
					$sales_profit_detail[] = array(
						'year' => date('Y'),
						'month' => ($i < 10) ? '0'.$i : $i,
						'sales_amount' => $configs['sales_amount'],
						'profit_amount' => $configs['profit_amount'],
					);
				}
			}
		}

		$this->render("edit",
			array(
				'detail' => $detail_row,
				'id' => $id,
				'param_key' => $param_key,
				'rows' => $rows,
				'account_list' => $account_list,
				'sites_arr' => $sites_arr,
				'mode' => $mode,
				'seller_user_list' => $user_list,
				'checkSite' => $checkSite, //如果是针对个人的设置，则显示站点，去重
				'checkAccount' => $checkAccount, //如果针对个人的设置，显示勾的账号，去重
				'list_num' => $taskKeys['list_num'],
				'optimization_num' => $taskKeys['optimization_num'],
				'sales_target' => sprintf('%d', $taskKeys['sales_target']),
				'sales_profit_detail' => $sales_profit_detail,
				'checkSeller' => $checkSeller,
				'platform' => $this->userPlatform(),
			)
		);
	}

	/**
	 * @param string $param_key
	 *
	 * 保存修改页
	 */
	public function update($param_key = 'task')
	{
		$mode = Yii::app()->request->getParam('mode');
		$id = Yii::app()->request->getParam('id', '');
		$seller_user_id = Yii::app()->request->getParam('seller_user_id');
		$site_list = Yii::app()->request->getParam('site_list');
		$account_list = Yii::app()->request->getParam('account_list');
		$list_num = Yii::app()->request->getParam('list_num');
		$optimization_num = Yii::app()->request->getParam('optimization_num');
		$sales_amount = Yii::app()->request->getParam('sales_amount');
		$profit_amount = Yii::app()->request->getParam('profit_amount');

		$platform = $this->userPlatform();
        if (empty($site_list)) {
            $site_model = $this->model('site', $platform);
            $site_list = (in_array($platform, array(Platform::CODE_AMAZON, Platform::CODE_LAZADA, Platform::CODE_EBAY)) ? array_keys($site_model::getSiteList()) : array(0));
        }

		//站点列表
		$site_model = $this->model('site');
		$sites_arr = $site_model->getSiteList();
		$model = $this->model($param_key);
		//只更新单条数据
		if (!empty($id)) {
			if ('task' == $param_key) {
				$updateData = array(
					'listing_num' => $list_num,
					'optimization_num' => $optimization_num,
					'updated_uid' => Yii::app()->user->id,
					'updated_at' => date('Y-m-d H:i:s'),
				);
			} else {
				$sales_target = 0;
				$profit_target = 0;
				$extend_model = $this->model('sales_extend');
				$year = date('Y');
				$seller_user_info = $model->findByPk($id);
				$user_id = isset($seller_user_info['seller_user_id']) ? $seller_user_info['seller_user_id'] : 0;

				foreach ($sales_amount as $sk => $sval) {
					$sales_target += $sval;
					$month = $sk+1;
					$month = ($month < 10) ? '0'.$month : $month ;
					$ex_row = $extend_model->getoneByCondition('id', " sales_id = '{$id}' AND month = '{$month}' AND year = '{$year}'");
					if (!empty($ex_row)) {
						$extend_model->updateData(array('sales_amount' => $sval, 'seller_user_id' => $user_id), " sales_id = '{$id}' AND month = '{$month}' AND year = '{$year}'");
					} else {
						$extend_model->saveData(array('sales_amount' => $sval, 'sales_id' =>$id, 'seller_user_id' => $user_id, 'month' => $month, 'year'=>$year));
					}
				}

				foreach ($profit_amount as $pk => $pv) {
					$profit_target += $pv;
					$month = $pk+1;
					$month = ($month < 10) ? '0'.$month : $month ;
					$ex_row = $extend_model->getoneByCondition('id', " sales_id = '{$id}' AND month = '{$month}' AND year = '{$year}'");
					if (!empty($ex_row)) {
						$extend_model->updateData(array('profit_amount' => $pv, 'seller_user_id' => $user_id), " sales_id = '{$id}' AND month = '{$month}' AND year = '{$year}'");
					} else {
						$extend_model->saveData(array('profit_amount' => $pv, 'sales_id' =>$id, 'seller_user_id' => $user_id, 'month' => $month, 'year'=>$year));
					}
				}

				//更新年销售额，年净利润
				$updateData = array(
					'sales_target' => $sales_target,
					'profit_target' => $profit_target,
					'updated_uid' => Yii::app()->user->id,
					'updated_at' => date('Y-m-d H:i:s'),
				);
			}
			$model->updateData($updateData, $id);
		} else {
			$extend_model = $this->model('sales_extend');
			//更新或者插入多条数据
			foreach ($seller_user_id as $k => $uid) {
				//保存每个销售对应设定值的id，如果新数据中没有，则要把老数据删除掉
				$ids = array();
				foreach ($account_list as $ak => $av) {
					foreach ($site_list as $sk => $sv) {
						$site_name = $sites_arr[$sv];
						//根据账号，站点，销售判定是否有权限设定此数据
						if (!$this->checkSellerSiteAccount($uid, $av, $site_name, $platform)) {
							//如果没有此账号对应的站点，则跳过
							continue;
						};
						$result = $model->find(
							array(
								'select' => array('id'),
								'condition' => 'seller_user_id = :seller_user_id AND account_id = :account_id AND site_id = :site_id',
								'params' => array('seller_user_id' => $uid, 'account_id' => $av, 'site_id' => $sv)
							));
						//有数据，则更新
						if (!empty($result)) {
							if ('task' == $param_key) {
								$updateData = array(
									'listing_num' => $list_num,
									'optimization_num' => $optimization_num,
									'updated_uid' => Yii::app()->user->id,
									'updated_at' => date('Y-m-d H:i:s'),
								);
							} else {
								$id = $result->id;
								$sales_target = 0;
								$profit_target = 0;
								$year = date('Y');

								foreach ($sales_amount as $sk => $sval) {
									$sales_target += $sval;
									$month = $sk+1;
									$month = ($month < 10) ? '0'.$month : $month ;
									$ex_row = $extend_model->getoneByCondition('id', " sales_id = '{$id}' AND month = '{$month}' AND year = '{$year}'");
									if (!empty($ex_row)) {
										$extend_model->updateData(array('sales_amount' => $sval, 'seller_user_id' => $uid), " sales_id = '{$id}' AND month = '{$month}' AND year = '{$year}'");
									} else {
										$extend_model->saveData(array('sales_amount' => $sval, 'seller_user_id' => $uid, 'sales_id' =>$id, 'month' => $month, 'year'=>$year));
									}
								}

								foreach ($profit_amount as $pk => $pv) {
									$profit_target += $pv;
									$month = $pk+1;
									$month = ($month < 10) ? '0'.$month : $month ;
									$ex_row = $extend_model->getoneByCondition('id', " sales_id = '{$id}' AND month = '{$month}' AND year = '{$year}'");
									if (!empty($ex_row)) {
										$extend_model->updateData(array('profit_amount' => $pv, 'seller_user_id' => $uid), " sales_id = '{$id}' AND month = '{$month}' AND year = '{$year}'");
									} else {
										$extend_model->saveData(array('profit_amount' => $pv, 'seller_user_id' => $uid, 'sales_id' =>$id, 'month' => $month, 'year'=>$year));
									}
								}

								$updateData = array(
									'sales_target' => $sales_target,
									'profit_target' => $profit_target,
									'updated_uid' => Yii::app()->user->id,
									'updated_at' => date('Y-m-d H:i:s'),
								);
							}
							$model->updateData($updateData, $result->id);
							$ids[] = $result->id;
						} else {
							$department = User::model()->getUserNameById($uid);
							$year = date('Y');
							//没数据，则插入
							$newData = array(
								'seller_user_id' => $uid,
								'account_id' => $av,
								'site_id' => $sv,
								'platform_code' => $platform,
								'department_id' => isset($department['department_id']) ? $department['department_id'] : '',
								'created_uid' => Yii::app()->user->id,
								'created_at' => date('Y-m-d H:i:s')
							);

							if ('task' == $param_key) {
								$newData = array_merge($newData, array('listing_num' => $list_num, 'optimization_num' => $optimization_num));
							} else {
								$newData = array_merge($newData, array('sales_target' => 0, 'profit_target' => 0));
							}

							$newId = $model->saveData($newData);

							if ('sales' == $param_key) {
								foreach ($sales_amount as $sk => $sval) {
									$sales_target += $sval;
									$month = $sk+1;
									$month = ($month < 10) ? '0'.$month : $month ;
									$ex_row = $extend_model->getoneByCondition('id', " sales_id = '{$newId}' AND month = '{$month}' AND year = '{$year}'");
									if (!empty($ex_row)) {
										$extend_model->updateData(array('sales_amount' => $sval, 'seller_user_id' => $uid), " sales_id = '{$newId}' AND month = '{$month}' AND year = '{$year}'");
									} else {
										$extend_model->saveData(array('sales_amount' => $sval, 'seller_user_id' => $uid, 'sales_id' =>$newId, 'month' => $month, 'year'=>$year));
									}
								}

								foreach ($profit_amount as $pk => $pv) {
									$profit_target += $pv;
									$month = $pk+1;
									$month = ($month < 10) ? '0'.$month : $month ;
									$ex_row = $extend_model->getoneByCondition('id', " sales_id = '{$newId}' AND month = '{$month}' AND year = '{$year}'");
									if (!empty($ex_row)) {
										$extend_model->updateData(array('profit_amount' => $pv, 'seller_user_id' => $uid), " sales_id = '{$newId}' AND month = '{$month}' AND year = '{$year}'");
									} else {
										$extend_model->saveData(array('profit_amount' => $pv, 'seller_user_id' => $uid, 'sales_id' =>$newId, 'month' => $month, 'year'=>$year));
									}
								}

								$updateData = array(
									'sales_target' => $sales_target,
									'profit_target' => $profit_target
								);
								$model->updateData($updateData, $newId);
							}
							$ids[] = $newId;
						}
					}
				}

				//循环完销售，则移除不选中的
				/*
				if (!empty($ids)) {
					//把不在这里的id数据找出来，保存到删除日志里
					$log_arr = $model->getDataByCondition("*", "seller_user_id = '{$uid}'  AND id NOT IN('" . join("','", $ids) . "')");
					if (!empty($log_arr)) {
						$delete_model = $this->model('delete_log');
						foreach ($log_arr as $ke => $va) {
							//获取扩展表的设置信息
							$extend_rows = array();
							if ('sales' == $param_key) {
								$extend_rows = $extend_model->getDataByCondition("*", " sales_id = '" . $va['id'] . "'");
								//删除扩展表中的数据
								$extend_model->remove($va['id']);
							}
							$delete_model->saveData(
								array(
									'seller_user_id' => $va['seller_user_id'],
									'account_id' => $va['account_id'],
									'site_id' => $va['site_id'],
									'param_key' => $param_key,
									'data' => json_encode(array('data' => $va, 'extend' => $extend_rows)),
									'created_at' => date('Y-m-d H:i:s'),
								)
							);
						}
					}
					$model::remove($uid, $ids);
				}
				*/
			}
		}

		$returnData = $this->returnData($param_key);
		$jsonData = array(
			'message' => '更改成功',
			'forward' => $returnData['forward'],
			'navTabId' => $returnData['navTabId'],
			'callbackType' => 'closeCurrent'
		);
		echo $this->successJson($jsonData);
	}


	/**
	 * @param $seller_id
	 * @param $account_id
	 * @param $site
	 * @param string $platform
	 * @return bool
	 *
	 * 根据销售账号，站点，账号核对是否可以设定此数据
	 */
	private function checkSellerSiteAccount($seller_id, $account_id, $site, $platform = Platform::CODE_EBAY)
	{
		$seller_user = SellerUserToAccountSite::model()->checkSellerAccountSite($platform, $account_id, $site, $seller_id);
		return !empty($seller_user) ? (($seller_id == $seller_user['seller_user_id']) ? true : false) : false;
	}


	protected function returnData($param_key)
	{
		$data = array();
		switch ($param_key) {
			case 'task':
				$data['forward'] = Yii::app()->createUrl('/task/tasksetting');
				$data['navTabId'] = 'page' . Menu::model()->getIdByUrl('/task/tasksetting');
				break;
			case 'sales':
				$data['forward'] = Yii::app()->createUrl('/task/tasksalesetting');
				$data['navTabId'] = 'page' . Menu::model()->getIdByUrl('/task/tasksalesetting');
				break;
            case 'appeal':
                $data['forward'] = Yii::app()->createUrl('/task/task/index');
                $data['navTabId'] = 'page_task_index';
                break;
            case 'appeal_process':
                $data['forward'] = Yii::app()->createUrl('/task/task/exception');
                $data['navTabId'] = 'page_listing_exception';
                break;
			default:
				$data['forward'] = Yii::app()->createUrl('/task/tasksetting');
				$data['navTabId'] = 'page' . Menu::model()->getIdByUrl('/task/tasksetting');
				break;
		}

		return $data;
	}
}