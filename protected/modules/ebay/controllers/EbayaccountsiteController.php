<?php
class EbayaccountsiteController extends UebController
{
	/**
	 * @todo ebay站点管理列表
	 * @author Michael
	 * @since 2015/08/05
	 */
	public function actionIndex()
	{
 		$model = UebModel::model('EbayAccountSite');
 		$ebayAccountModel = new EbayAccount();
 		$ebayAccountSiteModel = new EbayAccountSite();
 		$ebaySiteModel = new EbaySite;
 		$warehouseModel = new Warehouse();
 		//1 获取账号列表
 		$accountList = $ebayAccountModel->getAbleAccountList();
 		//获取账号下面所有的站点数据
 		if($accountList){
 			foreach ($accountList as $key=>$account){
 				$accountSiteList = $ebayAccountSiteModel->getAccountSiteListByAccountID($account['id']);
 				$selectedSiteIDs = array();
 				$selectedWarehouseIDs = array();
 				if($accountSiteList){
 					foreach ($accountSiteList as $asc){
 						$selectedSiteIDs[] = $asc['site_id'];
 						$selectedWarehouseIDs[$asc['site_id']] = $asc['warehouse_id'];
 					}
 				}
 				$accountList[$key]['select_site_id'] = $selectedSiteIDs;
 				$accountList[$key]['select_warehouse_id'] = $selectedWarehouseIDs;
 			}
 		}
 		//2 获取站点列表
 		$siteList = $ebaySiteModel->getSiteList();
 		//3 获取仓库列表
 		$wareHouseList = $warehouseModel->getWarehousePairs();
 		
		$this->render('index', array(
				'model' 		=> 	$model,
				'accountList'	=>	$accountList,
				'siteList'		=>	$siteList,
				'wareHouseList'	=>	$wareHouseList
		));
	}
	/**
	 * @desc 保存
	 */
	public function actionSavedata(){
		//$this->print_r($_POST);
		$accountData = Yii::app()->request->getParam('account');
		$ebayAccountSiteModel = new EbayAccountSite();
		$failnum = 0;
		if($accountData){
			//MHelper::writefilelog('account_site.txt', json_encode($accountData)."\r\n" );
			$userID = Yii::app()->user->id;
			foreach ($accountData as $accountID=>$account){
				//清除掉每个账号下面的信息
				$ebayAccountSiteModel->deleteData("account_id=:account_id", array(":account_id"=>$accountID));
				$siteIDs = isset($account['site_id']) ? $account['site_id'] : array();
				if(empty($siteIDs)) continue;
				$warehouseIDs = isset($account['warehouse_id']) ? $account['warehouse_id'] : array();
				foreach ($siteIDs as $siteID){
					$data = array(
							'account_id'	=>	$accountID,
							'site_id'		=>	$siteID,
							'warehouse_id'	=>	$warehouseIDs[$siteID],
							'opration_id'	=>	$userID,
							'opration_date'	=>	date("Y-m-d H:i:s")
					);
					if(!$ebayAccountSiteModel->saveData($data)){
						$failnum++;
					}
				}
			}
		}
		if($failnum>0){
			echo $this->failureJson(array(
				'message'	=>	"部分保存失败!"
			));
		}else{
			$navTabId = 'page'.UebModel::model('Menu')->getIdByUrl('/ebay/ebayaccountsite/index');
			echo $this->successJson(array(
					'message'	=>	"保存成功",
					'navTabId'	=>	$navTabId
			));
		}
	}
}