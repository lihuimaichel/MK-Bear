<?php
/**
 * @desc Aliexpress分类属性
 * @author wx
 * @since 2015-09-11
 */
class AliexpressCategoryAttributes extends AliexpressModel{
    
    const EVENT_NAME = 'get_categories_attributes';
    const CATEGORY_ATTRIBUTE = 'category_attribute';
    
    /** @var int 账号ID*/
    protected $_accountID = null;
    
    /** @var string 异常信息*/
    protected $exception = null;
    
    /** @var integer 分类Id **/
    protected $_categoryId = null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
    	$this->_accountID = $accountID;
    }
    
    /**
     * @desc  设置分类id
     * @param integer $categoryId
     */
    public function setCategoryId($categoryId) {
    	$this->_categoryId = $categoryId;
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules(){}
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_category_attribute';
    }
    
    /**
     * @desc 获取分类属性
     * @param boolean $isFirst 是否是拉取主属性 true是，false否.
     */
    public function getCategoryAttributes( $isFirst = true, $parentAttrId = false, $parentAttrValId = false ){
        set_time_limit(3600);
        ini_set('display_errors', true);
        $attribute_id = Yii::app()->request->getParam('attribute_id');
        
    	$accountID = $this->_accountID;
        if(!$accountID){ 
            $account = AliexpressAccount::getAbleAccountByOne();
            $accountID = $account['id'];
        }
        $request = new GetCategoryAttributeRequest();
        $request->setCateId($this->_categoryId);
        if($parentAttrId && $parentAttrValId) $request->setParentAttrValueList( json_encode( array(array($parentAttrId,$parentAttrValId)) ) );
        
        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        if( $request->getIfSuccess() ){
            if($attribute_id){
                if( $isFirst ){
                        $this->deleteRecordByAttributeId( intval($this->_categoryId), intval($attribute_id) );
                }

                $attributes = $response->attributes;
                foreach($attributes as $attribute){
                    if(intval($attribute->id) == $attribute_id){
                        if( $isFirst ) $this->saveCategoryAttributes($attribute);
                        $this->loopSave($attribute,$parentAttrId,$parentAttrValId);
                    }
                    break;
                }
                
            } else {
                if( $isFirst ){
                        $this->deleteRecord( intval($this->_categoryId) );
                }

                $attributes = $response->attributes;
                foreach($attributes as $attribute){
                    if( $isFirst ) $this->saveCategoryAttributes($attribute);
                    $this->loopSave($attribute,$parentAttrId,$parentAttrValId);
                }
            }
            return true;
        }else{
            $this->setExceptionMessage($request->getErrorMsg());
            return false;
        }
    }
    
    /**
     * @desc 保存分类属性数据
     * @param unknown $attribute
     */
    public function saveCategoryAttributes( $attribute ){
        if( isset($attribute->id) ){
        	$showType = trim( $attribute->attributeShowTypeValue );
        	//解决销售属性显示为下拉框
        	$showType = ( !empty($attribute->sku) && !empty($attribute->spec) && $showType == 'list_box') ? 'check_box' : $showType;  
        	$data = array(
        			'type'       			=> self::CATEGORY_ATTRIBUTE,
        			'category_id'           => intval($this->_categoryId),
        			'attribute_id'			=> intval($attribute->id),
        			'spec'         			=> empty($attribute->spec)?0:$attribute->spec,
        			'sku'         			=> empty($attribute->sku)?0:$attribute->sku,
        			'customized_pic'        => empty($attribute->customizedPic)?0:$attribute->customizedPic,
        			'attribute_required'	=> empty($attribute->required)?0:$attribute->required,
        			'showtype_value'		=> $showType,
        			'input_type'			=> trim( $attribute->inputType ),
        			'timestamp'				=> date('Y-m-d H:i:s'),
        			'key_attribute'			=> empty($attribute->keyAttribute)?0:$attribute->keyAttribute,
        			'costomized_name'		=> empty($attribute->customizedName)?0:$attribute->customizedName,
        			'visible'				=> empty($attribute->visible)?0:$attribute->visible
        			
        	);
        	
        	$attrValIds = '';
        	if ( isset($attribute->values) ) {
        		foreach ($attribute->values as $key => $val) {
        			$attrValIds .=  intval($val->id)  . ',';
        		}
        		$attrValIds = substr($attrValIds, 0, -1);
        	}
        	$data['attribute_value_ids'] = $attrValIds;
        	
            $flag = $this->saveRecord($data);
        }
    }
    
    public function loopSave($attribute, $parentAttrId = false, $parentAttrValId = false) {
    	$this->saveAttribute($attribute);
    	if ( $parentAttrId && $parentAttrValId ) {
    		$this->saveAttributeValMap($attribute, $parentAttrId, $parentAttrValId);
    		$this->updateAttributeValue($parentAttrValId);
    	}
    	$this->saveAttributeValue($attribute);
    }
    
    /**
     * @desc 若有子属性，则标识父属性值为存在子属性
     */
    public function updateAttributeValue( $attributeValueId ){
    	$data = array('attribute_children'=>1);
    	$flag = $this->dbConnection->createCommand()->update(AliexpressAttributeValue::model()->tableName(), $data ,'attribute_value_id = "'.intval($attributeValueId).'"');
    	return $flag;
    }
    
    /**
     * @desc 保存属性
     */
    public function saveAttribute( $attribute ){
    	if( isset($attribute->id) ){
    		$data = array(
    				'attribute_id'           		 => intval($attribute->id),
    				'attribute_spec'				 => empty($attribute->spec)?0:$attribute->spec,
    				'attribute_visible'   			 => empty($attribute->visible)?0:$attribute->visible,
    				'attribute_customized_name'      => $attribute->customizedName,
    				'attribute_customized_pic'       => empty($attribute->customized_pic)?0:$attribute->customized_pic,
    				'attribute_key_attribute'        => empty($attribute->keyAttribute)?0:$attribute->keyAttribute,
    				'attribute_sku'					 => empty($attribute->sku)?0:$attribute->sku,
    				'attribute_required'			 => empty($attribute->required)?0:$attribute->required,
    				'attribute_input_type'			 => trim( $attribute->inputType ),
    				'attribute_showtype_value'		 => trim( $attribute->attributeShowTypeValue )
    		);
    		$ret = UebModel::model('AliexpressAttribute')->getAttrByAttrId(  intval($attribute->id) );
    		if($ret){
    			$flag = $this->dbConnection->createCommand()->update(AliexpressAttribute::model()->tableName(), $data ,'attribute_id = "'.intval($attribute->id).'"');
    		}else{
    			$flag = $this->dbConnection->createCommand()->insert(AliexpressAttribute::model()->tableName(), $data);
    		}
    		
    		$retLang = UebModel::model('MultiLanguage')->getLangList();
    		
    		$langTmp = array();
    		foreach( $retLang as $key => $value ){
    			$currShort = $value['google_code'];
    			$currIndex = strpos($currShort,'-');
    			if($currIndex>0) $currShort = substr($currShort,0,$currIndex);
    			$langTmp[$currShort] = $key;
    		}
    		$flagDel = UebModel::model('AliexpressAttributeLanguageMap')->deleteAll('attribute_id="'.intval($attribute->id).'"');
    		foreach( $attribute->names as $k => $v ){
    			$dataLang = array(
    					'attribute_id'           	 => intval($attribute->id),
    					'language_code'				 => $langTmp[$k],
    					'attribute_name'   			 => $v
    			);
    			$flag = $this->dbConnection->createCommand()->insert(AliexpressAttributeLanguageMap::model()->tableName(), $dataLang);
    		}
    		
    	}
    }
    
    /**
     * @desc 保存属性值 和 属性&属性值映射
     * @param unknown $attribute
     */
    public function saveAttributeValue( $attribute ) {
    	if( isset($attribute->id) ){
    		$attrValues = isset($attribute->values) ? $attribute->values : '';
    		if ( empty($attrValues) ) return false;
    		
    		foreach( $attrValues as $attrVal ){
    			$data = array(
    					'attribute_value_id'        => intval($attrVal->id),
    					'attribute_value_cn_name'	=> trim( $attrVal->names->zh ),
    					'attribute_value_en_name'   => trim( $attrVal->names->en ),
    					'create_time'      			=> date('Y-m-d H:i:s')
    			);
    			$ret = UebModel::model('AliexpressAttributeValue')->getAttrValByAttrValId(  intval($attrVal->id) );
    			if($ret){
    				$flag = $this->dbConnection->createCommand()->update(AliexpressAttributeValue::model()->tableName(), $data ,'attribute_value_id = "'.intval($attrVal->id).'"');
    			}else{
    				$flag = $this->dbConnection->createCommand()->insert(AliexpressAttributeValue::model()->tableName(), $data);
    			}
    			
    			
    			$dataMap = array(
    					'attribute_id'        	=> intval($attribute->id),
    					'attribute_value_id'	=> intval($attrVal->id)
    			);
    			$ret = UebModel::model('AliexpressAttributeMap')->getMapByAttrIdAndValId(  intval($attribute->id),intval($attrVal->id) );
    			if($ret){
    				$flag = $this->dbConnection->createCommand()->update(AliexpressAttributeMap::model()->tableName(),$dataMap,'attribute_id=:attribute_id and attribute_value_id=:attribute_value_id',array(':attribute_id'=>intval($attribute->id),':attribute_value_id'=>intval($attrVal->id)) );
    			}else{
    				$flag = $this->dbConnection->createCommand()->insert(AliexpressAttributeMap::model()->tableName(), $dataMap);
    			}
    			
    			/**拉取子属性 start**/
    			if( intval($attribute->id) && intval($attrVal->id) ) $this->getCategoryAttributes(false,intval($attribute->id), intval($attrVal->id));
    			/**拉取子属性 end.**/
    		}
    	}
    }
    
    /**
     * @desc 保存属性值的子属性
     * @param unknown $attribute
     * @param unknown $parentAttrId
     * @param unknown $parentAttrValId
     */
    public function saveAttributeValMap($attribute, $parentAttrId, $parentAttrValId) {
    	$data = array(
    			'attribute_id'                  => intval($parentAttrId),
    			'attribute_value_id'            => intval($parentAttrValId),
    			'attribute_value_children_id'   => intval($attribute->id) //子属性id
    	);
    	$attrValIds = '';
    	if ( isset($attribute->values) ) {
    		foreach ($attribute->values as $key => $val) {
    			$attrValIds .=  intval($val->id)  . ',';
    		}
    		$attrValIds = substr($attrValIds, 0, -1);
    	}
    	$data['attribute_value_children_values'] = $attrValIds;
    	
    	$ret = UebModel::model('AliexpressAttributeValueMap')->getAttrValMapByCondition(  intval($parentAttrId),intval($parentAttrValId),intval($attribute->id) );
    	if($ret){
    		$flag = $this->dbConnection->createCommand()
    				->update(
    							AliexpressAttributeValueMap::model()->tableName(), 
    							$data, 
    							'attribute_id=:attribute_id and attribute_value_id=:attribute_value_id and attribute_value_children_id=:attribute_value_children_id',
    							array( ':attribute_id'=>intval($parentAttrId),':attribute_value_id'=>intval($parentAttrValId),':attribute_value_children_id'=>intval($attribute->id) )
    						);
    	}else{
    		$flag = $this->dbConnection->createCommand()->insert(AliexpressAttributeValueMap::model()->tableName(), $data);
    	}
    	
    }
    
    /**
     * @desc 保存
     */
    public function saveRecord($params){
    	return $this->dbConnection->createCommand()->insert(self::tableName(), $params);
    }
    
    /**
     * @desc 删除分类属性
     */
    public function deleteRecord( $categoryId ){
    	return $this->dbConnection->createCommand()->delete(self::tableName(),'category_id = "'.$categoryId.'"');
    }
    
    /**
     * @desc 删除分类中的某个属性
     */
    public function deleteRecordByAttributeId( $categoryId, $attribute_id ){
    	return $this->dbConnection->createCommand()->delete(self::tableName(),'category_id = "'.$categoryId. '" and attribute_id = "' . $attribute_id . '"');
    }
    
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
    }
    
    /**
     * @desc 获取分类的必填属性ids
     * @param unknown $categoryID
     * @return Ambigous <multitype:, mixed>
     */
    public function getCategoryRequiredAttributeList($categoryID) {
    	return $command = $this->getDbConnection()->createCommand()
    		->select("*, t1.attribute_name")
    		->from(self::tableName() . ' t')
    		->leftJoin("ueb_aliexpress_attribute_language_map t1", "t.attribute_id = t1.attribute_id")
    		->where("t.category_id = :category_id", array(':category_id' => $categoryID))
    		->andWhere("t.attribute_required = 1")
    		->andWhere("t1.language_code = 'Chinese'")
    		->andWhere("t.sku <> 1")
    		->queryAll();
    }
    
    /**
     * @desc 获取分类下指定属性
     * @param unknown $categoryID
     * @param unknown $attributeID
     * @return mixed
     */
    public function getCategoryAttribute($categoryID, $attributeID) {
    	return $this->getDbConnection()->createCommand()
    		->from(self::tableName())
    		->select("*")
    		->where("category_id = :category_id", array(':category_id' => $categoryID))
    		->andWhere("attribute_id = :attribute_id", array(':attribute_id' => $attributeID))
    		->queryRow();
    }


    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return [type]         [description]
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }
}