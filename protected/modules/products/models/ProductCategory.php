<?php

/**
 * @package Ueb.modules.products.models
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class ProductCategory extends ProductsModel {
    
    /**
     * category parent id
     * 
     * @var type 
     */
     public $category_parent_id;
     
     /**
      * category attribute 
      * 
      * @var type 
      */
     public $category_attribute;
     
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
        return 'ueb_product_category';
    }
    
    public function rules() {
        $rules = array(         
            //array('category_cn_name,category_code,category_status,category_order,category_en_name,category_parent_id', 'required'), 
        	array('category_status,category_en_name,category_parent_id', 'required'),
            array('category_order', 'numerical', 'integerOnly'=>true),
            array('category_description', 'length', 'max'=>100),
            //array('category_cn_name,category_en_name', 'unique'),
        ); 
        
        return $rules;
    }    
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'category_cn_name'          => Yii::t('products', 'Category Chinese Name'),
            'category_en_name'          => Yii::t('products', 'Category English Name'),
            'category_code'             => Yii::t('products', 'Category Code'), 
            'category_description'      => Yii::t('products', 'Category Description'),
            'category_status'           => Yii::t('system', 'Status'),
            'category_order'            => Yii::t('system', 'Order'),           
            'category_parent_id'        => Yii::t('products', 'The Parent Category'),
            'category_attribute'        => Yii::t('products', 'Category attribute'),
//         	'attribute_sort'            => Yii::t('system', 'Order'),
        );
    }
    
    /**
     * get list pairs
     * 
     * @param array $values
     * @return array $data
     */
    public function getListPairs($values) {
        return $this->queryPairs('id,category_cn_name', array('IN', 'id', $values));              
    }

    /**
     * get tree cat list
     * 
     * @param type $status   
     * @return array $data
     */
    public function getTreeList($status = 1) {
        $selectObj = $this->getDbConnection()->createCommand()
                ->select('*')
                ->from(self::tableName());
        if ($status) {
            $selectObj->where("category_status = 1");
        }
        //$selectObj->andWhere('category_parent_id = 0');

        $list = $selectObj->order("category_level Desc, category_order Asc")
                ->queryAll();         
        $data = array();
        foreach ($list as $key => $val) {
            if (isset($data[$val['id']])) {
                $subcat = $data[$val['id']]['subcat'];
                unset($data[$val['id']]['subcat']);
                $data[$val['category_parent_id']]['subcat'][$val['id']] = array(
                    'id' => $val['id'],
                    'category_cn_name' => $val['category_cn_name'],
                    'category_parent_id' => $val['category_parent_id'],
                    'category_en_name' => $val['category_en_name'],
                    'subcat' => $subcat);
            } else {
                $data[$val['category_parent_id']]['subcat'][$val['id']] = array(
                    'id' => $val['id'],
                    'category_cn_name' => $val['category_cn_name'],
                    'category_parent_id' => $val['category_parent_id'],
                    'category_en_name' => $val['category_en_name'],
                    'subcat' => array());
            }
        }
        return $data[0]['subcat'];
    }
    
    /**
     * get product category list optioins
     * 
     * @return array
     */
    public function getListOptions($lang = 'cn') {
        $treelist = self::getTreeList();
        $data = $this->createListOptions($treelist);
        return $data;
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
            if (in_array($key, array( 'modify_user_id', 'modify_time', 'create_time', 'id'))) {
                continue;
            } else if ( $key == 'category_parent_id' ) {
                if ( $this->getIsNewRecord() ) {
                    $parentInfo = $this->findByPk($val);
                    $msg .= MHelper::formatInsertFieldLog($label, $parentInfo['category_cn_name']);
                } else {
                    $parentPairs = $this->queryPairs('id,category_cn_name', array('IN', 'id', array( $val, $this->beforeSaveInfo[$key])));                
                    $msg .= MHelper::formatUpdateFieldLog($label, $parentPairs[$this->beforeSaveInfo[$key]], $parentPairs[$val]);
                }                             
            } else { 
                if ( $this->getIsNewRecord() ) {
                    $msg .= MHelper::formatInsertFieldLog($label, $val);
                } else {
                    $msg .= MHelper::formatUpdateFieldLog($label, $this->beforeSaveInfo[$key], $val);
                }                                                     
            }                               
        } 
        return $msg; 
    }

    /**
     * get category list
     * @param   int $id category_parent_id default 0
     * 		     string  $space  defaulut null
     * @return array
     * @author Super 2013-12-19
     * 
     */

    public function getCatList($id=0,$space=''){
    	$data = self::getCategoryArr(CN);      	 	
    	static $new = array();
    	if($id > 0){
    		$space .= '|--';
    	}else
    		return array();
    	$cats = $this->getCat($id);
    	if( $cats )
    	{
    		foreach($cats as $catsInfo)
    		{
    			$id = $catsInfo['id'];
    			$language = $catsInfo['category_cn_name'] ? $catsInfo['category_cn_name'] : $catsInfo['category_en_name'];
    			$new[$id] = $space.$language;
	    		$this->getCatList($id,$space);
    		}
    	} 
    	return $new;	
    }
//     public function getCatList() {
//   //  	$data = self::getCategoryArr();
//     	//获得产品分类列表数组，先取出所有并在用的一级分类
//     	$new = array();
//     	$catsI = $this->getCat(0);
//     	foreach($catsI as $catsInfoI)
//     	{
//         	$idI = $catsInfoI['id'];
//     		$new[$idI] = $catsInfoI['category_cn_name'];    		 
//    			$catsII = $this->getCat($idI);
// 		    foreach($catsII as $catsInfoII)
// 		    {
// 		    	$idII = $catsInfoII['id'];
// 		    	$new[$idII] = "|--".$catsInfoII['category_cn_name'];
// 		    	$catsIII = $this->getCat($idII);
// 		    	foreach($catsIII as $catsInfoIII)
// 		    	{
// 		    		$idIII = $catsInfoIII['id'];
// 		    		$new[$idIII] = "|--|--".$catsInfoIII['category_cn_name']; 			
// 			    	$catsIV = $this->getCat($idIII);
// 			    	foreach($catsIV as $catsInfoIV)
// 			    	{
// 			    		$idIV = $catsInfoIV['id'];
// 			    		$new[$idIV] = "|--|--|--".$catsInfoIV['category_cn_name'];
// 			    		$catsV = $this->getCat($idIV);
// 			    		foreach($catsV as $catsInfoV)
// 			    		{
// 			    			$idV = $catsInfoV['id'];
// 			    			$new[$idV] = "|--|--|--|--".$catsInfoV['category_cn_name'];
// 			    			$catsVI = $this->getCat($idV);
// 			    			foreach($catsVI as $catsInfoVI)
// 			    			{
// 			    				$idVI = $catsInfoVI['id'];
// 			    				$new[$idVI] = "|--|--|--|--|--".$catsInfoVI['category_cn_name'];
// 			    			}
// 			    		}
// 			    	}
// 		    	}	
// 		 	}
//     }
//     return $new;
//     }
    //Super 2013-12-18 循环每个一级分类的id，用中文名和id组成数组，再分别获得其所有的子类id，循环每个子类id，得到中文名，最后组成数组
    public function getCat($category_parent_id){
    	$cats=$this->getDbConnection()->createCommand()
    	->select('id,category_cn_name,category_en_name,category_level')->from($this->tableName())
    	->where("category_parent_id = $category_parent_id and category_status = 1")->queryAll();
    	return $cats;
    }   
    //Nick 2013-9-14 获得传入id的全部子类id，以数组形式返回，若无子类，返回空。
    public function getSubArr($id){
    	$list=$this->getDbConnection()->createCommand()
    	->select('id')->from($this->tableName())->where("category_parent_id = '{$id}' and category_status = '1'")->queryAll();
    	return $list;
    }
    
    public function getAllIds($arr){
    	$ids=array();
    
    	foreach($arr as $key => $val){
    		$ids[] = $val['id'];
    		$sub = $this->getSubArr($val['id']);
    		if($sub){
    			$ids[] = $sub[0]['id'];
    			$this->getAllIds($sub);
    		}
    	}
    	return $ids;
    }
    
    /**
     * get all parent's ids 
     * @param  $id category id
     * @return string
     * @author Nick 2013-9-23
     */
    public function getParentList($id){
    	if(empty($id)) return '未定义';
		$categoryParentAndSonList = Yii::app()->cache->get('categoryParentandsonlist'.$id);
		if ( empty($categoryParentAndSonList ) )
		{
			$data = self::getCategoryArr(CN);
			$fid=$this->getParentId($id);
			$idArr=$this->getParentArr($fid);
			
			$num=count($idArr);
			$list1='';
			$list2='';
			for($i=$num-1;$i>=0;$i--){
				if($idArr[$i]=='0'){			
					continue;
				}else{					
					$list1.= $data[$idArr[$i]].'>>';
				}
			}		
				
			$list2.= $list1.$data[$id];
			$categoryParentAndSonList = $list2;
			Yii::app()->cache->set('categoryParentandsonlist'.$id, $categoryParentAndSonList, 60*60*720);
		}
    	return $categoryParentAndSonList;
    }
    
	public function getParentId($id){
    	$list=$this->getDbConnection()->createCommand()
    	->select('category_parent_id')->from($this->tableName())->where("id = '{$id}'")->queryRow();
    	return $list;
    }
    
    public function getParentArr($arr){
    	$ids=array();
    	$ids[] = $arr['category_parent_id'];
    	$sup = $this->getParentId($arr['category_parent_id']);
   		if($sup){
   			$ids[] = $sup['category_parent_id'];
   			$this->getParentArr($sup);
   		}	 
    	return $ids;
    }
    
    /**
     * get id and category name array
     * @param string $lan,cn==chinese,en==english
     * @return array
     * @author Nick 2013-9-24
     */
    public function getCategoryArr($lan=''){
    	static $data = array();
    	if ( empty($data) ) {
    		$selectObj = $this->getDbConnection()->createCommand()
    		->select('id,category_cn_name,category_en_name')->from($this->tableName());
    		$list = $selectObj->order("category_level Desc, category_order Asc")->queryAll();    		    	
    		$data = array();
    		foreach ($list as $key => $val) {
    			if($lan==CN){
    				$data[$val['id']] = $val['category_cn_name'] ? $val['category_cn_name'] : $val['category_en_name'];
    			}else if($lan==EN){
    				$data[$val['id']] = $val['category_en_name'];
    			}else{
    				$data[$val['id']] = $val['category_en_name'];
    			}
    		}
    	}
    	return $data;
    }
    
    /**
     * get created category id
     * @param none
     * @return string
     * @author Nick 2013-9-25
     */
    public function getCreateCatsId(){
    	$select=$this->getDbConnection()->createCommand()
    	->select('id')->from($this->tableName())->order("id Desc")->limit(1)->queryRow();
    	return $select['id'];
    }
    
    public function createListOptions($vars, $lang = 'cn') {
        $data = array();
        foreach ($vars as $key => $val) {          
            $text = $lang === 'cn' ? $val['category_cn_name'] : $val['category_en_name'];              
            if ( empty($val['subcat']) ) {
            	
                $data[$val['id']] = $text;
            } else {
            	
                $data[$text] = $this->createListOptions($val['subcat'], $lang);
            }
        }
        return $data;
    }
    
    /**
     * check is leaf
     * 
     * @param string $id
     * @return Boolean
     */
    public function checkLeaf($id) {
        $row = $this->find("category_parent_id = '{$id}'");
        
        return empty($row) ? true : false;
    }

    /**
     * get category attr
     * @parma Integer $index
     */
    public function getCatAttr($index,$category_id) {
        $row = '<tr>';
        $row .= '<td class="category-attr-td" width="50px"  >';
        $row .= Yii::t('products', 'Attribute');               
        $row .= '</td>';
        $row .= '<td class="category-attr-td" width="80px" >';              
        $row .= CHtml::dropDownList("categoryAttribute[attribute_id][$index]", '',UebModel::model('productAttribute')->queryPairs('id,attribute_name','attribute_is_public = 0'), array( 'empty' => Yii::t('system', 'Please Select'), 'validation' => 'ProductCategory'));
        $row .= '</td>';
        $row .= '<td class="category-attr-td"  width="50px">';
        $row .= Yii::t('products', 'Is required');
        $row .= '</td>';
        $row .= '<td class="category-attr-td"  width="50px">';
        $row .= CHtml::checkBox("categoryAttribute[attribute_is_required][$index]");
        $row .= '</td>';
        $row .=	'<td class="category-attr-td"  width="50px">';
        $row .= Yii::t('system', 'Order');
        $row .= '</td>';
        $row .= '<td class="category-attr-td"  width="50px">';
        $row .= CHtml::textField("categoryAttribute[attribute_sort][$index]", '', array( 'size' => 4));
        $row .= '</td>';
        $row .= '<td>';
        $row .= '<a class="btn-delete-attr" onclick="delCategoryAttr(this,'.$index.','.$category_id.');" href="javascript:void(0);">'.Yii::t('system', 'Delete').'</a>'; 
        $row .= '</td>';
        $row .= '</tr>'; 
        
        return $row;
    }

    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/products/productcat/index');
    } 
    
    
    /**
     * get product category list optioins
     *
     * @return array
     */
    public function getCategoryList($lang = 'cn') {
    	$treelist = self::getTreeList();
    	$data = array();
    	if($treelist){
    		$data = $this->createListOptions($treelist);
    	}
    	return $data;
    }
    
    
    
    /**
     * get Is Used Status
     * @author Nick 2013-10-24
     */
    public function getIsUsedStatus($attributeId,$categoryId){
    	$attributeProductIdArr = UebModel::model('productselectattribute')->getProductIdByAttributeId($attributeId);
   		$categoryProductIdArr = UebModel::model('product')->getProductIdByCategoryId($categoryId);
   		
   		if(array_intersect($attributeProductIdArr, $categoryProductIdArr)){
   			return true;
   		}else{
   			return false;
   		}
    }

    /**
     * Validate
     * @author Nick 2013-12-2
     */
    public function validate(){
    	$return = parent::validate();
    	$request = $_REQUEST;
    	$msg = self::customValidate();
    	if ( $msg ) {
    		$this->addError('ProductCategory', $msg);
    		$return = false;
    	}
    	
    	return $return;
    }
    
    /**
     * custom validate
     *
     * @param array $request
     * @param string $fieldName
     * @author Nick 2013-12-2
     */
    public function customValidate($fieldName = null) {
    	$request = $_REQUEST;
    	$msg = '';
    	if( isset($_REQUEST['categoryAttribute']['attribute_id']) ){
	    	$attributeIdArr = array_unique($_REQUEST['categoryAttribute']['attribute_id']);
	    	if(count($attributeIdArr) < count($_REQUEST['categoryAttribute']['attribute_id'])){
	    		return $msg = '属性不能重复';
	    	}
    	}
    }
    
    /**
     * get product category by id
     * @param int id
     * @return array $select
     */
    public function getProductCategoryById($id,$field='*'){
    	$select=$this->getDbConnection()->createCommand()
    	->select($field)
    	->from($this->tableName())
    	->where("id = $id")
    	->queryRow();
    	return $select;
    }
    
    /*
     * SKU的分类
     */
    public function getEnnameBySku($sku){
    	$select=$this->getDbConnection()->createCommand()
    	->select('product_category_id')
    	->from('ueb_product')
    	->where("sku = '{$sku}'")
    	->queryRow();    	
    	$id=$select['product_category_id'];
    	$enname=$this->getDbConnection()->createCommand()
    	->select('category_en_name')
    	->from($this->tableName())
    	->where("id = '{$id}'")
    	->queryRow();    	
    	return $enname;
    }
    
    /**
     * 根据ebay类别同步本地产品类别
     */
    public function updateProductCategory(){
    	$categories = UebModel::model('EbayCategory')->getCategoriesBySiteId(0);
    	foreach($categories as $category){
    		$params = array(
    				'id' 					=> $category['category_id'],
    				'category_parent_id' 	=> ($category['parent_id'] == $category['category_id']) ? 0 : $category['parent_id'],
    				'category_en_name' 		=> $category['category_name'],
    				'category_level' 		=> $category['level'] - 1,
    				'category_status' 		=> 1,
    		);
    		$this->saveData($params);
    	}
    }
    
    /**
     * 翻译分类
     * @author Gordon
     */
    public function translateProductCategory(){
    	$categories = $this->getDbConnection()->createCommand()
    				->select('id,category_en_name')
    				->from(self::tableName())
    				->where('category_status = 1')
    				->andWhere('category_cn_name = ""')
    				->queryAll();
    	foreach($categories as $category){
    		$cnName = UebModel::model('language')->translate('en', 'cn', $category['category_en_name']);
    		$this->updateByPk($category['id'], array('category_cn_name'=>$cnName));
    	}
    }
    
    /**
     * 保存记录
     * @param array $columns
     */
    public function saveData($columns){
    	$model = new self();
    	foreach($columns as $key=>$value){
    		$model->setAttribute($key, $value);
    	}
    	$model->save();
    }

    /**
     * 根据type_id得类型名称
     * @param	string	$id
     * @return	string
     * @author	tan
     * @since	2013-07-24
     */
    public function getProductCategoryNameById($id, $lang='cn') {
    	$id = intval($id);
    	$select=$this->getDbConnection()->createCommand()
    	->select('category_cn_name,category_en_name')
    	->from($this->tableName())
    	->where("id = $id")
    	->queryRow();
    	return $typeName = ($lang == 'cn') ? $select['category_cn_name'] : $select['category_en_name'];
    }

    /**
     * @desc 获取子类的所有父类
     * @author Gordon
     * @since 2014-08-06
     * @param int $id
     */
    public function getAllParentByCategoryId($id){
    	$catArr = array();
    	$catInfo = $this->findByPk($id);
    	if( $catInfo==null ){
    		return $catArr;
    	}
    	$parent = $catInfo->category_parent_id;
    	$catArr[$catInfo->category_level] = $id;
    	while( $parent > 0){
    		$parentInfo = $this->findByPk($parent);
    		if($parentInfo==null){
    			return $catArr;
    		}
    		$catArr[$parentInfo->category_level] = $parent;
    		$parent = $parentInfo->category_parent_id;
    	}
    	return $catArr;
    }
    

    /**
     * 根据分类id获取分类父级名称
     * 格式： 娃娃>>Dolls>>Antique (Pre-1930)>>Other
     * @param array $categoryIds：分类id数组
     * @return multitype:string
     */
    public function getListParentByIds($categoryIds){
    	$result = array();
    	if(empty($categoryIds)) return $result;
    	$list = $this->findAll("id in(".implode(',',$categoryIds).")");
    	$sep = '>>';
    	if ($list){
    		foreach ($list as $key=>$val){
    			$str = '';
    			$arr = $this->getAllParentByCategoryId($val['id']);
    			if($arr){
	    			ksort($arr);
	    			foreach($arr as $id){
	    				$data = $this->findByPk($id);
	    				if($data){
	    					$catName = !empty($data->category_cn_name) ? $data->category_cn_name : $data->category_en_name;
	    					$str .= $catName.$sep;
	    					$result[$val['id']] = rtrim($str,$sep);
	    				}
	    			}
    			}
    		}
    	}
    	return $result;
    }
    
    
    
}