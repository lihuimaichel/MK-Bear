<?php
/**
 * @desc 产品分类关联产品品类表
 * @author lihy
 *
 */
class ProductClassToOnlineClass extends ProductsModel{
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	
	public function tableName(){
		return "ueb_product_class_to_online_class";
	}
	
	
	/**
	 * 得到产品一级分类
	 * @param number $classId
	 * @return multitype:Ambigous <>
	 */
	public function getCateName1($classId=0){
		$catArr = array();
		$select = ' 1=1 ';
		if($classId){
			$select .= ' AND cate_id1 ='.$classId;
		}

		$cats = ProductCategoryOnline::model()->findAll(array(
				'select'=>array('distinct cate_id1','cate_name1')
		));
		foreach ($cats as $catInfo){
			$catArr[$catInfo->cate_id1] = $catInfo->cate_name1;
		}
		//echo '<pre>';print_r($catArr);die;
		return $catArr;
	}
}