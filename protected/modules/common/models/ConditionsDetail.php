<?php
/**
 * Conditions Field Detail Model
 * @author	wx
 * @since	2015-07-29
 */

class ConditionsDetail extends CommonModel {
	
	const DEL_OK = 1;
	const DEL_NO = 0;
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_conditions_detail';
	}
	
	public function rules() {
		return array(
				array('rule_id,field_id,field_name,cal_type,cal_value', 'required'),
				array('cal_unit_code,cal_unit_value,create_time,create_time,modify_time,modify_user_id,is_del,del_time,del_user_id','safe')
		);
	}
	
	/**
	 * Declares attribute labels.
	 * @return array
	 */
	public function attributeLabels() {
		return array(
				
		);
	}
	
	public function filterOptions() {
		return array(
				//'field_name','create_time','modify_time'
		);
	}
	
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'id',
		);
		$criteria = null;
		$dataProvider = parent::search(get_class($this), $sort,array(),$criteria);
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	private function addition($data) {
		foreach ($data as $key => $value) {
			//$data[$key]->rule_class_cn = $value->rule_class ? OrderRulesBase::getRuleClassList($value->rule_class) : '%';
		}
		return $data;
	}
	
	public function saveOrderRuleDetail($data) {
		$model = new self();
		foreach($data as $key => $value){
			$model->setAttribute($key,$value);
		}
		$model->create_time = date('Y-m-d H:i:s');
		$model->create_user_id = Yii::app()->user->id;
		$model->setIsNewRecord(true);
		if ($model->save()) {
			return $model->id;
		}
		return false;
	}
	
	/**
	 * 得到规则明细根据 RuleId
	 * @param	int		$ruleId
	 * @return	array
	 */
	public function getRuleDetailListByRuleId($ruleId,$fields='*') {
		$list = $this->getDbConnection()->createCommand()
				->select($fields)
				->from($this->tableName())
				->where("rule_id = {$ruleId}")
				->andWhere('is_del = 0')
				->queryAll();
		return $list;
	}
	
	public function getRuleDetailListByRuleId2($ruleId,$fields='*') {
		$list = $this->getDbConnection()->createCommand()
			->select($fields)
			->from($this->tableName())
			->where("rule_id = {$ruleId}")
			->queryAll();
		return $list;
	}
	
	/**
	 * 得到规则详情，规则ID，字段ID
	 */
	public function getRuleDetailListByRuleAndField($ruleId,$fieldId,$fields='*') {
		$list = $this->getDbConnection()->createCommand()
				->select($fields)
				->from($this->tableName())
				->where("rule_id = {$ruleId} and field_id = {$fieldId}")
				->queryAll();
		return $list;
	}
	
	/**
	 * 得到规则明细根据 ids
	 * @param	array	$ids
	 * @return	array
	 */
	public function getRuleDetailListByIds($ids,$fields='*') {
		$list = $this->getDbConnection()->createCommand()
		->select($fields)
		->from($this->tableName())
		->where(array('in','id',$ids))
		->queryAll();
		return $list;
	}
	
	/**
	 * 得到规则明细根据 field_id
	 * @param	array	$fieldIds
	 * @return	array
	 */
	public function getRuleDetailListByFieldIds($fieldIds, $fields='*') {
		!is_array($fieldIds) && $fieldIds = array($fieldIds);
		$list = $this->getDbConnection()->createCommand()
			->select($fields)
			->from($this->tableName())
			->where(array('in','field_id',$fieldIds))
			->queryAll();
		return $list;
	}
	
	/**
	 * 检查 cal_value 是否合法
	 * @param	string	$validateType
	 * @param	string	$value
	 * @return	mixed
	 */
	public function checkCalValueIsAvail($validateType, $value) {
		//var_dump($validateType, $value);exit();
		$ret = array('flag'=>true,'msg'=>'');
		if(empty($validateType)) return $ret;
		$arrValue = explode(TemplateRulesBase::STR_SEPARATOR, $value);
		$arrValue2 = array_unique($arrValue);

		if (count($arrValue) != count($arrValue2)) {
			$diffValue = array_diff_assoc($arrValue, $arrValue2);
			$diffValue = implode(',', $diffValue);
			$ret['flag'] = false;
			$ret['msg'] = '可能存在重复值，请检查 '.$diffValue;
			return $ret;
		}
		foreach ($arrValue as $val) {
			$val2 = trim($val);
			if (strlen($val) != strlen($val2)) {
				$ret['flag'] = false;
				$ret['msg'] = "请检查是否存在空格 '{$val}'";
				return $ret;
			}
			//检查国家
			if ($validateType == TemplateRulesBase::VALIDATE_COUNTRY) {
				$check = UebModel::model('Country')->getCountryCnameByEname(trim($val));
				if (!$check) {
					$ret['flag'] = false;
					$ret['msg'] = "国家：<font color=red>{$val}</font> 不存在，请检查！";
					break;
				}
			}
			//检查SKU
			if ($validateType == TemplateRulesBase::VALIDATE_SKU) {
				$check = UebModel::model('Product')->checkSkuIsExisted(trim($val));
				if (!$check) {
					$ret['flag'] = false;
					$ret['msg'] = "SKU：<font color=red>{$val}</font> 不存在，请检查！";
					break;
				}
			}
			//检查账号
			if ($validateType == TemplateRulesBase::VALIDATE_PLAT_ACCOUNT) {
				//$retAmazon = UebModel::model('AmazonAccount')->getByAccountName($val);
				$retEbay = UebModel::model('EbayAccount')->getByShortName($val);
				$retAli = UebModel::model('AliexpressAccount')->getAccountInfoByShortName($val);
				$retWish = UebModel::model('WishAccount')->getByAccountName($val);
				$retLazada = UebModel::model('LazadaAccount')->getByAccountName($val);
				if ( !$retEbay && !$retAli && !$retWish && !$retLazada) {
					$ret['flag'] = false;
					$ret['msg'] = "平台账号：<font color=red>{$val}</font> 不存在，请检查！";
					break;
				}
			}
			
		}
		return $ret;
		
	}
	
	/**
	 * get rule id by select_key
	 * @param	string	$selectKey
	 * @return	array
	 */
	public function getRuleIdBySelectkey($selectKey,$fields='*') {
		$ret = array();
		$list = $this->getDbConnection()->createCommand()
		->select($fields)
		->from($this->tableName())
		->where("cal_value like '%{$selectKey}%' and is_del=".self::DEL_NO)
		->queryAll();
		if ($list) {
			foreach ($list as $value) {
				$ret[] = $value['rule_id'];
			}
		}
		return $ret;
	}
	
	
}