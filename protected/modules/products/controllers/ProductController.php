<?php
/**
 * @desc 产品Model
 * @author Gordon
 * @since 2015-07-27
 */
class ProductController extends UebController{

    protected $_model = null;

    public function  init(){
        $this->_model = new Product();
        parent::init();
    }
    
    public function accessRules(){
    	return array(
    			array('allow', 'users'=>'*', 'actions'=>array('productview'))
    	);
    }
    /**
     * @desc 产品详情显示
     */
    public function actionProductview() {
        $sku = Yii::app()->request->getParam('sku');
        echo "<center><h2>{$sku}: 暂无查看详情！</h2></center>";
        /* $this->renderPartial('//layouts/iframe', array(
                'url'       => 'products/product/view/do/view',
                'server'    => 'oms',
                'params'    => array('sku' => $sku),
                'htmlOption'=> array(
                        'width'     => '800',
                        'height'    => '550',
                ),
        )); */
    }


    /**
     * 弹出显示sku属性框
     */
    public function actionViewskuattribute(){
        $sku = Yii::app()->request->getParam('sku','');
        if(!empty($sku)){
            $productInfo = $this->_model->getProductBySku($sku);
        }
        $model = $this->_model->loadModel($productInfo['id']);
        $this->render('viewskuattribute', array('model'=>$model));
    }


    /**
     * 显示sku属性---基本资料
     */
    public function actionBasicinformation(){
        $id             = Yii::app()->request->getParam('id','');
        $model          = $this->_model->loadModel($id);
        $mops           = UebModel::model('ProductInfringe')->find('sku = :sku',array(':sku' => $model->sku));
        $proCatOldModel = UebModel::model('ProductCategorySkuOld')->find('sku = :sku',array(':sku' => $model->sku)); 
        $encryptSku     = new encryptSku();

        if(empty($mops)){
            $mops = new ProductInfringe();
        }

        if(empty($proCatOldModel)){
            $productCatOldModel = new ProductCategorySkuOld();
        }

        $mds = UebModel::model('Productdesc')->find('sku ="'.$model->sku.'" and language_code ="'.EN.'"');

        $providerIds            = UebModel::model('ProductProvider')->getProviderIdByProductId($id);
        $model->combine         = UebModel::model('ProductCombine')->getCombineList($id);
        $model->bind            = UebModel::model('Productbind')->getBindSkuByBaseSku($model->sku);
        $productSecurityList    = UebModel::model('Product')->getProductSecurityList();
        $productInfringement    = UebModel::model('ProductInfringe')->getProductInfringementList();
        $productBrand           = UebModel::model('ProductBrand')->getListOptions();
        $model->security_level  = $mops->security_level ? $productSecurityList[$mops->security_level] : '-';
        $model->infringement    = $mops['infringement']>=1 ? $productInfringement[$mops['infringement']] : $productInfringement[1];
        if($model->drop_shipping){
            $model->provider_type = $model->dropshipping;
        }else{
            $model->provider_type = $model->provider;
        }

        //获取第一张副图
        $ft =  UebModel::model('Productimage')->getFtList($model->sku,0);
        if($ft){
            $ft = array_shift($ft);
            $url = '/products/Productimage/view1/sku/'.$model->sku;//获取下一张图片url
            $arr_img=array("style"=>"border:1px solid #ccc;padding:2px;","width"=>80,"height"=>80,'large-src'=>$ft, 'pic-link'=>$url);
            $arr_href=array('id'=>'ajax_1','for'=>$model->sku,'class'=>'cboxElement','title'=>Yii::t('products','Click me to view larger image'));
             
            $model->ft = CHtml::link(CHtml::image(Yii::app()->baseUrl.$ft,$model->sku,array("style"=>"border:1px solid #ccc;padding:2px;width:240px;")),$url,$arr_href);
        }else{
            $model->ft = CHtml::image(Yii::app()->baseUrl.'images/nopic.gif',$model->sku,array("style"=>"border:1px solid #ccc;padding:2px;"));
        }
        $baocai['baocai'] = $model->getByMaterialTypeId('1');
        $baocai['baozhun'] = $model->getByMaterialTypeId('2');
        $categories = ProductCategory::model()->getAllParentByCategoryId($model->product_category_id);
        $catetoryArr =  UebModel::model('ProductCategory')->getCategoryArr(CN);
        $newCategory='';
        empty($newCategory)?'':$newCategory;
        $catArr = array();
        $catetoryView = '';
        foreach($categories as $key=>$value){
            $catetoryView .= $catetoryArr[$value].">>";
        }
        $catetoryView = trim($catetoryView,">>");     

        $this->render('view', array(
            'model' => $model,
            'mds' => $mds,
            'baocai'=>$baocai,
            'mops'=>$mops,
            'catArr'=>$catArr,
            'catetoryView'=>$newCategory,
            'productBrand'=>$productBrand,
            'encryptSku' => $encryptSku
        ));
    }


    /**
     * 显示sku属性
     */
    public function actionProductattributes(){
        $model = new ProductSelectAttribute();
        $productId = Yii::app()->request->getParam('id');
        $attributeIdsInfo=$model->getAttributeIdByProductId($productId);
        $selectedId=$model->getSelectedIdByProduct($productId);
        $productInfo = UebModel::model('Product')->findByPk($productId);          
        $categoryAttributeList = UebModel::model('ProductCategoryAttribute')->getAttributeList($productInfo['product_category_id']);
      
        $publicAttributeList = UebModel::model('ProductAttribute')->getPublicAttributeList();
        foreach ($publicAttributeList as $key => $val) {
            $categoryAttributeList[$key] = $val;
        }
    
        unset($publicAttributeList);          
        $attributeIds = array_keys($categoryAttributeList);   
       
        $attributeListData = UebModel::model('ProductAttributeMap')->getListValueData($attributeIds);
     
        $selectAttrPairs = $model->getAttrList($productId);           
        if ( $productInfo['product_is_multi'] == 2 ) {              
            $selectMutiIds = $model->getMultiAttIdsByMultiId($productId);
            $multiSku = $productInfo['sku'];
        } else {
            $selectMutiIds = $model->getMultiAttIds($productId);  
            $multiSku = $model->getMultiSku($productId);  
            if ( empty($multiSku) && strpos($productInfo['sku'],'.') !== false ) {
                $multiSku = substr($productInfo['sku'], 0, strpos($productInfo['sku'],'.'));
            }
        }                      
        $model->setAttribute('multi_sku', $multiSku);      
        $productModel = UebModel::model('Product')->find("id = $productId");
    
        $isNopublicAttr=UebModel::model('ProductAttribute')->getNopublicAttr();
        $singleProduct=UebModel::model('Product')->findByPk($productId);
        if($singleProduct->product_is_multi==0){
            $model->multi_sku='';
        }   

        $this->render('attributes', array(
            'isNopublicAttr'        => $isNopublicAttr,
            'categoryAttributeList' => $categoryAttributeList,          
            'attributeListData'     => $attributeListData,
            'categoryId'            => $productInfo['product_category_id'],
            'selectAttrPairs'       => $selectAttrPairs,
            'productId'             => $productId,
            'attributeIdsInfo'      => $attributeIdsInfo,
            'selectedId'            => $selectedId,
            'product_is_multi'      => $productInfo['product_is_multi'],         
            'selectMutiIds'         => json_encode($selectMutiIds),            
            'model'                 => $model,
            'productModel'          => $productModel,
        ));
    }


    /**
     * 显示中文和英文描述
     */
    public function actionLangdescription(){
        $lang_code = CN;
        $google_code = 'zh-CN';
        $model = new Productdesc();             
        $productId = Yii::app()->request->getParam('id');
        $langCode = Yii::app()->request->getParam('langCode');
        if($langCode == 'en'){
            $lang_code = EN;
            $google_code = 'en';
        }

        $productInfo = UebModel::model('Product')->findByPk($productId);     
        $desc_info = $model->getDescriptionInfoBySkuAndLanguageCode($productInfo['sku'],$lang_code);        
        if($desc_info){
            $desc_id = $desc_info['id'];
            $model = Productdesc::model()->findByPk($desc_info['id']);
        }
        $arr = $model->attributes;
        $model->description = $model->description ? $model->description : '';
        $englishTitle=$model->getEnglishByProductId($productId);
        $this->render('description', array(
            'categoryId'            => $productInfo['product_category_id'],
            'sku'                   => $productInfo['sku'],
            'productId'             => $productId,
            'model'                 => $model,
            'lang_code'             => $lang_code,
            'google_code'           => $google_code,
            'desc_info'             => $desc_info,
            'englishTitle'          => $englishTitle            
        ));
    }


    /**
     * product multiple list
     */
    public function actionMulti() {
       $do = Yii::app()->request->getParam('do');
       $attributeIds = Yii::app()->request->getParam('attributeIds');  
       $categoryId = Yii::app()->request->getParam('categoryId');
       $productId = Yii::app()->request->getParam('productId'); 
       $product_is_multi = Yii::app()->request->getParam('product_is_multi');
       $product_multi_sku=UebModel::model('Product')->getSkuByProductId($productId); 
       if ( empty($attributeIds) ) { die('');}
       $attributeIds = trim($attributeIds, ',');
       $attributePairs = UebModel::model('ProductAttribute')->queryPairs('id,attribute_name', " id IN($attributeIds) ");
       $attributeListData=UebModel::model('ProductAttributeValueLang')->getAttrChineseVal($attributeIds);  
       if ( $product_is_multi == 2 ){  
           $selectMultiPairs = UebModel::model('ProductSelectAttribute')->getMultiListByMultiId($productId);             
       }
       $sonSkuInfo=UebModel::model('ProductSelectAttribute')->getSonSkuByProductId($productId);
       
        
       $this->render('_multi', array(
           'product_multi_sku'  => $product_multi_sku,
           'attributePairs'     => $attributePairs,
           'attributeListData'  => $attributeListData,
           'sonSkuInfo'         => $sonSkuInfo,
           'selectMultiPairs'   => $selectMultiPairs,
           'do'                 => $do
       )); 
       
    } 


    /**
     * 显示物流信息
     */
    public function actionLogisticsinformation(){
        $productId = Yii::app()->request->getParam('id');
        $productInfo = UebModel::model('Product')->findByPk($productId); 
        if(!$productInfo){
            exit('sku不存在');
        }

        $model          = $this->_model->loadModel($productId);

        $productPackage = UebModel::model('ProductToWayPackage')->getProductAllPackage();//所有包装方式
        $this->render('logisticsinformation', array(
                'model'                 => $model,
                'do'                    => '',
                'productPackage'       => $productPackage,
        ));
    }

}