<?php
/**
 * @desc 语言管理
 * @author Gordon
 */
class Language extends SystemsModel {
          
    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ueb_language';
	}

	/**
	 * 
	 */
	public function getLangList($lang_code=''){
		static $data = array();
		if(empty($data)){
			$list =  Yii::app()->db->createCommand()
			->select('id,language_code,google_code,cn_code')
			->from(self::tableName())
			->order('sort asc')
			->queryAll();
			if($list){
				foreach($list as $key=>$val){
					$data[$val['language_code']]['cn_code']=$val['cn_code'];
					$data[$val['language_code']]['google_code']=$val['google_code'];
				}
			}
		}
		
		if($lang_code) return $data[$lang_code];
		return $data;
	}
	
	public function getLangListarr(){
		static $data = array();
		if(empty($data)){
			$list =  Yii::app()->db->createCommand()
			->select('id,language_code,google_code,cn_code')
			->from(self::tableName())
			->order('sort asc')
			->queryAll();
			if($list){
				foreach($list as $key=>$val){
					$data[$val['language_code']]=$val['cn_code'].'['.$val['google_code'].']';
				}
			}
		}
		return $data;
	}
	
	/**
	 *参数：$language_code
	 *当$language_code为字符串时[$language_code= CN]，取单一语言信息
	 *当为数组时[$language_code=array(CN,EN,DE,...)]，返回所有语言信息
	 *返回最终该 键值为'language_code'的数组
	 */
	public function getLangByCode($language_code = CN){
		static $data = array();
		if(empty($data)){
			$dbObj =  Yii::app()->db->createCommand()
			->select('id,language_code,google_code,cn_code')
			->from(self::tableName());
			if($language_code){
	    		if(is_string($language_code)){
	    			$dbObj->andwhere('language_code=:language_code',array(':language_code'=>$language_code));
	    		}elseif(is_array($language_code) && !empty($language_code)){
	    			$dbObj->andwhere(array('in', 'language_code', $language_code));
	    		}else{}
	    	}
			$list = $dbObj->order('sort asc')->queryAll();
			if($list){
				foreach($list as $key=>$val){
					$data[$val['language_code']]['cn_code']=$val['cn_code'];
					$data[$val['language_code']]['language_code']=$val['language_code'];
				}
			}
		}

		return $data;
	}
	
	/**
	 * get language options
	 * return string: 属性翻译,描述翻译
	 */
	public function getLanguageOptions($attr_id='') {
		$str = '';
		$options = array(
			'1'=>Yii::t('system', 'Attributed Translation'),
			'2'=>Yii::t('system', 'Descriptive Translation'),
			'3'=>Yii::t('system', 'Attachment Translation'),
		);
		if(!empty($attr_id)) {
			$attr_id = rtrim($attr_id);
			$arr = explode(',',$attr_id);
			foreach($arr as $v){
				$str .= $options[$v].',';
			}
			return rtrim($str,',');
		}
		return $options;
	}

	/**
     * get index nav tab id 
     * 
     * @return type
     */
    public static function getIndexNavTabId() {
    	return Menu::model()->getIdByUrl('/systems/language/list');
    }  
    
    /**
     * get list by attributed
     * 
     * @param integer $attributed
     * @return array $result 
     */
    public function getListByAttributed($attributed) {
        $result = array();
        
        $list = $this->findAll();
        foreach ($list as $key => $val ) {
            if ( empty($val['attributed']) ) { continue;}
            if ( in_array($attributed, explode(",", $val['attributed']))) {
                $result[$key]['language_code'] = $val['language_code'];
                $result[$key]['google_code'] = $val['google_code'];
                $result[$key]['cn_code'] = $val['cn_code'];            
            }
        }
        
        return $result;
    }
    
    /**
     * get record log
     */
    public function getRecordLog() {
    	$msg = '';
    	foreach ( $this->getAttributes() as $key => $val ) {
    		if ( ! $this->getIsNewRecord() && $val == $this->beforeSaveInfo[$key] ) {
    			continue;
    		}
    		$label = $this->getAttributeLabel($key);
    		if (in_array($key, array( 'id', 'modify_user_id' ,'modify_time', 'create_user_id', 'create_time'))) {
    			continue;
    		}else if($key == 'attributed'){
    			if ( $this->getIsNewRecord() ) {
					$attributed = UebModel::model('Language')->getLanguageOptions($val);
    				$msg .= MHelper::formatInsertFieldLog($label, $attributed);
    			} else {
    				$attributed = UebModel::model('Language')->getLanguageOptions($val);
    				$before = UebModel::model('Language')->getLanguageOptions($this->beforeSaveInfo[$key]);
    				$msg .= MHelper::formatUpdateFieldLog($label, $before, $attributed);
    			}
    		}else {
    			if ( $this->getIsNewRecord() ) {
    				$msg .= MHelper::formatInsertFieldLog($label, $val);
    			} else {
    				$msg .= MHelper::formatUpdateFieldLog($label, $this->beforeSaveInfo[$key], $val);
    			}
    		}
    	}
    	$this->addLogMsg($msg);
    }
    
    /**
     * 翻译文字
     * @param string $fromLan
     * @param string $toLan
     * @param string $text
     * @return string
     */
    public function translate($fromLan, $toLan, $text){
    	$translator = new GoogleApiTranslator();
    	$translator->setLang($fromLan, $toLan);
    	$translator->setText($text);
    	$content = $translator->translate();
    	return $content;
    }
}