<?php
/**
 * @desc Amazon分类 控制器
 * @author Liz
 *
 */
class AmazoncategoryController extends UebController {
    /**
     * @desc 查询子分类记录
     * 
     */
    public function actionFindsubcategory() {
        $categoryID = Yii::app()->request->getParam('category_id');
        $categoryInfos = AmazonCategory::model()->getSubCategoryList($categoryID);
        if ($categoryInfos) {
            $categoryList = array();
            foreach ($categoryInfos as $categoryInfo) {
                $categoryList[$categoryInfo['id']] = $categoryInfo['en_name'];
            }
            echo $this->successJson(array(
                'category_list' => $categoryList
            ));
        } else {
            echo $this->failureJson(array(
                'message' => Yii::t('amazon_product', 'Could not Find Subcategories'),
            ));
        }
    }  

    /**
     * @desc 查找分类属性
     */
    public function actionFindcategoryattributes() {
        $categoryID = Yii::app()->request->getParam('category_id');
        $sku        = Yii::app()->request->getParam('sku');
        $addID      = Yii::app()->request->getParam('addid');   //修改时才有的ID

        $skuInfo = Product::model()->getProductInfoBySku($sku);
        $addInfo = AmazonProductAdd::model()->getInfoById($addID);
        if (empty($skuInfo)) {
            echo json_encode(array());
            Yii::app()->end();
        }

        $feed_product_type  = '';
        $item_type          = '';
        $topProductTypeText = '';
        $subProductTypeText = '';
        $categoryAttributes = array();
        $category_type_list = array();
        //查找分类下面sku属性
        $skuAttributes = AmazonCategory::model()->getCategoryAttributeList($categoryID);
        $tmpSkuAttributes = $skuAttributes;
        
        $cate_info = AmazonCategory::model()->getCategoryInfoByID($categoryID);    
        if ($cate_info){
            //查找对应的上传分类
            $feed_product_type = $cate_info['feed_product_type'];
            if(!empty($feed_product_type)) $feed_product_type = strtolower(str_replace("_","",$feed_product_type));
            //分类商品类型(item_type)
            $item_type = AmazonCategory::model()->getCategoryItemType($cate_info['category_id'],$cate_info['category_path_name']);

        }
        //从XSD上传分类取数据
        if ($feed_product_type){
            $category_type_list = AmazonCategoryXSD::model()->getCategoryProductTypeList($feed_product_type);
        }

        //配置从亚马逊分类选择后对应的上传分类类型     
        $current_category_list = array();   //指定的当前分类类型
        if ($category_type_list){
            foreach($category_type_list as $val){
                $current_category_info = array();
                $top_category_info = array();
                //如果为顶级分类（parent_id==0），则默认指定此顶级分类下的第一个分类
                if ($val['parent_id'] == 0){
                    $current_category_info = AmazonCategoryXSD::model()->getFirstCategoryInfoById($val['id']);
                    if($current_category_info){
                        $current_category_info['parent_title'] = $val['title'];
                    }else{
                        $current_category_info = $val;
                        $current_category_info['parent_title'] = '';
                    }
                    $current_category_list[$val['title']] = $current_category_info;
                }else{
                    $top_category_info = AmazonCategoryXSD::model()->getTopCategoryinfoByParentId($val['parent_id']);
                    if($top_category_info) $top_name = $top_category_info['title'];
                    if($top_name) $val['parent_title'] = $top_name;
                    if($top_name) $current_category_list[$top_name] = $val;
                }
                
            }
        }

        //如果是修改操作
        if ($addInfo && !empty($addInfo['product_type_text'])){
            $productTypeTextArr = explode('.',$addInfo['product_type_text']);
            $topProductTypeText = $productTypeTextArr[0];
            $subProductTypeText = isset($productTypeTextArr[1]) ? $productTypeTextArr[1] : '';   
            $current_category_list = array();
            $current_category_list[$topCate] = array(
                'id'           => ($addInfo['product_type_id'] > 0) ? $addInfo['product_type_id'] : 0,
                'title'        => $subProductTypeText,
                'parent_title' => $topProductTypeText,
                );
        }

        //如果关联不到对应XSD分类，则列出所有XSD顶级分类列表
        $top_category_list = array();
        // if (!$current_category_list){
           $top_category_list = AmazonCategoryXSD::model()->getTopCategoryList();
        // }
        // MHelper::printvar($current_category_list);

/*
        $productID = null;
        $productMainID = null;
        if ($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN)
            $productMainID = $skuInfo['id'];
        else
            $productID = $skuInfo['id'];
        $k = 0;
        $tmpSkuAttributes = array();    //解决前端json排序的问题，将attribute_id健换成0开始的数字
        $selectedSkuAttributes = array();
        $omsProductAttributesMap = array();
        foreach ($skuAttributes as $attributeID => $attributes) {
            //查找产品在OMS对应的属性
            $omsAttributeID = AttributeMarketOmsMap::model()->getOmsAttrIdByPlatAttrId(Platform::CODE_ALIEXPRESS, $attributeID);
            if (empty($omsAttributeID)) {
                $tmpSkuAttributes[$k] = $skuAttributes[$attributeID];
                $k++;
                continue;
            }
            //OMS上SKU对应的属性值列表
            $omsProductAttributes = ProductSelectAttribute::model()->getAttributeValList($omsAttributeID, null, $skuInfo['id']);
            $omsProductAttributesMap[$attributeID] = $omsProductAttributes;
            if (empty($omsProductAttributes)) {
                $tmpSkuAttributes[$k] = $skuAttributes[$attributeID];
                $k++;
                continue;
            }
            $tmpValueList = $attributes['value_list'];
            //查找OMS选中属性值对应平台属性值
            foreach ($omsProductAttributes as $omsProductAttribute) {
                foreach ($tmpValueList as $key => $list) {
                    //如果SKU的值和平台属性值对应上
                    if (trim($omsProductAttribute['attribute_value_name']) == trim($list['attribute_value_cn_name'])) {
                        $skuAttributes[$attributeID]['value_list'][$key]['selected'] = true;
                        $skuAttributes[$attributeID]['value_list'][$key]['sku'] = $omsProductAttribute['sku'];
                        $selectedSkuAttributes[$attributeID][$key]['attribute_id'] = $omsProductAttribute['attribute_id'];
                        $selectedSkuAttributes[$attributeID][$key]['pla_attribute_id'] = $attributeID;
                        $selectedSkuAttributes[$attributeID][$key]['pla_attribute_val_id'] = $list['attribute_value_id'];
                        $selectedSkuAttributes[$attributeID][$key]['attribute_value_id'] = $omsProductAttribute['attribute_value_id'];
                        unset($tmpValueList[$key]);
                        break;
                    } else {
                        $skuAttributes[$attributeID]['value_list'][$key]['selected'] = false;
                        $skuAttributes[$attributeID]['value_list'][$key]['sku'] = '';
                    }
                }
            }
            $tmpSkuAttributes[$k] = $skuAttributes[$attributeID];
            $k++;
        }
        $selectedSKUMap = array();
        //OMS上SKU对应的属性值列表（提取出来的，不含其它值）
        foreach ($omsProductAttributesMap as $skumap){
            foreach ($skumap as $skus){
                foreach ($selectedSkuAttributes as $sattrs){
                    foreach ($sattrs as $sattr){
                        if($skus['attribute_id'] == $sattr['attribute_id'] && $skus['attribute_value_id'] == $sattr['attribute_value_id']){
                            $selectedSKUMap[$skus['sku']][$sattr['pla_attribute_id']] = $sattr['pla_attribute_val_id'];
                        }
                    }
                    
                }
            }
            
        }

        echo '================================================================ $omsProductAttributesMap   ';
        print_r($omsProductAttributesMap);
        echo '================================================================ $selectedSkuAttributes   ';
        print_r($selectedSkuAttributes);
        echo '================================================================ $tmpSkuAttributes   ';
        print_r($tmpSkuAttributes);
        echo '================================================================ $selectedSKUMap   ';
        print_r($selectedSKUMap);
        echo '================================================================ $selectedSKUMap   ';
        
        foreach ($selectedSKUMap as $sku=>$skus){
            $str = array();
            ksort($skus);
            foreach ($skus as $key=>$_sku){
                $str[] = $key;
                $str[] = $_sku;
            }
            $selectedSKUMap[$sku] = implode("-", $str);
        }
        $selectedSKUMap = array_flip($selectedSKUMap);
        unset($selectedSkuAttributes, $omsProductAttributesMap);
        print_r($selectedSKUMap);exit;


        */
        //print_r($selectedSKUMap);
        //@TODO 普通属性对应OMS属性
        //$omsProductAttributes = ProductSelectAttribute::model()->getAttributeValList($attributeID, null, $skuInfo['id']);
        // if (!$tmpSkuAttributes) {
        //     echo  $this->failureJson(array(
        //         'message' => Yii::t('aliexpress_product', 'Could not Find Attributes'),
        //     ));
        // } else {

            $selectedSKUMap = array();
            $categoryAttributes = array(
                    'statusCode'        => '200',
                    'sku_attributes'    => $tmpSkuAttributes,
                    'common_attributes' => array(),
                    'selected_sku_map'  => $selectedSKUMap,
                    'selected_cateogry' => $current_category_list,
                    'top_category'      => $top_category_list,
                    'item_type'         => $item_type,
                    'product_type'      => $feed_product_type,
            ); 
            // MHelper::printvar($categoryAttributes);
            echo json_encode($categoryAttributes);
        // }
    }   


    /**
     * @desc查询关键字获取分类列表
     * @param $keyword 搜索关键字
     */
    public function actionGetCategoryListByKeyword() {
        $list = array();
        $keyword = Yii::app()->request->getParam('keyword');
        $keyword = trim($keyword);
        $categoryList = AmazonCategory::model()->getCategoryListByKeyword($keyword);
        if ($categoryList) {
            $list['statusCode'] = '200';
            $list['categoryList'] = $categoryList;
            
        }
        echo json_encode($list);
    } 

}