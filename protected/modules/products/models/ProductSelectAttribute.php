<?php
/**
 * @desc oms 产品所选择的属性  model
 * @author wx
 * 2015-09-22
 */
class ProductSelectAttribute extends ProductsModel {      

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
    public function tableName() {
        return 'ueb_product_select_attribute';
    }

    public function fullTableName()
	{
		return 'ueb_product.ueb_product_select_attribute';
	}

    public function attributeLabels() {
        return array( 
            'id'                            => Yii::t('system', 'No.'),    
            'multi_sku'                     => Yii::t('product', 'Multi Sku'),
            'modify_user_id'                => Yii::t('system', 'Modify User'),            
            'modify_time'                   => Yii::t('system', 'Modify Time'),
        );
    }

    public $multi_sku = null;
    
    /**
     * @desc 获取产品属性信息
     * @param integer $attributeId ,integer $productId ,integer $multiProductId
     */
    public function getAttributeValList( $attributeId,$productId,$multiProductId = 0 ){
    	$conditions = '';
    	if( $productId && !$multiProductId){
    		$conditions = 'a.product_id = "'.$productId.'"';
    	}elseif( $multiProductId && !$productId){
    		$conditions = 'a.multi_product_id = "'.$multiProductId.'"';
    	}elseif( $multiProductId && $productId ){
    		$conditions = 'a.multi_product_id = "'.$multiProductId.'" and a.product_id = "'.$productId.'"';
    	}
    	$ret = $this->dbConnection->createCommand()
    			->select( 'a.sku,a.attribute_id,a.attribute_value_id,b.attribute_value_name' )
    			->from( $this->tableName().' as a' )
    			->join(UebModel::model('ProductAttributeValue')->tableName().' as b', 'a.attribute_value_id = b.id')
    			->where('a.attribute_id="'.$attributeId.'"')
    			->andWhere( $conditions )
    			->queryAll();
    	
    	return empty($ret)?array():$ret;
    	
    }
    
	public function getMainSku($productID = null, $sku = null) {
		$command = $this->getDbConnection()->createCommand()
			->select("b.sku")
			->from(self::tableName() . ' a')
			->join("ueb_product b", "a.multi_product_id = b.id");
		if (!is_null($productID))
			$command->andWhere("a.product_id = :product_id", array(':product_id' => $productID));
		if (!is_null($sku))
			$command->andWhere("a.sku = :sku", array(':sku' => $sku));
		$command->distinct = "a.multi_product_id";
		return $command->queryScalar();
	}
	
	/**
	 * @desc 获取SKU属性列表
	 * @param unknown $mainProductId
	 * @param string $otherWhere
	 * @param unknown $otherParams
	 * @return mixed
	 */
	public function getSelectedAttributeSKUListByMainProductId($mainProductId, $otherWhere = '', $otherParams = array()){
		$dbcommand = $this->getDbConnection()->createCommand()
								->from(self::tableName())
								->where('multi_product_id=:multi_product_id', array(':multi_product_id'=>$mainProductId));
		$dbcommand->andWhere($otherWhere, $otherParams);
		$result =$dbcommand->queryAll();
		return $result;
	}
	/**
	 * @desc 根据产品id来获取对应的sku属性
	 * @param unknown $productId
	 * @param string $otherWhere
	 * @param unknown $otherParams
	 * @return mixed
	 */
	public function getSelectedAttributeSKUListByProductId($productId, $otherWhere = '', $otherParams = array()){
		$dbcommand = $this->getDbConnection()->createCommand()
							->from(self::tableName())
							->where('product_id=:product_id', array(':product_id'=>$productId));
		$dbcommand->andWhere($otherWhere, $otherParams);
		$result =$dbcommand->queryAll();
		return $result;
	}
	
	/**
	 * @desc 获取产品属性id
	 * @param unknown $sku
	 * @return Ambigous <multitype:>
	 */
	public function getProductAttributeIds($sku){
		static $attributes = array();
		if( !isset($attributes[$sku]) ){
			$result = $this->getDbConnection()->createCommand()
							->from(self::tableName())
							->where('sku=:sku', array(':sku'=>$sku))
							->queryAll();
			
			$attributes[$sku] = array();
			foreach($result as $v){
				$attributes[$sku][] = $v['attribute_id'];
			}
		}
		return $attributes[$sku];
	}
        
        /**
	 * @desc 获取SKU属性及属性值列表
	 * @param unknown $mainProductId
	 * @param string $otherWhere
	 * @param unknown $otherParams
	 * @return mixed
	 */
	public function getSelectedAttributeValueSKUListByMainProductId($mainProductId, $otherWhere = '', $otherParams = array()){
		$dbcommand = $this->getDbConnection()->createCommand()
                    ->from(self::tableName().' a')
                    ->join(ProductAttribute::model()->tableName(). ' b', 'a.attribute_id = b.id')
                    ->leftJoin(ProductAttributeValue::tableName() .' av', 'a.attribute_value_id=av.id')
                    ->where('multi_product_id=:multi_product_id', array(':multi_product_id'=>$mainProductId));
		$dbcommand->andWhere($otherWhere, $otherParams);
		$result =$dbcommand->queryAll();
		return $result;
	}


    /**
     * @param $product_id
     * @return array|CDbDataReader
     */
	public function getSubSkuListing($product_id)
    {
        $dbcommand = $this->getDbConnection()->createCommand()
            ->select('DISTINCT(pa.sku) AS sku')
            ->from(self::tableName().' pa')
            ->leftJoin(UebModel::model('Product')->tableName().' AS p', 'pa.product_id = p.id')
            ->where('pa.multi_product_id=:multi_product_id', array(':multi_product_id'=>$product_id))
            ->andWhere('p.product_is_multi = 1 AND product_status = 4');

        $rows = array();
        $result =$dbcommand->queryAll();
        if (!empty($result)) {
            foreach ($result as $k=>$v) {
                $rows[] = $v['sku'];
            }
        }
        return $rows;
    }
	
	/**
	 * @desc 获取主产品下面的属性
	 * @param unknown $mainProductId
	 * @param string $otherWhere
	 * @param unknown $otherParams
	 * @return unknown
	 */
	public function getAttributeSKUListByMainProductId($mainProductId, $otherWhere = '', $otherParams = array()){
		$dbcommand = $this->getDbConnection()->createCommand()
							->from(self::tableName().' a')
							->leftJoin(ProductAttribute::tableName() .' at', 'a.attribute_id=at.id')
							->where('multi_product_id=:multi_product_id', array(':multi_product_id'=>$mainProductId))
							->group("a.attribute_id");
		$dbcommand->andWhere($otherWhere, $otherParams);
		$result =$dbcommand->queryAll();
		return $result;
	}
	
	/**
	 * @desc 通过给出的sku获取sku属性列表
	 * @param unknown $skus
	 * @param string $otherWhere
	 * @param unknown $otherParams
	 * @return mixed
	 */
	public function getSkuAttributeListBySku($skus, $otherWhere = '', $otherParams = array()){
		if(!is_array($skus)){
			$skus = array($skus);
		}
		if(is_array($skus))
			$skus = MHelper::simplode($skus);
		$dbcommand = $this->getDbConnection()->createCommand()
							->select('s.*, a.attribute_name, av.attribute_value_name')
							->from(self::tableName().' s')
							->leftJoin(ProductAttribute::model()->tableName() . ' a', 's.attribute_id=a.id')
							->leftJoin(ProductAttributeValue::tableName() .' av', 's.attribute_value_id=av.id')
                                                        ->where("s.sku in({$skus})");
		$dbcommand->andWhere($otherWhere, $otherParams);
		$result = $dbcommand->queryAll();
		$newresult = array();
		if($result){
			foreach ($result as $val){
				$newresult[$val['sku']][$val['attribute_name']] = $val['attribute_value_name'];
			}
		}
		return $newresult;
	}
	
	/**
	 * @desc 获取子sku列表
	 * @param unknown $productID
	 * @return multitype:unknown
	 */
	public function getChildSKUListByProductID($productID){
		//multi_product_id sku product_id
		$result = $this->getDbConnection()->createCommand()
							->from(self::tableName())
							->select('product_id,sku')
							->where('multi_product_id=:multi_product_id', array(':multi_product_id'=>$productID))
							->group('product_id')
							->queryAll();
		$childskus = array();
		if($result){
			foreach ($result as $val){
				$childskus[$val['product_id']] = $val['sku'];
			}
		}
		return $childskus;
	}

    /**
     * @desc 根据sku获取属性
     * @param unknown $sku
     * @param string $code
     * @return Ambigous <multitype:, mixed>
     */
    public function getAttIdsBySku($sku,$code=''){
    	$attId = ProductAttribute::model()->getAttributeIdByCode($code); //属性类别id
    	return $this->getDbConnection()->createCommand()
		    	->select('a.attribute_value_id')
		    	->from(self::tableName().' a')
		    	->leftJoin(ProductAttributeMap::tableName().' m', 'a.attribute_value_id = m.attribute_value_id')
		    	->where("a.attribute_value_id > 0 AND m.attribute_id = '{$attId}' AND a.sku = '{$sku}'")
		    	->queryColumn();
    }


    /**
     * @desc 根据产品属性值获取产品ID
     * @param integer $attributeValueId
     * @return array
     */
    public function getProductIdByAttributeId($attributeValueId){
    	$resultArr = '';
    	$result = $this->getDbConnection()->createCommand()
		    	->select('sku')
		    	->from(self::tableName())
		    	->where("attribute_value_id = '{$attributeValueId}'")
		    	->group('sku')
		    	->queryAll();

		if($result){
			foreach ($result as $key => $value) {
				$resultArr[] = $value['sku'];
			}
		}

		return $resultArr;
    }


    /**
     * @desc 根据产品id获取sku属性
     * @param integer $productId
     * @return array
     */
    public function getAttributeIdByProductId($productId){
    	$joinTable = UebModel::model('ProductAttribute')->tableName();
    	$data= $this->getDbConnection()->createCommand()
    	->select('a.attribute_id,b.attribute_code')
    	->from(self::tableName() . ' a')
    	->join($joinTable . ' b', "a.attribute_id=b.id AND b.attribute_is_public='0'")
    	->where("a.multi_product_id = '{$productId}'")
    	->group("a.attribute_id,b.attribute_code")
    	->queryAll();

    	return $data;
    }


    /**
     * @desc 根据产品id获取sku属性
     * @param integer $productId
     * @return array
     */
    public function getSelectedIdByProduct($productId){
		$proAttrValTab=UebModel::model('ProductAttributeValue')->tableName();		
		$proAttrTab	  =UebModel::model('ProductAttribute')->tableName();		
		$imageAttrId=UebModel::model('ProductAttribute')->getAttributeIdByAttributeCode(ProductAttribute::IMAGE_ATTR);			
    	$data= $this->getDbConnection()->createCommand()
    	->select('*')
    	->from(self::tableName())
    	->where("multi_product_id = '{$productId}' and attribute_id ='{$imageAttrId}'")
    	->group("attribute_value_id")
    	->queryRow();
    	if(isset($data)){
    		$info= $this->getDbConnection()->createCommand()
    		->select('b.id')
    		->from($proAttrValTab .' a')
    		->join($proAttrTab .' b', "b.attribute_code=a.attribute_value_name")
    		->where("a.id = '{$data['attribute_value_id']}'")   		
    		->queryRow(); 
    		if(isset($info)){
    			return $info['id'];
    		}  		
    	}    	
    }


    /**
     * get product attributes
     * @param integer $productId
     */
    public function getAttrList($productId) {      	
        $data = array();    
        $list = $this->findAllByAttributes(array('product_id' => $productId));
        foreach ($list as $val) {      	
            if (! isset($data[$val['attribute_id']]) ) {          
                $data[$val['attribute_id']] = $val['attribute_value_id'];
            } else {         
                if (! is_array($data[$val['attribute_id']])) {                  
                    $temp = $data[$val['attribute_id']];
                    unset($data[$val['attribute_id']]);
                    $data[$val['attribute_id']][] = $temp;
                } 
                $data[$val['attribute_id']][] = $val['attribute_value_id'];
            }
        }
        return $data;
    }


    /**
     * get multi attr ids by multi product id
     * @param integer $productId
     * @return array
     */
    public function getMultiAttIdsByMultiId($productId) {
         return $this->getDbConnection()->createCommand()
                    ->select('attribute_id')
                    ->from(self::tableName())                                    
                    ->where(" multi_product_id = '{$productId}'") 
                    ->andWhere(" attribute_is_multi = '1'")
                    ->queryColumn();
    }


    /**
     * get multi attribute ids by the product ID
     * @param integer $produtId
     * @return array
     */
    public function getMultiAttIds($productId) {
         return $this->getDbConnection()->createCommand()
                    ->select('attribute_id')
                    ->from(self::tableName())
                    ->where(" product_id = '{$productId}'")
                    ->andWhere(" attribute_is_multi = '1'")
                    ->queryColumn();
    }


    /**
     * get multi sku 
     * @param integer $productId
     */
    public function getMultiSku($productId) {
        $sku = '';
        $row = $this->findByAttributes(array(
            'product_id'            => $productId,
            'attribute_is_multi'    => 1
        ));
        if (! empty($row['multi_product_id']) ) {
            $info = UebModel::model('Product')->findByPk($row['multi_product_id']);
            if (! empty($info) ) {
                $sku = $info['sku'];
            }
        } 
        return $sku;
    }


    public function getSkuByMultiProId($id){
        if(empty($id)){
            return '';
        }
        $descTable=UebModel::model('Productdesc')->tableName();
        $notPublicAttrId=UebModel::model('ProductAttribute')->getNotPublicAttrId();     
        $data= $this->getDbConnection()->createCommand()
        ->select('sku,product_id,attribute_id,attribute_value_id')
        ->from(self::tableName())
        ->where("multi_product_id = '{$id}'")
        ->andwhere(array('IN', 'attribute_id', $notPublicAttrId))
        ->queryAll();       
        
        $skuList = array();
        if($data){          
            foreach ($data as $val){    
                $val['attributeName']=UebModel::model('ProductSelectAttribute')->getSubSkuAttributeName($val['sku']);                
                $arr[$val['sku']] = $val;
                $skuList[] = $val['sku'];
            }
                    
            $info= $this->getDbConnection()->createCommand()
            ->select('*')
            ->from($descTable)
            ->where( 'sku in('.MHelper::simplode($skuList).')' );
            
            $info = $info->queryAll();
            
            foreach($info as $k => $v){
                $arr[$v['sku']][$v['language_code']] = $v['title'];
            }
            return $arr;
        }else{
            return $data;
        }   
    }


    /**
     * getSubSkuAttributeName
     */
    public function getSubSkuAttributeName($sku){
        $notPublicAttrId=UebModel::model('ProductAttribute')->getNotPublicAttrId();
        $data=$this->getDbConnection()->createCommand()
        ->select('attribute_value_id')
        ->from(self::tableName())
        ->where("sku= '{$sku}'")
        ->andwhere(array('IN', 'attribute_id', $notPublicAttrId))
        ->queryAll();
        if(!empty($data)){
            $str='';
            foreach ($data as $val){
                $str.=UebModel::model('ProductAttributeValue')->getSubSkuAttriValueName($val['attribute_value_id']);
            }
        }
        return $str;
    }


    /**
     * get multi list by multi id
     * @param integer $productId
     * @return array
     */
    public function getMultiListByMultiId($productId) {   
        $notPublicAttrId=UebModel::model('ProductAttribute')->getNotPublicAttrId();
        array_push($notPublicAttrId,'0');
        $list = $this->findAllByAttributes(array('multi_product_id' => $productId,'attribute_id'=>$notPublicAttrId)); 
        $productIds = array();
        $data = array();       
        foreach ($list as $val ) {
           $productIds[] = $val['product_id'];         
           $data[$val['product_id']][$val['attribute_id']] = UebModel::model('ProductAttributeValue')->getAttributeValueNameById($val['attribute_value_id']);
        }  
        unset($list);
        $productIds = array_unique($productIds);
        $productPairs = UebModel::model('Product')->queryPairs('id,sku', array('IN', 'id', $productIds));      
        $result = array();
        foreach ($data as $key => $val) {                       
            $result[] = array(
                'product_id' => $key,
                'sku'        => $productPairs[$key],
                'multi'      => $val,                       
            );              
        }   
        return $result;
    }


    /**
     * getSonSkuByProduct
     */
    public function getSonSkuByProductId($productId){
        $data= $this->getDbConnection()->createCommand()
        ->select('sku')
        ->from(self::tableName())       
        ->where("multi_product_id = '{$productId}'")    
        ->queryAll();
        $arr=array();
        foreach ($data as $val){
            $arr[]=$val['sku'];
        }
        if($arr){
            $data= $this->getDbConnection()->createCommand()
            ->select('sku,product_cost,product_weight,gross_product_weight')
            ->from(UebModel::model('Product')->tableName())
            ->where(array('in','sku',$arr))
            ->queryAll();
            $info=array();
            foreach ($data as $value){
                $info[$value['sku']]['product_cost']=$value['product_cost'];
                $info[$value['sku']]['product_weight']=$value['product_weight'];
                $info[$value['sku']]['gross_product_weight']=$value['gross_product_weight'];
            }
            return $info;
        }       
    }


    /**
     * 判断是否是不能刊登的属性
     * @param string  $sku
     * @param string  $platformCode  平台
     * @return boolen
     */
    public function getForbiddenAttribute($sku,$platformCode){
        $forbiddenArr = array(); 
        if($platformCode == Platform::CODE_ALIEXPRESS){
            $forbiddenArr = array(4,10,13,6923);  //4--纯电池  10--危险品 13--超尺寸 6923--超重量
        }

        $flag = false;
        //获取产品信息
        $skuInfo = Product::model()->getProductInfoBySku($sku);
        if(!$skuInfo){
            $flag = true;
        }
        //取出产品属性
        $attributeIdsInfo = $this->getSelectedAttributeSKUListByProductId($skuInfo['id']);
        foreach ($attributeIdsInfo as $attValue) {
            if(in_array($attValue['attribute_value_id'], $forbiddenArr)){
                $flag = true;
                break;
            }
        }
        
        return $flag;
    }
}