<?php
/**
 * @desc 销售人员与账号关系
 * @author hanxy
 *
 */
class JoomaccountsellerController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new JoomAccountSeller();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$aliAccountModel = new JoomAccount();
		$accountList     = $aliAccountModel->getIdNamePairs();
		$sellerList      = User::model()->getUserNameByDeptID(array(37));
		$this->render("index", array("model"=>$this->_model, 'accountList'=>$accountList, 'sellerList'=>$sellerList));
	}


	/**
	 * 设置账号
	 */
	public function actionAdd(){
		if($_POST){
			$sellerList     = Yii::app()->request->getParam('JoomAccountSeller');
			$seller_user_id = isset($sellerList['seller_user_id'])?$sellerList['seller_user_id']:'';
			$account_id     = isset($sellerList['account_id'])?$sellerList['account_id']:'';

			if(!$seller_user_id){
				echo $this->failureJson(array('message'=>'没有选择销售人员'));
				exit;
			}
				
			//删除已经存在的账号
			$this->_model->getDbConnection()->createCommand()->delete($this->_model->tableName(), "seller_user_id = ".$seller_user_id);
			
			//添加新的账号
			if($account_id){
				foreach ($account_id as $valueId) {
					$paramArr = array(
						'account_id'     => $valueId,
						'seller_user_id' => $seller_user_id,
						'create_time'    => date('Y-m-d H:i:s')
					);
					$this->_model->getDbConnection()->createCommand()->insert($this->_model->tableName(), $paramArr);
				}
			}

			$jsonData = array(
				'message' => '保存成功',
				'forward' =>'/joom/joomaccountseller/list',
				'navTabId'=> 'page' . JoomAccountSeller::getIndexNavTabId(),
				'callbackType'=>'closeCurrent'
			);
			echo $this->successJson($jsonData);
			
		}else{
			$aliAccountModel = new JoomAccount();
			$accountList     = $aliAccountModel->getIdNamePairs();
			$sellerList      = User::model()->getUserNameByDeptID(array(37));
			$this->render("add", array("model"=>$this->_model, 'accountList'=>$accountList, 'sellerList'=>$sellerList));
		}
	}


	/**
	 * 显示ajax数据
	 */
	public function actionAjaxdata(){
		$sellerID = Yii::app()->request->getParam('seller_id');
		$data = '';
		$dataList = $this->_model->getListByCondition('account_id','seller_user_id = '.$sellerID);
		$joomAccountModel = new JoomAccount();
		$accountList     = $joomAccountModel->getIdNamePairs();
		foreach ($accountList as $key => $value) {
			$select = '';
			if(in_array($key, $dataList)){
				$select = 'checked="checked"';
			}
			$data .= '<span><input name="JoomAccountSeller[account_id][]" type="checkbox" value="'.$key.'" '.$select.' />'.$value.'</span>';
		}
		$this->render("ajaxdata", array("data"=>$data));
	}
}