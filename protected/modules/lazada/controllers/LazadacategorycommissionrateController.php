<?php
/**
 * @desc Lazada分类设置佣金比例
 * @author hanxy
 * @since 2017-01-02
 */
class LazadacategorycommissionrateController extends UebController{
    /**
     * AliexpressProduct 模型
     * @var unknown
     */
    protected $_model = null; 
    
    /**
     * (non-PHPdoc)
     * @see CController::init()
     */
    public function  init(){
        $this->_model = new LazadaCategoryCommissionRate();
        parent::init();
    }
        
    /**
     * @desc 获取分类佣金比例
     */
    public function actionlist(){
        $this->render('list', array(
            'model' => $this->_model,
        ));
    }

    /**
     * @desc 批量删除佣金比例
     */
    public function actionDelete(){
        if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
            $ids = $_REQUEST['ids'];
            try {
                $flag = $this->_model->getDbConnection()->createCommand()->delete($this->_model->tableName(), " id IN (" . ($ids) .")");
                if (!$flag ) {
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
     * @desc 添加佣金比例
     */
    public function actionAdd(){
        $categoryLevenOne = LazadaCategory::model()->getCategoryByCondit();
        $siteList         = LazadaSite::model()->getSiteList();
        if($_POST){
            $commissionArr = Yii::app()->request->getParam('LazadaCategoryCommissionRate');
            $categoryId = $commissionArr['category_id'];
            $siteId = $commissionArr['site_id'];
            $commissionRate = Yii::app()->request->getParam('commission_rate');
            $categoryKeys = array_keys($categoryLevenOne);
            if(!in_array($categoryId, $categoryKeys)){
                echo $this->failureJson(array( 'message' => '类目不正确'));
                exit;
            }
            $siteKeys = array_keys($siteList);
            if(!in_array($siteId, $siteKeys)){
                echo $this->failureJson(array( 'message' => '站点不正确'));
                exit;
            }
            if(!is_numeric($commissionRate) || $commissionRate < 0 || $commissionRate >= 100){
                echo $this->failureJson(array( 'message' => '请输入0-100的数值,不要输入%'));
                exit;
            }

            $flag = false;
            $isExits = $this->_model->getOneByCondition('id','category_id = '.$categoryId.' AND site_id = '.$siteId);
            if($isExits){
                $flag = $this->_model->getDbConnection()->createCommand()->update($this->_model->tableName(), array('commission_rate'=>$commissionRate), 'id = '.$isExits['id']);
            }else{
                $flag = $this->_model->getDbConnection()->createCommand()->insert(
                    $this->_model->tableName(), 
                    array(
                        'commission_rate'=> $commissionRate,
                        'category_id'    => $categoryId,
                        'site_id'        => $siteId,
                        'commission_rate'=> $commissionRate,
                        'create_user_id' => isset(Yii::app()->user->id)?Yii::app()->user->id:0,
                        'create_time'    => date('Y-m-d H:i:s')
                    )
                );
            }

            if($flag){
                $jsonData = array(
                        'message' => '添加成功',
                        'forward' => '/lazada/lazadacategorycommissionrate/list',
                        'navTabId' => 'page'.Menu::model()->getIdByUrl('/lazada/lazadacategorycommissionrate/list'),
                        'callbackType' => 'closeCurrent'
                );
                echo $this->successJson($jsonData);
            }else{
                echo $this->failureJson(array( 'message' => '添加失败'));
            }
            Yii::app()->end();
        }
        $this->render("add", array('model'=>$this->_model, 'categoryLevenOne'=>$categoryLevenOne, 'siteList'=>$siteList));
        Yii::app()->end();
    }
}