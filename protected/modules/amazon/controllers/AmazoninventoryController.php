<?php
/**
 * Amazon Inventory Controller
 * @author 	Rex
 * @since 	2016-07-05
 */

class AmazoninventoryController extends UebController {

    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('getorders')
			),
		);
    }

	/**
	 * 获取线上AMAZON仓库库存
	 * @author 	Rex
	 * @since 	2016-07-05
	 * @link 	/amazon/amazoninventory/getfbainventory/account_id/13
	 */
	public function actionGetfbainventory() {
		$accountID = Yii::app()->request->getParam('account_id');
		$eventName = AmazonFbaInventory::EVENT_NAME;

		$startDateTime = date('Y-m-d 00:00:00', strtotime('-30 days')).'.000Z';
		
		if ($accountID) {
			//var_dump($accountID);
			$amazonLogModel = new AmazonLog();
			//$logID = $amazonLogModel->prepareLog($accountID, $eventName);
			$logID = 100;
			if ($logID) {

				$checkRunning = $amazonLogModel->checkRunning($accountID, $eventName);
				if (!$checkRunning) {
					$amazonLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
					exit("There Exists An Active Event");
				}else {

					$startDateTime = str_replace(" ", "T", $startDateTime);
					//获取库存
					$amazonInventoryModel = new AmazonFbaInventory();
					$amazonInventoryModel->setAccountID($accountID);
					$amazonInventoryModel->setQueryStartDateTime($startDateTime);
					$amazonInventoryModel->setLogID($logID);
					$amazonInventoryModel->getFbaInventoryList();
				}

			}

		} else {
			$fbaAccountIds = Yii::app()->erpApi->setServer('oms')->setFunction('warehouses:Warehouse:getAmazonFbaWarehouseOrAccountID')->setRequest(array(0,3))->sendRequest()->getResponse();
			foreach($fbaAccountIds as $accountID => $value){
    			MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$accountID);
    			sleep(5);
    		}
		}
		echo 'END';

	}

}


?>