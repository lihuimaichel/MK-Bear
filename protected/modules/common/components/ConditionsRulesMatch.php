<?php
/**
 * Conditions Rules Match Component
 * @since	2015-08-15
 */

class ConditionsRulesMatch extends CComponent {
	
	private $_ruleClass;	//规则类别
	private $_baseData;		//基础数据
	private $_inData;		//自定义数据
	private $_logId;		//日志Id
	
	private $_isTest = false;	//是否为测试模式
	
	static $ruleData;
	
	/**
	 * @desc 初始化动作
	 * @param unknown $class
	 */
	public function setRuleClass($class) {
		$this->_ruleClass = $class;
	}
	
	private function getFuncNameSuffix(){
		$list = array(
				TemplateRulesBase::MATCH_DESCRI_TEMPLATE => 'Desc',
				TemplateRulesBase::MATCH_PARAM_TEMPLATE  => 'Param',
				TemplateRulesBase::MATCH_PRICE_TEMPLATE  => 'Price',
		);
		return $list[$this->_ruleClass];
	}
	
	/**
	 * @desc 根据modellist预先查询出需要被判断的字段的值
	 */
	private function initBaseData(){
		$modelRuleClassMaps = TemplateRulesBase::getRuleClassMap( $this->_ruleClass );
		foreach( TemplateRulesBase::$modelList as $key => $value ){
			$retList = array();
			if( !in_array($key,$modelRuleClassMaps) ) continue;
			if( $key == TemplateRulesBase::MODEL_PRODUCT ){
				$condition = 'sku="'.$this->_inData['sku'].'"';
				$retList = $key::model()->getProductByCondition($condition,'*');
					
			}elseif( $key == TemplateRulesBase::MODEL_PRODUCT_SALES ){
				$condition = 'sku="'.$this->_inData['sku'].'" and platform_code = "'.$this->_inData['platform_code'].'"';
				$retList = $key::model()->getProductSalesByCondition($condition,'*');
					
			}elseif( $key == TemplateRulesBase::MODEL_PRODUCT_ROLE_ASSIGN ){
				$condition = 'sku="'.$this->_inData['sku'].'"';
				$retList = $key::model()->getProductRoleByCondition($condition,'*');
					
			}elseif( $key == TemplateRulesBase::MODEL_PRODUCT_CATEGORY ){
				$condition = 'sku="'.$this->_inData['sku'].'"';
				$retList = $key::model()->getProductCategoryByCondition($condition,'*');
				
			}elseif( $key == TemplateRulesBase::MODEL_VIRTUAL_SITE ){
				if( $this->_inData['platform_code'] == Platform::CODE_LAZADA ){
					//$tmpSite = LazadaSite::getSiteList($this->_inData['site_id']);
					$retList = array( 0=>array('site'=>$this->_inData['site_id']) );
				}
			}elseif( $key == TemplateRulesBase::MODEL_VIRTUAL_ACCOUNT ){
				if( $this->_inData['platform_code'] == Platform::CODE_LAZADA ){
					//$tmpAccount = LazadaAccount::getAccountInfoById($this->_inData['account_id']);
					$retList = array( 0=>array('account'=>$this->_inData['account_id']) );
				}else if( $this->_inData['platform_code'] == Platform::CODE_ALIEXPRESS ){
					//$retList = AliexpressAccount::getAbleAccountList();
					$retList = array( 0=>array('account'=>$this->_inData['account_id']) );
				}
			}
			$this->_baseData[$key] = $retList;
		}
		
	}
	
	/**
	 * 核验 inData  inData = array( 'sku','platform_code' );
	 */
	private function checkInData($inData) {
		$_data = array(
				TemplateRulesBase::MATCH_DESCRI_TEMPLATE => array('account_id','platform_code','site_id'),
				TemplateRulesBase::MATCH_PARAM_TEMPLATE => array('account_id','platform_code','site_id'),
				TemplateRulesBase::MATCH_PRICE_TEMPLATE => array('account_id','sku','platform_code')
		);
		
		$_inData = array();
		if (key_exists($this->_ruleClass, $_data)) {
			$checkKey = $_data[$this->_ruleClass];
			foreach ($inData as $key => $value) {
				if ( in_array($key, $checkKey) ) {
					$_inData[$key] = $value;
				}
			}
		}
		
		if (count($_inData)) {
			$this->_inData = $_inData;
		}
		$this->initBaseData();
		//var_dump($this->_baseData);exit;
		return true;
		
	}
	
	/**
	 * 匹配规则
	 * @param	array	$inData
	 */
	public function runMatch($inData) {
		if (empty($this->_ruleClass)) { return false; }
		$logArr = array();
		if( $this->_ruleClass == TemplateRulesBase::MATCH_PRICE_TEMPLATE ){ //匹配价格
			$logArr = array(
					'platform_code' => $inData['platform_code'],
					'rule_class' => $this->_ruleClass,
					'input_params'  => json_encode( array('sku'=>$inData['sku'],'platform_code'=>$inData['platform_code'],'account_id'=>$inData['account_id']) )
			);
			
		}elseif( $this->_ruleClass == TemplateRulesBase::MATCH_DESCRI_TEMPLATE ){ //匹配描述和标题
			$logArr = array(
					'platform_code' => $inData['platform_code'],
					'rule_class' => $this->_ruleClass,
					'input_params'  => json_encode( array('account_id'=>$inData['account_id'],'site_id'=>isset($inData['site_id']) ? $inData['site_id'] : '','platform_code'=>$inData['platform_code']) )
			);
		}elseif( $this->_ruleClass == TemplateRulesBase::MATCH_PARAM_TEMPLATE ){ //匹配参数模板
			$logArr = array(
					'platform_code' => $inData['platform_code'],
					'rule_class' => $this->_ruleClass,
					'input_params'  => json_encode( array('account_id'=>$inData['account_id'],'platform_code'=>$inData['platform_code']) )
			);
		}
		
		// $logId = UebModel::model('ConditionsRulesMatchLog')->addNewData($logArr);
		// if (!$logId) {
		// 	return false;
		// }
		// $this->_logId = $logId;
		
		$this->checkInData($inData);
		$_GET['is_test'] = isset($_GET['is_test'])?$_GET['is_test']:'0';
		($_GET['is_test'] == 1) && $this->_isTest = true;
		
		$func = 'runMatch'.$this->getFuncNameSuffix();
		$ret = $this->$func();
		if ($this->_isTest) {
			//exit('end');
		}
		
		return $ret;
	}
	
	/**
	 * @desc 匹配规则	[匹配价格模板]
	 */
	protected function runMatchPrice() {
		$platfrom = $this->_inData['platform_code'];
		$sku = $this->_inData['sku'];
		
		if (!empty($platfrom) && !empty($sku)) {
			$ruleList = UebModel::model('ConditionsRules')->getEnableRuleList($this->_ruleClass, $platfrom);
			$ruleListLog = UebModel::model('ConditionsRules')->getRuleListLogByData($ruleList);
			// UebModel::model('ConditionsRulesMatchLog')->updateByPk($this->_logId, array('start_match_time'=>date('Y-m-d H:i:s'),'search_rule'=>json_encode($ruleListLog),'status'=>ConditionsRulesMatchLog::STATUS_MATCH_START));
			self::$ruleData = $ruleList;
			$ret = $this->checkRuleExec();
		}
		return $ret;
		
	}
	
	/**
	 * @desc 匹配规则	[匹配描述模板]
	 */
	protected function runMatchDesc() {
		$platfrom = $this->_inData['platform_code'];
		$account = $this->_inData['account_id'];
	
		if ( !empty($platfrom) && !empty($account) ) {
			$ruleList = UebModel::model('ConditionsRules')->getEnableRuleList($this->_ruleClass, $platfrom);
			$ruleListLog = UebModel::model('ConditionsRules')->getRuleListLogByData($ruleList);
			// UebModel::model('ConditionsRulesMatchLog')->updateByPk($this->_logId, array('start_match_time'=>date('Y-m-d H:i:s'),'search_rule'=>json_encode($ruleListLog),'status'=>ConditionsRulesMatchLog::STATUS_MATCH_START));
			self::$ruleData = $ruleList;
			$ret = $this->checkRuleExec();
		}
	
		return $ret;
	
	}
	
	/**
	 * @desc 匹配规则	[匹配参数模板]
	 */
	protected function runMatchParam() {
		$platfrom = $this->_inData['platform_code'];
		$account = $this->_inData['account_id'];
	
		if (!empty($platfrom) && !empty($account)) {
			$ruleList = UebModel::model('ConditionsRules')->getEnableRuleList($this->_ruleClass, $platfrom);
			$ruleListLog = UebModel::model('ConditionsRules')->getRuleListLogByData($ruleList);
			// UebModel::model('ConditionsRulesMatchLog')->updateByPk($this->_logId, array('start_match_time'=>date('Y-m-d H:i:s'),'search_rule'=>json_encode($ruleListLog),'status'=>ConditionsRulesMatchLog::STATUS_MATCH_START));
			self::$ruleData = $ruleList;
			$ret = $this->checkRuleExec();
		}
		return $ret;
	
	}
	
	/**
	 * @desc 匹配规则 开始执行 循环匹配 
	 */
	private function checkRuleExec() {
		$ruleRet = '';
		$ruleRetLog = array();
		$curSort = -10000; //当前优先级
		$lastOkSort = -10000; //上一次优先级
		$lastCreateTime = ''; //上一次规则创建时间
		foreach (self::$ruleData as $key => $value) {

			if ($this->_isTest && isset($_GET['id']) && $value->id != $_GET['id']) {
				continue;
			}
			
			if ($this->_isTest) {
				echo '<font color=green><b>start check rule: '.$value->id.", ".$value->rule_name.'，'.$value->priority.'</b></font><br/>';
			}
			
			if ( $lastOkSort != -10000 ) {
				if( $value->priority < $lastOkSort ){
					break;
				}elseif( $value->priority == $lastOkSort && $value->create_time < $lastCreateTime ){
					break;
				}
			}
			
			$calArr = array();
			foreach ($value->detail as $key1 => $value1) {
				$fieldArr = explode('.', $value1->field_name);
				$calArr[$fieldArr[0]][$fieldArr[1]][] = $value1->attributes;
			}
			//print_r($calArr);
			$ret = $this->checkRuleDetailMain($calArr,$value->id); //开始具体匹配
			
			if ($ret === true) {
				//保存匹配到的rule
				$ruleRet = $value->template_id;
				
				$ruleRetLog[] = array( $value->id,$value->priority,$value->create_time,$value->template_id );
				$lastOkSort = $value->priority;
				$lastCreateTime = $value->create_time;
			}else {
				if (isset($_GET['is_test']) && $_GET['is_test']) {
					echo '<font color=red>未匹配成功，继续...</font><br/>';
				}
			}
			
			$curSort = $value->priority;
			
		}
		
		if ($this->_isTest) {
			var_dump($ruleRetLog);
		}
		if( $ruleRet ){
			$matchStatus = ConditionsRulesMatchLog::STATUS_MATCH_OK;
		}else{
			$matchStatus = ConditionsRulesMatchLog::STATUS_MATCH_FAIL;
		}
		// UebModel::model('ConditionsRulesMatchLog')->updateByPk($this->_logId, array('match_template_id'=>$ruleRet,'match_rule'=>json_encode($ruleRetLog),'end_match_time'=>date('Y-m-d H:i:s'),'status'=>$matchStatus));
		
		return $ruleRet;
	}
	
	
	/**
	 * 匹配规则主程序[Detail] $this->_baseData[$key] = $retList;
	 */
	private function checkRuleDetailMain($calArr,$ruleId) {
		
		$ret = true;
		
		if ($this->_isTest && $_GET['t1']) {
			echo '========================================================================================================== <br/>';
		}
		
		foreach ($calArr as $key => $value) { //model 循环model
			
			if ($this->_isTest && $_GET['t1']) {
				echo "Model --> $key <br/>==================================================<br/>";
			}
			
			foreach ($value as $keyField => $valField) { //循环字段
				if ($this->_isTest && $_GET['t1']) {
					echo "Field --> $keyField <br/>";
				}
				
				foreach ($valField as $keyIndex => $valDetail) { // 循环字段值详细   integer_index ,array( 0=>array('detailid','cal_type','cal_val','cal_unit_code','cal_unit_value',...),1=>array(),... )
					if ($this->_isTest && $_GET['t1']) {
						echo "f --> $keyIndex <br/>";
					}
					
					$waitCheckValue = array(); //待比较判断的值
					$fieldExtendList = UebModel::model('ConditionsFieldExtend')->getFieldExtend( $ruleId,$valDetail['field_id'] );//获取field_extend
					
					if( is_array($fieldExtendList) && count($fieldExtendList) > 0 ){ //存在 field_extend
						$extendTmpArr = array();
						foreach( $fieldExtendList as $extendKey => $extendVal ){
							$extendTmpArr[$extendVal['extend_name']] = $extendVal['extend_value'];
						}
						
						foreach( $this->_baseData[$key] as $dataKey => $dataVal ){
							$extendFlag = false;
							$expStr = '';
							foreach( $extendTmpArr as $extendKey => $extendVal ){
								$expStr .= "'{$dataVal[$extendKey]}' == '{$extendVal}' && ";
							}
							$expStr = substr($expStr,0,-3);
							//echo '<br/>'.$expStr;
							eval("\$extendFlag = ".$expStr.";");
							if( $extendFlag ){
								$dataVal['currency'] = isset($dataVal['currency'])?$dataVal['currency']:'';
								$waitCheckValue[] = array( 'checkVal'=>$dataVal[$keyField],'checkUnit'=>$dataVal['currency'] );
								break;
							}
						}
					}else{ //不存在 field_extend
						foreach( $this->_baseData[$key] as $dataKey => $dataVal ){
							$dataVal['currency'] = isset($dataVal['currency'])?$dataVal['currency']:'';
							$waitCheckValue[] = array( 'checkVal'=>$dataVal[$keyField],'checkUnit'=>$dataVal['currency'] );
						}
					}
					
					//var_dump($waitCheckValue);
					$flag2 = true;
					if ($waitCheckValue) {
						foreach ($waitCheckValue as $checkValue) { //开始遍历 对比
							if (isset($_GET['is_test']) && $_GET['is_test'] && isset($_GET['t1']) && $_GET['t1']) {
								echo "wait value:". $checkValue['checkVal']."<br/>";
							}
							$flag = true;
						
							$calUnit = array( 'unit_code'=>$valDetail['cal_unit_code'], 'unit_value'=>$valDetail['cal_unit_value'],'refer_code'=>array(TemplateRulesBase::SYS_CURRENCY=>$checkValue['checkUnit']) );
							$confrimValue = TemplateRulesBase::getCheckValue( trim($checkValue['checkVal']), $valDetail['field_name'], $this->_inData['platform_code'] );
							$calValueArr = TemplateRulesBase::getCalValueUnit($valDetail['cal_value'], $calUnit);//单位换算[比较值折算] array('ret_code'=>'g','ret_value'=>'100');
							$checkExpArr = TemplateRulesBase::getCalExp($valDetail['cal_type'], $calValueArr['ret_value']);
							$checkExp = str_replace('{waitCheckValue}', $confrimValue, $checkExpArr['exp']);
						
							eval("\$flag = ".$checkExp.";");
							if ($this->_isTest) {
								var_dump($checkExp, $flag);
							}
						
							$flag2 = $flag2 && $flag;
							
						}
						if ($this->_isTest && $_GET['t1']) {
							echo '<font color=red>Wait check result: </font>';
							var_dump($flag2);
						}
					}else {
						$flag2 = TemplateRulesBase::checkVoidValue($valDetail['cal_type']);
						if ($this->_isTest && $_GET['t1']) {
							echo "<font color=red>Wait check result [void value]: {$valDetail['cal_type']}</font>";
							var_dump($flag2);
						}
					}
					
					$ret = $ret && $flag2;

				}
				
				if ($this->_isTest && $_GET['t1']) {
					echo '<font color=green>Field check result: </font>';
					var_dump($ret);
				}
				
			}
			
		}
		
		if ($this->_isTest) {
			echo '<font color=blue>Rule check result: </font>';
			var_dump($ret);
		}

		return $ret;
	}

	
	/**
	 * 获取 log_id
	 */
	public function getLogId() {
		return $this->_logId;
	}
	
}