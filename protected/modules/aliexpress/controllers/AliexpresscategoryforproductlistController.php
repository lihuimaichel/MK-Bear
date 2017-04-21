<?php
/**
 * @desc   Aliexpress分类id获取
 * @author AjunLongLive!
 * @since  2017-04-03
 */
class AliexpresscategoryforproductlistController extends UebController{
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('getcategorys', 'getcategoryattributes')
			),
		);
    }

    /**
     * @desc 获取分类  http://erp_market.com/aliexpress/aliexpresscategory/getcategories/cate_id/1501
     */
    public function actionGetcategories(){
        set_time_limit(3600);
    	$cateId = isset($_REQUEST['cate_id'])?$_REQUEST['cate_id']:0; //category_id
        //取一个可用账号
        $account = AliexpressAccount::getAbleAccountByOne();
        $accountID = $account['id'];
        $logID = AliexpressLog::model()->prepareLog($accountID,AliexpressCategory::EVENT_NAME);
        if( $logID ){
            $checkRunning = AliexpressLog::model()->checkRunning($accountID, AliexpressCategory::EVENT_NAME);
            $checkRunning = true;
            if( !$checkRunning ){
                AliexpressLog::model()->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                echo $this->failureJson(array('message'=>'There Exists An Active Event'));
                exit;
            }else{
            	$aliexpressCategoryModel = new AliexpressCategory();
            	$cateLevel = 1;
            	$parentId = $cateId;
            	//找出指定分类信息
            	if($cateId > 0){
            		$cateInfo = $aliexpressCategoryModel->getCategotyInfoByID($cateId);
            		if(empty($cateInfo)){
                        echo $this->failureJson(array('message'=>"没有对应的分类"));
            			exit();
            		}
            		$cateLevel = intval($cateInfo['create_level'])+1;
            	}
                AliexpressLog::model()->setRunning($logID);
                $aliexpressCategoryModel->setAccountID($accountID);
                $params = array(
                		'cateId'=>$cateId,
                );
                $flag = $aliexpressCategoryModel->updateCategories($params, $cateLevel, $parentId);//拉取分类
                //更新日志信息
                if( $flag ){
                    AliexpressLog::model()->setSuccess($logID);
                    echo $this->successJson(array('message'=>'更新分类成功'));

                }else{
                    AliexpressLog::model()->setFailure($logID, $aliexpressCategoryModel->getExceptionMessage());
                    echo $this->failureJson(array('message'=>$aliexpressCategoryModel->getExceptionMessage()));
                }
            }
        }
    }
    
    /**
     * @desc 获取分类树
     */
    public function actionCategorytree(){
        $parentId = Yii::app()->request->getParam('category_id');
        $parentId = $parentId ? $parentId : 0;
        $categories = LazadaCategory::model()->getCategoriesByParentID($parentId);
        if(!$parentId){
            $this->render('CategoryTree',array(
                'categories'    => $categories,
            ));
        }else{
            echo json_encode($categories);
            Yii::app()->end();
        }
    }
    
    /**
     * @desc 更新分类属性   http://erp_market.com/aliexpress/aliexpresscategory/getcategoryattributes/category_id/100005827
     */
    public function actionGetcategoryattributes() {
        ini_set('memory_limit','2048M');
    	set_time_limit(300);
    	error_reporting(E_ALL);
    	ini_set("display_errors", true);
        $accountID = Yii::app()->request->getParam('account_id');
        if(!$accountID){
        	$accountInfo = AliexpressAccount::getAbleAccountByOne();
        	$accountID = $accountInfo['id'];
        }
    	//$accountID = 247;
    	$categoryID = Yii::app()->request->getParam('category_id');
    	$categoryIds = array();
    	//如果指定了要拉取属性的分类ID，则只拉该分类的属性，否则拉取所有分类的属性
    	if ($categoryID) {
    		$categoryIds[] = $categoryID;
    	} else {
    		$AliexpressCategoryIdList = AliexpressCategory::model()->getCategoryList('category_id');
    		if (!empty($AliexpressCategoryIdList)) {
    			foreach ($AliexpressCategoryIdList as $AliexpressCategoryId) {
    				$categoryIds[] = $AliexpressCategoryId['category_id'];
    			}
    		}
    	}
    	if(isset($_REQUEST['bug'])){
    		echo date("Y-m-d H:i:s")."<br/>";
    	}
    	
    	if (!empty($categoryIds)) {
    		$AliexpressLogModel = new AliexpressLog();
    		$logID = $AliexpressLogModel->prepareLog($accountID, AliexpressCategoryAttributes::EVENT_NAME);
    		if ($logID) {
    			$checkRunning = $AliexpressLogModel->checkRunning($accountID, AliexpressCategoryAttributes::EVENT_NAME);
    			if( !$checkRunning ){
    				$AliexpressLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    			} else {
    				$AliexpressLogModel->setRunning($logID);
    				foreach ($categoryIds as $categoryID) {
    					$AliexpressCategoryAttributes = new AliexpressCategoryAttributes();
    					$AliexpressCategoryAttributes->setAccountID($accountID);
    					$AliexpressCategoryAttributes->setCategoryId($categoryID);
    					$flag = $AliexpressCategoryAttributes->getCategoryAttributes(true);
    					if( !$flag ){
    						$AliexpressLogModel->setFailure($logID, $AliexpressCategoryAttributes->getExceptionMessage());
    						exit();
    					}
    				}
    				$AliexpressLogModel->setSuccess($logID);
    			}
    			
    		}
    	}
    	if(isset($_REQUEST['bug'])){
    		echo date("Y-m-d H:i:s")."<br/>";
    	}
    	echo json_encode(array(
    			'statusCode'	=>	200,
    			'message'		=>	'success'
    		));
   	}
    
    
    /**
     * @desc 获取关键词推荐分类   http://erp_market.com/aliexpress/aliexpresscategory/getcategorysuggest/keyword/
     */
    public function actionGetcategorysuggest() {
    	$accountInfo = AliexpressAccount::getAbleAccountByOne();
    	$accountID = $accountInfo['id'];
    	//$accountID = 247;
    	
    	$keyword = Yii::app()->request->getParam('keyword');
    	$keyword = str_replace( array(' to ',' for ',' in ',' & ',' + ',' of ',' and ',' or ',' For ',' Of ',' To ',' And ',' Of ',' In '),' ',$keyword );
    	$keywords = array();
    	if ($keyword) {
    		$keywords[] = $keyword;
    	}
    	if (!empty($keywords)) {
    		foreach ($keywords as $currKeyword) {
    			$AliexpressLogModel = new AliexpressLog();
    			$logID = $AliexpressLogModel->prepareLog($accountID, AliexpressCategory::EVENT_NAME_CATE_SUGGEST);
    			if ($logID) {
    				$checkRunning = $AliexpressLogModel->checkRunning($accountID, AliexpressCategory::EVENT_NAME_CATE_SUGGEST);
    				$checkRunning = true;
    				if( !$checkRunning ){
    					$AliexpressLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
    				}else{

                        /********           调用速卖通API接口开始   由于速卖通接口按关键字查询无法使用暂时屏蔽      *******/
    					// $AliexpressCategory = new AliexpressCategory();
    					// $AliexpressCategory->setAccountID($accountID);
    					// $AliexpressCategory->setKeyword($currKeyword);
    					// $returnArray = $AliexpressCategory->updateCategorySuggest();
    					// if( $returnArray['flag'] ){
    					// 	$AliexpressLogModel->setSuccess($logID);
    					// 	echo json_encode( array('statusCode'=>200,'categoryList'=>$returnArray['categoryList']) );
    					// }else{
    					// 	$AliexpressLogModel->setFailure($logID, $AliexpressCategory->getExceptionMessage());
    					// 	echo json_encode( array('statusCode'=>400, 'message' => $AliexpressCategory->getExceptionMessage()) );
    					// }
                        /********           调用速卖通API接口结束       *******/

                        //调用本地数据库
                        $aliexpressCategoryModel = new AliexpressCategory();
                        $infos = $aliexpressCategoryModel->getCategoryIDAndCategoryNameByKeyWords($currKeyword);
                        if($infos){
                            $returnArray = array();
                            foreach ($infos as $key => $value) {
                                $returnArray[] = array('category_id'=>$value['category_id'], 'category_name'=>$value['category_name']);
                            }
                            echo json_encode( array('statusCode'=>200,'categoryList'=>$returnArray) );
                        }else{
                            echo json_encode( array('statusCode'=>400, 'message' => '没有搜索结果') );
                        }
    				}
    			}
    		}
    	} else {
    		echo json_encode( array('statusCode'=>400, 'message' => Yii::t('aliexpress_product', 'Search Keywords Empty')) );
    	}
    }
    
    /**
     * @desc 查找分类的子分类
     */
    public function actionFindsubcategory() {
    	$categoryID = Yii::app()->request->getParam('category_id');
    	$categoryInfos = AliexpressCategory::model()->getSubCategory($categoryID);
    	if (!empty($categoryInfos)) {
    		$categoryList = array();
    		$level = '';
    		foreach ($categoryInfos as $categoryInfo) {
    			$categoryList[$categoryInfo->category_id] = $categoryInfo->en_name . '(' . $categoryInfo->cn_name . ')';
    			$level = $categoryInfo->create_level;
    		}
    		echo $this->successJson(array(
    			'category_list' => $categoryList,
    			'level' => $level,
    		));
    	} else {
    		echo $this->failureJson(array(
    			'message' => Yii::t('aliexpress_product', 'Could not Find Subcategories'),
    		));
    	}
    }
    /**
     * @desc 查找分类属性
     */
    public function actionFindcategoryattributes() {
    	$categoryID = Yii::app()->request->getParam('category_id');
    	$sku = Yii::app()->request->getParam('sku');
    	$skuInfo = Product::model()->getProductInfoBySku($sku);
    	if (empty($skuInfo)) {
    		echo json_encode(array());
    		Yii::app()->end();
    	}
    	$categoryAttributes = array();
    	//查找分类下面sku属性
    	$skuAttributes = AliexpressAttribute::model()->getCategoryAttributeList($categoryID, AliexpressAttribute::ATTRIBUTE_TYPE_SKU);
    	$commonAttributes = AliexpressAttribute::model()->getCategoryAttributeList($categoryID);
    	if (empty($commonAttributes)) {
    		MHelper::runThread(Yii::app()->createUrl('aliexpress/aliexpresscategory/getcategoryattributes/category_id/' . $categoryID));
    	}
    	$productID = null;
    	$productMainID = null;
    	if ($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN)
    		$productMainID = $skuInfo['id'];
    	else
    		$productID = $skuInfo['id'];
    	$k = 0;
    	$tmpSkuAttributes = array();	//解决前端json排序的问题，将attribute_id健换成0开始的数字
    	$selectedSkuAttributes = array();
    	$omsProductAttributesMap = array();
    	foreach ($skuAttributes as $attributeID => $attributes) {
    		//查找产品在OMS对应的属性
    		$omsAttributeID = AttributeMarketOmsMap::model()->getOmsAttrIdByPlatAttrId(Platform::CODE_ALIEXPRESS, $attributeID);
    		if (empty($omsAttributeID)) {
    			$tmpSkuAttributes[$k] = $skuAttributes[$attributeID];
    			$k++;
    			continue;
    		}
    		$omsProductAttributes = ProductSelectAttribute::model()->getAttributeValList($omsAttributeID, null, $skuInfo['id']);
    		$omsProductAttributesMap[$attributeID] = $omsProductAttributes;
    		if (empty($omsProductAttributes)) {
    			$tmpSkuAttributes[$k] = $skuAttributes[$attributeID];
    			$k++;
    			continue;
    		}
    		$tmpValueList = $attributes['value_list'];
    		//查找OMS选中属性值对应平台属性值
    		foreach ($omsProductAttributes as $omsProductAttribute) {
    			foreach ($tmpValueList as $key => $list) {
    				if (trim($omsProductAttribute['attribute_value_name']) == trim($list['attribute_value_cn_name'])) {
	    				$skuAttributes[$attributeID]['value_list'][$key]['selected'] = true;
	    				$skuAttributes[$attributeID]['value_list'][$key]['sku'] = $omsProductAttribute['sku'];
	    				$selectedSkuAttributes[$attributeID][$key]['attribute_id'] = $omsProductAttribute['attribute_id'];
	    				$selectedSkuAttributes[$attributeID][$key]['pla_attribute_id'] = $attributeID;
	    				$selectedSkuAttributes[$attributeID][$key]['pla_attribute_val_id'] = $list['attribute_value_id'];
	    				$selectedSkuAttributes[$attributeID][$key]['attribute_value_id'] = $omsProductAttribute['attribute_value_id'];
	    				unset($tmpValueList[$key]);
	    				break;
    				} else {
    					$skuAttributes[$attributeID]['value_list'][$key]['selected'] = false;
    					$skuAttributes[$attributeID]['value_list'][$key]['sku'] = '';
    				}
    			}
    		}
    		$tmpSkuAttributes[$k] = $skuAttributes[$attributeID];
    		$k++;
    	}
    	
    	$selectedSKUMap = array();
    	foreach ($omsProductAttributesMap as $skumap){
    		foreach ($skumap as $skus){
    			foreach ($selectedSkuAttributes as $sattrs){
    				foreach ($sattrs as $sattr){
    					if($skus['attribute_id'] == $sattr['attribute_id'] && $skus['attribute_value_id'] == $sattr['attribute_value_id']){
    						$selectedSKUMap[$skus['sku']][$sattr['pla_attribute_id']] = $sattr['pla_attribute_val_id'];
    					}
    				}
    				
    			}
    		}
    		
    	}
    	foreach ($selectedSKUMap as $sku=>$skus){
    		$str = array();
    		ksort($skus);
    		foreach ($skus as $key=>$_sku){
    			$str[] = $key;
    			$str[] = $_sku;
    		}
    		$selectedSKUMap[$sku] = implode("-", $str);
    	}
    	$selectedSKUMap = array_flip($selectedSKUMap);
    	unset($selectedSkuAttributes, $omsProductAttributesMap);
    	
    	//print_r($selectedSKUMap);
    	//@TODO 普通属性对应OMS属性
    	//$omsProductAttributes = ProductSelectAttribute::model()->getAttributeValList($attributeID, null, $skuInfo['id']);
    	if (empty($tmpSkuAttributes) && empty($commonAttributes)) {
    		echo  $this->failureJson(array(
    			'message' => Yii::t('aliexpress_product', 'Could not Find Attributes'),
    		));
    	} else {
	    	$categoryAttributes = array(
	    			'statusCode' => '200',
	    			'sku_attributes' => $tmpSkuAttributes,
	    			'common_attributes' => $commonAttributes,
	    			'selected_sku_map' => $selectedSKUMap
	    	); 
	    	echo json_encode($categoryAttributes);
    	}
    }
    
    /**
     * @desc 查找子属性
     */
    public function actionFindsubattributes() {
    	$attributeID = Yii::app()->request->getParam('attribute_id');
    	$valueID = Yii::app()->request->getParam('value_id');
    	$subAttributes = AliexpressAttribute::model()->getSubAttributes($attributeID, $valueID);
    	echo json_encode($subAttributes);
    }
    
    
    
    public function actionCategorybyKeyword(){
    	//获取SKU英文标题
    	//循环匹配
    	set_time_limit(2*3600);
    	ini_set("display_errors", true);
    	error_reporting(E_ALL);
    	$accountIds = array(149,150,151,152,153,154,155,157,158,159,160,161,162,163,164);
    	$limit = 1000;
    	do{
    		$accountID = $accountIds[rand(0, 14)];
    		$skuList = AliexpressSkuKeywordCategory::model()->getPendingMatchCategorySKUList($limit);
    		if(empty($skuList)){
    			$isContinue = false;
    		}else{
    			$isContinue = true;
    			//获取标题
    			$skuTitleList = Productdesc::model()->getTitlesBySkuAndLanguageCode($skuList, '');
    			$newSkuTitleList = array();
    			if($skuTitleList){
    				foreach ($skuTitleList as $sku){
    					$newSkuTitleList[$sku['sku']] = $sku['title'];
    				}
    			}
    			unset($skuTitleList);
    			foreach ($skuList as $sku){
    				if(isset($newSkuTitleList[$sku])){
    					$keyword = $newSkuTitleList[$sku];
    					$keyword = str_replace( array(' to ',' for ',' in ',' & ',' + ',' of ',' and ',' or ',' For ',' Of ',' To ',' And ',' Of ',' In '), ' ', $keyword );
    					$aliexpressCategoryModel = new AliexpressCategory;
    					$aliexpressCategoryModel->setAccountID($accountID);
    					$aliexpressCategoryModel->setKeyword($keyword);
    					$cateList = $aliexpressCategoryModel->getSuggestCategoryByKeyWord();
    					if($cateList){
    						//取第一个
    						$cateInfo = array_shift($cateList);
    						AliexpressSkuKeywordCategory::model()->updateDataBySku($sku, array(
    																							'status'	=>	1, 
    																							'catename1'	=>	isset($cateInfo[1]['en_name']) ? $cateInfo[1]['en_name'] : '',
    																							'catename2'	=>	isset($cateInfo[2]['en_name']) ? $cateInfo[2]['en_name'] : '',
    																							'catename3'	=>	isset($cateInfo[3]['en_name']) ? $cateInfo[3]['en_name'] : '',
    																							'catename4'	=>	isset($cateInfo[4]['en_name']) ? $cateInfo[4]['en_name'] : '',
    																						));
    					}else{
    						AliexpressSkuKeywordCategory::model()->updateDataBySku($sku, array('status'=>-1, 'message'=>$aliexpressCategoryModel->getExceptionMessage()));
    					}
    				}else{
    					AliexpressSkuKeywordCategory::model()->updateDataBySku($sku, array('status'=>-1, 'message'=>'Not Find Title'));
    				}
    			}
    		}
    	}while ($isContinue);
    	
    }


    /**
     * @desc 获取aliexpress的的顶级类目
     */
    public function actionIndex(){   
        set_time_limit(3600);
        $categoryList = array();
        $topCategories = AliexpressCategory::model()->getCategoryByCreateLevel(1);
        if (!empty($topCategories)) {
            foreach ($topCategories as $category) {
                $categoryList[$category->category_id] = $category->en_name . '(' . $category->cn_name . ')';
            }
        }

        $this->render('index', array('categoryList'=>$categoryList));
    }


    /**
     * @desc 通过类目ID获取aliexpress的下级的类目
     */
    public function actionList(){
        $categoryId = Yii::app()->request->getParam('categoryId');
        $list = AliexpressCategory::model()->getCategoriesByParentID($categoryId);
        $level = 2;
        if($list){
            $level = $list[0]['level'];
        }

        if($level == 2){
            $this->render('list', array('categoryId'=>$categoryId, 'list'=>$list, 'level'=>$level));
        }elseif ($level == 3) {
            $this->render('list_3', array('categoryId'=>$categoryId, 'list'=>$list, 'level'=>$level));
        }elseif ($level == 4) {
            $this->render('list_4', array('categoryId'=>$categoryId, 'list'=>$list, 'level'=>$level));
        }else{
            $this->render('list_5', array('categoryId'=>$categoryId, 'list'=>$list, 'level'=>$level));
        }
    }


    /**
     * @desc 设置栏目的佣金比例
     */
    public function actionSetcommissionrate(){
        $commissionRate = '';
        $categoryId = Yii::app()->request->getParam('categoryId');
        $aliCategoryCommissionRate = new AliexpressCategoryCommissionRate();
        //查询是否已经存在
        $commissionInfo = $aliCategoryCommissionRate->getOneByCondition('commission_rate','category_id = '.$categoryId);
        if($commissionInfo){
            $commissionRate = $commissionInfo['commission_rate'];
        }

        $this->render("commissionview", array(
            'model'=>$aliCategoryCommissionRate, 
            "categoryId"=>$categoryId, 
            'commissionRate'=>$commissionRate
        ));
    }


    /**
     * @desc 保存佣金比例
     */
    public function actionSavecommissionrate(){
        try{
            
            $categoryId = Yii::app()->request->getParam('categoryId');
            $commissionRate = Yii::app()->request->getParam('AliexpressCategoryCommissionRate');
            if(!$categoryId){
                throw new Exception("没有选择类目");
            }
            
            if(!isset($commissionRate['commission_rate'])){
                throw  new Exception("没有设置佣金比例");
            }

            $commission = $commissionRate['commission_rate'];
            $commission = str_replace('%', '', $commission);
            if(!is_numeric($commission)){
                throw  new Exception("佣金比例必须是正整数");
            }

            if(!in_array($commission, array(5,8))){
                throw  new Exception("佣金比例必须是5或8");
            }

            //判断是几级分类
            $aliexpressCategoryModel = new AliexpressCategory();
            $categoryInfo = $aliexpressCategoryModel->getCategotyInfoByID($categoryId);
            if(!$categoryInfo){
                throw new Exception("没有找到相应的类目");
            }

            //取出所有一级分类
            $levelOneArr = array();
            $levelOneInfo = $aliexpressCategoryModel->getCategoryByLevel();
            foreach ($levelOneInfo as $value) {
                if($value['category_id'] == 36) continue;
                $levelOneArr[] = $value['category_id'];
            }

            //获取子分类
            $nextCategoryIdArr = $aliexpressCategoryModel->getCategoriesByParentID(36);
            if($nextCategoryIdArr){
                foreach ($nextCategoryIdArr as $nextInfo) {
                    $levelOneArr[] = $nextInfo['category_id'];
                }
            }          

            if(!in_array($categoryId, $levelOneArr)){
                throw new Exception("此类目无法设置佣金比例");
            }

            $aliCategoryCommissionRate = new AliexpressCategoryCommissionRate();

            //查询是否已经存在
            $commissionInfo = $aliCategoryCommissionRate->getOneByCondition('category_id','category_id = '.$categoryId);
            $data = array(
                'commission_rate' => $commission,
                'create_user_id'  => isset(Yii::app()->user->id)?Yii::app()->user->id:0,
                'create_time'     => date('Y-m-d H:i:s')
            );

            if($commissionInfo){
                $wheres = 'category_id = '.$categoryId;
                $aliCategoryCommissionRate->updateData($data,$wheres);
            }else{
                $data['category_id'] = $categoryId;
                $aliCategoryCommissionRate->insertData($data);
            }

            $jsonData = array(
                'message' => '更改成功',
                'forward' =>'/aliexpress/aliexpresscategory/index',
                'navTabId'=> 'page' . AliexpressCategory::getIndexNavTabId(),
                'callbackType'=>'closeCurrent'
            );
            echo $this->successJson($jsonData);
            
        }catch (Exception $e){
            echo $this->failureJson(array('message'=>$e->getMessage()));
        }
        Yii::app()->end();
    }
}