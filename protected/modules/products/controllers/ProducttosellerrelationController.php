<?php 
/**
 * SKU与销售人员关系控制器
 * @author chenxy
 *
 */
class ProducttosellerrelationController extends UebController {
	public $modelClass = 'ProductToSellerRelation';
	
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
						'actions' => array('sellertoskuisok','selectproduct')
				),
		);
	}
	
	public function actionList(){
		$key = Yii::app()->request->getParam('key');
		$type = Yii::app()->request->getParam('type');
		$sellerId = Yii::app()->request->getParam('sellerId');
		$model = UebModel::model ( 'ProductToSellerRelation' );

		$this->render ( 'list', array (
				'model'       => $model,'key'=>$key,'type'=>$type,'sellerId'=>$sellerId
		) );
	}
	/**
	 * 添加SKU与销售人员关系
	 */
	public function actionCreate() {
		ini_set("display_errors", true);
		error_reporting(E_ALL);
		error_reporting(E_ERROR);
		$model = new ProductToSellerRelation();

        if (Yii::app()->request->isAjaxRequest && isset($_POST['ProductToSellerRelation'])) {
			//echo '<pre>';print_r($_POST);die;
            try {
            	$sellerId = $_POST['ProductToSellerRelation']['seller_id'];
            	$skuArr = $_POST['sku'];
            	$data= array();
            	$i = 0;
            	//echo '<pre>';print_r($skuArr);
            	foreach($skuArr as $productId=>$sku){
            		if(UebModel::model('ProductToSellerRelation')->find("sku = '{$sku}' and seller_id='{$sellerId}'")){
            			$i++;
            			continue;
            		}
            		$product    = UebModel::model('Product')->findByPk($productId);
            	    $classArr   = UebModel::model('ProductCategoryOnline')->getcategorysByClassId($product->online_category_id);

            		$datas[] = array(
            				'sku'            => $sku,
            				'product_id'     => $productId,
            				'seller_id'      => $sellerId,
            				'online_one_id'  => $classArr['cate_id1'],
            				'category_id'    => $classArr['category_id'],
            				'create_time'    => date('Y-m-d H:i:s'),
            				'create_user_id' => Yii::app()->user->id,
            		);
            	}
            	
            	//echo '<pre>';print_r($data);die;
            	if(!empty($datas)){
            		$flag = UebModel::model('ProductToSellerRelation')->batchInsertAll($datas);
            		//var_dump($flag);die;
            		$msg = implode('',UebModel::getLogMsg());
            		if (! empty($msg) ) {
            			Yii::ulog($msg, Yii::t('purchases', '产品与销售人员绑定'), Yii::t('purchases', '产品与销售人员绑定'));
            		}
            	}else{
            		echo $this->failureJson(array( 'message' => Yii::t('system', '已存在同样SKU、销售员信息')));
            		Yii::app()->end();
            	}
            	

            } catch (Exception $e) {
            		$flag = false;
            }
               if ( $flag ) {
               	
                    $jsonData = array(                    
                        'message' => Yii::t('system', 'Add successful'),
                        'forward' => '/products/producttosellerrelation/list',
                        'navTabId' => 'page'.ProductToSellerRelation::getIndexNavTabId(),
                        'callbackType' => 'closeCurrent'
                    );
                    echo $this->successJson($jsonData);
                }             
            if (! $flag) {
                echo $this->failureJson(array( 'message' => Yii::t('system', 'Add failure')));
            }
            Yii::app()->end();
        }

        
        $this->render('create', array('model' => $model));
		 
	}
	/**
	 * 编辑
	 */
	public function actionUpdate(){
		$id = Yii::app()->request->getParam('id');
		$model = UebModel::model('ProductToSellerRelation')->findByPk($id);
		$user = UebModel::model('User')->findByPk($model->seller_id);
		$model->MarketersManager_emp_dept = $user->department_id;
		if (Yii::app()->request->isAjaxRequest && isset($_POST['ProductToSellerRelation'])) {
			$sellerId = $_POST['ProductToSellerRelation']['seller_id'];
			if(UebModel::model('ProductToSellerRelation')->find("sku ='{$model->sku}' and seller_id ='{$sellerId}'")){
				echo $this->failureJson(array( 'message' => Yii::t('system', '已存在同样SKU、销售员信息')));
				Yii::app()->end();
			}
			$model->setAttribute('seller_id', $sellerId);
			$model->setAttribute('update_time', date('Y-m-d H:i:s'));
			$model->setAttribute('update_user_id', Yii::app()->user->id);
			if($model->save()){
				$jsonData = array(
						'message' => Yii::t('system', '更新成功'),
						'forward' => '/products/producttosellerrelation/list',
						'navTabId' => 'page'.ProductToSellerRelation::getIndexNavTabId(),
						'callbackType' => 'closeCurrent'
				);
				echo $this->successJson($jsonData);
			}else{
				echo $this->failureJson(array( 'message' => Yii::t('system', '更新失败')));
			}
			Yii::app()->end();
			echo '<pre>';print_r($_POST);die;
		}
		$this->render('update', array('model' => $model));
	}
	/**
	 * 批量删除
	 */
	public function actionRemoveskutoseller(){
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			try {
				UebModel::model('ProductToSellerRelation')->getDbConnection()
				->createCommand()->delete(UebModel::model('ProductToSellerRelation')->tableName(),  " id IN({$_REQUEST['ids']})");
				
				$flag = true;
			} catch (Exception $e) {
				$flag = false;
			}
			if ($flag) {
				$jsonData = array(
						'callback'  => 'tabAjaxDone',
						'message' => Yii::t('system', 'Delete successful'),
						'navTabId' => 'page' . ProductToSellerRelation::getIndexNavTabId(),
				);
			//	$jsonData['callbackType'] = 'closeCurrent';
				$jsonData['forward'] = '/products/producttosellerrelation/list';
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
		if($_FILES['import_file_product_seller']){
			//验证是否为EXCEL文件格式
			$path_arr = explode('.',$_FILES['import_file_product_seller']['name']);
			if($path_arr[1]== 'xls' || $path_arr[1] == 'xlsx'){
				move_uploaded_file($_FILES['import_file_product_seller']["tmp_name"],'./upload/'.$_FILES['import_file_product_seller']['name']);
			}else{
				echo $this->failureJson(array(
						'message' => Yii::t('products', 'Not Excel File'),
				));
				Yii::app()->end();
			}
		
			//获取文件路径
			$file_name=$_FILES['import_file_product_seller']['name'];
			Yii::import('application.vendors.MyExcel.php');
			$excel_file_path = './upload/'.$file_name;//Excel文件导入
		
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
			$notName ='';	
			$errors = '';
			$insertErrors = '';
			foreach ($excel_data as $key => $rows) {
				$errorsSku = 0;
				if ($key == 1|| $key==2) continue;
				$sku = trim($rows['A'],"'");
				$sku = trim($rows['A']);
				$sellerNameArr = array(
						'B' => trim($rows['B']),
						'C' => trim($rows['C']),
						'D' => trim($rows['D']),
						'E' => trim($rows['E']),
						'F' => trim($rows['F']),
						'G' => trim($rows['G']),
						'H' => trim($rows['H']),
						
				);
				
				if (empty($sku)) continue;
				$productInfo = UebModel::model('Product')->getBySku($sku);
				if(!$productInfo){
					$errors .=$sku.',';
					continue;
				}
				$productId = $productInfo->id;
				$classArr   = UebModel::model('ProductCategoryOnline')->getcategorysByClassId($productInfo->online_category_id);
				
				foreach($sellerNameArr as $name){
					$datas = array();
					if(!empty($name)){
						$user = UebModel::model('User')->find("user_full_name = '{$name}' and user_status = 1");
						if($user){
							$sellerId = $user->id;
							if(UebModel::model('ProductToSellerRelation')->find("sku = '{$sku}' and seller_id='{$sellerId}'")){
		            			continue;
		            		}
		            		$datas[] = array(
		            				'sku'            => $sku,
		            				'product_id'     => $productId,
		            				'seller_id'      => $sellerId,
		            				'online_one_id'  => $classArr['cate_id1'],
		            				'category_id'    => $classArr['category_id'],
		            				'create_time'    => date('Y-m-d H:i:s'),
		            				'create_user_id' => Yii::app()->user->id,
		            		);
		            		$flag = UebModel::model('ProductToSellerRelation')->batchInsertAll($datas);
		            		if(!$flag){
		            			$insertErrors .=$key.',';
		            		}
						}else{
							$notName .=$name.',';
						}
						
					}
				}
		
			}
			if(empty($errors) && empty($insertErrors) && empty($notName)){
				$jsonData = array(
						'message' => Yii::t('products','销售员绑定SKU成功'),//Yii::t('system', 'Add successful'),
						'forward' => '/products/producttosellerrelation/list',
						'navTabId' => 'page'.ProductToSellerRelation::getIndexNavTabId(),
						'callbackType' => 'closeCurrent'
				);
				echo $this->successJson($jsonData);
			}else{
				$errorAll = '';
				if($errors){
					$errorAll .='以下SKU没有在系统中找到:'.$errors;
				}
				if($notName){
					$errorAll .= $notName.'在系统中找不到对应的销售员..';
				}
				if($insertErrors){
					$errorAll .= '第'.$insertErrors.'行数据没有插入系统，请检查后再插入..';
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
	 * 判断选择的SKU是否属于销售员
	 */
	public function actionSellertoskuisok(){
		$ids = rtrim(Yii::app()->request->getParam('ids'),',');
		$sellerId = Yii::app()->request->getParam('seller');
		
		$product_obj = UebModel::model('ProductToSellerRelation')->getListPairsByIdArr(explode(',',$ids));
	//	echo '<pre>';print_r($product_obj);die;
		foreach($product_obj as $keys=>$val){
			if($val['seller_id'] != $sellerId){
				$jsonData = array('message' => Yii::t('system', '请选择属于选定销售员的SKU'),'statues'=>2);
    			echo $this->failureJson($jsonData);
    			Yii::app()->end();
			}
		}
		$jsonData = array('message' => Yii::t('system', '选择正确'),'statues'=>1);
		echo $this->failureJson($jsonData);
		Yii::app()->end();
	}
	/**
	 * 弹框出SKU与销售员的关系列表
	 */
	public function actionSelectproduct(){
		$ids = rtrim(Yii::app()->request->getParam('ids'),',');
		$key = Yii::app()->request->getParam('key');
		$type = Yii::app()->request->getParam('type');
		$product_obj = UebModel::model('ProductToSellerRelation')->getListPairsByIdArr(explode(',',$ids));
		foreach($product_obj as $keys=>$val){
			$productList = UebModel::model('Product')->findByPk($val['product_id']);
			$product_obj[$keys]['product_status'] = $productList->product_status;
		}
		
	//	echo '<pre>';print_r($product_obj);die;
		$this->render('selectsellertosku', array(
   			'product_obj' 		=> $product_obj,
			'key'				=>$key,
   		));
	}
}
?>