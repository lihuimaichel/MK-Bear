<?php
/** 
 * Entrance Of API
 * @package API.controllers
 * @author Gordon
 * @since 2015-01-05
 */
class ApiController extends UebController {
	
	/**
	 * @desc API请求入口方法
	 */
	public function actionIndex(){
		set_time_limit(60);
		ini_set('display_errors', false);
		error_reporting(0);
		$this->layout = false;
		$apiParam = $_REQUEST['col'];
		$apiParam = json_decode($apiParam);
		$model = new ApiModel;
		$attribute = $apiParam;
		if( $apiParam ){
			$model->initApiParam($attribute);
			if( $model->authenticate() ){//通过验证
				$result = $model->run();	
				echo json_encode($result);//返回json格式
				MHelper::writefilelog('api_success_'.date('Ymd').'.txt',date("Y-m-d H:i:s").' ##### '.json_encode(array('request'=>$apiParam,'response'=>$result))."\r\n\r\n");
			}else{
				echo json_encode($model->_buildReturnData());//返回json格式
				MHelper::writefilelog('api_failure_'.date('Ymd').'.txt',date("Y-m-d H:i:s").' ##### '.json_encode(array('request'=>$apiParam,'response'=>$model->_buildReturnData()))."\r\n\r\n");
			}
		}
	}
}