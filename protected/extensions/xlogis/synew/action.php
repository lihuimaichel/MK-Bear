<?php
/**
 * 顺友 API 对接
 * @author ltf
 * @since 2016/08/08
 */
require_once Yii::app()->basePath.'/extensions/xlogis/synew/config.php';
class SyServiceAction extends SyService{
	
	//private $_error = '';

	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 获取配置信息
	 */
	/* public function getConfigInfo($queryType){
		return parent::curl_config_post($queryType, "GetConfig");
	} */
	
	/**
	 * 获取跟踪号
	 * @param string $packageData
	 * @return object
	 */
	public function getTrackNum($packageData){
		$result = parent::curl_post($packageData,'createAndConfirmPackages');//执行上传
		$resultObj = json_decode($result);
		if(isset($_REQUEST['bug'])){
			echo "<br/>=======syb ori api=======<br/>";
			print_r($resultObj).'<pre>';
			//print_r($resultObj->data->resultList);
		}
		try
		{
			if($resultObj->ack == 'success'){//请求成功
				$trackInfo	= $resultObj->data->resultList[0];
				if ($trackInfo->processStatus == 'success') {
					$syOrderNo 		= $trackInfo->syOrderNo;
					$trackingNumber	= $trackInfo->trackingNumber;
					$return = array('uploadflag'=>true,'uploadmsg'=>array('trackNum' => $trackingNumber,'processCode' => $syOrderNo));
				}else {
					$errorInfo	= $trackInfo->errorList;
					$errorMsg = '';
					foreach ($errorInfo as $val) {
						$errorMsg	.= 'Code:'.$val->errorCode.'  Message:'.$val->errorMsg.';';
					}
					$return = array('uploadflag'=>false,'uploadmsg'=>$errorMsg);
				}
			}else{//失败
				$errorCode	= $resultObj->errorCode;
				$errorMsg	= $resultObj->errorMsg;
				$return = array('uploadflag'=>false,'uploadmsg'=>'Code:'.$errorCode.'  Message:'.$errorMsg);
			}
			return $return;
		}catch (Exception $e){
			$return = array('uploadflag'=>false,'uploadmsg'=>'CatchException:'.$e->getMessage());
			return $return;
		}
	}
	
	/**
	 * 推送包裹信息
	 * @param unknown $code
	 * @return Ambigous <string>
	 */
	/* public function uploadPackageInfo($packageInfo) {
		$response = parent::curl_post_upload($packageInfo);//执行上传
		$resultObj = simplexml_load_string($response);
		$return = array();
		try
		{
			$retData = $resultObj->responseItems->response;
			if($retData->success == 'true'){//成功
				$packageId = '';
				$tracknum  = '';
				$return = array('uploadflag'=>true,'uploadmsg'=>$retData->success);
			}else{//失败
				$code = (string) $retData->reason;
				$errorMsg = $this->getUploadErrorCodeMsg($code);
				$return = array('uploadflag'=>false,'uploadmsg'=>'Code:'.$retData->reason.'  Message:'.$errorMsg);
			}
			return $return;
		}catch (Exception $e){
			$return = array('uploadflag'=>false,'uploadmsg'=>'CatchException:'.$e->getMessage());
			return $return;
		}
	} */
	
	/**
	 * 获取跟踪号错误信息
	 * @param unknown $code
	 * @return Ambigous <string>
	 */
// 	public function getErrorCodeMsg($code){
// 		$codeArr = array(
// 				'S01' => '非法的JOSN格式',
// 				'S02' => '非法的授权验证',
// 				'S03' => '非法的数字签名',
// 				'S04' => '网络超时',
// 				'S05' => '深圳集团系统异常',
// 				'S06' => '非法版本号 version 不正确',
// 				'B01' => '暂无条码分配',
// 				'B02' => '业务类型错误',
// 		);
// 		return $codeArr[$code];
// 	}
	
// 	/**
// 	 * 上传包裹错误信息
// 	 * @param unknown $code
// 	 * @return Ambigous <string>
// 	 */
// 	public function getUploadErrorCodeMsg($code){
// 		$codeArr = array(
// 				'S01' => '非法的XML/JOSN',//系统错误代码以下
// 				'S02' => '非法的数字签名',
// 				'S03' => '非法的物流公司/仓储公司',
// 				'S04' => '非法的通知类型',
// 				'S05' => '非法的通知内容',
// 				'S06' => '网络超时，请重试',
// 				'S07' => '系统异常，请重试',
// 				'S08' => 'HTTP状态异常（非200）',
// 				'S09' => '返回报文为空',
// 				'S10' => '找不到对应的网关信息',
// 				'S11' => '非法的网关信息',
// 				'S12' => '非法的请求参数',
// 				'S13' => '业务服务异常',//系统错误代码以上
// 				'B00' => '未知业务错误',//业务逻辑错误以下
// 				'B01' => '关键字段缺失',
// 				'B02' => '关键数据格式不正确',
// 				'B03' => '没有找到请求数据',
// 				'B04' => '当前数据状态不能进行该项操作',
// 				'B98' => '数据保存失败,包裹重复提交或其他错误'//业务逻辑错误以上
// 		);
// 		return $codeArr[$code];
// 	}
	
// 	/**
// 	 * 获取错误信息
// 	 * @return string
// 	 */
// 	public function getErrorMsg(){
// 		return $this->_error;
// 	}
}