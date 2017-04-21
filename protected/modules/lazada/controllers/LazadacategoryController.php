<?php
/**
 * @desc Lazada分类相关
 * @author Gordon
 * @since 2015-08-13
 */
class LazadacategoryController extends UebController{
    
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
     * @desc 获取分类
     * @author Gordon
     * @link /lazada/lazadacategory/getcategories
     */
    public function actionGetcategories(){
        $account = LazadaAccount::model()->getOneByCondition("id,account_id,site_id","site_id=1 and status = 1 and is_lock = 0 ");//马来站是主站,取一个可用账号
        $accountID    = $account['id'];
        $apiAccountID = $account['account_id'];
        $siteID       = $account['site_id'];
        $logID = LazadaLog::model()->prepareLog($accountID,LazadaCategory::EVENT_NAME);
        if( $logID ){
            $checkRunning = LazadaLog::model()->checkRunning($accountID, LazadaCategory::EVENT_NAME);
            if( !$checkRunning ){
                LazadaLog::model()->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
            }else{
                LazadaLog::model()->setRunning($logID);
                $lazadaCategoryModel = new LazadaCategory();
                $lazadaCategoryModel->setSiteID($siteID);
                $lazadaCategoryModel->setAccountID($apiAccountID);
                $flag = $lazadaCategoryModel->updateCategories();//拉取分类
                //更新日志信息
                if( $flag ){
                    LazadaLog::model()->setSuccess($logID);
                }else{
                    echo $lazadaCategoryModel->getExceptionMessage();
                    LazadaLog::model()->setFailure($logID, 'error');
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
     * @desc 批量刊登获取分类树
     */
    public function actionCategorytreeBatch(){
        $parentId = Yii::app()->request->getParam('category_id');
        $parentId = $parentId ? $parentId : 0;
        $categories = LazadaCategory::model()->getCategoriesByParentID($parentId);
        if(!$parentId){
            $this->render('CategoryTreeBatch',array(
                'categories'    => $categories,
            ));
        }else{
            echo json_encode($categories);
            Yii::app()->end();
        }
    }
    
    /**
     * @desc 批量刊登获取分类树
     */
    public function actionGetBreadcrumbCategory(){
        $category_id = Yii::app()->request->getParam('category_id');
        $BreadcrumbCategory = LazadaCategory::model()->getBreadcrumbCategory($category_id);
        echo json_encode($BreadcrumbCategory);
        Yii::app()->end();
    }
    
    /**
     * @desc 更新分类属性
     */
    public function actionGetcategoryattributes() {
    	set_time_limit(3600);
    	$accountInfo = LazadaAccount::getAbleAccountByOne();
    	$accountID = $accountInfo['account_id'];
    	$siteID = $accountInfo['site_id'];
    	$categoryID = Yii::app()->request->getParam('category_id');
    	$categoryIds = array();
    	//如果指定了要拉取属性的分类ID，则值拉该分类的属性，否则拉取所有LEVEL为1的分类的属性
    	if ($categoryID) {
    		$categoryIds[] = $categoryID;
    	} else {
    		$lazadaCategoryModels = LazadaCategory::model()->getCategoryByLevel(1);
    		if (!empty($lazadaCategoryModels)) {
    			foreach ($lazadaCategoryModels as $lazadaCategoryModel) {
    				$categoryIds[] = $lazadaCategoryModel->category_id;
    			}
    		}
    	}
    	
    	if (!empty($categoryIds)) {
    		foreach ($categoryIds as $categoryID) {
    			$LazadaLogModel = new LazadaLog();
	    		$logID = $LazadaLogModel->prepareLog($accountID, LazadaCategoryAttributes::EVENT_NAME);
	    		if ($logID) {
	    			$checkRunning = $LazadaLogModel->checkRunning($accountID, LazadaCategoryAttributes::EVENT_NAME);
	    			if( !$checkRunning ){
	    				$LazadaLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
	    			}else{
	    				$LazadaCategoryAttributes = new LazadaCategoryAttributes();
	    				$LazadaCategoryAttributes->setAccountID($accountID);
	    				$LazadaCategoryAttributes->setSiteID($siteID);
	    				$LazadaCategoryAttributes->setCategoryID($categoryID);
	    				$flag = $LazadaCategoryAttributes->getCategoryAttributes();
	    			    if( $flag ){
		                    $LazadaLogModel->setSuccess($logID);
		                }else{
		                    $LazadaLogModel->setFailure($logID, $LazadaCategoryAttributes->getExceptionMessage());
		                }
	    			}  			
	    		}
    		}
    	}
    	exit('DONE');
    }


    /**
     * @desc 新接口获取分类
     * @since 2016-12-16
     * @author hanxy
     */
    public function actionGetcategoriesnew(){
        set_time_limit(3600);
        //取一个可用账号
        $account   = LazadaAccount::getAbleAccountByOne();
        $accountID = $account['id'];
        $siteID    = $account['site_id'];
        $logID = LazadaLog::model()->prepareLog($accountID,LazadaCategory::EVENT_NAME,$siteID);
        if( $logID ){
            $checkRunning = LazadaLog::model()->checkRunning($accountID, LazadaCategory::EVENT_NAME,$siteID);
            if( !$checkRunning ){
                LazadaLog::model()->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
            }else{
                LazadaLog::model()->setRunning($logID);
                $lazadaCategoryModel = new LazadaCategory();
                $lazadaCategoryModel->setApiAccount($accountID);
                $flag = $lazadaCategoryModel->updateCategoriesNew();//拉取分类
                //更新日志信息
                if( $flag ){
                    LazadaLog::model()->setSuccess($logID);
                }else{
                    LazadaLog::model()->setFailure($logID, $lazadaCategoryModel->getExceptionMessage());
                }
            }
        }
        exit('DONE');
    }


    /**
     * @desc 新接口更新分类属性
     * @since 2016-12-16
     * @author hanxy
     */
    public function actionGetcategoryattributesnew() {
        set_time_limit(2*3600);
        $accountInfo = LazadaAccount::getAbleAccountByOne();
        $accountID = $accountInfo['id'];
        $siteID = $accountInfo['site_id'];
        $categoryID = Yii::app()->request->getParam('category_id');
        $categoryIds = array();
        //如果指定了要拉取属性的分类ID，则值拉该分类的属性，否则拉取所有LEVEL为1的分类的属性
        if ($categoryID) {
            $categoryIds[] = $categoryID;
        } else {
            $lazadaCategoryModels = LazadaCategory::model()->getCategoryByLevel(1);
            if (!empty($lazadaCategoryModels)) {
                foreach ($lazadaCategoryModels as $lazadaCategoryModel) {
                    $categoryIds[] = $lazadaCategoryModel->category_id;
                }
            }
        }
        
        if (!empty($categoryIds)) {
            foreach ($categoryIds as $categoryID) {
                $LazadaLogModel = new LazadaLog();
                $logID = $LazadaLogModel->prepareLog($accountID, LazadaCategoryAttributes::EVENT_NAME, $siteID);
                if ($logID) {
                    $checkRunning = $LazadaLogModel->checkRunning($accountID, LazadaCategoryAttributes::EVENT_NAME, $siteID);
                    if( !$checkRunning ){
                        $LazadaLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                    }else{
                        $LazadaCategoryAttributes = new LazadaCategoryAttributes();
                        $LazadaCategoryAttributes->setApiAccount($accountID);
                        $LazadaCategoryAttributes->setCategoryID($categoryID);
                        $flag = $LazadaCategoryAttributes->getCategoryAttributesNew();
                        if( $flag ){
                            $LazadaLogModel->setSuccess($logID);
                        }else{
                            $LazadaLogModel->setFailure($logID, $LazadaCategoryAttributes->getExceptionMessage());
                        }
                    }           
                }
            }
        }
        exit('DONE');
    }
}