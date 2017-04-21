<?php

/**
 * @package Ueb.modules.products.controllers
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class ProductcatController extends UebController {
 
    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
      return array(
				array(
					'allow',
					'users' => array('*'),
					'actions' => array('Index')
				),
		);
    }

    public function actionIndex() {  
       $this->render('index');
    }

    /**
     * add cat list
     */
    public function actionCreate() {
        $model = new ProductCategory();
        if ( isset($_GET['catTreeItem']) ) {
            $model->category_parent_id = $_GET['catTreeItem'];
        }        
        if (Yii::app()->request->isAjaxRequest && isset($_POST['ProductCategory'])) {        
            $catParentId = $_POST['ProductCategory']['category_parent_id'];          
            $parentInfo = $model->findByPk($catParentId);           
            $model->attributes = $_POST['ProductCategory'];
            $model->setAttribute('category_level', $parentInfo['category_level'] + 1);           
            $model->setAttribute('modify_user_id', Yii::app()->user->id);
            $model->setAttribute('create_time', date('Y-m-d H:i:s'));
            if ( $model->validate()) {
                $transaction = $model->getDbConnection()->beginTransaction();
                try {
                    if ( $model->save(false) ) {                       
                        if ( isset($_POST['categoryAttribute'])) {                          
                            UebModel::model('productCategoryAttribute')->batchSave($model->id, $_POST['categoryAttribute']);                          
                        }
                    }  
                    $msg = UebModel::getLogMsg();
                    if (! empty($msg) ) {
                        Yii::ulog($msg, Yii::t('products', 'Product Category'), $_POST['ProductCategory']['category_cn_name']);
                    }                                     
                    $transaction->commit();
                    $flag = true;
                } catch (Exception $e) {
                    $transaction->rollback();
                    $flag = false;
                }
                if ($flag) {
                	//查出刚刚添加的id
                	$id = UebModel::model('productCategory')->getCreateCatsId();
                	//添加类目缓存
                	$categoryParentAndSonList = UebModel::model('productCategory')->getParentList($id);
                	Yii::app()->cache->set('categoryParentandsonlist'.$id, $categoryParentAndSonList, 60*60*720);
                	
                    $jsonData = array(
                        'message' => Yii::t('system', 'Add successful'),
                        'forward' => '/products/productcat/index',
                        'navTabId' => 'page' . UebModel::model('productCategory')->getIndexNavTabId(),
                        'callbackType' => 'closeCurrent'
                    );
                    echo $this->successJson($jsonData);
                }
            } else {
                $flag = false;
            }
            if (!$flag) {
                echo $this->failureJson(array('message' => Yii::t('system', 'Add failure')));
            }
            Yii::app()->end();
        }
        $this->render('create', array('model' => $model));
    }

    /**
     * update cat list
     * 
     * @param type $id
     */
    public function actionUpdate($id) {
        $model = $this->loadModel($id);       
        if (Yii::app()->request->isAjaxRequest && isset($_POST['ProductCategory'])) {
            $model->attributes = $_POST['ProductCategory'];
            $model->setAttribute('modify_user_id', Yii::app()->user->id);
            $transaction = $model->getDbConnection()->beginTransaction();
            if ($model->validate()) {
                try {
                    if ( $model->save(false) ) {                       
                        if ( isset($_POST['categoryAttribute'])) {                          
                            UebModel::model('productCategoryAttribute')->batchSave($model->id, $_POST['categoryAttribute']);
                        }
                    }
                    $msg = UebModel::getLogMsg();
                    if (! empty($msg) ) {
                        Yii::ulog($msg, Yii::t('products', 'Product Category'), $_POST['ProductCategory']['category_cn_name']);
                    } 
                    $transaction->commit();
                    $flag = true;
                } catch (Exception $exc) {
                    $flag = false;
                }            
                if ($flag) {
                	//删除和修改类目缓存
                	Yii::app()->cache->delete('categoryParentandsonlist'.$id);
                	$categoryParentAndSonList = UebModel::model('productCategory')->getParentList($id);
                	Yii::app()->cache->set('categoryParentandsonlist'.$id, $categoryParentAndSonList, 60*60*720);
                	
                    $jsonData = array(
                        'message' => Yii::t('system', 'Save successful'),
                        'forward' => '/products/productcat/index',
                        'navTabId' => 'page' . UebModel::model('productCategory')->getIndexNavTabId(),
                        'callbackType' => 'closeCurrent'
                    );
                }
                echo $this->successJson($jsonData);
            } else {
                $flag = false;
            }
            if (!$flag) {
            	$msg = $model->getValidateErrors();
            	$msg = empty($msg) ? '' : ' : '.$msg;
                echo $this->failureJson(array(
                    'message' => Yii::t('system', 'Save failure').$msg));
            }
            Yii::app()->end();
        }
        $categoryAttribute = null;       
        if ( $isLeaf = $model->checkLeaf($id) ) {         
            $categoryAttribute = UebModel::model('productCategoryAttribute')->getByCategoryId($id);  
        }        
        $this->render('update', array(
            'model'                 => $model,
            'categoryAttribute'     => $categoryAttribute,
            'isLeaf'                => $isLeaf
        ));
    }

    /**
     * delete cat list by cat id
     * 
     * @param type $id
     */
    public function actionDelete($id) {
        $model = $this->loadModel($id);
        if (Yii::app()->request->isAjaxRequest) {
            $transaction = $model->getDbConnection()->beginTransaction();
            try {
                $model->delete();           
                $transaction->commit();
                $flag = true;
            } catch (Exception $e) {
                $transaction->rollback();
                $flag = false;
            }
            if ($flag) {
            	//删除类目缓存
            	Yii::app()->cache->delete('categoryParentandsonlist'.$id);
            	
                $jsonData = array(
                    'message' => Yii::t('system', 'Delete successful'),
                    'navTabId' => 'page' . UebModel::model('productCategory')->getIndexNavTabId(),
                );
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
    * get category attr
    */ 
   public function actionGetattr() {
       $index = Yii::app()->request->getParam('index');
       $category_id = Yii::app()->request->getParam('category_id');
       $category_id = !empty($category_id) ? $category_id : '0';
	   $this->render('_catAttr',array(
			'index' => $index,
			'category_id' => $category_id,
	   ));
   }

   public function loadModel($id) {
        $model = UebModel::model('productCategory')->findByPk((int) $id);
        if ($model === null)
            throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
        return $model;
    }
    

    /**
     * get Is Used Status
     * @author Nick 2013-10-24
     * 
     */
    public function actionGetIsUsedStatus(){
    	$attributeId = Yii::app()->request->getParam('select_id');
    	$categoryId = Yii::app()->request->getParam('category_id');
    	$status = UebModel::model('productCategory')->getIsUsedStatus($attributeId,$categoryId);
    	echo $status;
    }
    
    /**
     * 更新产品分类
     */
    public function actionUpdatecategory(){
    	UebModel::model('productCategory')->updateProductCategory();
    }  
    
    /**
     * 翻译分类
     */
    public function actionTranslatecategory(){
    	UebModel::model('productCategory')->translateProductCategory();
    }
    
    public function actionGetcattree(){
    	if( isset($_POST['parent_id']) ){
    		$parentId = $_POST['parent_id'];
    		$returnObject = new stdClass();
    		if($parentId==0){
    			
    		}else{
	    		$parentInfo = UebModel::model('ProductCategory')->getProductCategoryById($parentId);
	    		$returnObject->parent = $parentInfo;
    		}
    		$returnObject->cat = UebModel::model('ProductCategory')->getCat($parentId);
    		$returnObject->level = $parentId==0 ? 0 : intval($parentInfo['category_level']) + 1;
    		echo json_encode($returnObject);exit;
    	}else{
    		
    	}
    } 
}