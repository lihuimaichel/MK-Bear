<?php
/**
 * @package Ueb.modules.products.controllers
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class ProductController extends UebController {

	public $modelClass = 'Product';
    
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
				'actions' => array('Baseu','Index','importomsskudata','updatecost','websitenewsku')
			),
		);
	}
    /**
     * product list
     */
    public function actionList(){
    	$model = UebModel::model('Product');
        $do = Yii::app()->request->getParam('do'); 
        $key = Yii::app()->request->getParam('key');
        $type = Yii::app()->request->getParam('type');
        $model_name = Yii::app()->request->getParam('model_name') ? Yii::app()->request->getParam('model_name') : 'Product';
   
        $roleArr = UebModel::model('Role')->getUserRole('all');
        $this->render('list', array(
        	'model' => $model,'modelName'=>$model_name,'key'=>$key,'type'=>$type,'roleArr'=>$roleArr
        ));
   	}
   	
   	/**
   	 * product list for select
   	 */
   	public function actionSelectproduct(){
   		$ids = rtrim(Yii::app()->request->getParam('ids'),',');
   		$key = Yii::app()->request->getParam('key');
   		$type = Yii::app()->request->getParam('type');
		$product_obj = UebModel::model('Product')->getProductInfoByIds($ids,array(CN));//只取中文描述
		
		//产品最后报价
		$product_obj = UebModel::model('PurchaseInquire')->getLastInquireByProductInfo($product_obj);
		$arr = UebModel::model('Currency')->getCurrencyParis();
		foreach($arr as $val){
			$currencyArr[$val->code] = $val->code.'-'.$val->currency_name;
		}
		$warehouseArr = UebModel::model('Warehouse')->getParaList();
		$this->render('selectproduct', array(
   			'product_obj' 		=> $product_obj,
			'ids'				=>$ids,
			'languageCode'		=>CN,
			'currencyArr'		=>$currencyArr,
			'key'				=>$key,
			'type'				=>$type,
			'warehouseArr'		=>$warehouseArr
   		));
   	}
    
    /**
     * add a product
     */
    public function actionCreate(){
        $model = new Product();
        $this->render('create', array('model' => $model));
    }
    
    /**
     * update the product
     */
    public function actionUpdate($id) { 
    	$model = $this->loadModel($id);
        $do = Yii::app()->request->getParam('do'); 
        $this->render('update', array('model' => $model,'do' => $do));
    }
    
    /**
     * 按选择批量修改
     */
    public function actionBatchupdate(){
    	$model = new Product();
    	$mps = new ProductSecurity();
    	$do = Yii::app()->request->getParam('do');
    	if (Yii::app()->request->isAjaxRequest && isset($_POST['ProductId']))    		
    	{    	   		
    		if(!isset($_POST['Product']) && !isset($_POST['ProductDesc']) && !isset($_POST['Provider'])){
    			echo $this->failureJson(array('message'=> Yii::t('products', 'Select the item need bulk changes')));
    			Yii::app()->end();
    		}

    		$flag = false;
    		if (!empty($_POST['ProductId']['product_id'])) {	
    			    $transaction = $model->getDbConnection()->beginTransaction();
    			try {
    				$id_1 = rtrim($_POST['ProductId']['product_id'],',');
    				$productIsToMid=array('is_to_mid'=>0);
    				$model->updateAll($productIsToMid, " id in ( " . $id_1 . " )");
	    			$ids = explode(',',$id_1);
	    			
	    			$skuArr=UebModel::model('Product')->getSkusByProductIds($ids);
	    		//批量修改 安全级别  和侵权种类 侵权描述
	    			if(!empty($_POST['Product']['security_level'])){	    				
		    			$arraySecurity=array('security_level'=>$_POST['Product']['security_level']);
	    				$mps->updateAll($arraySecurity, " sku in ( " . implode(',',$skuArr). " )");
		    			$flag = true;
	    			}
	    			if(!empty($_POST['Product']['infringement'])){
	    				$arrayInfringe=array('infringement'=>$_POST['Product']['infringement']);
	    				$mps->updateAll($arrayInfringe, " sku in ( " . implode(',',$skuArr). " )");
	    				$flag = true;
	    			}	    			
	    			if(!empty($_POST['Product']['infringement_reason'])){
	    				$arrayInfringeReason=array('infringement_reason'=>$_POST['Product']['infringement_reason']);
	    				$mps->updateAll($arrayInfringeReason, " sku in ( " . implode(',',$skuArr). " )");
	    				$flag = true;
	    			}
	    			if(!empty($_POST['Product']['infringe_platform'])){
	    				$arrayInfringePlatform=array('infringe_platform'=>$_POST['Product']['infringe_platform']);
	    				$mps->updateAll($arrayInfringePlatform, " sku in ( " . implode(',',$skuArr). " )");
	    				$flag = true;
	    			}
	    		//批量修改跟单人
	    			if(!empty($_POST['Product']['single_person'])){
	    				$models=new Productrole();
	    				$singlePerson=array('user_id'=>$_POST['Product']['single_person']);
	    				$models->updateAll($singlePerson, " sku in ( " . implode(',',$skuArr). " ) and role_code='purchaser'");
	    				$flag = true;
	    			}
	    		//批量修改Category
	    			if(!empty($_POST['Product']['classid'])){
	    				$flag = UebModel::model('ProductCategorySkuOld')->updateCategoryBySku($skuArr,$_POST['Product']['classid']);
	    			}
	    			//批量修改产品基本信息
	    			if(isset($_POST['Product']) && !empty($_POST['Product'])){
	    				if($_POST['Product']['product_category_id']==0){
	    					unset($_POST['Product']['product_category_id']);
	    				}
	    				$arrIds=explode(',',$id_1);
// 	    				$model->batchUpdateCostLog($arrIds,$_POST['Product']['product_cost']);//批量修改 产品价格 添加日志
	    				$arr_product = $_POST['Product'];
	    				$arr_product['modify_user_id']=Yii::app()->user->id;
	    				$arr_product['modify_time']= date('Y-m-d H:i:s');
	    		        UebModel::model('Productjob')->bathUpdateProductStatus($arr_product['product_status'],$skuArr);
	    				$model->updateAll($arr_product, " id in ( " . $id_1 . " )");
	//     				$flag = UebModel::model('product')->getDbConnection()->createCommand()->update($_POST['Product'], " id IN({$id_1})");
	    				$flag = true;

	    			}
    				//更改供应商code
	    			if(isset($_POST['Provider']) && !empty($_POST['Provider']['provider_code'])){
    					UebModel::model('ProductProvider')->batchSaveProvider($ids,$id_1, $_POST['Provider']['provider_code']);
    					$flag = true;
    				}
    				/**更改报关中文名
    				if(isset($_POST['ProductDesc']) && !empty($_POST['ProductDesc']['customs_name'])){
    					UebModel::model('productDesc')->customsNameSave($ids, $_POST['ProductDesc']['customs_name']);
    					$flag = true;
    				}
    				*/
    								
    				$transaction->commit();
	    			//$flag = true;
    			} catch (Exception $e) {
    				$transaction->rollback();
    				//die($e->getMessage());
    				$flag = false;
    			}	   			
    			if ($flag) {
                    $jsonData = array(
                        'message'       => Yii::t('common', 'Batch update successful'),
                    	'forward' => '/products/product/list',
                    	'navTabId' => 'page'.Product::getIndexNavTabId(),
                    	'callbackType' => 'closeCurrent'
                    );
                    echo $this->successJson($jsonData);
                }
            }
            if (! $flag) {
                echo $this->failureJson(array('message'=> Yii::t('common', 'Batch update failure')));
            }
    		Yii::app()->end();
    	}
    	$category = UebModel::model('ProductCategory')->getCat(0);
    	$catArr = array();
    	$catetoryView = '';
    	foreach($category as $cat){
    		$catArr[$cat['id']] = $cat['category_cn_name'];
    	}
    	if($do=='selected'){
	    	if (isset($_REQUEST['ids'])) {
	    		$ids = Yii::app()->request->getParam('ids');
	    		$this->render('batch_update', array('model' => $model,'ids' => $ids,'catArr'=>$catArr));
	    	}
    	}else{   		
    		$this->render('batch_update_query', array('model' => $model));
    	}
    	
    }
    
    /**
     * 按查询结果批量修改
    */
    public function actionBatchupdatequery(){
    	$model = new Product();
    	$mps = new ProductSecurity();
    	$do = Yii::app()->request->getParam('do');
    	$ids = array();//产品ID,一维数组
    	$productIds = ''; 	 
    	$criteria = new CDbCriteria();   
		$criteria->select = 'id,sku';   
		$criteria->addCondition(Yii::app()->session->get(get_class($model).'_condition'));		
		$arr_info = $model->findAll($criteria);  
    	foreach($arr_info as $key=>$val){
    		$ids[]= $val['id'];
    		$productIds .= $val['id'].',';
    	}
    	if (Yii::app()->request->isAjaxRequest && isset($_POST['ProductId'])){
    		if(!isset($_POST['Product']) && !isset($_POST['ProductDesc']) && !isset($_POST['Provider'])){
    			echo $this->failureJson(array('message'=> Yii::t('products', 'Select the item need bulk changes')));
    			Yii::app()->end();
    		}
    		$flag = false;
    		$id_1 = implode(',',$ids);//产品ID字符串，逗号隔开
    		$productIsToMid=array('is_to_mid'=>0);
    		$model->updateAll($productIsToMid, " id in ( " . $id_1 . " )");
    		$skuArr=UebModel::model('Product')->getSkusByProductIds($ids);
	    	//批量修改 安全级别  和侵权种类 侵权描述
	    	if(!empty($_POST['Product']['security_level'])){	    				
	    		$arraySecurity=array('security_level'=>$_POST['Product']['security_level']);
    			$mps->updateAll($arraySecurity, " sku in ( " . implode(',',$skuArr). " )");
	    		$flag = true;
    		}
    		if(!empty($_POST['Product']['infringement'])){
    			$arrayInfringe=array('infringement'=>$_POST['Product']['infringement']);
    			$mps->updateAll($arrayInfringe, " sku in ( " . implode(',',$skuArr). " )");
    			$flag = true;
    		}	    			
    		if(!empty($_POST['Product']['infringement_reason'])){
    			$arrayInfringeReason=array('infringement_reason'=>$_POST['Product']['infringement_reason']);
    			$mps->updateAll($arrayInfringeReason, " sku in ( " . implode(',',$skuArr). " )");
    			$flag = true;
    		}
    		if(!empty($_POST['Product']['infringe_platform'])){
    			$arrayInfringePlatform=array('infringe_platform'=>$_POST['Product']['infringe_platform']);
    			$mps->updateAll($arrayInfringePlatform, " sku in ( " . implode(',',$skuArr). " )");
    			$flag = true;
    		}
    		//批量修改跟单人
    		if(!empty($_POST['Product']['single_person'])){
    			$models=new Productrole();
    			$singlePerson=array('user_id'=>$_POST['Product']['single_person']);
    			$models->updateAll($singlePerson, " sku in ( " . implode(',',$skuArr). " ) and role_code='purchaser'");
    			$flag = true;
    		}
    		//批量修改Category
    		if(!empty($_POST['Product']['classid'])){
    			$flag = UebModel::model('ProductCategorySkuOld')->updateCategoryBySku($skuArr,$_POST['Product']['classid']);
    		}
    		if (!empty($ids)) {
    			$transaction = $model->getDbConnection()->beginTransaction();
    			try {
    				//批量修改产品基本信息
    				if(isset($_POST['Product']) && !empty($_POST['Product'])){
    					if($_POST['Product']['product_category_id']==0){
    						unset($_POST['Product']['product_category_id']);
    					}
    					$arrIds=explode(',',$id_1);
//     					$model->batchUpdateCostLog($arrIds,$_POST['Product']['product_cost']);//批量修改 产品价格 添加日志
    					$arr_product = $_POST['Product'];
    					$arr_product['modify_user_id']=Yii::app()->user->id;
    					$arr_product['modify_time']= date('Y-m-d H:i:s');
    					UebModel::model('Productjob')->bathUpdateProductStatus($arr_product['product_status'],$skuArr);
    					$model->updateAll($arr_product, " id in ( " . $id_1 . " )");
    					//$flag = UebModel::model('product')->getDbConnection()->createCommand()->update($_POST['Product'], " id IN({$id_1})");
    					$flag = true;
    				}
    				//更改供应商code
    				if(isset($_POST['Provider']) && !empty($_POST['Provider']['provider_code'])){
    					UebModel::model('ProductProvider')->batchSaveProvider($ids,$id_1, $_POST['Provider']['provider_code']);
    					$flag = true;
    				}
    				/**更改报关中文名
    				if(isset($_POST['ProductDesc']) && !empty($_POST['ProductDesc']['customs_name'])){
    					UebModel::model('productDesc')->customsNameSave($ids, $_POST['ProductDesc']['customs_name']);
    					$flag = true;
    				}
    				*/
    
    				$transaction->commit();
    				//$flag = true;
    			} catch (Exception $e) {
    				$transaction->rollback();
    				//die($e->getMessage());
    				$flag = false;
    			}
    			 
    			if ($flag) {
    				$jsonData = array(
    						'message'       => Yii::t('common', 'Batch update successful'),
    						'forward' => '/products/product/list',
    						'navTabId' => 'page'.Product::getIndexNavTabId(),
    						'callbackType' => 'closeCurrent'
    				);
    				echo $this->successJson($jsonData);
    			}
    		}
    		if (! $flag) {
    			echo $this->failureJson(array('message'=> Yii::t('common', 'Batch update failure')));
    		}
    		Yii::app()->end();
    	}
    	
    	$productNum = count($ids);
    	$this->render('batch_update', array('model' => $model,'ids' => $productIds,'productNum' => $productNum));
    	
    	 
    }
   
	/**
     * view the product
     */
    public function actionView(){
    	$id = Yii::app()->request->getParam('id','');
    	$sku = Yii::app()->request->getParam('sku','');
    	if(empty($id) && !empty($sku)){
    		$id = UebModel::model('Product')->getProductIdBySku($sku);
    	}
        $model = $this->loadModel($id);
        $do = Yii::app()->request->getParam('do');
        $this->render('_tabs', array('model' => $model,'do' => $do,'action' => 'view','sku'=>$sku));
    }
    
    /**
     * checkInventory
     * 查看库存
     */
    
    public function actionCheckInventory(){
    	$id = Yii::app()->request->getParam('id','');
    	$sku = Yii::app()->request->getParam('sku');
    	$productInfo=UebModel::model('WarehouseSkuMap')->getInfoBySku($sku);    	
    	//$warehouse=UebModel::model("PurchaseInfo")->getWarehouseById($productInfo['warehouse_id']);
    	$this->render('check_inventory',array(
    		'productInfo'=>$productInfo,
    		//'warehouse'=>$warehouse,
    	));
    }
    /**
     * base info
     */
    public function actionBasec() {
        $model = new Product();
        $mds = new Productdesc();
        $mps = new ProductSecurity();
        $proCatOldModel = new ProductCategorySkuOld(); 
        $do = '';
        $lang_code = Yii::app()->request->getParam('lang_code');
        $ac = Yii::app()->request->getParam('ac');
      
        if (Yii::app()->request->isAjaxRequest && isset($_POST['Product'])) {   
        	$typeId=$_POST['Product']['original_material_type_id'];        	
			if($_POST['Product']['product_combine_code']){
				$arr=explode('+',$_POST['Product']['product_combine_code']);
				$nums='';
				$str='';
				foreach ($arr as $val){
					$str=explode('*',$val);
					$nums+=$str[1];
				}
			}			
            $providerCode = trim($_POST['Product']['provider_code']);
            $model->attributes = $_POST['Product'];
            $userId = Yii::app()->user->id;
            //$sku = UebModel::model('autoCode')->getCode('product');
            $sku=(string)$model->createSkugetNewTime(); 
            if ( $_POST['Product']['product_is_multi'] == $model->attributeMultiItem) {
                $sku = $sku.'.01';
            }
            $model->setAttribute('sku', $sku);
//			$model->setAttribute('product_status', Product::STATUS_NEWLY_DEVELOPED);
            $model->setAttribute('create_user_id', $userId);
            $model->setAttribute('modify_user_id', $userId);
            $model->setAttribute('original_material_type_id',$typeId);
         	$model->setAttribute('product_pack_code', $_POST['Product']['product_pack_code']);
         	$model->setAttribute('product_package_code', $_POST['Product']['product_package_code']);
            $model->setAttribute('create_time', date('Y-m-d H:i:s'));
            $model->setAttribute('modify_time', date('Y-m-d H:i:s'));
            $model->setAttribute('provider_code', $_POST['Product']['provider_code']);
            $model->setAttribute('product_type', $_POST['Product']['product_type']);
//          $model->setAttribute('product_category_id', $_POST['Product']['product_category_id']);
            $model->setAttribute('product_brand_id', $_POST['Product']['product_brand_id']);
            $model->setAttribute('product_cost', $_POST['Product']['product_cost']);
            $model->setAttribute('product_weight', $_POST['Product']['product_weight']);
            $model->setAttribute('product_length', $_POST['Product']['product_length']);
            $model->setAttribute('product_width', $_POST['Product']['product_width']);
            $model->setAttribute('product_height', $_POST['Product']['product_height']);           
            $model->setAttribute('product_is_multi', $_POST['Product']['product_is_multi']);
            $model->setAttribute('product_is_bak', empty($typeId)?$_POST['Product']['product_is_bak']:'0');
            $model->setAttribute('product_status', $_POST['Product']['product_status']);
            $model->setAttribute('product_combine_code', $_POST['Product']['product_combine_code']); 
            $model->setAttribute('product_combine_num', isset($nums)?$nums:'0');
            $model->setAttribute('product_original_package', $_POST['Product']['product_original_package']);           
            $model->setAttribute('product_bind_code', $_POST['Product']['product_bind_code']);
            $model->setAttribute('provider_type', $_POST['Product']['provider_type']);
            $model->setAttribute('product_cn_link', $_POST['Product']['product_cn_link']);
            $model->setAttribute('product_en_link', $_POST['Product']['product_en_link']);
            $model->setAttribute('product_to_way_package', $_POST['Product']['product_to_way_package']);
            if($_POST['Product']['provider_type'] == $model->provider){
            	$model->setAttribute('product_prearrival_days', $_POST['Product']['product_prearrival_days']);
            	$model->setAttribute('product_bak_days', $_POST['Product']['product_bak_days']);
            }else if($_POST['Product']['provider_type'] == $model->dropshipping){
            	$model->setAttribute('drop_shipping', $providerCode);
           		$model->setAttribute('drop_shipping_sku', '');//此处暂时为空，到时用API获取分销商料号
            }            
            $productbind = '';            
            if(! empty($_POST['bind_sku']) && ! empty($_POST['type_binding'])){
            	$arr_bind = array();
            	foreach($_POST['bind_sku'] as $k=>$v){
            		$productbind .= $v.'+'.$_POST['type_binding'][$k].',';
            		$arr_bind[] = array('bind_sku'=>$v,'type_binding'=>$_POST['type_binding'][$k]);
            	}
            	$model->setAttribute('product_bind_code', rtrim($productbind,','));
            }
            $titleInfo=UebModel::model('Productdesc')->getCnTitle($_POST['Productdesc']['title']);        
			if($titleInfo !=$_POST['Productdesc']['title']){
				if ( $model->validate()) {
					$transaction = $model->getDbConnection()->beginTransaction();
					try {
						if ( $lastId = $model->save() ) {
							//产品任务 保存
							$productJob=new Productjob();
							UebModel::model('Productjob')->saveJob($sku,$_POST['Product']['product_status']);
							
							//保存老系统产品类别，到时不用再删除
							if(!empty($_POST['ProductCategorySkuOld']['classid'])){
								$proCatOldModel->setAttribute('classid', $_POST['ProductCategorySkuOld']['classid']);
							}
							$proCatOldModel->setAttribute('sku', $sku);
							$proCatOldModel->save();				
							$id = $model->id;
							//保存 保全级别及 侵权
							if(!empty($id)){
								$mps->setAttribute('sku', $sku);
								$mps->setAttribute('security_level', $_POST['Product']['security_level']);
								$mps->setAttribute('infringement', $_POST['Product']['infringement']);
								$mps->setAttribute('infringement_reason', $_POST['Product']['infringement_reason']);
								$mps->setAttribute('infringe_platform', $_POST['Product']['infringe_platform']);
								$mps->setAttribute('operating_id', $userId);
								$mps->save();
							}				
							//保存开发人与创建人 相同
							UebModel::model('Productrole')->saveProductDevelopers($id,$userId);				
							//保存跟单人员
							if (! empty($_POST['Product']['single_person'])) {
								$singlePerson = $_POST['Product']['single_person'];
								UebModel::model('Productrole')->batchSave($id, $singlePerson);
							}
							//保存产品组合关系
							if (! empty($_POST['Product']['product_combine_code'])) {
								$combineCode = $_POST['Product']['product_combine_code'];
								UebModel::model('ProductCombine')->batchSave($id, $combineCode);
							}
							//将产品绑定入库
							if(! empty($_POST['bind_sku']) && ! empty($_POST['type_binding'])){
								UebModel::model('Productbind')->batchSave($model->sku, $arr_bind);
							}
							//保存产品、供应商关系
							UebModel::model('ProductProvider')->batchSave($id, $providerCode);
				
							//保存中文标题
							$mds->saveTitle($_POST['Productdesc']['title'],$sku,$id);
						}
						$productMsg = UebModel::getLogMsg();
						if (! empty($productMsg)) {
							Yii::ulog($productMsg, Yii::t('products', 'Product management'),$sku);
						}
						$transaction->commit();
						$flag = true;
					} catch (Exception $e) {
						$transaction->rollback();
						die($e->getMessage());
						$flag = false;
					}					
				} else {
					$flag = false;
				}
			}else{
				echo $this->failureJson(array('message' => Yii::t('products', 'The title already exists, please re-enter title')));
				Yii::app()->end();
			}
			if ($flag) {
				$jsonData = array(
						'message'       => Yii::t('system', 'Add successful'),
						'callback'      => 'tabAjaxDone',
						'id'            => $id,
						'ac'            => $ac
				);
				if($ac=='save_close'){
					$jsonData['forward'] = '/products/product/list';
					$jsonData['navTabId'] = 'page' . Product::getIndexNavTabId();
				}
				echo $this->successJson($jsonData);
			}
            if (! $flag) {
                echo $this->failureJson(array('message' => Yii::t('system', 'Add failure')));
            }
            Yii::app()->end();
        }
        $baocai['baocai'] = $model->getByMaterialTypeId('1');
        $baocai['baozhun'] = $model->getByMaterialTypeId('2');
        $category = UebModel::model('ProductCategory')->getCat(0);
        $catArr = array();
        foreach($category as $cat){
        	$catArr[$cat['id']] = $cat['category_cn_name'];
        }
        $toWayPackage=UebModel::model('ProductToWayPackage')->getProductPackageData();//来货方式包装
        $infringePlatform=UebModel::model('platform')->getPlatformList();
        $this->render('_base', array(
        		'model' => $model,
        		'mds'=>$mds,
        		'proCatOldModel'=>$proCatOldModel, 
        		'do' => $do,
        		'lang_code'=>$lang_code,
        		'baocai'=>$baocai,
        		'catArr'=>$catArr,
        		'toWayPackage'=>$toWayPackage,
        		'infringePlatform'=>$infringePlatform
        	)
        );
    }
    /**
     * update product status
     * @param primary $id
     */
    public function actionUpdatestatus($id) {
    	$model = $this->loadModel($id);
    	if (Yii::app()->request->isAjaxRequest && isset($_POST['Product'])) {
    		$model->attributes = $_POST['Product'];
    		if ( $model->validate()) {
    			try {
    				if($_POST['Product']['product_status']!=7){
    					$_POST['Product']['stock_reason']='';
    				}
    				$flag = $model->updateByPk($id, array('product_status'=>$_POST['Product']['product_status'],'is_to_mid'=>0,'modify_time'=>date('Y-m-d H:i:s'),'stock_reason'=>$_POST['Product']['stock_reason']));
    				$model->updateJobStatus($model->sku,$_POST['Product']['product_status']);
    					$productMsg = UebModel::getLogMsg();
    					if (! empty($productMsg)) {
    						Yii::ulog($productMsg, Yii::t('products', 'Edit the product Status'),$model->sku);
    					}
    			}catch (Exception $e){
    				die($e->getMessage());
    				$flag = false;
    			}
    		}else{
    			$flag= false;
    		}
    		if($flag){
    			$jsonData = array(
    					'message'   => Yii::t('system', 'Save successful'),
    					'forward' => '/products/product/list',
    					'navTabId' => 'page'.Product::getIndexNavTabId(),
    					'callbackType' => 'closeCurrent'
    			);
    			echo $this->successJson($jsonData);
    		}else{
    			echo $this->failureJson(array('message'=> Yii::t('system', 'Save failure')));
    		}
    		Yii::app()->end();
    	}

    	$this->render('updatestatus', array('model' => $model));
    }
    /**
     * update product cost
     * @param primary $id
     */
    public function actionUpdatecost($id) {
    	$model = $this->loadModel($id);
    	if (Yii::app()->request->isAjaxRequest && isset($_POST['Product'])) {
    		$model->attributes = $_POST['Product'];
    		if ( $model->validate()) {
    			try {
    				$costLog='sku的价格Product_cost由'.$model->product_cost.'修改为'.$_POST['Product']['product_cost'];
    				$flag=UebModel::model('ProductUpdateLog')->AddProductUpdateLog($model->sku,Product::PRODUCT_BASIC_INFO,$costLog);
    				if($flag){
    					$model->updateByPk($id, array('product_cost'=>$_POST['Product']['product_cost'],'is_to_mid'=>0,'modify_time'=>date('Y-m-d H:i:s')));
    				}else{
    					$flag=false;
    				}	
    			}catch (Exception $e){
    				die($e->getMessage());
    				$flag = false;
    			}
    		}else{
    			$flag= false;
    		}
    		if($flag){
    			$jsonData = array(
    					'message'   => Yii::t('system', 'Save successful'),
    					'forward' => '/products/product/list',
    					'navTabId' => 'page'.Product::getIndexNavTabId(),
    					'callbackType' => 'closeCurrent'
    			);
    			echo $this->successJson($jsonData);
    		}else{
    			echo $this->failureJson(array('message'=> Yii::t('system', 'Save failure')));
    		}
    		Yii::app()->end();
    	}
    	$this->render('updatecost', array('model' => $model));
    }
    
    /**
     * base info
     */
    public function actionBaseu($id) {		
        $model = $this->loadModel($id);
        $oldProductWeight=$model->product_weight;
        $oldPackagePack=$model->product_package_code.'-'.$model->product_pack_code;
        $mops= UebModel::model('ProductSecurity')->find('sku = :sku',array(':sku' => $model->sku));
		$proCatOldModel = UebModel::model('ProductCategorySkuOld')->find('sku = :sku',array(':sku' => $model->sku)); 
      	 if(empty($mops)){
       		$mps = new ProductSecurity();
      	 }
      	 if(empty($proCatOldModel)){
      	 	$productCatOldModel = new ProductCategorySkuOld();
      	 }
      	$modelX= UebModel::model('Productrole')->find('pro_id = :pro_id and role_code = :role_code',array(':pro_id' =>$id,':role_code'=>Productrole::ROLE_CODE));
     	if(empty($modelX)){
       		$models = new Productrole();
      	 }
      	$do = Yii::app()->request->getParam('do');
        $ac = Yii::app()->request->getParam('ac');
        if (Yii::app()->request->isAjaxRequest && isset($_POST['Product'])) {
        	$typeId=$_POST['Product']['original_material_type_id'];
        	if($_POST['Product']['product_combine_code']){
        		$arr=explode('+',$_POST['Product']['product_combine_code']);
        		$nums='';
        		$str='';
        		foreach ($arr as $val){
        			$str=explode('*',$val);
        			$nums+=$str[1];
        		}
        	}
            $providerCode = trim($_POST['Product']['provider_code']);
            $model->attributes = $_POST['Product'];
            $userId = Yii::app()->user->id;
            $sku=$model['sku'];
            $time=date('Y-m-d H:i:s');
            $model->setAttribute('modify_user_id', $userId);
            $model->setAttribute('modify_time', $time);
            $model->setAttribute('original_material_type_id', $_POST['Product']['original_material_type_id']);
       	    $model->setAttribute('product_pack_code', $_POST['Product']['product_pack_code']);
        	$model->setAttribute('product_package_code', $_POST['Product']['product_package_code']);
            $model->setAttribute('provider_code', $_POST['Product']['provider_code']);
            $model->setAttribute('product_type', $_POST['Product']['product_type']);
			$model->setAttribute('product_category_id', $_POST['Product']['product_category_id']);
			$model->setAttribute('product_brand_id', $_POST['Product']['product_brand_id']);
// 			$model->setAttribute('product_cost', $_POST['Product']['product_cost']);
            $model->setAttribute('product_weight', $_POST['Product']['product_weight']);
			$model->setAttribute('product_length', $_POST['Product']['product_length']);
			$model->setAttribute('product_width', $_POST['Product']['product_width']);
			$model->setAttribute('product_height', $_POST['Product']['product_height']);
			$model->setAttribute('product_is_multi', $_POST['Product']['product_is_multi']);
			$model->setAttribute('product_is_bak', empty($typeId)?$_POST['Product']['product_is_bak']:'0');
//          $model->setAttribute('product_status', $_POST['Product']['product_status']);
            $model->setAttribute('provider_type', $_POST['Product']['provider_type']);
            $model->setAttribute('product_combine_code', $_POST['Product']['product_combine_code']);
            $model->setAttribute('product_combine_num', isset($nums)?$nums:'0');
            $model->setAttribute('product_original_package', $_POST['Product']['product_original_package']);
            $model->setAttribute('product_cn_link', $_POST['Product']['product_cn_link']);
            $model->setAttribute('product_en_link', $_POST['Product']['product_en_link']);
            $model->setAttribute('product_to_way_package', $_POST['Product']['product_to_way_package']);
            $model->setAttribute('is_to_mid', 0);
        	if($_POST['Product']['provider_type'] == $model->provider){       		
            	$model->setAttribute('product_prearrival_days', $_POST['Product']['product_prearrival_days']);
            	$model->setAttribute('product_bak_days', $_POST['Product']['product_bak_days']);
            	$model->setAttribute('drop_shipping', '');
            	$model->setAttribute('drop_shipping_sku', '');
            }else if($_POST['Product']['provider_type'] == $model->dropshipping){
            	
            	$model->setAttribute('drop_shipping', $providerCode);
           		$model->setAttribute('drop_shipping_sku', '');//此处暂时为空，到时用API获取分销商料号
            }
            if($oldProductWeight !=$_POST['Product']['product_weight']){ //产品成本价修改日志
            	$costLog='sku的重量Product_weight由'.$oldProductWeight.'修改为'.$_POST['Product']['product_weight'];            	
            	UebModel::model('ProductUpdateLog')->AddProductUpdateLog($sku,Product::PRODUCT_BASIC_INFO,$costLog);
            }
       		if($oldPackagePack != $_POST['Product']['product_package_code'].'-'.$_POST['Product']['product_pack_code']){
       			$log='sku的包装包材由'.$oldPackagePack.'修改为'.$_POST['Product']['product_package_code'].'-'.$_POST['Product']['product_pack_code'];
       			UebModel::model('ProductUpdateLog')->AddProductUpdateLog($sku,Product::PRODUCT_BASIC_INFO,$log);
       		}
            
            if($mops){
            	$mops->setAttribute('security_level', $_POST['Product']['security_level']);
            	$mops->setAttribute('infringement', $_POST['Product']['infringement']);
            	$mops->setAttribute('infringement_reason', $_POST['Product']['infringement_reason']);
            	$mops->setAttribute('infringe_platform', $_POST['Product']['infringe_platform']);
            	$mops->setAttribute('operating_id', $userId);
            	$mops->setAttribute('operating_time', $time);           	
            	$mops->save();
            }else{          	            	
            	$mps->setAttribute('sku', $sku);
            	$mps->setAttribute('security_level', $_POST['Product']['security_level']);
            	$mps->setAttribute('infringement', $_POST['Product']['infringement']);
            	$mps->setAttribute('infringement_reason', $_POST['Product']['infringement_reason']);
            	$mps->setAttribute('infringe_platform', $_POST['Product']['infringe_platform']);
            	$mps->setAttribute('operating_id', $userId);
            	$mps->setAttribute('operating_time', $time);
            	$mps->save();         	
            }
            $userName=MHelper::getUsername($_POST['Product']['single_person']);
            if($modelX){            	
            	$modelX->setAttribute('sku', $sku);
            	$modelX->setAttribute('pro_id', $id);
            	$modelX->setAttribute('pro_category_id',$model->product_category_id);
            	$modelX->setAttribute('role_name', '');
            	$modelX->setAttribute('role_code', Productrole::ROLE_CODE);
            	$modelX->setAttribute('user_id', $_POST['Product']['single_person']);
            	$modelX->setAttribute('user_name', $userName);
            	$modelX->setAttribute('modify_user_id', $userId);
            	$modelX->setAttribute('modify_time', date('Y-m-d H:i:s'));  
				$modelX->save();
            }else{
            	$models->batchSave($id, $_POST['Product']['single_person']);
            }
            
            if($proCatOldModel){
            	$proCatOldModel->setAttribute('classid', $_POST['ProductCategorySkuOld']['classid']);
            	$proCatOldModel->save();
            }else{
            	$productCatOldModel->setAttribute('sku', $sku);
            	$productCatOldModel->setAttribute('classid', $_POST['ProductCategorySkuOld']['classid']);
            	$productCatOldModel->save();
            }
            
            $productbind = '';

            if(! empty($_POST['bind_sku']) && ! empty($_POST['type_binding'])){
            	$arr_bind = array();
            	foreach($_POST['bind_sku'] as $k=>$v){
            		$productbind .= $v.'+'.$_POST['type_binding'][$k].',';
            		$arr_bind[] = array('bind_sku'=>$v,'type_binding'=>$_POST['type_binding'][$k]);
            	}
            	$model->setAttribute('product_bind_code', rtrim($productbind,','));
            }

            if ( $model->validate()) {
                $transaction = $model->getDbConnection()->beginTransaction();
        		try {
        			if ( $model->save() ) {
        				if (! empty($_POST['Product']['product_combine_code'])) {
        					$combineCode = $_POST['Product']['product_combine_code'];
        					UebModel::model('ProductCombine')->batchSave($id, $combineCode);
        				}
        				
        				UebModel::model('ProductProvider')->batchSave($id, $providerCode);
        				//将产品绑定入库
        				if(! empty($_POST['bind_sku']) && ! empty($_POST['type_binding'])){
        					UebModel::model('Productbind')->batchSave($model->sku, $arr_bind);
        				}
        			}
        			 
        			$productMsg = UebModel::getLogMsg();
        			if (! empty($productMsg)) {
        				Yii::ulog($productMsg, Yii::t('products', 'Product management'),$model->sku);
        			}

        			$transaction->commit();
        			$flag = true;
        		} catch (Exception $e) {
        			$transaction->rollback();
        			//die($e->getMessage());
        			$flag = false;
        		}
                if ($flag) {
                    $jsonData = array(
                        'message'   => Yii::t('system', 'Save successful'),    
                        'ac'        => $ac,
                    	'callback'  => 'tabAjaxDone'
                    );
                    if($ac=='save_close'){
                    	$jsonData['forward'] = '/products/product/list';
                    	$jsonData['navTabId'] = 'page' . Product::getIndexNavTabId();
                    }
                    echo $this->successJson($jsonData);
                }
            } else {                
                $flag = false;
            }
            if (! $flag) {
            	$msg = $model->getValidateErrors();
            	$msg = empty($msg) ? '' : ' : '.$msg;
                echo $this->failureJson(array('message' => Yii::t('system', 'Save failure').$msg));
            }
            Yii::app()->end();
        }
        $providerIds 			= UebModel::model('ProductProvider')->getProviderIdByProductId($id);
        $model->combine 		= UebModel::model('ProductCombine')->getCombineList($id);
        $model->bind  			= UebModel::model('Productbind')->getBindSkuByBaseSku($model->sku);
        $providerCode 			= UebModel::model('Provider')->getCodeById($providerIds);
        $productSecurityList	= UebModel::model('Product')->getProductSecurityList();//产品侵权List
        $productInfringement	= UebModel::model('Product')->getProductInfringementList();
        $productBrand 			= UebModel::model('Productbrand')->getListOptions();
     	$bindProvider 			= UebModel::model('ProductProvider')->getBindSkuProvider($id);
     	$proCatOldModel 		= $proCatOldModel ? $proCatOldModel : $productCatOldModel;
        $model->provider_code 	= empty($providerCode) ? '' : implode(",", $providerCode);
        $model->provider_code 	= trim( $model->provider_code,',');
        $model->security_level 	= $mops->security_level ? $productSecurityList[$mops->security_level] : '-';
        $model->infringement 	= $mops->infringement>=1 ? $productInfringement[$mops->infringement] : $productInfringement[1];
       
        if($model->drop_shipping){
        	$model->provider_type = $model->dropshipping;
        }else{
        	$model->provider_type = $model->provider;
        }
        
        //获取第一张副图
        $ft =  UebModel::model('Productimage')->getFtList($model->sku,0);
        if($ft){
        	$ft = array_shift($ft);
        	$url = '/products/Productimage/view1/sku/'.$model->sku;//获取下一张图片url
        	$arr_img=array("style"=>"border:1px solid #ccc;padding:2px;","width"=>80,"height"=>80,'large-src'=>$ft, 'pic-link'=>$url);
        	$arr_href=array('id'=>'ajax_1','for'=>$model->sku,'class'=>'cboxElement','title'=>Yii::t('products','Click me to view larger image'));
        	 
        	$model->ft = CHtml::link(CHtml::image(Yii::app()->baseUrl.$ft,$model->sku,array("style"=>"border:1px solid #ccc;padding:2px;width:240px;")),$url,$arr_href);
        }else{
        	$model->ft = CHtml::image(Yii::app()->baseUrl.'images/nopic.gif',$model->sku,array("style"=>"border:1px solid #ccc;padding:2px;"));
        }
        $baocai['baocai'] = $model->getByMaterialTypeId('1');
        $baocai['baozhun'] = $model->getByMaterialTypeId('2');
        $category = UebModel::model('ProductCategory')->getCat(0);
        $categories = ProductCategory::model()->getAllParentByCategoryId($model->product_category_id);
        $catetoryArr =  UebModel::model('ProductCategory')->getCategoryArr(CN);
        $newCategory=$model->getCategoryBySku($model->sku);
        empty($newCategory)?'':$newCategory;
        $catArr = array();
        $catetoryView = '';
        foreach($category as $cat){
        	$catArr[$cat['id']] = $cat['category_cn_name'];
        }
        foreach($categories as $key=>$value){
        	$catetoryView .= $catetoryArr[$value].">>";
        }
        $catetoryView = trim($catetoryView,">>");     
        $toWayPackage=UebModel::model('ProductToWayPackage')->getProductPackageData();//来货方式包装
        $infringePlatform=UebModel::model('platform')->getPlatformList();
        if(isset($do) && $do){  
        	$this->render('view', array(
        			'model' => $model,
        			'do' => $do,
        			'baocai'=>$baocai,
        			'mops'=>$mops,
        			'catArr'=>$catArr,
        			'catetoryView'=>$newCategory,
        			'bindProvider'=>$bindProvider,
        			'productBrand'=>$productBrand
        		)
        	);
        }else{
        	$this->render('_base', array(
        			'model' => $model,
        			'do' => $do,
        			'baocai'=>$baocai,
        			'proCatOldModel'=>$proCatOldModel,
        			'mops'=>$mops,
        			'catArr'=>$catArr,
        			'modelX'=>$modelX,
        			'toWayPackage'=>$toWayPackage,
        			'infringePlatform'=>$infringePlatform
        		)
        	);
        }
    }
    

    /**
     * delete the products
     *
     * @param type $id
     */
    public function actionDelete() {
    	if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
    		try {
    			$flag = UebModel::model('Product')->getDbConnection()
                    ->createCommand()
                    ->delete(UebModel::model('Product')->tableName(), " id IN({$_REQUEST['ids']})");
    			if ( ! $flag ) {
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
    
   /**
    * get child sku
    * @author Nick 2013-11-18
    */ 
   public function actionGetchild() {
       $index = Yii::app()->request->getParam('index');
       $this->render('_childsku',array(
       		'index' => $index,
       ));
   }
	/**
    * get binding sku
    */ 
   public function actionGetBindSku() {
       $index = Yii::app()->request->getParam('index');
       $row = UebModel::model('Productbind')->getBindSku($index);
       die($row);
   }
    public function loadModel($id) {      	  
        $model = UebModel::model('Product')->findByPk((int) $id);
        if ( $model === null )
            throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));            
        return $model;
    } 
    
    /**
     * Check sku
     * @author Nick 2013-11-6
     */
    public function actionChecksku(){
    	$sku = Yii::app()->request->getParam('sku');
    	$status = UebModel::model('Product')->checkSkuIsExisted($sku);
    	echo $status;
    }
    
    /**
     * get product info by sku
     */
    public function actionGetsku(){
    	$sku = Yii::app()->request->getParam('sku');
    	$key = Yii::app()->request->getParam('key');
    	$arrProduct = UebModel::model('Product')->getBySku($sku);
    	if($arrProduct){
    		$arrProductDesc = UebModel::model('Productdesc')->getDescriptionInfoByProductIdAndLanguageCode($arrProduct['id'],CN);
    		if(empty($arrProductDesc)){
    			$arrProductDesc = UebModel::model('Productdesc')->getDescriptionInfoByProductIdAndLanguageCode($arrProduct['id'],EN);
    		}   		
    		$arr['id']=$arrProduct['id'];
    		$arr['price']=$arrProduct['product_cost'];
    		$arr['title']=$arrProductDesc['title'];
    		$arr['img']= MHelper::getProductPicBySku($sku,"thumb",$key);
    		//获取最新报价
    		$arr['last_inquire']= UebModel::model('PurchaseInquire')->getLastInquireBySku($sku);
    		exit(json_encode($arr));
    	}
    	else exit('nodata');
    }

    public function getGalleryBySku($sku,$image){
    	$imageSku=substr($image,0,-4);		 
    	$data=UebModel::model('Productimage')->getFtLists($sku,0);
		$str='';
		unset($data[$imageSku]);
		foreach ($data as $val){
			$arr=explode('/',$val);
			$str.=$arr[6].',';
		}
		return !empty($str)?substr($str,0,-1):'';
    }
    public function getimageBySku($sku){
    	$data=UebModel::model('Productimage')->getFtLists($sku,0);
		$list=array();
   		 foreach ($data as $val){
			$arr=explode('/',$val);
			$list[]=$arr[6];
		}
		return $list[0];
    }
    
    public function actionWebsiteNewSku(){
    	$this->render('import');
    }
    /**
     * 网站 需要的OMS新品数据导出
     */
    
    public function actionImportomsskudata(){
    	ini_set('memory_limit','50M');
    	ini_set('post_max_size','50M');
    	ini_set('upload_max_filesize','50M');
    	set_time_limit('3600');
    	if($_FILES['batchFile']){
    		$path_arr = explode('.',$_FILES['batchFile']['name']);
    		if($path_arr[1]== 'xls' || $path_arr[1] == 'xlsx'){
    			move_uploaded_file($_FILES['batchFile']["tmp_name"],'./upload/'.$_FILES['batchFile']['name']);//存在则移动
    		}else{
    			exit('请选择Execl文件上传！！');
    		}
    	}else{
    		exit('文件上传失败！！');
    	}	
    	
    	$file_name=$_FILES['batchFile']['name'];//获取文件名字
    	Yii::import('application.vendors.MyExcel.php');
    	$excel_file_path = './upload/'.$file_name;//Excel文件导入
    	$objMyExcel = new MyExcel();
    	$excel_data = $objMyExcel->get_excel_con($excel_file_path);//得到文件数据
    	
    	if(!file_exists($excel_file_path)){
    		exit('文件不存在');
    	}
    	$skuArr=array();
    	for($i=1;$i<= count($excel_data);$i++){//取sku
    		if(strlen($excel_data[$i]['A']) > 10){
    			$skuArr[]=sprintf("%.2f", $excel_data[$i]['A']);
    		}else{
    			$skuArr[]=strval($excel_data[$i]['A']);
    		}
    	}
    	$newSkuArr=array();
    	foreach($skuArr as $val){
    		$model=UebModel::model('Product')->find('sku = :sku',array(':sku' => $val));
    		if($model->product_is_multi==2){//如果是主sku  找出相应的子SKU
    			$sonSkuArr=UebModel::model('ProductSelectAttribute')->getSubSkuArrData($model->id);   	//得到主sku的子sku		
    			if(!empty($sonSkuArr)){
    				foreach ($sonSkuArr as $sonSku){
    					$newSkuArr[]=strval($sonSku);
    				}
    			}	
    			$newSkuArr[]=strval($val);
    			
    		}
    		if($model->product_is_multi==1){//如果是子sku
    			$mainSkuId=UebModel::model('ProductSelectAttribute')->getMultiProductIdBySonSku($val);//先找出主sku的id
    			if(!empty($mainSkuId)){
    				$mainSkuInfo=UebModel::model('Product')->findByPk($mainSkuId);//主sku
    				$sonSkuArr=UebModel::model('ProductSelectAttribute')->getSubSkuArrData($mainSkuId);//主sku 下的所有 子sku
    				if(!empty($sonSkuArr)){
    					foreach ($sonSkuArr as $sonSku){
    						$newSkuArr[]=strval($sonSku);
    					}
    				}
    				$newSkuArr[]=strval($mainSkuInfo->sku);
    			}else{
    				$newSkuArr[]=strval($val);
    			}	
    		}
    		if($model->product_is_multi==0){//单品
    			$newSkuArr[]=strval($val);		
    		}
    	}
    	$newSkuArrs=array_unique($newSkuArr);
    	if(isset($newSkuArrs)){
    		foreach ($newSkuArrs as $sku){
    			$models=UebModel::model('Product')->find('sku = :sku',array(':sku' => $sku));
    			$skuInfo=UebModel::model('Product')->getSkuDataBySku($sku);
    			if($models->product_is_multi==1){
    				$sonSkuAttr=UebModel::model('ProductSelectAttribute')->getSonAttrInfoBySku($sku);//子sku的所有 私有属性
    				$sonSkuAttrCategory=UebModel::model('ProductSelectAttribute')->getSonSkuAttrCategory($sku);//子sku的私有属性类别
    			}
    			if($models->product_is_multi==2){
    				$subSku=UebModel::model('ProductSelectAttribute')->getSubSkuArrData($models->id);//主sku的所有子sku
    				$strSubSku=implode(',',$subSku);
    			} 
    			$image=	$this->getimageBySku($sku);  //主图 小图 缩略图
    			$gallery=$this->getGalleryBySku($sku,$image);//幅图
    			$skuAttrCategory='';
    			if($models->product_is_multi==1){
    				$skuAttrCategory=$sonSkuAttrCategory;
    			}elseif($models->product_is_multi==2){
    				$skuAttrCategory=UebModel::model('ProductSelectAttribute')->getMainSkuAttr($models->id);
    			}else{
    				$skuAttrCategory='';
    			}
    			$dataInsert[] = array(
    					'store' 				=>'',
    					'websites' 				=>'',
    					'attribute_set' 		=>'',
    					'type' 					=>$models->product_is_multi==2?'configurable':'simple',
    					'sku' 					=>"'".$sku."'",
    					'categories' 			=>'',
    					'color' 				=>$models->product_is_multi==1?$sonSkuAttr[22]:'',
    					'new_size' 				=>$models->product_is_multi==1?$sonSkuAttr[23]:'',
    					'new_style' 			=>$models->product_is_multi==1?$sonSkuAttr[24]:'',
    					'price' 				=>'',
    					'weight' 				=>$skuInfo['product_weight'],
    					'name' 					=>$skuInfo['title'],
    					'image' 				=>!empty($image)?$image:'',
    					'thumbnail' 			=>!empty($image)?$image:'',
    					'small_image' 			=>!empty($image)?$image:'',
    					'gallery' 				=>!empty($gallery)?$gallery:'',
    					'short_description' 	=>'',
    					'description' 			=>$skuInfo['description']."\n"."included:"."\n".$skuInfo['included'],
    					'meta_title' 			=>'',
    					'meta_keyword' 			=>'',
    					'meta_description' 		=>'',
    					'status' 				=>'',
    					'visibility' 			=>$models->product_is_multi==1?'Nowhere':'Catalog, Search',
    					'tax_class_id' 			=>'',
    					'qty' 					=>'',
    					'is_in_stock' 			=>'',
    					'options_container' 	=>'',
    					'associated' 			=>$models->product_is_multi==2?$strSubSku:'',
    					'crosssell' 			=>'',
    					'config_attributes' 	=>$skuAttrCategory,
    					'tier_prices' 			=>'',
    					'has_options' 			=>' '.$models->product_is_multi==2?1:'',
    					'super_attribute_pricing' =>'',
    			);	 
    		}
    	}
    	
    	//CSV  格式导出
    	$filename ='skudata.csv'; //设置文件名
    	header("Content-type:text/csv");
    	header("Content-Disposition:attachment;filename=".$filename);
    	header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
    	header('Expires:0');
    	header('Pragma:public');
    	$head_arr = array(0=>'store',1=>'websites',2=>'attribute_set',3=>'type',4=>'sku',5=>'categories',6=>'color',7=>'new_size',8=>'new_style',9=>'price',10=>'weight',
    			11=>'name',12=>'image',13=>'thumbnail',14=>'small_image',15=>'gallery',16=>'short_description',17=>'description',18=>'meta_title',19=>'meta_keyword',
    			20=>'meta_description',21=>'status',22=>'visibility',23=>'tax_class_id',24=>'qty',25=>'is_in_stock',26=>'options_container',27=>'associated',
    			28=>'crosssell',29=>'config_attributes',30=>'tier_prices',31=>'has_options',32=>'super_attribute_pricing');
    	$fp = fopen('php://output', 'a');
    	
    	foreach($head_arr as $key => $val ){
    		$head_arr[$key] = iconv('utf-8','gbk',$val);
    	}
    	@fputcsv( $fp,$head_arr);
    	 
    	$sheet_values=array();
    	// 循环添加 数据
    	foreach ($dataInsert as $info){
    		$sheet_values[0]  = $info['store'];
    		$sheet_values[1]  = $info['websites'];
    		$sheet_values[2]  = $info['attribute_set'];
    		$sheet_values[3]  = $info['type'];
    		$sheet_values[4]  = $info['sku'];
    		$sheet_values[5]  = $info['categories'];
    		$sheet_values[6]  = $info['color'];
    		$sheet_values[7]  = $info['new_size'];
    		$sheet_values[8]  = $info['new_style'];
    		$sheet_values[9]  = $info['price'];
    		$sheet_values[10] = $info['weight'];
    		$sheet_values[11] = $info['name'];
    		$sheet_values[12] = $info['image'];
    		$sheet_values[13] = $info['thumbnail'];
    		$sheet_values[14] = $info['small_image'];
    		$sheet_values[15] = $info['gallery'];
    		$sheet_values[16] = $info['short_description'];
    		$sheet_values[17] = $info['description'];
    		$sheet_values[18] = $info['meta_title'];
    		$sheet_values[19] = $info['meta_keyword'];
    		$sheet_values[20] = $info['meta_description'];
    		$sheet_values[21] = $info['status'];
    		$sheet_values[22] = $info['visibility'];
    		$sheet_values[23] = $info['tax_class_id'];
    		$sheet_values[24] = $info['qty'];
    		$sheet_values[25] = $info['is_in_stock'];
    		$sheet_values[26] = $info['options_container'];
    		$sheet_values[27] = $info['associated'];
    		$sheet_values[28] = $info['crosssell'];
    		$sheet_values[29] = $info['config_attributes'];
    		$sheet_values[30] = $info['tier_prices'];
    		$sheet_values[31] = $info['has_options'];
    		$sheet_values[32] = $info['super_attribute_pricing'];
    		foreach ($sheet_values as $k =>$v){
    			$sheet_values[$k] = iconv('utf-8','gbk',$v);
    		}
    		@fputcsv( $fp,$sheet_values);
    	}
    	@fclose($fp);
    	exit();
    }
}