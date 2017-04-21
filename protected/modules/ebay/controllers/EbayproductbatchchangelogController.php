<?php
class EbayproductbatchchangelogController extends UebController
{
	public function actionList(){
		$model = new EbayProductBatchChangeLog();
		$this->render("list", array('model'=>$model));
	}
	
	public function actionChangenow(){
		error_reporting(E_ALL);
		try{
			$id = Yii::app()->request->getParam('id');
			$ebayBatchChangemodel = new EbayProductBatchChangeLog();
			$info = $ebayBatchChangemodel->findByPk($id);
			if(empty($info)){
				throw new Exception("不存在");
			}
			if($info['status'] == EbayProductBatchChangeLog::STATUS_OPERATING ){
				throw new Exception("已经在上传了");
			}
			$model = new EbayProduct();
			$ebayBatchChangemodel->updateDataByPK($info['id'], array('status'		=>	EbayProductBatchChangeLog::STATUS_OPERATING,
						'update_time'	=>	date("Y-m-d H:i:s")));
			$res = $model->changOnlineDescriptionByItemID($info['item_id'], $info['account_id'], $info['type']);
			$nowtime = date("Y-m-d H:i:s");
			if(!$res){
				$exceptionMsg = $model->getExceptionMessage();
				$exceptionCode = $model->getExceptionCode();
				$data = array(
						'status'		=> $exceptionCode==2? EbayProductBatchChangeLog::STATUS_IMG_FAILURE : EbayProductBatchChangeLog::STATUS_FAILURE,
						'upload_count'	=>	intval($list['upload_count'])+1,
						'update_time'	=>	$nowtime,
						'last_msg'		=>	$exceptionMsg
				);
				$ebayBatchChangemodel->updateDataByPK($info['id'], $data);
				throw new Exception($model->getExceptionMessage());
			}else{
				$data = array(
						'status'		=>	EbayProductBatchChangeLog::STATUS_SUCCESS,
						'update_time'	=>	$nowtime,
						'last_msg'		=>	'success'
				);
				$ebayBatchChangemodel->updateDataByPK($info['id'], $data);
			}
			echo $this->successJson(array('message'=>'操作成功'));
		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
	}
	
	

	/**
	 * @DESC 批量更改
	 * @throws Exception
	 */
	public function actionBatchuploadupdatedesc(){
		error_reporting(E_ALL);
		$ids = Yii::app()->request->getParam('ids');
		try{
			if(empty($ids)){
				throw new Exception("还没有选择");
			}
			$idArr = explode(",", $ids);
			$errMsg = "";
			foreach ($idArr as $id){
				try{
					$ebayBatchChangemodel = new EbayProductBatchChangeLog();
					$info = $ebayBatchChangemodel->findByPk($id);
					if(empty($info)){
						throw new Exception("不存在");
					}
					if($info['status'] == EbayProductBatchChangeLog::STATUS_OPERATING ){
						throw new Exception("已经在上传了");
					}
					$model = new EbayProduct();
					$ebayBatchChangemodel->updateDataByPK($info['id'], array('status'		=>	EbayProductBatchChangeLog::STATUS_OPERATING,
							'update_time'	=>	date("Y-m-d H:i:s")));
					$res = $model->changOnlineDescriptionByItemID($info['item_id'], $info['account_id'], $info['type']);
					$nowtime = date("Y-m-d H:i:s");
					if(!$res){
						$exceptionMsg = $model->getExceptionMessage();
						$exceptionCode = $model->getExceptionCode();
						$data = array(
								'status'		=> $exceptionCode==2? EbayProductBatchChangeLog::STATUS_IMG_FAILURE : EbayProductBatchChangeLog::STATUS_FAILURE,
								'upload_count'	=>	intval($list['upload_count'])+1,
								'update_time'	=>	$nowtime,
								'last_msg'		=>	$exceptionMsg
						);
						$ebayBatchChangemodel->updateDataByPK($info['id'], $data);
						throw new Exception($model->getExceptionMessage());
					}else{
						$data = array(
								'status'		=>	EbayProductBatchChangeLog::STATUS_SUCCESS,
								'update_time'	=>	$nowtime,
								'last_msg'		=>	'success'
						);
						$ebayBatchChangemodel->updateDataByPK($info['id'], $data);
					}
				
				}catch (Exception $e){
					$errMsg .= $e->getMessage()."<br/>";
				}
			}
			echo $this->successJson(array('message'=>'操作成功'));
		}catch (Exception $e){
			echo $this->failureJson(array('message'=>$e->getMessage()));
		}
		
	}
	
	public function actionTest(){
		set_time_limit(3600);
		error_reporting(E_ALL);
		$itemIDS = array(
				/* '322266913474', */
				'322267027364','291880630325','162208985863','172347831677','172347969250','152247804978','162209138328','162209142440','282183962910',
				'182286799065','172347969312','272382925330','302080017890','162208987120','172347832197','182286935712','232088334314','152247806771','162209139935',
				'162209143878','361737231567','291880939851','291880939948','322266887209','322267027042','322266887282','322267027131','272382449160','291880607265',
				'282184304995','282183895570','272382811424','272382898162','291880920436','272382880880','112141894115','162208730559','142122836600','331975542218',
				'331975773419','291880465173','291880939416','302079637773','282172825609','222247793314','401191932690','291880939601','272382740319','282183948346',
				'361736768429','282183922719','172348400071','162209586525','291880541720','272382712661','322266861874','322266967726','322267026648','282183847009',
				'272382908849','322267354502','291880630073','302079638181','272383181889','361737232707','282183726653','302079146799','302079719265','322267379838',
				'282184480708','272383964747','322267903655','162208987014','201672308913','182286935455','162208984378','162209139330','162209143677','282183725553',
				'282183912229','232088308196','282183923111','282184303008','361737231990','162208987648','282183912798','222255320266','152247808080','152247963408',
				'162209144360','272383182045','361737233429','401191662072','311702463775','401192190145','322266887496','322267027987','302079767053'
				
		);
		$ebayBatchChangemodel = new EbayProductBatchChangeLog();
		$model = new EbayProduct();
		foreach ($itemIDS as $itemID){
			$info = $model->find("item_id=:item_id", array(':item_id'=>$itemID));
			if(empty($info)){
				echo "itemID:{$itemID} 不存在！<br/>";
				continue;
			}
			
			$res = $model->changOnlineDescriptionByItemID($info['item_id'], $info['account_id'], 2);
			if($res){
				echo "itemID:{$itemID} 成功！<br/>";
			}else{
				$msg = $model->getExceptionMessage();
				echo "itemID:{$itemID} 失败：{$msg}！<br/>";
			}
		}
	}
}