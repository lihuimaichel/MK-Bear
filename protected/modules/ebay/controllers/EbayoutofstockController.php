<?php
/**
 * @desc ebay 断货设置
 * @author yangsh
 * @since 2016-08-29
 */
class EbayoutofstockController extends UebController
{

    /**
     * [actionList description]
     * @return [type] [description]
     */
	public function actionList(){
		$this->render("list", array( 'model'=>EbayOutofstock::model() ) );
	}

	/**
	 * @desc 设置断货处理,针对listing loction为本地仓发货
	 */
	public function actionSetoutofstock() {
		if (!Yii::app()->request->isAjaxRequest || !isset($_REQUEST['ids']) ) {
			echo $this->failureJson(array('message'=> Yii::t('system', 'Illegal Operation')));
			Yii::app()->end();
		}
		$ebayOutofstock 	= EbayOutofstock::model();
		$skuArr         	= explode(',',$_REQUEST['ids']);
		$userId         	= (int)Yii::app()->user->id;
		$nowTime        	= date('Y-m-d H:i:s');
		$allItems       	= array();
		$success        	= $failure = $failureMsg = array();
		$insertData     	= array();
		foreach ($skuArr as $sku){
			$row  = array('sku'=>$sku,'is_outofstock'=>0,'operator'=>$userId,'operate_time'=>$nowTime);
			$info = $ebayOutofstock->getOneByCondition('id',"sku='{$sku}'");
			if (empty($info)) {
				$ebayOutofstock->dbConnection->createCommand()->insert($ebayOutofstock->tableName(),$row);
			} else {
				$ebayOutofstock->dbConnection->createCommand()->update($ebayOutofstock->tableName(),$row,"sku='{$sku}'");
			}
			$data = EbayProductVariation::model()->filterByCondition('v.account_id,v.item_id,v.sku_online,v.sku,v.quantity_available,p.location',"v.sku='{$sku}' and p.item_status=1 and v.account_id not in(". implode(',',EbayAccount::$OVERSEAS_ACCOUNT_ID) .") ");
			//MHelper::writefilelog('my.txt','data---'.print_r($data,true)."\r\n");//test
			if ( empty($data) ) {
				continue;
			}
			foreach ($data as $v) {
				$item = array('item_id'=>$v['item_id'],'sku_online'=>$v['sku_online'],'count'=>0,'callback_sku'=>$v['sku'],'old_available'=>$v['quantity_available']);
				$reviseData[$v['account_id']][] = $item;
				$combinKey                      = $v['item_id'].'_'.$v['sku_online'];
				$allItems[$combinKey]           = $item;
			}
			//MHelper::writefilelog('my.txt','reviseData----'.print_r($reviseData,true)."\r\n");//test
			if ($reviseData) {
				foreach ($reviseData as $accountID => $datalist) {
					foreach ($datalist as $val) {
						$callbackArr = EbayProduct::model()->reviseEbayListing($accountID, $val);
						//MHelper::writefilelog('my.txt','callbackArr----'.print_r($callbackArr,true)."\r\n");//test
						if (!empty($callbackArr) && $callbackArr['errorCode'] == 200) {
							$success[] = $callbackArr['data'];
						} else {
							$failure[] = $val['item_id'].'_'.$val['sku_online'];
							$failureMsg[] = $callbackArr['errorMsg'];
						}
					}
				}
			}
		}
		$result = array();
		if($success){
			foreach ($success as $v) {
				$tmp = explode('_',$v);
				$item = $allItems[$v];
				$result[$item['callback_sku']]['success'][$tmp[0]][$tmp[1]] = $item['old_available'];
			}
		}
		if ($failure) {
			foreach ($failure as $v) {
				$tmp = explode('_',$v);
				$item = $allItems[$v];
				$result[$item['callback_sku']]['failure'][$tmp[0]][$tmp[1]] = $item['old_available'];
			}
		}
		$ack = empty($failure) || !empty($success) ? 1 : 2;
		$isOutofstock = $ack == 1 ? 1 : 2;
		foreach ($result as $sku => $v) {
			$operatorNote = array('is_outofstock'=>$isOutofstock,'operate_time'=>$nowTime,'data'=>array(1=>array('success'=>isset($v['success'])?$v['success']:array(),'failure'=>isset($v['failure'])?$v['failure']:array())) );
			$ebayOutofstock->dbConnection->createCommand()->update($ebayOutofstock->tableName(),array('is_outofstock'=>$isOutofstock,'ack'=>$ack,'message'=> implode(',',$failureMsg),'operator'=>$userId,'operate_time'=>$nowTime,'operate_note'=> json_encode($operatorNote) ),"sku='{$sku}'");
		}
		if ($ack == 1) {
			$jsonData = array(
				'message' =>Yii::t('system', 'Success Operation'),
				'navTabId'=>'page' . Menu::model()->getIdByUrl('/ebay/ebayoutofstock/list')
			);
			echo $this->successJson($jsonData);
		} else {
			echo $this->failureJson(array('message'=> Yii::t('system', 'Failure Operation')));
		}
		Yii::app()->end();
	}

	/**
	 * @desc 取消断货处理,针对listing loction为本地仓发货
	 */
	public function actionCanceloutofstock() {
		if (!Yii::app()->request->isAjaxRequest || !isset($_REQUEST['ids']) ) {
			echo $this->failureJson(array('message'=> Yii::t('system', 'Illegal Operation')));
			Yii::app()->end();
		}
		$ebayOutofstock 	= EbayOutofstock::model();
		$skuArr         	= explode(',',$_REQUEST['ids']);
		$nowTime        	= date('Y-m-d H:i:s');
		$userId         	= (int)Yii::app()->user->id;
		$allItems       	= array();
		$setArr 			= array();
		$success        	= $failure = array();
		foreach ($skuArr as $sku){
			$data = EbayProductVariation::model()->filterByCondition('v.account_id,v.item_id,v.sku_online,v.sku,v.quantity_available,p.location',"v.sku='{$sku}' and p.item_status=1 and v.account_id not in(". implode(',',EbayAccount::$OVERSEAS_ACCOUNT_ID) .") ");
			if (empty($data)) {
				continue;
			}
			$exist = 0;
			foreach ($data as $v) {
				$exist = 1;
			}
			if (!$exist) {
				continue;
			}
			$res = $ebayOutofstock->getOneByCondition('*',"sku='{$sku}'");
			if (empty($res)) {
				continue;
			}
			$operatorNote = json_decode($res['operate_note'],true);
			if(empty($operatorNote['data']) || empty($operatorNote['data'][1]) || empty($operatorNote['data'][1]['success'])) {
				continue;
			}
			$setArr[$sku] = $operatorNote['data'][1];
			foreach ($operatorNote['data'][1]['success'] as $itemID => $datalist) {
				$ebayProductInfo = EbayProduct::model()->getEbayProductInfoJoinAccount('a.account_id,b.relist_qty',"a.item_id='{$itemID}'");
				if (empty($ebayProductInfo)) {
					continue;
				}
				foreach ($datalist as $onlineSku => $relistQty) {
					$relistQty = (int)$ebayProductInfo['relist_qty'];//取销售账号设置的恢复数量 add in 20170215
					$item = array('item_id'=>$itemID,'sku_online'=>$onlineSku,'count'=>$relistQty,'callback_sku'=>$sku);
					$combinKey = $itemID.'_'.$onlineSku;
					$allItems[$combinKey] = $item;
					$reviseData[$ebayProductInfo['account_id']][] = $item;
				}
			}
			foreach ($reviseData as $accountID => $datalist) {
				foreach ($datalist as $val) {
					$callbackArr = EbayProduct::model()->reviseEbayListing($accountID, $val);
					if (!empty($callbackArr) && $callbackArr['errorCode'] == 200) {
						$success[] = $callbackArr['data'];
					} else {
						$failure[] = $val['item_id'].'_'.$val['sku_online'];
						$failureMsg[] = $callbackArr['errorMsg'];
					}
				}
			}
		}
		$result = array();
		if($success){
			foreach ($success as $v) {
				$tmp = explode('_',$v);
				$item = $allItems[$v];
				$result[$item['callback_sku']]['success'][$tmp[0]][$tmp[1]] = $item['count'];
			}
		}
		if ($failure) {
			foreach ($failure as $v) {
				$tmp = explode('_',$v);
				$item = $allItems[$v];
				$result[$item['callback_sku']]['failure'][$tmp[0]][$tmp[1]] = $item['count'];
			}
		}

		$ack = empty($failure) || !empty($success) ? 1 : 2;
		$isOutofstock = $ack == 1 ? 2 : 1;
		if ($result) {
			foreach ($result as $sku => $v) {
				$operatorNote = array('operate_time'=>$nowTime,'data'=>array(2=>array('success'=>isset($v['success'])?$v['success']:array(),'failure'=>isset($v['failure'])?$v['failure']:array()) ) );
				if (isset($setArr[$sku])) {
					$operatorNote['data'][1] = $setArr[$sku];
				}
				$ebayOutofstock->dbConnection->createCommand()->update($ebayOutofstock->tableName(),array('is_outofstock'=>$isOutofstock,'ack'=>$ack,'message'=> implode(',',$failureMsg),'operator'=>$userId,'operate_time'=>$nowTime,'operate_note'=>json_encode($operatorNote) ),"sku='{$sku}'");
			}
		} else {
			$ebayOutofstock->dbConnection->createCommand()->update($ebayOutofstock->tableName(),array('is_outofstock'=>$isOutofstock,'ack'=>$ack,'message'=> implode(',',$failureMsg),'operator'=>$userId,'operate_time'=>$nowTime,'operate_note'=>'' ),"sku='{$sku}'");
		}

		if ($ack == 1) {
			$jsonData = array(
				'message' =>Yii::t('system', 'Success Operation'),
				'navTabId'=>'page' . Menu::model()->getIdByUrl('/ebay/ebayoutofstock/list')
			);
			echo $this->successJson($jsonData);
		} else {
			echo $this->failureJson(array('message'=> Yii::t('system', 'Failure Operation')));
		}
		Yii::app()->end();
	}

}