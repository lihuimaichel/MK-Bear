<?php
/**
 * @desc ebay拍卖计划
 * @author lihy
 *
 */
class EbayproductauctionController extends UebController{
	
	public function actionIndex(){
		$model = UebModel::model("EbayProductAddAuction");
		$this->render("index", array(
					'model'	=>	$model
		));			
	}
	
	/**
	 * @desc 更新拍卖状态
	 * @throws Exception
	 */
	public function actionUpdatestatus(){
		$ids = 	Yii::app()->request->getParam('ids');
		$type = Yii::app()->request->getParam('type');
		try{
			if(empty($ids)) throw new Exception("参数错误");
			$ids = explode(",", $ids);
			if($type == 1){
				$status = EbayProductAddAuction::AUCTION_STATUS_OFF;
			}else{
				$status = EbayProductAddAuction::AUCTION_STATUS_ON;
			}
			$res = UebModel::model("EbayProductAddAuction")->setAuctionStatus($ids, $status);
			if(!$res){
				throw new Exception("操作失败");
			}
			echo $this->successJson(array('message'=>'操作成功！'));
		}catch (Exception $e){
			echo $this->failureJson(array(
				'message'	=>	$e->getMessage()
			));
		}		
	}
	
	/**
	 * @desc 循环刊登拍卖产品
	 * @throws Exception
	 */
	public function actionCircleauctionadd(){
		set_time_limit(36000);
		ini_set("display_error", true);
		error_reporting(E_ALL);
		$ebayProductAddAuctionModel = new EbayProductAddAuction();
		$penddingList = $ebayProductAddAuctionModel->getProductAuctionPenddingAddList();
		//var_dump($penddingList);
		if($penddingList){
			foreach ($penddingList as $list){
				//@todo 每次循环刊登做日志记录
				$startTime = strtotime($list['start_time']);
				if(((time()-$startTime)/3600/24) % intval($list['plan_day']) < 1 && (time()-$startTime)/3600/24 > 1 ){
					//生成今日任务数据
					$time = date("Y-m-d H:i:s");
					//$time_bg = date("Y-m-d").' 00:00:00';
					//如果到了,判断当天是否有生成
					$timeHMS = substr($list['start_time'], 11, 8);
					if( time() >= strtotime(date("Y-m-d").' '.$timeHMS) ){
						$timebg = date("Y-m-d").' '.$timeHMS;
					}else{
						$timebg = date("Y-m-d H:i:s",strtotime(date("Y-m-d").' '.$timeHMS)-86400);
					}
					//有生成跳过
					$check = $ebayProductAddAuctionModel->checkAuctionExistsByCondition('start_time > "'.$timebg.'" AND start_time < "'.$time.'" AND pid = '.$list['id']);
					if($check){
						continue;
					}
					try {
						$dbtransaction = $ebayProductAddAuctionModel->getDbConnection()->beginTransaction();
						
						//添加产品
						$newAddID = EbayProductAdd::model()->autoAddProduct($list['add_id']);
						if($newAddID){
							//添加拍卖数据
							$data = array(
									'add_id'	=>	$newAddID,
									'update_time' => $time,
									'update_user_id' => '0',
									'start_time' => $time,
									'end_time' => $time,
									'pid' => $list['id']
							);
							$id = $ebayProductAddAuctionModel->saveData($data);
							if(!$id) throw new Exception("add auction data failure");
							
							//更新最初的拍卖数据
							$updateData = array(
								'count'=>$list['count']+1,
								'update_time'=>$time
							);
							$res = $ebayProductAddAuctionModel->updateDataByID($updateData, $list['id']);
							if(!$res){
								throw new Exception("update auction failure");
							}
						}
						echo "{$list['id']} done";
						$dbtransaction->commit();
					}catch (Exception $e){
						$dbtransaction->rollback();
						echo $e->getMessage();
						continue;
					}
				}
			}
		}
	}
}