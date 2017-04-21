<?php
/**
 * Conditions rules manage
 * @author	wx
 * @since	2015-07-29
 */

class ConditionsrulesController extends UebController {
	
	public $modelClass = 'ConditionsRules';
	
	/**
	 * list page
	 */
	public function actionList() {
		$model = new $this->modelClass;
		$this->render('list', array('model'=>$model));
	}
	
	/**
	 * Create pape
	 */
	public function actionCreate() {
		$model = new $this->modelClass;
		$model2 = new ConditionsField();
		$model3 = new ConditionsDetail();
		if (Yii::app()->request->isAjaxRequest && isset($_POST[$this->modelClass])) {
			//var_dump($_POST);exit;
			$transaction = $model->getDbConnection()->beginTransaction();
			$flag = false;
			try {
				$ruleSort = intval($_POST[$this->modelClass]['priority']);
				
				$data = array(
						'rule_name'		=> trim($_POST[$this->modelClass]['rule_name']),
						'rule_class'	=> trim($_POST[$this->modelClass]['rule_class']),
						'platform_code' => $_POST[$this->modelClass]['platform_code'],
						'is_enable'		=> $_POST[$this->modelClass]['is_enable'],
						'template_id'	=> trim($_POST[$this->modelClass]['template_id']),
						'priority'		=> TemplateRulesBase::getNewSort($ruleSort),
				);
				
				if (empty($data['rule_name']) || $data['rule_class'] < 1 || $ruleSort < 0) {
					echo $this->failureJson(array( 'message' => Yii::t('conditionsrules', 'Required exist not filled') ));
					Yii::app()->end();
				}
				
				$ruleInfo = $model->getRuleInfoByRuleName($data['rule_name']);
				if ($ruleInfo) {
					echo $this->failureJson(array( 'message' => Yii::t('orderrules', 'Not add, rule name is exist') ));
					Yii::app()->end();
				}
				
				$ruleId = $model->saveOrderRule($data);
				
				if ($ruleId) {
					//写日志
					$updateLogData = array(
							'type'	=> ConditionsUpdateLog::TYPE_RULE,
							'update_id'	=> $ruleId,
							'update_name' => $data['rule_name'],
							'update_content' => UebModel::model('ConditionsUpdateLog')->getUpdateMsg($data, 'N'),
					);
					$ret = UebModel::model('ConditionsUpdateLog')->saveNewData($updateLogData);
					
					foreach ($_POST['sel_field_id'] as $key => $val) {
						$fieldInfo = $model2->getByPk($val);
						$arrCond = json_decode($_POST['sel_field_content'][$key],true);
						
						foreach ($arrCond as $val2) {
							$dataDetail = array(
									'rule_id'		=> $ruleId,
									'field_id'		=> $fieldInfo['id'],
									'field_name'	=> $fieldInfo['field_name'],
									'cal_type'		=> $val2['cal'],
									'cal_value'		=> $val2['cont'],
									'cal_unit_code'	=> $fieldInfo['unit_code'],
									'cal_unit_value'=> $val2['unit_val'],
							);
							
							$checkRet = $model3->checkCalValueIsAvail($fieldInfo['validate_type'], $dataDetail['cal_value']);
							if (!$checkRet['flag']) {
								echo $this->failureJson(array( 'message' => $checkRet['msg']));
								Yii::app()->end();
							}
							
							$detailId = UebModel::model('ConditionsDetail')->saveOrderRuleDetail($dataDetail);
							
							//写明细日志
							$updateLogData = array(
									'type'	=> ConditionsUpdateLog::TYPE_RULE_DETAIL,
									'update_id'	=> $detailId,
									'update_name' => $dataDetail['field_name'],
									'update_content' => UebModel::model('ConditionsUpdateLog')->getUpdateMsg($dataDetail, 'N'),
							);
							$ret = UebModel::model('ConditionsUpdateLog')->saveNewData($updateLogData);
						}
						
						//添加字段扩展信息 start
						$fieldArr = explode('.', $fieldInfo['field_name']);
						$fieldExtendList = TemplateRulesBase::$modelList[$fieldArr[0]]['field_extends'][$fieldArr[1]];
						if( isset($fieldExtendList) && count($fieldExtendList)>0 ){
							foreach( $fieldExtendList as $k => $v ){
								$currExtend = $_POST[$k][$fieldInfo['id']];
								$extendData = array(
										'field_id' => $fieldInfo['id'],
										'rule_id'  => $ruleId,
										'extend_name' => $k,
										'extend_value' => $currExtend
								);
								UebModel::model('ConditionsFieldExtend')->saveNewData($extendData);
							}
						}
						//添加字段扩展信息 end
						
					}
					
				}
				$transaction->commit();
				$flag = true;
			} catch (Exception $e) {
				$transaction->rollback();
				$flag = false;
			}
			if ( $flag ) {
				$jsonData = array(
						'message' => Yii::t('system', 'Add successful'),
						'forward' => '/common/conditionsrules/list',
						'navTabId' => 'page'.Menu::model()->getIdByUrl('/common/conditionsrules/list'),
						'callbackType' => 'closeCurrent'
				);
				echo $this->successJson($jsonData);
				Yii::app()->end();
			}
			echo $this->failureJson(array( 'message' => Yii::t('system', 'Add failure')));
			Yii::app()->end();
		}
		
		$this->render('create', array('model'=>$model));
	}
	
	/**
	 * update
	 */
	public function actionUpdate($id) {
		$id = intval($id);
		error_reporting(E_ALL);
		ini_set("display_errors", true);
		if ($id < 0) {	return false; }
		$model = $this->loadModel($id);
		$model2 = new ConditionsField();
		$model3 = new ConditionsDetail();
		$model4 = new ConditionsFieldExtend();
	
		if (Yii::app()->request->isAjaxRequest && isset($_POST[$this->modelClass])) {
						
			$transaction = $model->getDbConnection()->beginTransaction();
			$flag = false;
			$msg = "";
			try {
				$ruleSort = intval($_POST[$this->modelClass]['priority']);
				$data = array(
						'rule_name'		=> trim($_POST[$this->modelClass]['rule_name']),
						'rule_class'	=> trim($_POST[$this->modelClass]['rule_class']),
						'platform_code' => $_POST[$this->modelClass]['platform_code'],
						'is_enable'		=> $_POST[$this->modelClass]['is_enable'],
						'template_id'	=> trim($_POST[$this->modelClass]['template_id']),
						'priority'		=> TemplateRulesBase::getNewSort($ruleSort),
						'modify_time'	=> date('Y-m-d H:i:s'),
						'modify_user_id'=> Yii::app()->user->id,
				);
				
				$data['platform_code'] == '' && $data['platform_code'] = '%';
				
				if (empty($data['rule_name']) || $data['rule_class'] < 1 || $ruleSort < 0) {
					echo $this->failureJson(array( 'message' => Yii::t('orderrules', 'Required exist not filled') ));
					Yii::app()->end();
				}
				
				if ($data['rule_name'] != $model->rule_name) {
					$ruleInfo = $model->getRuleInfoByRuleName($data['rule_name']);
					if ($ruleInfo) {
						echo $this->failureJson(array( 'message' => Yii::t('orderrules', 'Not add, rule name is exist') ));
						Yii::app()->end();
					}
				}
				
				//写主日志
				$updateInfo = array_diff_assoc($_POST[$this->modelClass],$model->attributes);
				$updateName = $model->rule_name;
				$updateLogData = array(
						'type'	=> ConditionsUpdateLog::TYPE_RULE,
						'update_id'	=> $id,
						'update_name' => $updateName,
						'update_content' => UebModel::model('ConditionsUpdateLog')->getUpdateMsg($updateInfo),
				);
				$ret = UebModel::model('ConditionsUpdateLog')->saveNewData($updateLogData);
			
				$ret = $model->updateByPk($id, $data);
				if ($ret) {
					
					//写明细日志
					$detailListOk = $model3->getRuleDetailListByRuleId($id);
					foreach ($detailListOk as $value) {
						$updateLogData = array(
								'type'	=> ConditionsUpdateLog::TYPE_RULE_DETAIL,
								'update_id'	=> $value['id'],
								'update_name' => $updateName.', '.$value['field_name'],
								'update_content' => '更新前：'.json_encode($value),
						);
						$ret = UebModel::model('ConditionsUpdateLog')->saveNewData($updateLogData);
					}
					
					$model3->updateAll(array('is_del'=>ConditionsDetail::DEL_OK,'del_user_id'=>1), "rule_id=$id");
					
					foreach ($_POST['sel_field_id'] as $key => $val) {
						$fieldInfo = $model2->getByPk($val);
						$arrCond = json_decode($_POST['sel_field_content'][$key],true);
						$detailList = $model3->getRuleDetailListByRuleAndField($id, $val);
						
						$i = 0;
						$arrCond2 = $arrCond;
						foreach ($detailList as $key => $val3) {
							if ($val3['field_id'] == $val) {
								if ($i < count($arrCond)) {
									$dataDetail = array(
											'field_id'		=> $fieldInfo['id'],
											'field_name'	=> $fieldInfo['field_name'],
											'cal_type'		=> $arrCond[$i]['cal'],
											'cal_value'		=> $arrCond[$i]['cont'],
											'cal_unit_code'	=> $fieldInfo['unit_code'],
											'cal_unit_value'=> $arrCond[$i]['unit_val'],
											'is_del'		=> ConditionsDetail::DEL_NO,
											'modify_time'	=> date('Y-m-d H:i:s'),
											'modify_user_id'=> Yii::app()->user->id,
								
									);
									
									$checkRet = $model3->checkCalValueIsAvail($fieldInfo['validate_type'], $dataDetail['cal_value']);
									if (!$checkRet['flag']) {
										echo $this->failureJson(array( 'message' => $checkRet['msg']));
										Yii::app()->end();
									}
									
									$model3->updateByPk($val3['id'], $dataDetail);
									unset($arrCond2[$i]);
								}else {
									$model3->updateByPk($val3['id'], array('is_del'=>ConditionsDetail::DEL_OK,'del_user_id'=>intval(Yii::app()->user->id),'del_time'=>date('Y-m-d H:i:s')));
								};
								$i++;
							}
						} 

						//新增条件
						foreach ($arrCond2 as $val2) {
							$dataDetail = array(
									'rule_id'		=> $id,
									'field_id'		=> $fieldInfo['id'],
									'field_name'	=> $fieldInfo['field_name'],
									'cal_type'		=> $val2['cal'],
									'cal_value'		=> $val2['cont'],
							);
							
							$checkRet = $model3->checkCalValueIsAvail($fieldInfo['validate_type'], $dataDetail['cal_value']);
							if (!$checkRet['flag']) {
								echo $this->failureJson(array( 'message' => $checkRet['msg']));
								Yii::app()->end();
							}
							
							$ret = $model3->saveOrderRuleDetail($dataDetail);
						}
						
						//更新字段扩展信息 start
						$model4->deleteAll( 'rule_id=:rule_id and field_id=:field_id',array(':rule_id'=>$id,':field_id'=>$val) );
						$fieldArr = explode('.', $fieldInfo['field_name']);
						$fieldExtendList = TemplateRulesBase::$modelList[$fieldArr[0]]['field_extends'][$fieldArr[1]];
						if( isset($fieldExtendList) && count($fieldExtendList)>0 ){
							foreach( $fieldExtendList as $k => $v ){
								$currExtend = $_POST[$k][$fieldInfo['id']];
								$extendData = array(
										'field_id' => $fieldInfo['id'],
										'rule_id'  => $id,
										'extend_name' => $k,
										'extend_value' => $currExtend
								);
								if($currExtend) UebModel::model('ConditionsFieldExtend')->saveNewData($extendData);
							}
						}
						//添加字段扩展信息 end
						
					}
						
				}
				$transaction->commit();
				$flag = true;
			} catch (Exception $e) {
				$transaction->rollback();
				$flag = false;
				$msg = $e->getMessage();
			}
			if ( $flag ) {
				$jsonData = array(
						'message' => Yii::t('system', 'Update successful'),
						'forward' => '/common/conditionsrules/list',
						'navTabId' => 'page'.Menu::model()->getIdByUrl('/common/conditionsrules/list'),
						'callbackType' => 'closeCurrent'
				);
				echo $this->successJson($jsonData);
				Yii::app()->end();
			}
			echo $this->failureJson(array( 'message' => $msg));
			Yii::app()->end();
			
		}
		$model->template_name = $this->getTemplateNameByPlatform($model->platform_code, $model->rule_class, $model->template_id);
		$this->render('update', array('model'=>$model));
	}
	
	/**
	 * 开启，停用
	 */
	public function actionBatchchangestatus() {
		$model = new $this->modelClass;
		$flag = Yii::app ()->request->getParam ( 'type' ) == '0' ? false : true;
		if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
			$transaction = $model->getDbConnection()->beginTransaction();
			try {
				$flag = $model->swithUsingRule($_REQUEST['ids'], $flag);
				if (! $flag) {
					throw new Exception ( 'Oprate failure' );
				}
				$transaction->commit();
			} catch (Exception $e) {
				$transaction->rollback();
				$flag = false;
			}
			if ( $flag ) {
				$jsonData = array(
						'message' => Yii::t('system', 'Update successful'),
				);
				echo $this->successJson($jsonData);
				Yii::app()->end();
			}
			echo $this->failureJson(array( 'message' => Yii::t('system', 'Update failure')));
			Yii::app()->end();
			
		}
	}
	
	/**
	 * update sort
	 */
	public function actionUpdatesort() {
		if (isset($_REQUEST['id']))
			$model = $this->loadModel($_REQUEST['id']);
		else return;
		
		$sort = intval($_REQUEST['sort']);
		$sort = TemplateRulesBase::getNewSort($sort);
		$model->priority = $sort;
		try {
			$model->setIsNewRecord(false);
			$flag = $model->save();
		} catch (Exception $e) {
			$flag = false;
		}
		if ( $flag ) {
			$jsonData = array(
					'message' => Yii::t('system', 'Oprate successful'),
					'forward' => '/common/conditionsrules/list',
					'navTabId' => 'page'.Menu::model()->getIdByUrl('/common/conditionsrules/list'),
					'callbackType' => 'closeCurrent'
			);
			echo $this->successJson($jsonData);
		}
		if (! $flag) {
			echo $this->failureJson(array( 'message' => Yii::t('system', 'Oprate failure')));
		}
		Yii::app()->end();
	}
	
	public function showSort($data, $row, $c) {
		echo CHtml::textField('priority', $data->priority, array('onblur'=>"updateSort(".$data->id.",this.value);",'size'=>5));
	}
	
	/**
	 * Show Explain
	 */
	public function actionShowexplain($type) {
		$model = new $this->modelClass;
		$this->render('_show_explain', array('model'=>$model,'type'=>$type));
	}
	
	/**
	 * ajax 得到可选条件
	 */
	public function actionGetcanselectres() {
		
		$ruleClass = isset($_GET['c'])?$_GET['c']:'';
		$ruleId = isset($_GET['id'])?$_GET['id']:'';
		$platformCode = isset($_GET['p'])?$_GET['p']:'';
		$model = new $this->modelClass;
		$model2 = new ConditionsField();
		$ruleClass == '%' && $ruleClass = 0;
		
		$conditionsFieldList = $model2->getRuleFieldListByClass($ruleClass,$platformCode);
		//处理有 ruleId情况
		if ($ruleId) {
			$ruleDetailList = UebModel::model('ConditionsDetail')->getRuleDetailListByRuleId($ruleId);
			$arrDetailField = array();
			foreach ($ruleDetailList as $key => $value) {
				if (!key_exists($value['field_id'], $arrDetailField) ) {
					$arrDetailField[$value['field_id']] = array('field_name'=>array($value['field_name']),'detail_id'=>array($value['id']));
				}else {
					$arrDetailField[$value['field_id']]['detail_id'][] = $value['id'];
					$arrDetailField[$value['field_id']]['field_name'][] = $value['field_name'];
				}
			}
			foreach ($conditionsFieldList as $key => $value) {
				if (key_exists($value['id'], $arrDetailField)) {
					$conditionsFieldList[$key]['is_checked'] = 1;
					$conditionsFieldList[$key]['detail_id'] = $arrDetailField[$value['id']]['detail_id'];
				}
			}
		}
		$this->render('_select_res',array('model'=>$model,'conditionsFieldList'=>$conditionsFieldList,'ruleClass'=>$ruleClass));
	}
	
	/**
	 * ajax 得到已选条件
	 */
	public function actionGetselectcontent() {
		
		$fid = isset($_GET['fid'])?$_GET['fid']:'';
		$fsta = isset($_GET['fsta'])?$_GET['fsta']:'';
		$did = isset($_GET['d_id'])?$_GET['d_id']:'';
		
		$model = new $this->modelClass;
		$model2 = new ConditionsField();
		$fieldInfo = $model2->getByPk($fid);
		$fieldInfo['field_content'] = '';
		$fieldExtendArr = '';
		$fieldExtendStr = '';
		if ($did) {
			$data2 = array();
			$arrDetailId = json_decode($did);
			
			$detailList = UebModel::model('ConditionsDetail')->getRuleDetailListByIds($arrDetailId);
			//var_dump($detailList);exit;
			$ruleId = 0;
			foreach ($detailList as $value) {				
				$data2[] = array('cal'=>$value['cal_type'], 'cont'=>$value['cal_value'], 'unit_val'=>$value['cal_unit_value']);
				$ruleId = $value['rule_id'];
			}
			
			$fieldInfo['field_content'] = json_encode($data2);
			$fieldInfo['d_id'] = $did;
			
			$fieldExtendArr = UebModel::model('ConditionsFieldExtend')->getFieldExtend($ruleId,$fid);
			
			/* if( is_array($fieldExtendArr) && count($fieldExtendArr) > 0 ){
				foreach( $fieldExtendArr as $value ){
					$fieldExtendStr .= $value['extend_name'].'|'.$value['extend_value'].',';
				}
				$fieldExtendStr = substr($fieldExtendStr,0,-1);
			} */
		}
		$this->render( '_select_ok_res',array('model'=>$model,'ruleFieldInfo'=>$fieldInfo,'fieldExtendArr'=>$fieldExtendArr,'fieldExtendStr'=>$fieldExtendStr) );
		
	}
	
	/**
	 * 选择字段内容
	 */
	public function actionSetrulerescontent($fid) {
		
		$extendstr = isset($_GET['extendstr'])?$_GET['extendstr']:'';
		$model = new $this->modelClass;
		$model2 = new ConditionsField();
		
		$fieldInfo = $model2->getByPk($fid);
		$fieldArr = explode('.', $fieldInfo['field_name']);
		$fieldInfo['auto_complete'] = 0;
		$fieldInfo['auto_complete_field'] = '';
		
		$dropDownList = array();
		if( $fieldInfo['field_type'] == ConditionsField::FIELD_TYPE_LIST ){ //获取需要自动补全的字段的值
			if( $fieldArr[1] == 'country' ){ //国家
				$dropDownList = array();
				$fieldInfo['auto_complete'] = 1;
				$fieldInfo['auto_complete_field'] = TemplateRulesBase::AUTO_COMPLETE_COUNTRY;
			}else if( $fieldArr[1] == 'account' ){ //平台账号
				$dropDownList = array();
				$fieldInfo['auto_complete'] = 1;
				$fieldInfo['auto_complete_field'] = TemplateRulesBase::AUTO_COMPLETE_PLATFORMACCOUNT;
			}else if( $fieldArr[1] == 'user' ){ //用户列表
				$dropDownList = array();
				$fieldInfo['auto_complete'] = 1;
				$fieldInfo['auto_complete_field'] = TemplateRulesBase::AUTO_COMPLETE_USER;
			}else if( $fieldArr[1] == 'classid' ){ //产品分类(大类)
				$dropDownList = array();
				$fieldInfo['auto_complete'] = 1;
				$fieldInfo['auto_complete_field'] = TemplateRulesBase::AUTO_COMPLETE_SKU_CATEGORY_OLD;
			}else if( $fieldArr[1] == 'site' ){ //站点
				$dropDownList = array();
				$fieldInfo['auto_complete'] = 1;
				$fieldInfo['auto_complete_field'] = TemplateRulesBase::AUTO_COMPLETE_SITE;
			}
		}
		
		$fieldInfo['unit_code_data'] = TemplateRulesBase::getCalUnitCodeToData($fieldInfo['unit_code']);
		
		$fieldInfo['field_extends'] = TemplateRulesBase::$modelList[$fieldArr[0]]['field_extends'][$fieldArr[1]];
		
		$selectedArr = array();
		if( $extendstr ){
			$extendArr = explode(',', $extendstr);
			foreach( $extendArr as $value ){
				$extendKeyValue = explode('|', $value);
				$selectedArr[$extendKeyValue[0]] = $extendKeyValue[1];
			}
		}
		
		
		$this->render('_set_rule_res_content', array('model'=>$model,'ruleFieldInfo'=>$fieldInfo,'dropDownList'=>$dropDownList,'selectedArr'=>$selectedArr));
	}
	
	/**
	 * @desc 获取国家列表
	 */
	public function actionGetcountry(){
		$where = '';
		$dropDownList = UebModel::model('Country')->getCountryByCondition($where,'cn_name,en_name');
		
		$result = '';
		foreach( $dropDownList as $k=>$v ){
			$enName = $v['en_name'];
			$cnName = $v['cn_name'];
			$result .= "{\"auto_x\":\"{$enName}\",\"auto_y\":\"{$cnName}\"},";
		}
		$result = trim($result,',');
		$result = '['.$result.']';
		echo $result;
		exit;
	}
	
	/**
	 * @desc 获取平台账号列表
	 */
	public function actionGetplatformaccount(){
		$fid = isset($_GET['fid'])?$_GET['fid']:'';
		$fieldInfo = UebModel::model('ConditionsField')->getByPk($fid);
		
		$result = '';
		$ret = array();
		switch( $fieldInfo['platform_code'] ){
			case Platform::CODE_ALIEXPRESS:
				$ret = AliexpressAccount::getAbleAccountList();
				foreach( $ret as $k=>$v ){
					$result .= "{\"auto_x\":\"{$v['short_name']}\",\"auto_y\":\"{$v['short_name']}\"},";
				}
				break;
			case Platform::CODE_EBAY:
				$ret = UebModel::model('EbayAccount')->getAbleAccountList();
				foreach( $ret as $k=>$v ){
					$result .= "{\"auto_x\":\"{$v['short_name']}\",\"auto_y\":\"{$v['short_name']}\"},";
				}
				break;
			case Platform::CODE_AMAZON:
				$ret = AmazonAccount::getAbleAccountList();
				foreach( $ret as $k=>$v ){
					$result .= "{\"auto_x\":\"{$v['account_name']}\",\"auto_y\":\"{$v['account_name']}\"},";
				}
				break;
			case Platform::CODE_WISH:
				$ret = WishAccount::getAbleAccountList();
				foreach( $ret as $k=>$v ){
					$result .= "{\"auto_x\":\"{$v['account_name']}\",\"auto_y\":\"{$v['account_name']}\"},";
				}
				break;
			case Platform::CODE_LAZADA:
				$ret = LazadaAccount::getAbleAccountList();
				foreach( $ret as $k=>$v ){
					$result .= "{\"auto_x\":\"{$v['seller_name']}\",\"auto_y\":\"{$v['seller_name']}\"},";
				}
				break;
		}
	
		$result = trim($result,',');
		$result = '['.$result.']';
		echo $result;
		exit;
	}
	
	/**
	 * @desc 获取平台站点列表
	 */
	public function actionGetsite(){
		$fid = isset($_GET['fid'])?$_GET['fid']:'';
		$fieldInfo = UebModel::model('ConditionsField')->getByPk($fid);
	
		$result = '';
		$ret = array();
		switch( $fieldInfo['platform_code'] ){
			case Platform::CODE_ALIEXPRESS:
				$ret = AliexpressAccount::getAbleAccountList();
				foreach( $ret as $k=>$v ){
					$result .= "{\"auto_x\":\"{$v['short_name']}\",\"auto_y\":\"{$v['short_name']}\"},";
				}
				break;
			case Platform::CODE_EBAY:
				$ret = UebModel::model('EbayAccount')->getAbleAccountList();
				foreach( $ret as $k=>$v ){
					$result .= "{\"auto_x\":\"{$v['short_name']}\",\"auto_y\":\"{$v['short_name']}\"},";
				}
				break;
			case Platform::CODE_AMAZON:
				$ret = AmazonAccount::getAbleAccountList();
				foreach( $ret as $k=>$v ){
					$result .= "{\"auto_x\":\"{$v['account_name']}\",\"auto_y\":\"{$v['account_name']}\"},";
				}
				break;
			case Platform::CODE_WISH:
				$ret = WishAccount::getAbleAccountList();
				foreach( $ret as $k=>$v ){
					$result .= "{\"auto_x\":\"{$v['account_name']}\",\"auto_y\":\"{$v['account_name']}\"},";
				}
				break;
			case Platform::CODE_LAZADA:
				$ret = LazadaSite::getSiteList();
				foreach( $ret as $k=>$v ){
					$result .= "{\"auto_x\":\"{$v}\",\"auto_y\":\"{$v}\"},";
				}
				break;
		}
	
		$result = trim($result,',');
		$result = '['.$result.']';
		echo $result;
		exit;
	}
	
	/**
	 * @desc 获取用户列表
	 */
	public function actionGetuser(){
		$where = '';
		$dropDownList = MHelper::getUserInfoList(0);
		$result = '';
		foreach( $dropDownList as $k=>$v ){
			$result .= "{\"auto_x\":\"{$v}\",\"auto_y\":\"{$v}\"},";
		}
		$result = trim($result,',');
		$result = '['.$result.']';
		echo $result;
		exit;
	}
	
	/**
	 * @desc 获取产品分类-系统定义的大类
	 */
	public function actionGetcategoryskuold(){
		$where = '';
		$dropDownList = UebModel::model('ProductCategoryOld')->getProductCategoryByCondition($where,'id,cls_name,en_name');
		$result = '';
		foreach( $dropDownList as $k=>$v ){
			$result .= "{\"auto_x\":\"{$v['en_name']}\",\"auto_y\":\"{$v['cls_name']}\"},";
		}
		$result = trim($result,',');
		$result = '['.$result.']';
		echo $result;
		exit;
	}
	
	/**
	 * ajax 处理选择条件
	 */
	public function actionResponseselectcontent() {
		$fid = isset($_POST['fid'])?$_POST['fid']:'';
		$postData = isset($_POST['data'])?$_POST['data']:'';
		
		$data = json_decode($postData, true);
		$calStr = 'cal_'.$fid.'_';
		$contStr = 'cont_'.$fid.'_';
		$unitValueStr = 'unit_code_'.$fid.'_';
		$data2 = array();
		$result = array();
		
		foreach ($data as $key => $value) {
			$k = 0;
			$k = str_replace($calStr, '', $key);
			$data[$unitValueStr.$k] = isset($data[$unitValueStr.$k])? $data[$unitValueStr.$k] : '';
			(is_numeric($k) && !isset($data2[$k])) && $data2[$k] = array('cal'=>$value, 'cont'=>$data[$contStr.$k], 'unit_val'=>$data[$unitValueStr.$k]);
		}
		
		foreach ($data2 as $key => $value) {
			if ($value['cal'] == '') {
				exit('-100');
			}
			if ($value['cont'] == '') {
				exit('-200');
			}
		}
		
		if (count($data2) > 0) {
			$result = json_encode($data2);	
		}
		echo $result;
		
	}
	
	/**
	 * @desc 获取模板
	 */
	public function actionSettemplate() {
		$actionId = $_GET['action_id'];
		$platformCode = $_GET['platform_code'];
		$tab_title = TemplateRulesBase::getRuleClassList($actionId);
		
		if (intval($actionId) < 1) {
			$jsonData = array('message' => Yii::t('orderrules', 'Please select rule action'));
			echo $this->failureJson($jsonData);
			Yii::app()->end();
		}
		if( $actionId == TemplateRulesBase::MATCH_PARAM_TEMPLATE ){
			if( trim($platformCode) == Platform::CODE_LAZADA ){
				$url = 'lazada/lazadaparamtemplate/list/target/dialog/model_name/'.$this->modelClass;
				
			}else if( trim($platformCode) == Platform::CODE_EBAY ){
				$url = 'lazada/lazadaparamtemplate/list/target/dialog/model_name/'.$this->modelClass;
				
			}else if( trim($platformCode) == Platform::CODE_ALIEXPRESS ){
				$url = 'aliexpress/aliexpressparamtemplate/list/target/dialog/model_name/'.$this->modelClass;
				
			}else if( trim($platformCode) == Platform::CODE_AMAZON ){
				Yii::app()->end();
			}else if( trim($platformCode) == Platform::CODE_WISH ){
				Yii::app()->end();
			}
		}else if( $actionId == TemplateRulesBase::MATCH_DESCRI_TEMPLATE ){
			$url = 'common/descriptiontemplate/list/target/dialog/model_name/'.$this->modelClass.'/platform_code/'.$platformCode;
		}else if( $actionId == TemplateRulesBase::MATCH_PRICE_TEMPLATE ){
			$url = 'common/salepricescheme/list/target/dialog/model_name/'.$this->modelClass.'/platform_code/'.$platformCode;
		}
		echo json_encode( array('url'=>urlencode($url),'tab_title'=>str_replace('匹配', '-', $tab_title)) );
		Yii::app()->end();
	}
	
	public function loadModel($id) {
		$model = UebModel::model($this->modelClass)->findByPk((int) $id);
		if ( $model === null )
			throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
	
		return $model;
	}
	
	public function accessRules() {
		return array(
				array(
						'allow',
						'actions' => array('test'),
						'users'=>array('*'),
				),
		);
	}
	
	/**
	 * @desc 根据平台和规则类别，显示模板名称
	 */
	public function getTemplateNameByPlatform( $platformCode,$ruleClass,$templateId ){
		$templateName = '';
		if( $ruleClass == TemplateRulesBase::MATCH_PARAM_TEMPLATE ){
			if( $platformCode == Platform::CODE_LAZADA ){
				$lazadaParamTemplate = UebModel::model('LazadaParamTemplate')->getParamTemplateByPk( $templateId );
				$templateName = $lazadaParamTemplate['tpl_name'];
			}else if( $platformCode  == Platform::CODE_EBAY ){
					
			}else if( $platformCode  == Platform::CODE_ALIEXPRESS ){
				$aliexpressParamTemplate = UebModel::model('AliexpressParamTemplate')->getParamTemplateByPk( $templateId );
				$templateName = $aliexpressParamTemplate['tamplate_name'];
			}else if( $platformCode  == Platform::CODE_AMAZON ){
				Yii::app()->end();
			}else if( $platformCode  == Platform::CODE_WISH ){
				Yii::app()->end();
			}
		}else if( $ruleClass == TemplateRulesBase::MATCH_DESCRI_TEMPLATE ){
			$descTemplate = UebModel::model('DescriptionTemplate')->getDescTemplateByPk( $templateId );
			$templateName = $descTemplate['template_name'];
		
		}else if( $ruleClass == TemplateRulesBase::MATCH_PRICE_TEMPLATE ){
			$priceTemplate = UebModel::model('SalePriceScheme')->getPriceTemplateByPk( $templateId );
			$templateName = $priceTemplate['scheme_name'];
		
		}
		return $templateName;
	}
	
}