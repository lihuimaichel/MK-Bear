<?php
class DescriptionTemplate extends CommonModel {
	
	const STATUS_OPEN = 1;			//状态-开启
	const STATUS_CLOSED = 0;		//状态-关闭
	
	/**
	 * @desc 模板预览地址
	 * @var string
	 */
	public $preview_url = null;
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_description_template';
	}
	
	public function filterOptions() {
		$result = array(
			array(
				'name' => 'platform_code',
				'type' => 'dropDownList',
				'data' => CHtml::listData(UebModel::model('Platform')->findAll(), 'platform_code', 'platform_name'),
				'search' => '=',
			),
		);
		return $result;
	}
	
	/**
	 * @desc 验证规则
	 * @see CModel::rules()
	 */
	public function rules() {
		$rules = array(
			array('template_content, template_name, platform_code', 'required'),
			array('title_prefix, title_suffix', 'safe'),
			array('status', 'numerical')
		);
		return $rules;
	}
	
    /**
     * get search info
     */
    public function search(){              
    	$sort = new CSort('DescriptionTemplate');
      	
        $sort->attributes = array(
            'defaultOrder'  => 'create_time',            
        );
      	$dataProvider = parent::search(get_class($this), $sort,array(),$this->_setCDbCriteria());
      	$data = $this->addition($dataProvider->data);
		$dataProvider->setData($dataProvider->data);
      	return $dataProvider;       	            	
    }

    /**
     * @desc 处理列表数据
     * @param array $datas
     * @return array
     */
    public function addition($datas) {
    	return $datas;
    }
    
    /**
     * @desc 设置列表条件
     * @return CDbCriteria
     */
    private function _setCDbCriteria(){
    	$criteria = new CDbCriteria();
    	return $criteria;
    }
   
    /**
     * @desc 属性标签
     * @see CModel::attributeLabels()
     */
    public function attributeLabels() {
    	return array(
    			'id' 					=> Yii::t('system', 'NO.'),
    			'template_name' 		=> Yii::t('description_template', 'Template Name'),
    			'template_content' 		=> Yii::t('description_template', 'Template Content'),
    			'title_prefix'			=> Yii::t('description_template', 'Title Prefix'),
    			'title_suffix'			=> Yii::t('description_template', 'Title Suffix'),
    			'status'				=> Yii::t('system', 'Status'),
    			'create_user_id' 		=> Yii::t('system', 'Create User'),
    			'create_time' 			=> Yii::t('system', 'Create Time'),
    			'modify_user_id' 		=> Yii::t('system', 'Modify User'),
    			'modify_time' 			=> Yii::t('system', 'Modify Time'),
    			'template_preview' 		=> Yii::t('description_template', 'Template Preview'),
    			'platform_code'         => Yii::t('system', 'Platform'),
    	);
    }
    
    /**
     * @desc 获取菜单对应ID
     * @return integer
     */
    public static function getIndexNavTabId() {
    	return UebModel::model('Menu')->getIdByUrl('/common/descriptiontemplate/list');
    }
    
    /**
     * @desc　获取状态listing
     * @param string $key
     * @return Ambigous <string, Ambigous <string, string, unknown>>|multitype:string Ambigous <string, string, unknown>
     */
    public function getStatusList($key = null) {
    	$statusList = array(
    		self::STATUS_OPEN => Yii::t('description_template', 'Status Normal'),
    		self::STATUS_CLOSED => Yii::t('description_template', 'Status Invalid'),
    	);
		if (!is_null($key) && array_key_exists($key, $statusList))
			return $statusList[$key];
		return $statusList;
    }
    
    /**
     * @desc 根据模板id查找模板信息
     * @param unknown $id
     */
    public function getParamTplById($id) {
    	$id = (array) $id;
    	return $this->getDbConnection()
		    	->createCommand()
		    	->select('id,template_name as tpl_name')
		    	->from(self::tableName())
		    	->where(array('IN', 'id', $id))
		    	->andWhere('status='.self::STATUS_OPEN)
		    	->queryAll();
    }
    
    public function getDescTemplateByPk( $id ){
    	$retObj = $this->findByPk($id,'status = '.self::STATUS_OPEN);
    	return $retObj->attributes;
    }
    
    /**
     * @desc 根据ID获取描述模板
     * @param unknown $id
     */
    public function getDescriptionTemplateByID($id) {
    	return $this->getDbConnection()->createCommand()
    		->from(self::tableName())
    		->where("id = :id", array(':id' => $id))
    		->queryRow();
    }
    
    //获取产品在某平台某账号中的预览描述
    public function getDescription($content, $description, $title = '', $included = '', $imageList = array()) {
    	if(!empty($description)){
    		$description = $this->getMatchResult($description);
    	}
    	if(!empty($included)){
    		$included = $this->getMatchResult($included);
    	}
    	$content = str_replace('[title/]',$title,$content);
    	if (!empty($imageList))
    		$content = str_replace('[firstimage/]',$imageList[0],$content);
    	//替换description
    	$content = $this->getReplacedListContent($content,'description','descriptionline',$description);
    	//替换include
    	$content = $this->getReplacedListContent($content,'included','includedline',$included);
    	//替换图片
    	$content = $this->getReplacedListContent($content,'imagelist','imageurl',$imageList);
    	
    	return $content;
    }

    //获取替换列表后的内容(例如:图片,描述等等)
    public function getReplacedListContent($content,$listname,$singlename,$listdata){
    	if (empty($listdata)) return $content;
    	$res = array();//匹配结果
    	$pattern = '/\['.$listname.'\](.*)\[\/'.$listname.'\]/isU';
    	if(preg_match_all($pattern,$content,$res)){
    		$singlestr = $res[1][0];
    		$dataliststr = '';
    		foreach ($listdata as $value){
    			/*
    				if(trim($value)==''){
    			continue;
    			}
    			*/
    			$dataliststr .= str_replace('['.$singlename.'/]',$value,$singlestr);
    		}
    		$content = preg_replace($pattern,$dataliststr,$content);
    	}
    	return $content;
    }

    /**
     *
     * 分析输入的内容是HTML还是纯文本格式
     * @param unknown_type $content
     */
    public function getMatchResult($content){
    	$match_model_1 = '/<p.*?>.*<\/p>/iUs';
    	$match_model_2 = '/<table.*?>.*<\/table>/iUs';
    	$match_model_3 = '/<br\s?\/?>/iUs';
    	$model_4 = '/<div.*?>.*<\/div>/iUs';
    	if(preg_match($match_model_1,$content)||preg_match($match_model_2,$content)||preg_match($match_model_3,$content)||preg_match($model_4, $content)){
    		$description = array($content);
    	}else{
    		$description = explode("\n",$content);
    	}
    	return $description;
    }
    
    /**
     * @desc 获取最优描述模板
     * @param unknown $params
     * @return unknown|boolean
     */
    public function getTemplateInfo($params = array()) {
    	$ruleModel = new ConditionsRulesMatch();
    	$ruleModel->setRuleClass(TemplateRulesBase::MATCH_DESCRI_TEMPLATE);
    	$descriptionTemplateID = $ruleModel->runMatch($params);
    	if (empty($descriptionTemplateID) || !($descriptTemplate = $this->getDescriptionTemplateByID($descriptionTemplateID))) {
			return $descriptTemplate;
    	}
    	return false;
    }
    
    /**
     * @desc 获取平台配置的描述模板
     * @param unknown $platformCode
     */
    public function getDescriptionTemplate($platformCode) {
    	return $this->dbConnection->createCommand()
    		->from(self::tableName())
    		->select("*")
    		->where("platform_code = :platform_code", array(':platform_code' => $platformCode))
    		->queryAll();
    }
}