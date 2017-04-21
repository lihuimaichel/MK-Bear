<?php
/**
 * 
 * @author lihy
 *
 */
class WishbuyerskuscheduledownloadfileController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new WishZeroStockSku()
		));
	}
	
	/**
	 * @link /wish/wishbuyerskuscheduledownloadfile/createskuschedule/day/
	 */
	public function actionCreateskuschedule(){
		set_time_limit(3600);
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		$wishBuyerSkuScheduleModel = new WishBuyerSkuSchedule();
		$wishModel = new WishSpecialOrderAccount;
		$lists = $wishModel->findAll("status=1 and schedule_status=1");
		$day = Yii::app()->request->getParam("day", 20);
		$daycount = Yii::app()->request->getParam("daycount", 50);
		if(empty($day)) $day = 20;
		if(empty($daycount)) $daycount = 50;
		if($lists){
			$filename = 'everydayskuschedule_' . date("Y-m-d")."_".rand(1000, 9999).'.csv'; //设置文件名
			$uploadDir = "./uploads/downloads/";
			if(!is_dir($uploadDir)){
				mkdir($uploadDir, 0755, true);
			}
			$filename2 = $uploadDir.$filename;
			$fd = fopen($filename2, "w+");
			echo "<pre>";
			foreach ($lists as $list){
				if(empty($list['buyer_id'])) continue;
				//if($list['buyer_id'] != '574d2a7a34220519387dcec6') continue;
				$order = intval($list['schedule_order']);
				$exportData = $wishBuyerSkuScheduleModel->createSkuSchedule2($list['buyer_id'], $day, $daycount, 0, $order);// 
				
				//print_r($exportData);
				$wishBuyerSkuScheduleModel->getExportData($fd, $exportData, $list['buyer_id']);
				//break;
			}
			
			fclose($fd);
			 //写入日志表
			FileDownloadList::model()->addData(array(
					'filename'		=>	"wish排期SKU列表-".date("Y-m-d"),
					'local_path'	=>	$filename2,
					'create_time'	=>	date("Y-m-d H:i:s"),
					'create_user_id'=>	intval(Yii::app()->user->id),
					'platform_code'	=>	Platform::CODE_WISH
			));
		}
		echo "finish";
	}
	
	/**
	 * @link /wish/wishbuyerskuscheduledownloadfile/refreshorder
	 */
	public function actionRefreshorder(){
		exit;
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		set_time_limit(300);
		$wishBuyerAccountScheduleModel = new WishBuyerAccountSchedule();
		$wishBuyerSkuScheduleModel = new WishBuyerSkuSchedule();
		$wishModel = new WishSpecialOrderAccount;
		$lists = $wishModel->findAll("status=1");
		if($lists){
			foreach ($lists as $list){
				if(empty($list['buyer_id'])) continue;
				$order = intval($list['schedule_order']);
				if($order == 0){
					$orderField = "id";
					continue;
				}else{
					$orderField = "order_num".$order;
				}
				$orderStr = $orderField." ASC";
				$buyerAccountList = $wishBuyerAccountScheduleModel->getListPairsByBuyerID($list['buyer_id']);
				if(empty($buyerAccountList)) continue;
				foreach ($buyerAccountList as $account){
					//var_dump($account);
					$scheduleList = $wishBuyerSkuScheduleModel->getDbConnection()->createCommand()
											->from($wishBuyerSkuScheduleModel->tableName())
											->order($orderStr)
											->where("account_name='{$account['account_name']}'")
											->limit(5000)
											->queryAll();
					//var_dump($scheduleList);
					$curMaxScheduleList = isset($scheduleList[$account['schedule_offset']-1]) ? $scheduleList[$account['schedule_offset']-1] : $scheduleList[0];
					//var_dump($curMaxScheduleList);
					$newOrderScheduleList = array_slice($scheduleList, $account['schedule_offset']);
					//var_dump($newOrderScheduleList);
					if($newOrderScheduleList){
						$maxOrderNum = $curMaxScheduleList[$orderField]++;
						foreach ($newOrderScheduleList as $schedule){
							//$orderNumber = rand($maxOrderNum, 20000);
							++$maxOrderNum;
							$wishBuyerSkuScheduleModel->getDbConnection()->createCommand()
											->update($wishBuyerSkuScheduleModel->tableName(), array($orderField=>$maxOrderNum), "id='{$schedule['id']}'");
						}
					}
				}
				//@todo test
				
			}
		}
		echo "finish";
	}
	
	
	/**
	 * @link /wish/wishbuyerskuscheduledownloadfile/createschduleaccount/runtype/
	 */
	public function actionCreateschduleaccount(){
		$runtype = Yii::app()->request->getParam("runtype");
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		if($runtype != 10){
			exit("此执行会对数据进行初始化，确定要执行的话，请指定runtype=10");
		}
		$wishSkuScheduleModel = new WishBuyerSkuSchedule();
		//初始化sku序号
		$wishSkuScheduleModel->getDbConnection()->createCommand("UPDATE ueb_wish_buyer_sku_schedule SET 
																order_num1=ROUND(RAND()*10000),
																order_num2=ROUND(RAND()*10000),
																order_num3=ROUND(RAND()*10000),
																order_num4=ROUND(RAND()*10000),
																order_num5=ROUND(RAND()*10000),
																order_num6=ROUND(RAND()*10000),
																order_num7=ROUND(RAND()*10000),
																order_num8=ROUND(RAND()*10000),
																order_num9=ROUND(RAND()*10000),
																order_num10=ROUND(RAND()*10000)
																where id>0")->execute();
		//获取accountID
		$accountList = $wishSkuScheduleModel->getDbConnection()->createCommand()->select("account_name")->from($wishSkuScheduleModel->tableName())->group("account_name")->queryAll();
		if(!$accountList){
			exit("账号为空");
		}
		$newAccountList = array();
		foreach ($accountList as $account){
			$_accountName = strtoupper(trim($account['account_name']));
			$newAccountList[$_accountName] = $_accountName;
		}
		//获取buyerID
		$wishSpecialOrderAccountModel = new WishSpecialOrderAccount();
		$buyerIDs = $wishSpecialOrderAccountModel->getDbConnection()->createCommand()->select("buyer_id")
											->from($wishSpecialOrderAccountModel->tableName())
											->where("status=1 and schedule_status=1")
											->group("buyer_id")->queryAll();
		if(!$buyerIDs){
			exit("buyerID为空");
		}
		
		$wishBuyerAccountScheduleModel = new WishBuyerAccountSchedule();
		$wishBuyerAccountScheduleModel->getDbConnection()->createCommand()->delete($wishBuyerAccountScheduleModel->tableName());
		foreach ($buyerIDs as $buyerid){
			foreach ($newAccountList as $account){
				$data = array(
						'buyer_id'		=>	$buyerid['buyer_id'],
						'account_name'	=>	$account,
						'schedule_number'	=>	0,
						'schedule_offset'	=>	0
				);
				$wishBuyerAccountScheduleModel->getDbConnection()->createCommand()->insert($wishBuyerAccountScheduleModel->tableName(), $data);
			}
		}
		
		echo "finish!";
	}
	
}