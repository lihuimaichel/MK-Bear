<?php
/**
 * @desc Lazada分类属性
 * @author Gordon
 * @since 2015-08-15
 */
class LazadaCategoryAttribute extends LazadaModel{
    
    const TYPE_CATEGORY_ATTRIBUTE = 'category_attribute';
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 标明系统属性
     */
    public static function getSystemConfig(){
        return array(
                'min_delivery_time',
                'max_delivery_time',
                'tax_class',
                'warranty_type',
                'warranty',
                'return_policy',
                'buyer_protection_details_txt',
                'manufacturer_txt',
                'TaxClass',
                'ShipmentType',
        );
    } 
    
    public static function getNecessaryConfig(){
        return array(
                'name',
                'name_ms',
                'browse_nodes',
                'primary_category',
                'categories',
                'special_price',
                'price',
                'special_from_date',
                'special_to_date',
                'short_description',
                'description',
                'description_ms',
                'package_content',//included
                'seller_promotion',
                'video',
                'sku_supplier_source',
                'parent_sku',
                'barcode_ean',//UPC/EAN
                'quantity',
                'variation',
                'package_weight',
                'package_width',
                'package_length',
                'package_height',
                'product_weight',
                'product_measures',
                'brand',
                'SellerSku',
                '__images__',
        );
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
        return 'ueb_lazada_category_attribute';
    }
    
    /**
     * @desc 根据分类ID查找属性
     */
    public function getCategoryAttrbuteByCategoryID($categoryID){
        //获取顶级分类ID
        $topID = LazadaCategory::model()->getTopCategory($categoryID);
        return $this->dbConnection->createCommand()
                    ->select('value')
                    ->from(self::tableName())
                    ->where('category_id = '.$topID)
                    ->andWhere('type = "'.self::TYPE_CATEGORY_ATTRIBUTE.'"')
                    ->queryColumn();
    }
    
    /**
     * @desc 过滤掉系统属性
     */
    public static function filterSystemConfig($name){
        $systemConfig = self::getSystemConfig();
        $necessaryConfig = self::getNecessaryConfig();
        if( in_array($name,$systemConfig) || in_array($name,$necessaryConfig) ){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * @desc 从线上抓取分类属性
     */
    public static function getCategoryAttributeOnline($siteID, $acconutID, $PrimaryCategory){
        //$PrimaryCategory = 4510
        $request = new GetCategoryAttributesRequest();
        $request->setPrimaryCategory($PrimaryCategory);
        $request->setSiteID($siteID);
        $response = $request->setAccount($acconutID)->setRequest()->sendRequest()->getResponse();
        return $response;
        if( $request->getIfSuccess() ){
            
        }
    }


    /**
     * @desc 新接口从线上抓取分类属性
     * @author hanxy
     * @since  2016-12-16
     */
    public static function getCategoryAttributeOnlineNew($acconutID, $PrimaryCategory){
        $request = new GetCategoryAttributesRequestNew();
        $request->setPrimaryCategory($PrimaryCategory);
        $response = $request->setApiAccount($acconutID)->setRequest()->sendRequest()->getResponse();
        return $response;
    }


    /**
     * @从ueb_lazada_category_attribute表中取出类目属性,并转换成html格式
     * @param int   $categoryID              类目ID
     * @param bool  $size_multi_variation    是否是主sku
     * @param array $productInfo             产品属性
     * @return string
     */
    public function getCategoryAttributeToHtml($categoryID, $size_multi_variation = false, $productInfo){
        $htmlAttributeString = '';
        $response = LazadaCategoryAttribute::model()->getCategoryAttributeOnlineNew($productInfo['account_id'], $categoryID);
        if(isset($response->Body->Attribute) && !empty($response->Body->Attribute)){
            //禁止引用外部xml实体
            $val = json_decode(json_encode($response->Body),true);
            foreach ($val['Attribute'] as $key => $info) {
                $valueText = '';
                $htmlAttribute = '';
                $names = $info['name'];
                //size在上面多属性的时候，下面不显示size
                if($names == 'size' && $size_multi_variation == true){
                    continue;
                }

                if($this->filterSystemConfig($names)){
                    if($names == 'warranty_type'){
                        $info['inputType'] = 'richText';
                        $info['options'] = '';
                    } else {
                        continue;
                    }
                }

                $label = $info['label'];
                $isMandatory = $info['isMandatory'];
                $trimLabel = str_replace(array('_',' '), '', strtolower($names));
                $valueText = isset($productInfo[$trimLabel])?$productInfo[$trimLabel]:'';
                $description = isset($info['Description'])?$info['Description']:null;
                switch ($info['inputType']) {
                    case 'singleSelect':
                        $htmlAttribute .= '<select id="'.$names.'" name="attributeInfo['.$names.']">';
                        $htmlAttribute .= '<option value="">Please Select</option>';
                        foreach ($info['options'] as $option) {
                            foreach ($option as $k => $v) {
                                $singleValue = ($v['name'] == $valueText)?' selected = "selected"':'';
                                $htmlAttribute .= '<option'.$singleValue.'>'.$v['name'].'</option>';
                            }
                        }
                        $htmlAttribute .= '</select>';
                        break;
                    case 'text':
                        if($names=='model'){
                            $isMandatory = 1;
                            $valueText = $productInfo['seller_name'].'-'.$productInfo['seller_sku'];
                        }
                        $htmlAttribute .= '<input type="text" name="attributeInfo['.$names.']" value="'.$valueText.'" />';
                        break;
                    case 'numeric':
                        $htmlAttribute .= '<input type="text" name="attributeInfo['.$names.']" value="'.$valueText.'" />';
                        break;
                    case 'richText':
                        if($names=='warranty_type'){
                            $valueText = 'No Warranty';
                        }
                        $htmlAttribute .= '<input type="text" name="attributeInfo['.$names.']" value="'.$valueText.'" />';
                        break;
                    case 'multiSelect':
                        $htmlAttribute .= '<div style="float:left;width:900px;"><ul class="multi_select">';
                        foreach ($info['options'] as $option) {
                            foreach ($option as $k => $v) {
                                $checked = ($valueText == $v['name'])?'checked':'';
                                $htmlAttribute .= '<li>'
                                    . '<input name="attributeInfo['.$names.']['.$v['name'].']" type="checkbox" '.$checked.' value="'.$v['name'].'" id="'.$names.'_'.$v['name'].'" />'
                                    . '<label for="'.$names.'_'.$v['name'].'">'.$v['name'].'</label>';
                                if($names=='color_family'){
                                        $htmlAttribute .= '<div style="display:inline-block;border:1px #ccc solid;width:10px;height:10px;background:'.$v['name'].';"></div>';
                                }
                                $htmlAttribute .= '</li>';
                            }
                        }
                        $htmlAttribute .= '</ul></div>';
                        break;
                    default:
                        break;
                }

                $htmlAttributeString .= '<div class="row">'
                        . '<label for="'.$names.'">'.$label.($isMandatory==1 ? '<span class="required">*</span>' : '').'</label>'
                        . $htmlAttribute
                        . '<span class="attributeDoc" style="line-height:26px;margin-left:10px;">'.($description==null ? '' : $description).'</span>'
                        . '</div>';
            }
        }

        return $htmlAttributeString;
    }
}