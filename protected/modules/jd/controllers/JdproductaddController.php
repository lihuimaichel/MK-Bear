<?php
/**
 * @desc 京东刊登controller
 * @author zhangf
 *
 */
class JdproductaddController extends UebController {
	
	/**
	 * @desc 自动上传产品
	 */
	public function actionAutouploadproduct() {
		//set_time_limit(0);
		$addIDs = array();
		$successList = array();
		$addInfos = JdProductAdd::model()->getWaitingUploadRecords();
		if (!empty($addInfos)) {
			foreach ($addInfos as $addInfo) {
				$addIDs[$addInfo['account_id']][] = $addInfo['id'];
			}
		}
		foreach ($addIDs as $accountID => $ids) {
			//创建日志
			$jdLog = new JdLog();
			$logID = $jdLog->prepareLog($accountID, JdProductAdd::EVENT_NAME);
			if ($logID) {
				//检查任务是否可以运行
				$checkRunning = $jdLog->checkRunning($accountID, JdProductAdd::EVENT_NAME);
				if (!$checkRunning) {
					$jdLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
				} else {
					//设置日志正在运行
					$jdLog->setRunning($logID);
					foreach ($ids as $id) {
						$wareID = JdProductAdd::model()->uploadProduct($id);
						if (!empty($wareID))
							$successList[$accountID][] = $wareID;
					}
					//更新日志信息
					$jdLog->setSuccess($logID);
				}
			}
		}
		//将上传的产品拉到系统
		if (!empty($successList)) {
			foreach ($successList as $accountID => $wareIDs)
				MHelper::runThreadSOCKET('/jd/jdapirequest/getware/account_id/' . $accountID . '/ware_id/' . implode(',', $wareIDs));
		}
		exit('DONE');
	}
	
	/**
	 * @desc 测试添加产品图片
	 */
	public function actionAddimage() {
		$addID = Yii::app()->request->getParam('add_id');
		$addInfo = JdProductAdd::model()->findByPk($addID);
		$images = ProductImageAdd::model()->getImageBySku($addInfo->sku, $addInfo->account_id, Platform::CODE_JD);
		$imagesZT = $images[ProductImages::IMAGE_ZT];
		$images = array_shift($imagesZT);
		$imagesFT = $images[ProductImages::IMAGE_FT];
		$accountID = $addInfo->account_id;
		$wareID = $addInfo->ware_id;
		foreach ($imagesZT as $image) {
			$imagePath = $image['local_path'];
			$JdProductAddModel = new JdProductAdd();
			$flag = $JdProductAddModel->addProductImage($accountID, $wareID, $imagePath);
			if (!$flag) {
				echo '--------------';
				echo $JdProductAddModel->getErrorMessage();
			}
		}
	}
	
	/**
	 * @desc 待刊登列表
	 * @author Michael
	 */
	public function actionList(){
		$model = JdProductAdd::model();
		$this->render('list',array(
				'model'		=> $model
		));
	}
	
	public function actionAddvariationproduct() {
		set_time_limit(0);
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		$skuInfo = array(
			'sku' => '57382.01',
			'attributes' => '10494:2269214',
			'price' => 737,
			'stock_num' => 999,
			'id' => 1,
		);
		JdProductAdd::model()->addVariationSku(1, 931100, $skuInfo);
	}
	
	/**
	 * @desc 删除待刊登列表
	 * @author Michael
	 */
	public function actionDelete()
	{
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				$flag =UebModel::model('JdProductAdd')->deleteLazadaById($_REQUEST['ids']);
				if (!$flag ) {
					throw new Exception('Delete failure');
				}
				$jsonData = array(
						'message' => Yii::t('system', 'Delete successful'),
				);
				echo $this->successJson($jsonData);
			} catch (Exception $exc) {
				$jsonData = array(
						'message' => Yii::t('system', 'Delete failure')
				);
				echo $this->failureJson($jsonData);
			}
			Yii::app()->end();
		}
	}
	
	public function actionTest() {
		//set_time_limit(0);
		ini_set('display_errors', true);
		error_reporting(E_ALL);
		ini_set('max_input_time', 1200);
		$url = 'http://gw.api.alibaba.com/openapi/';
		//$url = 'https://api.jd.com/routerjson';
		$imageUrl = 'http://172.16.1.11/upload/image/main/6/9/69847.01-1.jpg';
		$filePath = dirname(__FILE__) . '/69847.01-1.jpg';
		$imageBytes = file_get_contents($filePath);
		$imageBytes = base64_encode($imageBytes);
		//$a = file_get_contents($url);
		//var_dump($a);exit;
/* 		try {
		$curl = Yii::app()->curl;
		$curl->setOption(CURLOPT_TIMEOUT,300);
		$curl->setOption(CURLOPT_CONNECTTIMEOUT,300);
		$curl->setOption(CURLOPT_HTTPHEADER, array("Expect:"));
		$response = $curl->post($url, $imageBytes);
		var_dump($response);
		
		} catch (Exception $e) {
			var_dump($curl->getInfo());
			
		} */
 		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1200);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1200);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $imageBytes);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
 		var_dump($response);
		var_dump(curl_getinfo($ch));
		$error = curl_error($ch);
		var_dump($error);
	}
}