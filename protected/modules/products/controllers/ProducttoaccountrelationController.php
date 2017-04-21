<?php 
/**
 * SKU与账号关系控制器
 * @author chenxy
 *
 */
class ProducttoaccountrelationController extends UebController {
	public $modelClass = 'ProductToAccountRelation';
	
	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules(){
		return array(
				array(
						'allow',
						'users' => array('*'),
						'actions' => array('setrelationsellerandsku','platformsiteaccountcheck','accounttoskuisok','Accounttoskuisokall')
				),
		);
	}
	
	public function actionList(){
		ini_set('memory_limit','2000M');
		set_time_limit('3600');
		//error_reporting(E_ALL);
		$model=new ProductToAccountRelation();
		$this->render ( 'list', array (
				'model' => $model,
		) );
	}

	/**
	 * 添加SKU与账号关系
	 */
	public function actionCreate() {
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		error_reporting(E_ERROR);
		$model = new ProductToAccountRelation();
		$platformCodeToSite = array('EB','AMAZON','LAZADA');//必须要选择站点的平台
        if (Yii::app()->request->isAjaxRequest && isset($_POST['ProductToAccountRelation'])) {
            try {
            	$platformCode = $_POST['ProductToAccountRelation']['platform_code'];
            	$site         = $_POST['ProductToAccountRelation']['site'] ? $_POST['ProductToAccountRelation']['site']:'';
            	$accountId    = $_POST['ProductToAccountRelation']['account_id'];
            	$sellerId    = $_POST['ProductToAccountRelation']['seller_user_id'];
            	if(in_array($platformCode,$platformCodeToSite) && (empty($site)||empty($accountId))){
            		echo $this->failureJson(array( 'message' => Yii::t('system', '请选择正确的站点，账号')));
            		Yii::app()->end();
            	}
            	$wheres = "  and  platform_code ='{$platformCode}' ";
            	if(!empty($site)){
            		$wheres.=" and site = '{$site}'";
            	}
            	if($platformCode =='NF'){
            		$site ='nf';
            	}else if($platformCode =='KF'){
            		$site ='kf';
            		if(empty($accountId)){
            			echo $this->failureJson(array( 'message' => Yii::t('system', '请选择正确的账号')));
            			Yii::app()->end();
            		}
            	}else if($platformCode =='ALI'){
            		if(empty($accountId)){
            			echo $this->failureJson(array( 'message' => Yii::t('system', '请选择正确的账号')));
            			Yii::app()->end();
            		}
            		$site ='ali';
            	}else if($platformCode =='JDGJ'){
            		$site ='jdgj';
            	}else if($platformCode =='JM'){
            		if(empty($accountId)){
            			echo $this->failureJson(array( 'message' => Yii::t('system', '请选择正确的账号')));
            			Yii::app()->end();
            		}
            	}

            	if(!empty($accountId)){
            		$wheres.=" and account_id = '{$accountId}'";
            	}
            	$result = UebModel::model('ProductMarketersManager')->find("seller_user_id = '{$sellerId}' $wheres");
            	if(!$result){
            		echo $this->failureJson(array( 'message' => Yii::t('system', '该账号不属于选择的销售员')));
            		Yii::app()->end();
            	}
            	$skuArr = $_POST['sku'];
            	
            	$datas = array();
            	$readyPublishTime = $_POST['ready_publish_time'];
            	if($readyPublishTime < date('Y-m-d 00:00:00')){
            		echo $this->failureJson(array( 'message' => Yii::t('system', '预计刊登时间，必填且必须大于等于今天')));
            		Yii::app()->end();
            	}
            	$pulishSku = '';
            	foreach($skuArr as $productId=>$sku){
            		$where = '';
            		$where = " platform_code ='{$platformCode}' ";
            		if(!empty($site) && in_array($platformCode,$platformCodeToSite)){
            			$where.=" and site = '{$site}'";
            		}
            		if(!empty($accountId)){
            			$where.=" and account_id = '{$accountId}'";
            		}
            		$where .= " and sku = '{$sku}' ";
            		$result = UebModel::model('ProductToAccountRelation')->getRecordByselect($platformCode,$where);            		
            		if($result){
						continue;
            		}
            		$where .= " and online_status = 1 ";
            		if(UebModel::model('ProductPlatformListing')->find($where)){
            			$pulishSku .=$sku.',';
            			continue;
            		}
            		$datas[] = array(
            				'sku'            => $sku,
            				'product_id'     => $productId,
            				'platform_code'  => $platformCode,
            				'site'  		 => $site,
            				'account_id'     => $accountId,
            				'create_time'    => date('Y-m-d H:i:s'),
            				'create_user_id' => Yii::app()->user->id,
            				'ready_publish_time' => $_POST['ready_publish_time'],
            				'seller_user_id' => $sellerId,
            		);
            	}
               if(!empty($datas)){
            		$flag = UebModel::model('ProductToAccountRelation')->batchInsertAll($platformCode,$datas);
            		$msg = implode('',UebModel::getLogMsg());
            		if (! empty($msg) ) {
            			Yii::ulog($msg, Yii::t('purchases', '产品与账号绑定'), Yii::t('purchases', '产品与账号绑定'));
            		}
            	}else{
            		echo $this->failureJson(array( 'message' => Yii::t('system', '已存在同样SKU、销售员信息')));
            		Yii::app()->end();
            	}
            } catch (Exception $e) {
            		$flag = false;
            }
            //存在已经刊登的SKU
            $isPulishSku ='';
            if($pulishSku){
            	$isPulishSku = ',以下SKU'.$pulishSku.'已经刊登过了，不能重复刊登';
            }
           
            if ( $flag ) {
                    $jsonData = array(                    
                        'message' => '添加成功'.$isPulishSku,
                        'forward' => '/products/producttoaccountrelation/list',
                        'navTabId' => 'page'.ProductToAccountRelation::getIndexNavTabId(),
                        'callbackType' => 'closeCurrent'
                    );
                    echo $this->successJson($jsonData);
                }             
            if (! $flag) {
                echo $this->failureJson(array( 'message' => Yii::t('system', 'Add failure')));
            }
            Yii::app()->end();
        }

        $depList = UebModel::model('Department')->getMarketsDepartmentInfo();
        //超级管理，主管 组长可以查看全部自己部门下面所有销售人员
        $userId = Yii::app()->user->id;
        $isSuper = UebModel::model("UserSuperSetting")->checkSuperPrivilegeByUserId($userId);
        $isAdmin = UebModel::model("AuthAssignment")->checkCurrentUserIsAdminister($userId, '');
        $isGroup = false;
        if(!$isSuper && !$isAdmin){
        	$isGroup = UebModel::model("AuthAssignment")->checkCurrentUserIsGroup($userId, '');
        }
        if(!$isSuper){
        	//获取当前用户所属部门ID
        	$depId = UebModel::model("User")->getDepIdById($userId);
        	$depList = array($depId=>$depList[$depId]);
        }
        $this->render('create', array('model' => $model, 'depList'=>$depList));
	}

	/**
	 * 编辑
	 */
	public function actionUpdate(){
		$id = Yii::app()->request->getParam('id');
		$platformCode = Yii::app()->request->getParam('platform_code');

		if (!empty($_POST['sku']) ){
			//echo '<pre>';print_r($_POST);die;
			$sku = $_POST['sku'];
			$readyPublishTime = $_POST['ready_publish_time'];
			if($readyPublishTime < date('Y-m-d 00:00:00')){
				echo $this->failureJson(array( 'message' => Yii::t('system', '预计刊登时间，必填且必须大于等于今天')));
				Yii::app()->end();
			}
			$data = array(
					'ready_publish_time'  => $readyPublishTime,
            		'update_time'    => date('Y-m-d H:i:s'),
            		'update_user_id' => Yii::app()->user->id,
			);
			$flag = UebModel::model('ProductToAccountRelation')->setUpdateBySelect($platformCode,"id =$id",$data);
			if($flag){
				$jsonData = array(
						'message' => Yii::t('system', '更新成功'),
						'forward' => '/products/producttoaccountrelation/list',
						'navTabId' => 'page'.ProductToAccountRelation::getIndexNavTabId(),
						'callbackType' => 'closeCurrent'
				);
				echo $this->successJson($jsonData);
			}else{
				echo $this->failureJson(array( 'message' => Yii::t('system', '更新失败')));
			}
			Yii::app()->end();
		}
		$where = " id = $id ";
		$model = UebModel::model('ProductToAccountRelation')->getRecordByselect($platformCode,$where);
		$this->render('update', array('model' => $model));
	}

	/**
	 * 批量删除
	 */
	public function actionRemoveskutoseller(){
	//	$platformCode = Yii::app()->request->getParam('platform_code');
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				$ids = trim($_REQUEST['ids'],',');
				$idArr = explode(',',$ids);
				$idStr = '';
				foreach($idArr as $list){
					$listArr = explode('_',$list);
					$idStr .= $listArr[0].',';
					$platformCode =$listArr[1];
				}
				$idStr = trim($idStr,',');
				UebModel::model('ProductToAccountRelation')->getDbConnection()
				->createCommand()->delete("ueb_product_to_account_relation_".strtoupper($platformCode),  " id IN ({$idStr})");
				
				$flag = true;
			} catch (Exception $e) {
				$flag = false;
			}
			if ($flag) {
				$jsonData = array(
					//	'callback'  => 'tabAjaxDone',
						'message' => Yii::t('system', 'Delete successful'),
					//	'navTabId' => 'page' . ProductToAccountRelation::getIndexNavTabId(),
				);
				//$jsonData['callbackType'] = 'closeCurrent';
				//$jsonData['forward'] = '/products/producttoaccountrelation/list';
				echo $this->successJson($jsonData);
			} else {
				$jsonData = array(
						'message' => Yii::t('system', 'Delete failure')
				);
				echo $this->failureJson($jsonData);
			}
		
			Yii::app()->end();
		}
		
	}

	/**
	 * 批量导入销售人员分配
	 */
	public function actionImport(){
		ini_set('memory_limit','2000M');
		set_time_limit('3600');
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		if(!empty($_FILES['import_file_product_seller'])){
			//验证是否为EXCEL文件格式
			$path_arr = explode('.',$_FILES['import_file_product_seller']['name']);
			if($path_arr[1]== 'xls' || $path_arr[1] == 'xlsx'){
				move_uploaded_file($_FILES['import_file_product_seller']["tmp_name"],'./uploads/'.$_FILES['import_file_product_seller']['name']);
			}else{
				echo $this->failureJson(array(
						'message' => Yii::t('products', 'Not Excel File'),
				));
				Yii::app()->end();
			}
		
			//获取文件路径
			$file_name=$_FILES['import_file_product_seller']['name'];
			Yii::import('application.vendors.MyExcel.php');
			$excel_file_path = './uploads/'.$file_name;//Excel文件导入
		
			//解析EXCEL数据
			$objMyExcel = new MyExcel();
			$excel_data = $objMyExcel->get_excel_con($excel_file_path);
			//EXCEL数据验证
			if(!file_exists($excel_file_path)){
				echo $this->failureJson(array(
						'message' => Yii::t('products', 'File Not Exists'),
				));
				Yii::app()->end();
			}
			if(count($excel_data)<2){
				echo $this->failureJson(array(
						'message' => Yii::t('products', '不能导入空数据'),
				));
				Yii::app()->end();
			}
			$isOk    = '';
			$notName ='';	
			$errors  = '';
			$insertErrors = '';
			$notPlatform  = '';
			$notAccount   = '';
			$notUser      = '';
			$pulishSku    = '';//判断SKU是否已经刊登了
			$platform     = UebModel::model('Platform')->getPlatformList();
			$platformArr  = array();//为了防止导入的数据填写的平台大小写不一样,导致匹配不成功
			foreach($platform as $k=>$val){
				$platformArr[$k] = strtolower($val) ;
			}
			$platformArray = array_flip($platformArr);
			$platformCodeToSite = array('EB','AMAZON','LAZADA');//必须要选择站点的平台
		    $wishList	= $this->getWishAccount();
		    $aliList	= $this->getAliAccount();
		    $ebayList	= $this->getEbayAccount();
		    $amazonList	= $this->getAmazonAccount();
		    $lazadaList	= $this->getLazadaAccount();
		    $joomList	= $this->getJoomAccount();
			foreach ($excel_data as $key => $rows) {
				$errorsSku = 0;
				if ($key == 1) continue;
				$sku          = trim($rows['A']);
				$platformCode = strtolower(trim($rows['B']));
				$account      = trim($rows['C']);
				$site         = trim($rows['D']);
				$sellerName   = trim($rows['E']);
				$readyPublishTime   = trim($rows['F']);
				$timeStr = "";
				$userName     = UebModel::model('User')->find("user_full_name = '{$sellerName}' and user_status = 1");
				if (empty($sku)||empty($platformCode)) continue;
				if(in_array($platformArray[$platformCode],$platformCodeToSite) && (empty($site)||empty($account))){
					$notAccount .= $key.',';
					continue;
				}
				$times = explode('/',$readyPublishTime);
				if(strlen($times[0])<4){
					echo $this->failureJson(array(
							'message' => Yii::t('products', '导入的日期格式不对，请对其进行转换'),
					));
					Yii::app()->end();
				}
				if(empty($userName['id'])){
					$notUser .=$sellerName.',';
					continue;
				}
				$sellerId = $userName['id'];
				$productInfo = UebModel::model('Product')->getBySku($sku);

				if(!$productInfo){
					$errors .=$sku.',';
					continue;
				}
				$productId = $productInfo->id;
				
				if(!in_array($platformCode,$platformArr)){
					$notPlatform .= $key.',';
					continue;
				}
				$platform = $platformArray[$platformCode];
				$where = " platform_code ='{$platform}' ";
				if(!empty($site)){
					$where.=" and site = '{$site}'";
				}
				if($platform =='NF'){
					$site ='nf';
				}else if($platform =='KF'){
					$site ='kf';
					$accountId = isset($wishList[$account]) ? $wishList[$account] : '';
					if(empty($accountId)){
						$notAccount .= $key.',';
						continue;
					}
				}else if($platform =='ALI'){
					$site ='ali';
					$accountId = isset($aliList[$account]) ? $aliList[$account] : '';
					if(empty($accountId)){
						$notAccount .= $key.',';
						continue;
					}
					
				}else if($platform =='JDGJ'){
					$site ='jdgj';
				}else if($platform =='JM'){
					$accountId = isset($joomList[$account]) ? $joomList[$account] : '';
					if(empty($accountId)){
						$notAccount .= $key.',';
						continue;
					}
				}else if($platform =='EB'){
					$accountId = isset($ebayList[$account]) ? $ebayList[$account] : '';
					if(empty($accountId)){
						$notAccount .= $key.',';
						continue;
					}
				}else if($platform =='AMAZON'){
					$accountId = isset($amazonList[$account]) ? $amazonList[$account] : '';
					if(empty($accountId)){
						$notAccount .= $key.',';
						continue;
					}
				}else if($platform =='LAZADA'){
					$accountId = isset($lazadaList[$account]) ? $lazadaList[$account] : '';
					if(empty($accountId)){
						$notAccount .= $key.',';
						continue;
					}
				}

				if(!empty($accountId)){
					$where.=" and account_id = '{$accountId}'";
				}

				$marketersList = UebModel::model('ProductMarketersManager')->find("seller_user_id = '{$sellerId}' and $where");
	
				if(!$marketersList){
					echo $this->failureJson(array( 'message' => Yii::t('system', '该账号不属于选择的销售员')));
					Yii::app()->end();
				}
				$where .= " and sku = '{$sku}' ";
				$result = UebModel::model('ProductToAccountRelation')->getRecordByselect($platform,$where);
				if($result){
					continue;
				}
			    $skuToSeller	 =	UebModel::model('ProductToSellerRelation')->find("sku = '{$sku}' and seller_id = '{$sellerId}'");
			    if(!$skuToSeller){
			    	$isOk .=$key.',';
			    	continue;
			    }
				$readyPublishTime   = strtotime($readyPublishTime);
				$readyPublishTime   = date('Y-m-d H:i:s',$readyPublishTime);
				if($readyPublishTime < date('Y-m-d 00:00:00')){
					$timeStr .=$key.','; 
					continue;
				}
				$where .= " and online_status = 1 ";
				if(UebModel::model('ProductPlatformListing')->find($where)){
					$pulishSku .=$key.',';
					continue;
				}
				$data = array();
				$data[0] = array(
						'sku'            => $sku,
						'product_id'     => $productId,
						'platform_code'  => $platform,
						'site'           => $site,
						'account_id'     => $accountId,
						'create_time'    => date('Y-m-d H:i:s'),
						'create_user_id' => Yii::app()->user->id,
						'seller_user_id' => $sellerId,
						'ready_publish_time'  => $readyPublishTime,
				);
				$falg = UebModel::model('ProductToAccountRelation')->batchInsertAll($platform,$data);
				if(!$falg){
					$insertErrors .=$key.',';
				}
		
			}
			if(empty($errors) && empty($insertErrors) && empty($notAccount) && empty($notPlatform) && empty($notUser)  && empty($timeStr) && empty($isOk)){
				if(empty($data)){
					echo $this->failureJson(array(
							'message' => Yii::t('products', '不能导入空数据'),
					));
					Yii::app()->end();
				}
				$jsonData = array(
						'message' => Yii::t('products','账号绑定SKU成功'),//Yii::t('system', 'Add successful'),
						'forward' => '/products/producttoaccountrelation/list',
						'navTabId' => 'page'.ProductToAccountRelation::getIndexNavTabId(),
						'callbackType' => 'closeCurrent'
				);
				echo $this->successJson($jsonData);
			}else{
				$errorAll = '';
				if($errors){
					$errorAll .='以下SKU没有在系统中找到:'.$errors;
				}
				if($notAccount){
					$errorAll .= '第'.$notAccount.'行的账号没有在系统中找到..';
				}
				if($notPlatform){
					$errorAll .= '第'.$notPlatform.'行的平台名称写法不对..';
				}
				if($insertErrors){
					$errorAll .= '第'.$insertErrors.'行数据没有插入系统，请检查后再插入..';
				}
				if($notUser){
					$errorAll .='以下名字不存在系统中:'.$notUser;
				}

				if($timeStr){
					$errorAll .= '第'.$timeStr.'行的预计刊登时间必填且必须大于等于今天..';
				}
				if($pulishSku){
					$errorAll .= '第'.$pulishSku.'行数据已经刊登了，请不要重新刊登..';
				}
				if($isOk){
					$errorAll .= '第'.$isOk.'行SKU没有与相关销售员绑定，请先绑定..';
				}
				echo $this->failureJson(array(
						'message' => $errorAll
				));
			}
		
			Yii::app()->end();
		}
		$this->render('import');
	}

	/**
	 * 获取wish账号列表
	 */
	public function getWishAccount(){
		$data = array();
		$wishList	= UebModel::model('WishAccount')->getAbleAccountList();
		foreach($wishList as $val){
			$data[$val['account_name']] = $val['id'];
		}
		return $data;
	}

	/**
	 * 获取ali账号列表
	 */
	public function getAliAccount(){
		$data = array();
		$wishList	= UebModel::model('AliexpressAccount')->getAbleAccountList();
		foreach($wishList as $val){
			$data[$val['short_name']] = $val['id'];
		}
		return $data;
	}

	/**
	 * 获取ebay账号列表
	 */
	public function getEbayAccount(){
		$data = array();
		$wishList	= UebModel::model('EbayAccount')->getAbleAccountList();
		foreach($wishList as $val){
			$data[$val['short_name']] = $val['id'];
		}
		return $data;
	}
	
	/**
	 * 获取亚马逊账号列表
	 */
	public function getAmazonAccount(){
		$data = array();
		$wishList	= UebModel::model('AmazonAccount')->getAbleAccountList();
		foreach($wishList as $val){
			$data[$val['account_name']] = $val['id'];
		}
		return $data;
	}

	/**
	 * 获取lazada账号列表
	 */
	public function getLazadaAccount(){
		$data = array();
		$wishList	= UebModel::model('LazadaAccount')->getAbleAccountList();
		foreach($wishList as $val){
			$data[$val['short_name']] = $val['old_account_id'];
		}
		return $data;
	}

	/**
	 * 获取joom账号列表
	 */
	public function getJoomAccount(){
		$data = array();
		$wishList	= UebModel::model('JoomAccount')->getAbleAccountList();
		foreach($wishList as $val){
			$data[$val['account_name']] = $val['id'];
		}
		return $data;
	}

	/**
	 * 判断账号站点是否属于该销售员
	 */
	public function actionPlatformsiteaccountcheck(){
		$platform  = Yii::app()->request->getParam('platform');
		$sellerId  = Yii::app()->request->getParam('sellerId');
		$accountId = Yii::app()->request->getParam('accountId');
		$result = UebModel::model('ProductMarketersManager')->find("seller_user_id = '{$sellerId}' and platform_code = '{$platform}' and account_id = '{$accountId}'");
		if($result){
			echo json_encode(array('status'=>1));exit;
		}else{
			echo json_encode(array('status'=>0));exit;
		}
	}
	
	public function actionPlatformAccount(){
		$arr = UebModel::model('ProductToAccountRelation')->getPlatformAccount(trim($_POST['platform']));
		print_r(json_encode($arr));
		exit;
	
	}
	
	public function actionPlatformAccountById(){
		$arr = UebModel::model('ProductToAccountRelation')->getPlatformAccountById(trim($_POST['platform']));
		print_r(json_encode($arr));
		exit;
	}
	
	public function actionPlatformAccountByIdnew(){
		$platformCode = trim(Yii::app()->request->getParam("platform"));
		$sellerId = Yii::app()->request->getParam("seller_id");
		$arr = UebModel::model('ProductToAccountRelation')->getPlatformAccountById($platformCode, null, $sellerId);
		$newArr = array();
		if($arr){
			foreach ($arr as $key=>$val){
				$newArr["#".$key] = $val;
			}
		}
		echo (json_encode($newArr));
		exit;
	}

	public function actionPlatformSite(){
		$arr = UebModel::model('ProductToAccountRelation')->getSiteByPlatfromCode(trim($_POST['platform']));
		print_r(json_encode($arr));
		exit;
	}

	public function actionPlatformSiteOffer(){
		$arr = UebModel::model('ProductToAccountRelation')->getOfferSiteByPlatfromCode(trim($_POST['platform']));
		print_r(json_encode($arr));
		exit;
	}
	
	/**
	 * 判断未刊登弹框页面的数据是否可以添加到待刊登列表中
	 */
	public function actionAccounttoskuisok(){
		$ids           = Yii::app()->request->getParam('ids');
		$platformCode  = Yii::app()->request->getParam('platform_code');
		$sellerId      = Yii::app()->request->getParam('seller');
		$accountId     = Yii::app()->request->getParam('accountId');
		$site          = Yii::app()->request->getParam('site');
		$ids           = trim($ids,',');
		$where = " platform_code ='{$platformCode}' ";
		if(!empty($site)){
			$where.=" and site = '{$site}'";
		}
		if(!empty($accountId)){
			$where.=" and account_id = '{$accountId}'";
		}
		$where .= " and product_id in ( $ids) ";
		$result = UebModel::model('ProductToAccountRelation')->getRecordByselectAll($platformCode,$where);
		if($result){
			$skus ='';
			foreach($result as $val){
				$skus .=$val['sku'].',';
			}
			echo json_encode(array('status'=>1,'message'=>$skus.'已经在该销售员的账号中已经存在记录了'));exit;
		}else{
			echo json_encode(array('status'=>0,'message'=>''));exit;
		}
	}
	
	/**
	 * 判断未刊登页面的数据是否有待刊登的数据，如果有待刊登数据，则返回待刊登的产品ID
	 */
	public function actionAccounttoskuisokall(){
		$ids           = Yii::app()->request->getParam('ids');
		$platformCode  = Yii::app()->request->getParam('platform_code');
		$sellerId      = Yii::app()->request->getParam('seller');
		$accountId     = Yii::app()->request->getParam('accountId');
		$site          = Yii::app()->request->getParam('site');
		$ids           = trim($ids,',');
		$where = " platform_code ='{$platformCode}' ";
		if(!empty($site)){
			$where.=" and site = '{$site}'";
		}
		if(!empty($accountId)){
			$where.=" and account_id = '{$accountId}'";
		}
		$where .= " and product_id in ( $ids) ";
		$result = UebModel::model('ProductToAccountRelation')->getRecordByselectAll($platformCode,$where);
		if($result){
			$idArr =array();
			foreach($result as $val){
				$idArr[]= $val['product_id'];
			}
				
			echo json_encode(array('status'=>1,'idArr'=>$idArr));exit;
		}else{
			echo json_encode(array('status'=>0,'idArr'=>''));exit;
		}
	}

	/**
	 * 未刊登的sku转为待刊登时候，弹出预计刊登时间选择框
	 */
	public function actionSetskupublishtime(){
		$ids           = Yii::app()->request->getParam('ids');
		$platformCode  = Yii::app()->request->getParam('platform_code');
		$sellerId      = Yii::app()->request->getParam('seller');
		$accountId     = Yii::app()->request->getParam('accountId');
		$site          = Yii::app()->request->getParam('site');
		$isMulti		= Yii::app()->request->getParam('is_multi');
		$ids           = trim($ids,',');
		$where = " platform_code ='{$platformCode}' ";
		if(!empty($site)){
			$where.=" and site = '{$site}'";
		}
		if(!empty($accountId)){
			$where.=" and account_id = '{$accountId}'";
		}
		$where .= " and product_id in ( $ids) ";
		$result = UebModel::model('ProductToAccountRelation')->getRecordByselectAll($platformCode,$where);
		if($result){
			$skus ='';
			foreach($result as $val){
				$skus .=$val['sku'].',';
			}
			echo json_encode(array('status'=>1,'message'=>$skus.'已经在该销售员的账号中已经存在记录了'));exit;
		}else{
			if (Yii::app()->request->isAjaxRequest && isset($_POST['ready_publish_time'])) {
				//	echo '<pre>';print_r($_POST);die;
				if($_POST['ready_publish_time'] < date('Y-m-d 00:00:00')){
					echo $this->failureJson(array( 'message' => Yii::t('system', '预计刊登时间，必填且必须大于等于今天')));
					Yii::app()->end();
				}
	
				$skuArray = UebModel::model('Product')->getListPairsByIdArr(explode(',',$ids));
				$skuArray2 = array();

				//检查sku刊登权限
				$checkPowerMsg = '';
				foreach($skuArray as $value){
					if( !Product::model()->checkCurrentUserAccessToSaleSKUNew($value['sku'], $accountId, $platformCode)){
						$checkPowerMsg .= $value['sku'].Yii::t('system', 'Not Access to Add the SKU').'<br/>';
						continue;
					}
					$skuArray2[] = $value;
				}
				
				if (empty($skuArray2)) {
					echo $this->failureJson(array( 'message' => Yii::t('system', '所选sku没有权限刊登!')));
					Yii::app()->end();
				} else {
					$datas = array();
					foreach($skuArray2 as $value){
						$parentSKU = "";
						if($isMulti){
							//@todo 待优化
							$parentSKU = (string)UebModel::model('Product')->getMainSkuByVariationSku($value['sku']);
						}
						$datas[] = array(
								'sku'            => $value['sku'],
								'product_id'     => $value['id'],
								'platform_code'  => $platformCode,
								'site'  		 => $site,
								'account_id'     => $accountId,
								'create_time'    => date('Y-m-d H:i:s'),
								'create_user_id' => Yii::app()->user->id,
								'seller_user_id' => $sellerId,
								'ready_publish_time'=> $_POST['ready_publish_time'],
								'is_multi'		=>	$isMulti,
								'parent_sku'	=>	$parentSKU
						);
					}
		
					$flag = UebModel::model('ProductToAccountRelation')->batchInsertAll($platformCode,$datas);
					if($flag){
						$jsonData = array(
								'message' => Yii::t('system', '批量待刊登成功').$checkPowerMsg,
								'callbackType' => 'closeCurrent'
						);
						echo $this->successJson($jsonData);
						Yii::app()->end();
					}else{
						echo $this->failureJson(array( 'message' => Yii::t('system', '批量待刊登失败，请联系IT') .$checkPowerMsg ) );
						Yii::app()->end();
					}
				}
			}
			//echo $ids.'-'.$platformCode.'-'.$site.'-'.$accountId.'-'.$sellerId;
			$this->render('setskupublishtime',array('ids'=>$ids,'seller'=>$sellerId,'platform_code'=>$platformCode,'site'=>$site,'accountId'=>$accountId, 'is_multi'=>$isMulti));
		}
	
	}
}
?>