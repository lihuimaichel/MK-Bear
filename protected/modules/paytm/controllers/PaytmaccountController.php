<?php
/**
 * @desc paytm账号控制器
 * @since 2017-02-28
 *
 */
class PaytmaccountController extends UebController {

	/**
	 * 获取授权code
	 * @link /paytm/paytmaccount/getauthorizecode/debug/1/account_id/1
	 */
	public function actionGetauthorizecode($accountID,$state) {
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$saccountID = trim(Yii::app()->request->getParam('account_id'));
		$sstate = trim(Yii::app()->request->getParam('state','abc'));
		$accountID = $accountID ? $accountID : $saccountID;
		$state = $state ? $state : $sstate;
		if($accountID == '') {
			die('account_id is empty');
		}
		$pass = array();//生产环境有值
		$request = new GetAuthorizeCodeRequest();
		$request->setAccount($accountID);
		$request->setPassword($pass[$accountID]);
		$request->setState($state);
		$res = $request->setRequest()->sendRequest()->getResponse();
		echo '<pre>';print_r($res);	
		return $res;	
	}

	/**
	 * 获取token
	 * @link /paytm/paytmaccount/gettoken/debug/1/account_id/1
	 */
	public function actionGettoken(){
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$accountID = trim(Yii::app()->request->getParam('account_id'));
		$state = trim(Yii::app()->request->getParam('state','abc'));
		if($accountID == '') {
			die('account_id is empty');
		}		
		$getAuthCodeRes = $this->actionGetauthorizecode($accountID,$state);
		if($getAuthCodeRes && !isset($getAuthCodeRes->error)) {
			$request = new GetTokenRequest();
			$request->setAccount($accountID);
			$request->setCode($getAuthCodeRes->code);
			$request->setState($getAuthCodeRes->state);
			$res = $request->setRequest()->sendRequest()->getResponse();
			echo '<pre>';print_r($res);	
			if($request->getIfSuccess() && $res) {
				$model = new PaytmAccount();
				$accountInfo = $model->find("id={$accountID}");
				$accountInfo->access_token       = $res->access_token;
				$accountInfo->token_expired_time = time()+intval($res->expires_in);
				$accountInfo->refresh_token      = $res->refresh_token;
				$accountInfo->modify_time        = date('Y-m-d H:i:s');
				$isOk = $accountInfo->save();
				var_dump($isOk);
			}
		}
		Yii::app()->end("finish");
	}

	/**
	 * @desc 刷新token
	 * @link /paytm/paytmaccount/refreshtoken/account_id/1
	 */
	public function actionRefreshtoken() {
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$saccountID = Yii::app()->request->getParam('account_id');
		$condition = $saccountID ? " and id={$saccountID} " : '';
		$accountList = PaytmAccount::model()->getListByCondition('id,account_name,token_expired_time',
			"status=".PaytmAccount::STATUS_OPEN.$condition);
		foreach ($accountList as $info){
			$accountID = $info['id'];
			echo "<br>", $accountID," -- ",$info['account_name'];
			if($info['token_expired_time']-24*3600 > time()){//快过期的24小时内刷新
				echo "不需刷token";
				continue;
			}
			echo "<br>";
			$getAuthCodeRes = $this->actionGetauthorizecode($accountID,'abc');
			if($getAuthCodeRes && !isset($getAuthCodeRes->error)) {
				$request = new GetTokenRequest();
				$request->setAccount($accountID);
				$request->setCode($getAuthCodeRes->code);
				$request->setState($getAuthCodeRes->state);
				$res = $request->setRequest()->sendRequest()->getResponse();
				echo '<pre>';print_r($res);
				if($request->getIfSuccess() && $res) {
					$model = new PaytmAccount();
					$accountInfo = $model->find("id={$accountID}");
					$accountInfo->access_token       = $res->access_token;
					$accountInfo->token_expired_time = time()+intval($res->expires_in);
					$accountInfo->refresh_token      = $res->refresh_token;
					$accountInfo->modify_time        = date('Y-m-d H:i:s');
					$isOk = $accountInfo->save();
					echo $accountID.' --- '.$info['account_name']."刷新成功!<br>";
				}
			}
		}
		Yii::app()->end("## finish ##");
	}

	/**
	 * @desc 同步账号信息
	 * @link /paytm/paytmaccount/synctokentooms/account_id/1
	 */
	public function actionSynctokentooms(){
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );
		
		$saccountID = Yii::app()->request->getParam('account_id');
		$condition = $saccountID ? " and id={$saccountID} " : '';
		$accountList = PaytmAccount::model()->getListByCondition('*',"status=".PaytmAccount::STATUS_OPEN.$condition);

		//循环每个账号发送一个拉listing的请求
		foreach ($accountList as $accountInfo) {
			$accountID = $accountInfo['id'];
			$info = OmsPaytmAccount::model()->getOneByCondition('iid',"iid='{$accountID}'");
			if(empty($info)){
				$data = array(
						'iid'                => $accountInfo['id'],//需要同步的信息
						'account_name'       => $accountInfo['account_name'],
						'short_name'         => $accountInfo['short_name'],
						'status'             => $accountInfo['status'],
						'is_lock'          	 => $accountInfo['is_lock'],						
						'site'               => '',
						'shop_id'            => 0,
						'partner_name'       => '',
						'partner_id'      	 => 0,
						'access_token'       => '',
						'refresh_token'      =>	'',
						'secret_key' 		 =>	'',
						'service_url'        =>	'',
				);
				$res = OmsPaytmAccount::model()->insertData($data);
			}else{
				$data = array(
						'account_name'       => $accountInfo['account_name'],
						'short_name'      	 =>	$accountInfo['short_name'],
						'status'             => $accountInfo['status'],
						'is_lock'          	 => $accountInfo['is_lock'],	
				);
				$res = OmsPaytmAccount::model()->updateData($data, "iid='{$accountID}'");
			}
			echo $accountID.' -- '. $accountInfo['account_name'].'--'.($res?'success':'failure')."<br>";
		}
		Yii::app()->end("## finish ##");
	}	

}