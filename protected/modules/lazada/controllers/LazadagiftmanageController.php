<?php
/**
 * lazada赠品管理
 */
class LazadagiftmanageController extends UebController {
	
    protected $_model = null; 
    
    public function  init(){
        $this->_model = new LazadaGiftManage();
        parent::init();
    }

	public function actionList(){
		$this->render('list',array('model'=>$this->_model));
	}
	
    /**
     * @desc 停用或启用
     */
    public function actionOpenorshutdown(){
        if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'] ) && isset($_REQUEST['status']) ) {
            try {
                $flag = $this->_model->openOrShutDown($_REQUEST['ids'], $_REQUEST['status']);
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
     * @desc 添加
     */
    public function actionAdd(){
        if(Yii::app()->request->isPostRequest
             && 'add' == Yii::app()->request->getParam('act') ){
        	$formData = Yii::app()->request->getParam('LazadaGiftManage');
            $accountId = isset($formData['account_id']) ? $formData['account_id'] : '';
            $sku = Yii::app()->request->getParam('sku');
            $giftSku = Yii::app()->request->getParam('gift_sku');
            if(empty($accountId)){
                echo $this->failureJson(array( 'message' => '请选择账号'));
                exit;
            }
            if(empty($sku)){
                echo $this->failureJson(array( 'message' => '在线sku不能为空'));
                exit;
            }

            $sysSku = encryptSku::getRealSku ( $sku );
            if($sysSku) {
                $productInfo = Product::model()->getBySku($sysSku);
            }
            if(empty($sysSku) || empty($productInfo)) {
                echo $this->failureJson(array( 'message' => '在线sku【'.$sku.'】有误!'));
                exit;
            }

            if(empty($giftSku)){
                echo $this->failureJson(array( 'message' => '赠品sku不能为空'));
                exit;
            }
            $productInfo = Product::model()->getBySku($giftSku);
            if(empty($productInfo)) {
                echo $this->failureJson(array( 'message' => '赠品sku【'.$giftSku.'】系统中不存在'));
                exit;
            }
            $data = array(
				'account_id'     => $accountId,
				'sku'            => $sku,
                'sys_sku'        => $sysSku,
				'gift_sku'       => $giftSku,
				'create_user_id' => isset(Yii::app()->user->id)?Yii::app()->user->id:0,
				'create_time'    => date('Y-m-d H:i:s'),
            );
            $isExits = $this->_model->getOneByCondition('id',"account_id='{$accountId}' and sku='{$sku}' and gift_sku='{$giftSku}' ");
            if(!empty($isExits)){
				echo $this->failureJson(array( 'message' => '赠品配置已存在,无需添加!'));
                exit;
            }
            if($this->_model->insertData($data)){
                $jsonData = array(
					'message'      => '添加成功',
					'forward'      => '/lazada/lazadagiftmanage/list',
					'navTabId'     => 'page'.Menu::model()->getIdByUrl('/lazada/lazadagiftmanage/list'),
					'callbackType' => 'closeCurrent'
                );
                echo $this->successJson($jsonData);
            }else{
                echo $this->failureJson(array( 'message' => '添加失败'));
            }
            Yii::app()->end();
        }
        $accountList = array(0=>'请选择')+LazadaGiftManage::getAccountList();
        $this->render("add", array('model'=>$this->_model, 'accountList'=>$accountList, 'act'=>'add'));
        Yii::app()->end();
    }

    /**
     * @desc 编辑
     */
    public function actionEdit() {
    	$id = Yii::app()->request->getParam('id','');
        if($id == '') {
            echo $this->failureJson(array( 'message' => '非法访问'));
            exit;
        }
        $info = $this->_model->getOneByCondition("*","id='{$id}'");        
        if(Yii::app()->request->isPostRequest
             && 'edit' == Yii::app()->request->getParam('act') ){
        	$formData = Yii::app()->request->getParam('LazadaGiftManage');
            $accountId = isset($formData['account_id']) ? $formData['account_id'] : '';
            $sku = Yii::app()->request->getParam('sku');
            $giftSku = Yii::app()->request->getParam('gift_sku');
            if(empty($accountId)){
                echo $this->failureJson(array( 'message' => '请选择账号'));
                exit;
            }
            if(empty($sku)){
                echo $this->failureJson(array( 'message' => '在线sku不能为空'));
                exit;
            }

            $sysSku = encryptSku::getRealSku ( $sku );
            if($sysSku) {
                $productInfo = Product::model()->getBySku($sysSku);
            }
            if(empty($sysSku) || empty($productInfo)) {
                echo $this->failureJson(array( 'message' => '在线sku【'.$sku.'】有误!'));
                exit;
            }

            if(empty($giftSku)){
                echo $this->failureJson(array( 'message' => '赠品sku不能为空'));
                exit;
            }
            $productInfo = Product::model()->getBySku($giftSku);
            if(empty($productInfo)) {
                echo $this->failureJson(array( 'message' => '赠品sku【'.$giftSku.'】系统中不存在'));
                exit;
            }
            $data = array(
				'account_id'     => $accountId,
				'sku'            => $sku,
                'sys_sku'        => $sysSku,
				'gift_sku'       => $giftSku,
				'update_user_id' => isset(Yii::app()->user->id)?Yii::app()->user->id:0,
				'update_time'    => date('Y-m-d H:i:s'),
            );
            if($info['account_id'] == $data['account_id'] && $info['sku'] == $data['sku'] && $info['gift_sku'] == $data['gift_sku'] ){
                echo $this->failureJson(array( 'message' => '赠品配置未变化,无需修改!'));
                exit;
            }
            $isExits = $this->_model->getOneByCondition('id',"account_id='{$accountId}' and sku='{$sku}' and gift_sku='{$giftSku}' ");
            if(!empty($isExits)){
                echo $this->failureJson(array( 'message' => '赠品配置已存在,无需修改!'));
                exit;
            }
            if($this->_model->updateData($data,"id={$info['id']}")){
                $jsonData = array(
					'message'      => '修改成功',
					'forward'      => '/lazada/lazadagiftmanage/list',
					'navTabId'     => 'page'.Menu::model()->getIdByUrl('/lazada/lazadagiftmanage/list'),
					'callbackType' => 'closeCurrent'
                );
                echo $this->successJson($jsonData);
            }else{
                echo $this->failureJson(array( 'message' => '修改失败'));
            }
            Yii::app()->end();
        }
        $accountList = array(0=>'请选择')+LazadaGiftManage::getAccountList();
        $this->render("add", array('model'=>$this->_model, 'info'=>$info, 'accountList'=>$accountList, 'act'=>'edit'));
        Yii::app()->end();
    }   
	
}