<?php
/**
 * 云途API接口
 * @author gk
 * @since 2014/11/09
 */
require_once Yii::app()->basePath.'/extensions/xlogis/yuntu/config.php';
class YtServiceAction extends YtService{
	private $_error = '';

	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 新建快件信息
	 * @param string $packageId
	 * @param tinyInt $expressType
	 * @return object
	 */
	public function createPackage($packageData){
		$data = json_encode(array($packageData));
		$result = parent::curl_post($data, 'create');//执行上传
		$resultObj = json_decode($result);
		if( $resultObj->ResultCode=='0000' ){//成功
			$obj = $resultObj->Item['0'];
			if($obj->TrackStatus == 2){
				$check = $this->getTrackNum($packageData['OrderNumber']);
				$object = json_decode($check);
				if($object->ResultCode=='0000'){
					$trackInfo = $object->Item['0'];
					if($trackInfo->TrackingNumber){
						return 'success-%%'.$trackInfo->TrackingNumber.'-%%'.$trackInfo->WayBillNumber;
					}else{
						return 'error-%%查询跟踪号失败';
					}
				}else{
					return 'error-%%'.$object->ResultDesc;	//提交失败的错误
				}
			}else{
				$tracknum = $obj->OrderId;
				return 'success-%%'.$tracknum.'-%%'.$obj->WayBillNumber;
			}
		}else{//成功
			$obj = $resultObj->Item['0'];
			if( mb_strpos($obj->Feedback,'参考单号已存在') !== false ){//已上传过但没拿到tracknum的包裹重新获取
				$check = $this->getTrackNum($packageData['OrderNumber']);
				$object = json_decode($check);
				if($object->ResultCode=='0000'){
					$trackInfo = $object->Item['0'];
					if($trackInfo->TrackingNumber){
						return 'success-%%'.$trackInfo->TrackingNumber.'-%%'.$trackInfo->WayBillNumber;
					}else{
						return 'error-%%查询跟踪号失败';
					}
				}
			}else{ 
				return 'error-%%'.$obj->Feedback;
			}
		}
		return false;			
	}
	
	/**
	 * 获取tracknum
	 * @param string $packageId
	 * @return
	 */
	public function getTrackNum($packageId){
		$check = parent::curl_get('GetTrackNumber',array('orderId'=>$packageId));
		return $check;
	}
	
	public function getShipInfo($country){
		$result = parent::curl_get('getShipInfo',array('countryCode'=>$country));
		return $result;
	}
	
	/**
	 * 获取错误信息
	 * @return string
	 */
	public function getErrorMsg(){
		return $this->_error;
	}

	/**
	 * 获取国家列表
	 * @return object
	 */
	public function getCountry(){
		$result = parent::curl_get('getCountry');
		return $result;
	}

	/**
	 * 修改订单的重量
	 * @param $trackNum
	 * @param $weight
	 * @return string
	 */
	public function updateWeight($trackNum, $weight){
		if(empty($trackNum) || empty($weight)){
			return 'error-%%跟踪单号或者重量为空!';
		}
		$data = array(
			'OrderNumber'=>$trackNum,
			'Weight'=>$weight
		);
		try{
			$data = json_encode($data);
			$result = parent::curl_post($data, 'updateWeight');
			$resultObj = json_decode($result);
			$jsonErrorCode = json_last_error();
			if($jsonErrorCode > 0){
				throw new Exception('返回结果解析错误,错误code:'.$jsonErrorCode.'result:'.$result);
			}
			if($resultObj->ResultCode == '0000'){
				if($resultObj->Item->Rueslt == 'success'){
					return 'success-%%'.$resultObj->Item->OrderNumber.'重量修改成功!';
				}else{
					return 'error-%%接口返回错误:"'.$resultObj->Item->OrderNumber.'"'.$resultObj->Item->ErrorMeesage;
				}
			}else{
				return 'error-%%接口返回错误:'.$resultObj->ResultDesc;
			}
		}catch(Exception $e){
			return 'error-%%Exception:'.$e->getMessage();
		}
		return 'error-%%接口未知的错误!';
	}

	/**
	 * 云途获取面单
	 * @param $packageId
	 * @return array
	 */
	public function getPackageLabel($packageId){
		$returnArr = array(
			'flag'=>false,
			'errorMsg'=>'程序错误!'
		);
		if(empty($packageId)){
			$returnArr['errorMsg'] = '包裹号不能为空!';
			return $returnArr;
		}
		try{
			$data = json_encode(array($packageId));
			$result = parent::curl_post($data, 'printUrl');
			$resultObj = json_decode($result);
			$jsonErrorCode = json_last_error();
			if($jsonErrorCode > 0){
				throw new Exception('返回结果解析错误,错误code:'.$jsonErrorCode.'result:'.$result);
			}
			if($resultObj->ResultCode == '0000'){
				if(!empty($resultObj->Item)){
					if($resultObj->Item[0]->LabelPrintInfos[0]->ErrorCode == '100' && !empty($resultObj->Item[0]->Url)){
						$returnArr['flag'] = true;
						$returnArr['url'] = $resultObj->Item[0]->Url;
						$returnArr['errorMsg'] = '面单获取成功';
					}else{
						$returnArr['errorMsg'] = '接口返回错误:"data=>'.serialize($resultObj);
					}
				}else{
					$returnArr['errorMsg'] = '接口返回错误:'.$resultObj->ResultDesc;
				}
			}else{
				$returnArr['errorMsg'] = '接口返回错误:'.$resultObj->ResultDesc;
			}
		}catch(Exception $e){
			$returnArr['errorMsg'] = 'Exception:'.$e->getMessage();
		}
		return $returnArr;
	}

}