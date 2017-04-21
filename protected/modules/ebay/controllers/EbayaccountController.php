<?php
class EbayaccountController extends UebController
{
	
	/**
	 * @todo ebay帐号管理列表
	 * @author Michael
	 * @since 2015/07/31
	 */
	public function actionIndex()
	{
 		$model = UebModel::model('EbayAccount');
		$this->render('index', array(
				'model' => $model,
		));
	}
	
	/**
	 * @todo ebay添加帐号
	 * @author Michael
	 * @since 2015/07/31
	 */
	public function actionCreate()
	{
		$model = UebModel::model('EbayAccount');
		if(Yii::app()->request->isAjaxRequest && isset($_POST['EbayAccount'])){
			$model->attributes = $_POST['EbayAccount'];
			if($model->validate()){
				$transaction = $model->getDbConnection()->beginTransaction();
				$flag=true;
				try{
					$model->setIsNewRecord(true);
					$model->save();
					if(!empty($_POST['EbayAccount']['publish_count'])){
						$account_config_model = UebModel::model('Ebayaccountconfig');
						$account_config_model->setIsNewRecord(true);
						$account_config_model -> setAttribute('account_id',$model->attributes['id']);
						$account_config_model -> setAttribute('config_type','publish_count');
						$account_config_model -> setAttribute('config_value',$_POST['EbayAccount']['publish_count']);
						$account_config_model ->save();
					}
					if(!empty($_POST['EbayAccount']['if_adjust_count'])){
						$account_config_model1 = new Ebayaccountconfig();
						$account_config_model1->setIsNewRecord(true);
						$account_config_model1 -> setAttribute('account_id',$model->attributes['id']);
						$account_config_model1 -> setAttribute('config_type', 'if_adjust_count');
						$account_config_model1 -> setAttribute('config_value', $_POST['EbayAccount']['if_adjust_count']);
						$account_config_model1->save();
					}
					$transaction->commit();
				}catch(Exception $e){
					$flag = false;
					$transaction->rollback();
				}
			} 	
            if($flag){                  
                    $jsonData = array(                    
                        'message' => Yii::t('system','Add successful'),
                        'forward' => '/ebay/ebayaccount/index',
                    	'navTabId' => 'page'.EbayAccount::getIndexNavTabId(),
                        'callbackType' => 'closeCurrent'
                    );
                    echo $this->successJson($jsonData);
            } 
            if (!$flag) {
                echo $this->failureJson(array( 'message' => Yii::t('system', 'Add failure')));
            }
            Yii::app()->end(); 
		}else{
			$this->render('create',array('model' => $model));
		}
	}
	
	/**
	 * @todo ebay帐号开启
	 * @author Michael
	 * @since 2015/08/01
	 */
	public function actionOpen()
	{
		if(Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])){
			$ids = explode(',',$_REQUEST['ids']);
			foreach($ids as $key=>$id){
				$model = $this->loadModel($id);
				$flag = UebModel::model('EbayAccount')->getDbConnection()->createCommand()->update(EbayAccount::tableName(), array('status'=>Ebayaccount::STATUS_OPEN),'id=:id', array(':id' =>$id));
			}
			if($flag){
				$jsonData = array(
					'message' =>Yii::t('system', 'Save successful'),
					'forward' =>'/ebay/ebayaccount/index',
					'navTabId'=>'page' . Ebayaccount::getIndexNavTabId(),
					'callbackType'=>'closeCurrent'
				);
				echo $this->successJson($jsonData);
			}
			if(!$flag) {
				echo $this->failureJson(array('message'=>Yii::t('system','Save failure')));
			}
			Yii::app()->end();
		}
	}
	
	/**
	 * @todo ebay帐号锁定
	 * @author Michael
	 * @since 2015/08/01
	 */
	public function actionLock() {
		if(Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])){
			$ids = explode(',', $_REQUEST['ids']);
			foreach($ids as $key=>$id){
				$model = $this->loadModel($id);
				$flag  = UebModel::model('Ebayaccount')->getDbConnection()->createCommand()->update(Ebayaccount::tableName(),array('is_lock'=>Ebayaccount::STATUS_ISLOCK),'id=:id',array(':id'=>$id));
			}
			if($flag) {
				$jsonData = array(
					'message' =>Yii::t('system', 'Save successful'),
					'forward' =>'/ebay/ebayaccount/index',
					'navTabId'=>'page' . Ebayaccount::getIndexNavTabId(),
					'callbackType'=>'closeCurrent'
				);
				echo $this->successJson($jsonData);
			}
			if(!$flag) {
				echo $this->failureJson(array('message'=>Yii::t('system', 'Save failure')));
			}
			Yii::app()->end();
		}
	}


    /**
     * @desc 搜索账号
     * @author ketu.lai
     * @date 2017/02/20
     */
    public function actionAccountLookup()
    {
        $filters = array();
        $storeName = Yii::app()->request->getParam('short_name');
        if ($storeName) {
            $filters['short_name'] = $storeName;
        }
        $accountList = EbayAccount::model()->findAccountByFilter($filters);
        $this->render("account_lookup", array('accountList'=> $accountList));
    }
	/**
	 * @todo ebay帐号编辑
	 * @author Michael
	 * @since 2015/08/01
	 */
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		if(Yii::app()->request->isAjaxRequest && isset($_POST['EbayAccount'])) {
			if($model->validate()){
				$model->attributes = $_POST['EbayAccount'];
				$flag = $model->save();
				if($flag){
					$jsonData = array(
						'message' => Yii::t('system', 'Save successful'),
						'forward' =>'/ebay/ebayaccount/index',
						'navTabId'=> 'page' .EbayAccount::getIndexNavTabId(),
						'callbackType'=>'closeCurrent'
					);
					echo $this->successJson($jsonData);
				}else{
					$flag = false;
				}
			}
			if(!$flag){
				echo $this->failureJson(array('message'=>Yii::t('system','Save failure')));	
			}
		}else {
			$this->render('update',array('model' =>$model));
		}
		
	}
	/**
	 * @todo 实例化Model模型
	 * @param int $id
	 */
	public function loadModel($id){
		$model = EbayAccount::model()->findByPk((int)$id);
		if($model === null){
			throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
		}
		return $model;
	}
	
	/**
	 * @desc 根据站点id获取可用的账号
	 */
	public function actionGetaccountbysite(){
		$site = Yii::app()->request->getParam("site_id");
		$accountAll  = EbayAccountSite::model()->getAbleAccountListBySiteID($site);
		$accounts    = array();
		foreach($accountAll as $account){
			//TODO 排除锁定状态设定为无法刊登的账号
			$accounts[$account['id']] = $account['short_name'];
		}
		echo $this->successJson(array('data'=>$accounts));
	}

	/**
	 * @desc 获取授权码  -- restful api
	 * @link /ebay/ebayaccount/getauthcode/debug/1/account_id/7
	 * @author yangsh
	 */
	public function actionGetauthcode() {
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$accountID = trim(Yii::app()->request->getParam('account_id',''));
		if($accountID == '') {
			die('account_id is empty');
		}
		$accountInfo = EbayAccountRestful::model()->getOneByCondition("b.id,b.short_name,a.client_id,a.ru_name","a.account_id={$accountID}");
		if(empty($accountInfo)) {
			die('account Not Exist');	
		}
		$ebayRestfulKeys = ConfigFactory::getConfig('ebayRestfulKeys');
        if(empty($ebayRestfulKeys)) {
            throw new Exception("ebayRestfulKeys Not Exist");
        }		
		//1.直接跳转方式
		// $url = $ebayRestfulKeys['authorizationUrl']."authorize?client_id=".$accountInfo['client_id']."&redirect_uri=".$accountInfo['ru_name']."&response_type=code&state=".$accountID."&scope=".implode(' ',EbayRestfulApiAbstract::$ALL_EBAY_SCOPE_LIST);
		// header("Location:".$url);
        //2.提交表单方式
		echo "<form action='".$ebayRestfulKeys['authorizationUrl']."authorize' target='__blank'>
				short_name:".$accountInfo['short_name']."<br/>
				client_id:<input type='text' name='client_id' value='".$accountInfo['client_id']."' style='width:360px'/><br/>
				ru_name:<input type='text' name='redirect_uri' value='".$accountInfo['ru_name']."' style='width:360px'/><br/>
				scope:<textarea name='scope' rows='14' cols='90'>".implode(' ',EbayRestfulApiAbstract::$ALL_EBAY_SCOPE_LIST)."</textarea><br/>
				<input type='hidden' name='state' value='".$accountInfo['id']."' />
				<input type='hidden' name='response_type' value='code' />
					<input type='submit' name='提交'/>
				</form>";
		die();
	}

	/**
	 * @desc 获取Access Token  -- restful api
	 * @link /ebay/ebayaccount/gettoken/debug/1/account_id/7/code/##
	 *       /ebay/ebayaccount/gettoken/debug/1/state/7/code/##
	 * @author yangsh       
	 */
	public function actionGettoken() {
		set_time_limit(600);
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$saccountID = trim(Yii::app()->request->getParam('account_id',''));
		$state = trim(Yii::app()->request->getParam('state',''));
		$code = trim(Yii::app()->request->getParam('code'));
		$accountID = $saccountID != '' ? $saccountID : $state;
		if($accountID == '') {
			die('account_id is empty');
		}
		if($code == '') {
			die('code is empty');
		}
		$request = new Auth_GetAccessTokenRequest();
		$request->setAccount($accountID);
		$request->setCode($code);
		$res = $request->setRequest()->sendRequest()->getResponse();
		if($request->getIfSuccess() && $res) {
			EbayAccountRestful::model()->updateData(array(
				'access_token'               => $res->access_token,
				'access_token_expires_time'  => date('Y-m-d H:i:s',time()+intval($res->expires_in)),
				'access_token_update_time'   => date('Y-m-d H:i:s'),
				'refresh_token'              => $res->refresh_token,
				'refresh_token_expires_time' => date('Y-m-d H:i:s',time()+intval($res->refresh_token_expires_in)),
			),"account_id={$accountID}");
		}
		echo '<pre>';print_r($res);
		Yii::app()->end('ok');
	}

	/**
	 * @desc 刷新token -- restful api
	 * @link /ebay/ebayaccount/refreshtoken/debug/1/account_id/7
	 * @author yangsh
	 */
	public function actionRefreshtoken() {
		set_time_limit(3600);
		ini_set ( 'display_errors', true );
		error_reporting( E_ALL & ~E_STRICT );

		$saccountID = Yii::app()->request->getParam('account_id');
		$condition = $saccountID ? " and id={$saccountID} " : '';
		$accountList = EbayAccountRestful::model()->getListByCondition('b.id,b.short_name,a.access_token_expires_time',
			"b.status=".EbayAccount::STATUS_OPEN.$condition);
		foreach ($accountList as $info){
			$accountID = $info['id'];
			echo "<br>", $accountID," -- ",$info['short_name'];
			if(strtotime($info['access_token_expires_time'])-720 > time()){//快过期的12分钟内刷新
				echo "不需刷token";
				continue;
			}
			echo "<br>";
			$request = new Auth_RefreshTokenRequest();
			$request->setAccount($accountID);
			$res = $request->setRequest()->sendRequest()->getResponse();
			echo '<pre>';print_r($res);
			if($request->getIfSuccess() && $res) {
				EbayAccountRestful::model()->updateData(array(
					'access_token'               => $res->access_token,
					'access_token_expires_time'  => date('Y-m-d H:i:s',time()+intval($res->expires_in)),
					'access_token_update_time'   => date('Y-m-d H:i:s'),
				),"account_id={$accountID}");
				echo $accountID.' --- '.$info['short_name']."刷新成功!<br>";
			}			
		}
		echo "<br>";
		Yii::app()->end("## finish ##");
	}	

	/**
	 * @desc 刷新token -- restful api -- 每5分钟检查一次
	 * @link /ebay/ebayaccount/checktoken/max_hour/24/account_id/7
	 * @author yangsh
	 */
	public function actionChecktoken() {
		set_time_limit(600);
		//定时任务
		$accountIdArr = array(7,8,9,12,19,62);
		//$accountList = EbayAccount::model()->getAbleAccountList();
        // foreach ($accountList as $accountInfo) {
        //     $accountIdArr[] = $accountInfo['id'];
        // }		
		foreach ($accountIdArr as $accountID) {
			$url = Yii::app()->request->hostInfo.'/ebay/ebayaccount/refreshtoken/account_id/'.$accountID;
            echo $url." <br>\r\n";
            MHelper::runThreadBySocket($url);
            sleep(3);
		}
		exit('finish');
	}
	
	/**
	 * @desc 获取sessionID
	 * @link /ebay/ebayaccount/getsession
	 */
	public function actionGetsession(){
		$type = Yii::app()->request->getParam('type');
		$accountID = Yii::app()->request->getParam('account_id', 68);
		$runame = Yii::app()->request->getParam('runame', 'Louie_lee-Louielee-uebtes-ffkzf');
		//获取sessionID
		$request = new GetSessionIDRequest;
		
		$request->setAccount($accountID);
		$request->setRuName($runame);
		$response = $request->setRequest()->sendRequest()->getResponse();
		$this->print_r($response);
		$sessionID = $response->SessionID;
		echo "<br/>";
		echo "<form action='https://signin.sandbox.ebay.com/ws/eBayISAPI.dll?SignIn' target='__blank'>
					<input type='text' name='runame' value='{$runame}' style='width:360px'/><br/>
				sessionId:<input type='text' name='SessID' value='{$sessionID}' style='width:360px'/><br/>
				runparams:<input type='text' name='ruparams' style='width:360px' /><br/>
					<input type='submit'/>
				</form>";
		exit;
		
	}
	/**
	 * @desc 
	 * @link /ebay/ebayaccount/fetchtoken
	 */
	public function actionFetchtoken(){
		$sessionID = Yii::app()->request->getParam('session_id');
		$accountID = Yii::app()->request->getParam('account_id', 68);
		//获取token
		$request = new FetchTokenRequest;
		$request->setAccount($accountID);
		$request->setSessionID($sessionID);
		$response = $request->setRequest()->sendRequest()->getResponse();
		$this->print_r($response);
		
		exit();
	}

	/**
	 * 同步到OMS
	 * /ebay/ebayaccount/tooms/id/37
	 */
	public function actionTooms(){
		set_time_limit(1200);
		ini_set("display_errors", true);
		error_reporting(E_ALL);

		$ID = Yii::app()->request->getParam('id');

		$omsEbayAccountModel = new OmsEbayAccount();
		$omsIds = $omsEbayAccountModel->getOmsEbayAccountID();
		
		//通过id查询出账号信息
		$wheres = "id > 0";
		if($ID){
			$wheres = "id = ".$ID;
		}
		$accountArr = array();
		$ebayAccountModel = new EbayAccount();
		$accountInfo = $ebayAccountModel->getListByCondition('*',$wheres);
		foreach ($accountInfo as $accInfo) {

			$data = array(
				'user_name'          => $accInfo['user_name'],
				'store_name'         => $accInfo['store_name'],
				'short_name'         => $accInfo['short_name'],
				'user_token'         => $accInfo['user_token'],
				'user_token_endtime' => $accInfo['user_token_endtime'],
				'email'              => $accInfo['email'],
				'group_id'           => $accInfo['group_id'],
				'appid'              => $accInfo['appid'],
				'devid'              => $accInfo['devid'],
				'certid'             => $accInfo['certid']
		    );

			if(in_array($accInfo['id'], $omsIds)){
			    $omsEbayAccountModel->updateData($data, "id=:id", array(':id'=>$accInfo['id']));
			    echo $accInfo['id'].'--更新成功<br>';
			}else{
				$data['status']  = $accInfo['status'];
				$data['is_lock'] = $accInfo['is_lock'];
				$data['id']      = $accInfo['id'];
				$result = $omsEbayAccountModel->insertData($data);
				if($result){
					echo $accInfo['id'].'--插入成功<br>';
				}
			}

		}

		Yii::app()->end();
	}

}