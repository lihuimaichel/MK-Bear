<?php
/**
 * @desc Lazada刊登
 * @author Gordon
 * @since 2015-08-12
 */
class LazadaproductaddController extends UebController{
	
    /**
     * @desc Lazada刊登(1.sku录入)
     */
    public function actionProductaddstepfirst(){
        $params = array();
        if( Yii::app()->request->getParam('dialog')==1 ){
            $params['dialog'] = true;
        }
        $this->render('productAdd1',$params);
    }
    
    /**
     * @desc Lazada刊登(2.账号选择)
     */
    public function actionProductaddstepsecond(){
        $sku = Yii::app()->request->getParam('sku');
        
        //检测是否有权限去刊登该sku
        //上线后打开注释---lihy 2016-05-10
        if(! Product::model()->checkCurrentUserAccessToSaleSKU($sku, Platform::CODE_LAZADA)){
                echo $this->failureJson(array(
                    'message' => Yii::t('system', 'Not Access to Add the SKU')
                ));
                Yii::app()->end();
        }
        
        //获取刊登类型
        $listingType = LazadaProductAdd::getListingType();
        //获取刊登模式
        $listingMode = LazadaProductAdd::getListingMode();
        //站点
        $listingSite = LazadaSite::getSiteList();
        //sku信息
        $skuInfo = Product::model()->getProductInfoBySku($sku);
        if(!$skuInfo){
            echo $this->failureJson(array(
                'message' => Yii::t('lazada', 'Invalide SKU'),
            ));
            Yii::app()->end();
        }
        //sku图片加载
//        $imageType = array('zt','ft');
//        $skuImg = array();
//        foreach($imageType as $type){
//            $images = Product::model()->getImgList($sku,$type);
//            foreach($images as $k=>$img){
//                $skuImg[$type][$k] = $config['oms']['host'].$img;
//            }
//        }
        //使用java组api获取产品图片显示
        $skuImg = ProductImageAdd::getOrPushImageUrlFromRestfulBySku($skuInfo, $pushWithChild = true, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_LAZADA);
        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }


        if($skuImg == array()){
            echo $this->failureJson(array(
        	'message' => Yii::t('system', 'image not found'),
            ));
            Yii::app()->end();
        }
        //刊登模式
        $this->render('productAdd2',array(
            'sku'           => $sku,
            'skuInfo'       => $skuInfo,
            'listingType'   => $listingType,
            'listingMode'   => $listingMode,
            'skuImg'        => $skuImg,
            'listingSite'   => $listingSite,
        ));
    }
    
    /**
     * @desc Lazada刊登(3.刊登资料详情)
     */
    public function actionProductaddstepthird(){
        $listingType = Yii::app()->request->getParam('listing_type');
        $listingMode = Yii::app()->request->getParam('listing_mode');
        $listingSite = Yii::app()->request->getParam('listing_site');
        $listingAccount = Yii::app()->request->getParam('accounts');//array

        //MHelper::writefilelog('lazada-param.txt', print_r($_REQUEST,true)."\r\n");  

        $sku = Yii::app()->request->getParam('sku');
        if( !$listingType || !$listingMode || !$listingAccount){
        	echo $this->failureJson(array(
        		'message' => Yii::t('system', 'Fill Form Error'),
        	));
            Yii::app()->end();
        }
        //1.验证sku
        $skuInfo = Product::model()->getProductInfoBySku($sku);
        if(!$skuInfo){
            echo $this->failureJson(array(
                'message' => 'Sku Not Exists',
            ));
            Yii::app()->end();
        }

        //验证主sku
        Product::model()->checkPublishSKU($skuInfo['product_is_multi'], $skuInfo);

        $accountInfos = array();
        //验证刊登权限,平台/账号/站点/sku
        foreach ($listingAccount as $sellerAccountId) {
            $lazadaAcountInfo = LazadaAccount::model()->getApiAccountByIDAndSite($sellerAccountId, $listingSite);
            $accountInfos[] = $lazadaAcountInfo;
            if(! Product::model()->checkCurrentUserAccessToSaleSKUNew($sku, $lazadaAcountInfo['old_account_id'], Platform::CODE_LAZADA, $listingSite)){
                echo $this->failureJson(array(
                    'message' => Yii::t('system', 'Not Access to Add the SKU'),
                ));
                Yii::app()->end();
            }
        }

        //2.准备刊登信息(分单品和多属性)
        /**@ 获取刊登参数*/
        //获取刊登类型
        $listingTypeArr = LazadaProductAdd::getListingType();
        //获取刊登模式
        $listingModeArr = LazadaProductAdd::getListingMode();
        //获取站点
        $listingSiteArr = LazadaSite::getSiteList();
        //获取平台可用价格促销方案
        $pricePromotionArr = UebModel::model('PricePromotionScheme')->getPricePromotionScheme(Platform::CODE_LAZADA, true);
        $pricePromotionList = array();
        if (!empty($pricePromotionArr)) {
        	foreach ($pricePromotionArr as $pricePromotion)
        		$pricePromotionList[$pricePromotion['id']] = $pricePromotion['name'];
        }
        //获取账号
        // $accountInfos = array();
        // foreach ($listingAccount as $accountID) {
        // 	$accountInfo = LazadaAccount::model()->getApiAccountByIDAndSite($accountID, $listingSite);
        // 	$accountInfos[] = $accountInfo;
        // }
        $listingParam = array(
                'listing_type'      => array('id' => $listingType, 'text' => $listingTypeArr[$listingType]),
                'listing_mode'      => array('id' => $listingMode, 'text' => $listingModeArr[$listingMode]),
                'listing_site'      => array('id' => $listingSite, 'text' => $listingSiteArr[$listingSite]),
                'listing_account'   => $accountInfos,
        		'promotion_list' 	=> $pricePromotionList,
        );
        /**@ 获取产品信息*/
//        $imageType = array('ft');
//        $config = ConfigFactory::getConfig('serverKeys');
//        foreach($imageType as $type){
//            $images = Product::model()->getImgList($sku,$type);
//            foreach($images as $k=>$img){
//                $skuImg[$type][$k] = $config['oms']['host'].$img;
//            }
//        }
        //使用java组api获取产品图片显示
        $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_LAZADA);
        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }
        
        //在描述中插入图片
        if(isset($skuImg['ft'])){
            $countNum = 1;
            $descriptionImg = '<div style="text-align:center;">';
            foreach ($skuImg['ft'] as $imgValue) {
                if($countNum > 8) break;
                $imgUrlString = preg_replace('/([^?]+)?.*/', '$1', $imgValue);
                $descriptionImg .= '<p><img src="'.$imgUrlString.'" width="798px" /></p>';
            }
            $descriptionImg .= '</div>';
            $skuInfo['description']['english'] = $skuInfo['description']['english'].$descriptionImg;
        }

        $listingProduct = array(
                'sku'           => $sku,
                'skuImg'        => $skuImg,
                'skuInfo'       => $skuInfo,
        );
        
        /**@ 获取产品属性*/
        $listingAttribute = array();
        $attributeListData = UebModel::model('ProductAttributeMap')->getListValueData(3);
        if(isset($attributeListData[3])){
            $listingAttribute = $attributeListData[3];
        }

        $selectAttrPairs = ProductSelectAttribute::model()->getAttrList($skuInfo['id']);

        //精简模式
        if($listingMode==LazadaProductAdd::LISTING_MODE_EASY){
            $this->render('_formEasy', array(
                    'listingParam'           => $listingParam,
                    'listingProduct'         => $listingProduct,
                    'listingAttribute'       => $listingAttribute,
                    'selectAttrPairs'        => $selectAttrPairs,
            ));
        }
        //详细模式
        else{
            $this->render('_formAll');
        }
    }
    
    /**
     * @desc 获取可用账号
     */
    public function actionGetableaccount(){
        $sku            = Yii::app()->request->getParam('sku');
        $listingType    = Yii::app()->request->getParam('listing_type');
        $siteID = Yii::app()->request->getParam('site_id');
        $accounts = LazadaProductAdd::model()->getAbleAccountsBySku($sku, $siteID);
        $userID = isset(Yii::app()->user->id)?Yii::app()->user->id:'';
        if(!$userID){
            echo $this->failureJson(array('message' => '登录状态失效，请重新登录'));
            Yii::app()->end();
        }
        //通过userid取出对应的账号
        $userAccount = LazadaAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.$userID);
        $ableAccounts = array();
        foreach($accounts as $id=>$account){
            if($userAccount && !in_array($id, $userAccount)){
                continue;
            }
            $ableAccounts[] = array(
                'id'            => $id,
                'short_name'    => $account,
            );
        }
        echo json_encode($ableAccounts);exit;
    }
    
    /**
     * @desc 获取确认分类后需要的数据
     */
    public function actionConfirmcategory(){
        $sku            = Yii::app()->request->getParam('sku');
        $categoryID     = Yii::app()->request->getParam('category_id');
        $listing_type   = Yii::app()->request->getParam('listing_type');
        $siteID         = Yii::app()->request->getParam('site_id');
        $currency       = LazadaSite::getCurrencyBySite($siteID);
        $accounts       = explode(',', Yii::app()->request->getParam('accounts'));
        $editID         = Yii::app()->request->getParam('edit_id');
        $output = new stdClass();
        //1.获取分类相关信息
        $output->categoryName = LazadaCategory::model()->getBreadcrumbCategory($categoryID);
        //2.获取卖价
        //根据刊登条件匹配卖价方案 TODO
        $salePrice = $profit = $profitRate = $calcDesc = array();
        foreach($accounts as $account){
            $model_lazadaproduct = new LazadaProduct(); 
            $dataParam = array(
                ':platform_code'         => Platform::CODE_LAZADA,
                ':profit_calculate_type' => SalePriceScheme::PROFIT_SYNC_TO_SALE_PRICE
            );
            $schemeWhere = 'platform_code = :platform_code AND profit_calculate_type = :profit_calculate_type';
            $salePriceScheme = SalePriceScheme::model()->getSalePriceSchemeByWhere($schemeWhere,$dataParam);
            if (!$salePriceScheme) {
                $tplParam = $model_lazadaproduct->tplParam;
            } else {
                $tplParam = array(
                        'standard_profit_rate'  => $salePriceScheme['standard_profit_rate'],
                        'lowest_profit_rate'    => $salePriceScheme['lowest_profit_rate'],
                        'floating_profit_rate'  => $salePriceScheme['floating_profit_rate'],
                );
            }

            //计算卖价，获取描述
            $priceCal = new CurrencyCalculate();
            //设置参数值
            $priceCal->setProfitRate($tplParam['standard_profit_rate']);//设置利润率
            $priceCal->setCurrency($currency);//币种
            $priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
            $priceCal->setSku($sku);//设置sku
            $priceCal->setSiteID($siteID);//设置站点
            $output->priceDetail[$account]['salePrice']     = $priceCal->getSalePrice();//获取卖价
            $output->priceDetail[$account]['profit']        = $priceCal->getProfit();//获取利润
            $output->priceDetail[$account]['profitRate']    = $priceCal->getProfitRate();//获取利润率
            $output->priceDetail[$account]['desc']          = $priceCal->getCalculateDescription();//获取计算详情
        }
        //3.获取分类下的attribute
        // $account_info = LazadaAccount::model()->getAccountInfoById($account);
        //老接口
        // $response = LazadaCategoryAttribute::model()->getCategoryAttributeOnline($siteID, $account_info['account_id'], $categoryID);
        //新接口
        $response = LazadaCategoryAttribute::model()->getCategoryAttributeOnlineNew($account, $categoryID);

        //MHelper::writefilelog('lazada/getCategoryAttributeOnline/'.date("Ymd").'/'.date("H").'/response.txt', implode('--',array($siteID, $account_info['account_id'], $categoryID)).' '. date('Y-m-d H:i:s').' ### '. print_r($response,true)."\r\n" );// add for test

        $body = $response->Body;//->Attribute
        $body = (array)$body;
        if(isset($body['Attribute'])){
            $attr = $body['Attribute'];
            foreach($attr as $detail){
                if( LazadaCategoryAttribute::filterSystemConfig($detail->name) ){
                    if($detail->name == 'warranty_type'){
                        $detail->inputType = 'richText';
                        $detail->options = '';
                    } else {
                        continue;
                    }
                }
                $detail->Description = trim($detail->Description);
                $output->attributes[] = $detail;
            }
        }
        //4.获取多属性信息 TODO
        if($listing_type == LazadaProductAdd::LISTING_TYPE_VARIATION){
            $variation_info = $this->getVariationinfo($sku, $siteID, $editID);
            $output->variation_info = $variation_info;
        }
        echo json_encode($output);exit;
    }
    
    /**
     * @desc 计算利润情况
     */
    public function actionGetpriceinfo(){
        $sku            = Yii::app()->request->getParam('sku');
        $categoryID     = Yii::app()->request->getParam('category_id');
        $accountID      = Yii::app()->request->getParam('account_id');
        $siteID         = Yii::app()->request->getParam('site_id');
        $currency       = LazadaSite::getCurrencyBySite($siteID);
        $salePrice      = Yii::app()->request->getParam('price');
        $priceCal = new CurrencyCalculate();
        $priceCal->setCurrency($currency);//币种
        $priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
        $priceCal->setSku($sku);//设置sku
        $priceCal->setSalePrice($salePrice);
        $priceCal->setSiteID($siteID);//设置站点
        $output = new stdClass();
        $output->salePrice  = $priceCal->getSalePrice();
        $output->profit     = $priceCal->getProfit();
        $output->profitRate = $priceCal->getProfitRate();
        $output->desc       = $priceCal->getCalculateDescription();
        echo json_encode($output);exit;
    }
    
    /**
     * @desc 保存刊登数据
     */
    public function actionSaveData(){
      
    	$encryptSku = new encryptSku();
        $saveData = $_POST;
        $flag = true;
        $message = '';
        $updateID = Yii::app()->request->getParam('update_id');

        //1.检查数据
        if( empty($_POST['baseInfo']['account']) ){
            $flag = false;
            $message .= '<li>No Account</li>';
        }
        $mainAddData = array();
        $baseInfo = $_POST['baseInfo'];

        //如果是修改产品，产品状态只能是待上传、上传失败
        if($updateID){
            $newProductInfo = LazadaProductAdd::model()->findByPk($updateID);
            if($baseInfo['sku'] != $newProductInfo['sku'] || !in_array($newProductInfo['status'], array(LazadaProductAdd::UPLOAD_STATUS_DEFAULT,LazadaProductAdd::UPLOAD_STATUS_FAILURE))){
                $flag = false;
                $message .= '<li>Edit Product Status Error</li>';
            }
        }

        //验证主sku
        $skuInfo = Product::model()->getProductInfoBySku($baseInfo['sku']);
        if(!$skuInfo){
            $flag = false;
            $message .= '<li>Sku Not Exists</li>';
        }

        //转换成sku库的判断是否是单品的属性值
        if($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
            $productSelectedAttribute = new ProductSelectAttribute();
            $skuAttributeList = $productSelectedAttribute->getChildSKUListByProductID($skuInfo['id']);
            if(!$skuAttributeList){
                $flag = false;
                $message .= '<li>异常主sku</li>';
            }
        }
        
        foreach($baseInfo['account'] as $accountID){
            if( !isset($baseInfo['title'][$accountID]) || !$baseInfo['title'][$accountID] ){
                $flag = false;$message .= '<li>'.Yii::t('common','Title Is Required').'</li>';
            }
            if( !isset($baseInfo['sale_price'][$accountID]) || !$baseInfo['sale_price'][$accountID] ){
                $flag = false;$message .= '<li>'.Yii::t('common','Sale Price Is Required').'</li>';
            }
            if(!isset($baseInfo['description'][$accountID]) || !$baseInfo['description'][$accountID]) {
            	$flag = false;$message .= '<li>'.Yii::t('common','Description Is Required').'</li>';
            }
            if(!isset($baseInfo['highlight'][$accountID]) || !$baseInfo['highlight'][$accountID]) {
            	$flag = false;$message .= '<li>'.Yii::t('common','Highlight Is Required').'</li>';
            }
        }
        if( !isset($_POST['category_id']) || !$_POST['category_id'] ){
            $flag = false;$message .= '<li>'.Yii::t('common','Category Is Required').'</li>';
        }
        if( !isset($_POST['imageInfo']['sortImg']) || empty($_POST['imageInfo']['sortImg']) ){
            $flag = false;$message .= '<li>'.Yii::t('common','One Zt At Least').'</li>';
        }

        //多属性信息验证
        if(isset($saveData['variationSku'])){
            foreach ($saveData['variationSku'] as $key=>$value){
                if(trim($value) == ''){
                    continue;
                }
                if( !isset($saveData['variationValue'][$key]) || !$saveData['variationValue'][$key] ){
                    $flag = false;$message .= '<li>'.Yii::t('common','Variation Is Required').'</li>';
                    break;
                }
                if( !isset($saveData['variationSalePrice'][$key]) || !$saveData['variationSalePrice'][$key] ){
                    $flag = false;$message .= '<li>'.Yii::t('common','Sale Price Is Required').'</li>';
                    break;
                }
                if( !isset($saveData['variationPrice'][$key]) || !$saveData['variationPrice'][$key] ){
                    $flag = false;$message .= '<li>'.Yii::t('common','Price Is Required').'</li>';
                    break;
                }
                if( !isset($saveData['variationSalePriceStart'][$key]) || !$saveData['variationSalePriceStart'][$key] ){
                    $flag = false;$message .= '<li>'.Yii::t('common','Sale Start Date Is Required').'</li>';
                    break;
                }
                if( !isset($saveData['variationSalePriceEnd'][$key]) || !$saveData['variationSalePriceEnd'][$key] ){
                    $flag = false;$message .= '<li>'.Yii::t('common','Sale End Date Is Required').'</li>';
                    break;
                }
            }
        }
        

        //exit;
        
        $addIDArr = array();

        if( $flag ){
        	
            //2.保存基本数据
	        // $sellerSku = $baseInfo['sku'];
            foreach($baseInfo['account'] as $accountID){
        		$sellerSku = $encryptSku->getEncryptSku($baseInfo['sku']);
                
                //如果是编辑产品,sellersku取老的sellersku
                if($updateID){
                    $sellerSku = $baseInfo['seller_sku'];
                }

                $mainAddData[$accountID] = array(
                    'account_id'    => $accountID,
                    'sku'           => $baseInfo['sku'],
                	'seller_sku'	=> $sellerSku,
                    'site_id'       => $baseInfo['listing_site'],
                    'currency'      => LazadaSite::getCurrencyBySite($baseInfo['listing_site']),
                    'listing_type'  => intval($baseInfo['listing_type']),
                    'title'         => trim( addslashes($baseInfo['title'][$accountID])),
                    'category_id'   => $_POST['category_id'],
                    'brand'         => addslashes($_POST['brand']),
                    'create_user_id'=> Yii::app()->user->id,
                    'create_time'   => date('Y-m-d H:i:s'),
                    'modify_user_id'=> Yii::app()->user->id,
                    'modify_time'   => date('Y-m-d H:i:s'),
                	'description'   => $baseInfo['description'][$accountID],
                	'highlight'		=> $baseInfo['highlight'][$accountID],
                );

                //修改的产品状态变为待上传
                if($updateID){
                    $mainAddData[$accountID]['status'] = 0;
                }

				$discountTpl = array(
					'discount' => 0.5,
					'start_date' => date('Y-m-d H:i:s'),
					'end_date' => date('Y-m-d H:i:s' ,strtotime('+10 year'))
				);
				$price = round($baseInfo['sale_price'][$accountID] / $discountTpl['discount'], 2);
				$mainAddData[$accountID]['price'] = $price;
		        $mainAddData[$accountID]['sale_price'] = floatval($baseInfo['sale_price'][$accountID]);
		        $mainAddData[$accountID]['sale_price_start'] = $discountTpl['start_date'];
		        $mainAddData[$accountID]['sale_price_end'] = $discountTpl['end_date'];

                //判断是添加还是更新
                if($updateID){
                    $addID = $updateID;
                    $updateResult = LazadaProductAdd::model()->updateData($mainAddData[$accountID], 'id = :id', array(':id'=>$updateID));
                    if(!$updateResult){
                        $addID = null;
                    }
                }else{
                    $addID = LazadaProductAdd::model()->saveRecord($mainAddData[$accountID]);
                }

                if( !$addID ){
                    $flag = false;$message .= '<li>'.Yii::t('common','340Save Failed').'</li>';
                    break;
                }
                $addIDArr[$addID] = $addID;
                $accountID_list[$addID] = $accountID;
            }
        }
        if( $flag ){
            //获取站点语言
            // $language = LazadaSite::getLanguageBySite($baseInfo['listing_site']);
            //3.保存图片及排序
            if(!isset($saveData['variationSku'])){
                $imageSort = $_POST['imageInfo']['sortImg'];
                asort($imageSort);
                $imageAddData = array();
                $count = 1;
                $maxCount = LazadaProductImageAdd::MAX_IMAGE_NUMBER;
                foreach($imageSort as $imgName=>$sort){
                    //图片名称等于sku的图片不上传
                    if($imgName == $baseInfo['sku']){
                        continue;
                    }

                    //检查图片是否大于8张
                    if($count>$maxCount) break;

                    foreach($baseInfo['account'] as $accountID){                    
                    	//检查图片是否存在
                    	if (LazadaProductImageAdd::model()->checkImageExists($imgName, LazadaProductImageAdd::IMAGE_ZT, $accountID))
                    		continue;

                    	$localPath =  explode('?', ProductImageAdd::getImageUrlFromRestfulByFileName($imgName, $baseInfo['sku'], $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_LAZADA));

                        //按顺序组合数据
                        $imageAddData = array(
                            'image_name'    => $imgName,
                            'sku'           => trim($baseInfo['sku']),
                            'type'          => LazadaProductImageAdd::IMAGE_ZT,
                            'local_path'    => $localPath[0],
                            'account_id'    => $accountID,
                            'upload_status' => LazadaProductImageAdd::UPLOAD_STATUS_DEFAULT,
                            'create_user_id'=> Yii::app()->user->id,
                            'create_time'   => date('Y-m-d H:i:s'),
                        );
                        //保存
                        $imageModel = new LazadaProductImageAdd();
                        $imageModel->setAttributes($imageAddData,false);
                        $imageModel->setIsNewRecord(true);
                        $imageModel->save();
                    }

                    $count++;
                }
            }
            //4.保存属性信息
            $productAddAttributeModle = new LazadaProductAddAttribute();
            if( !empty($_POST['attributeInfo']) ){
                if($updateID){
                    $productAddAttributeModle->deleteAll('add_id = :add_id', array(':add_id' => $updateID));
                }
                foreach($_POST['attributeInfo'] as $attributeName=>$value){
                    foreach($addIDArr as $id){
                        if( is_array($value) ){
                            $value = implode(',', $value);
                            $productAddAttributeModle->saveRecord($id, $attributeName, $value);
                        }else{
                            if( trim($value) != '' ){
                                if($attributeName == 'model'){
                                    $accountInfo = LazadaAccount::getAccountInfoById($accountID);
                                    $accountName = $accountInfo['seller_name'];
                                    $value = $accountName.'-'.$sellerSku;
                                }

                                $productAddAttributeModle->saveRecord($id, $attributeName, $value);
                            }
                        }
                    }
                }
            }
            
            //5.保存lazada多属性信息
            $listing_type = LazadaProductAdd::LISTING_TYPE_FIXEDPRICE;
            if(isset($saveData['variationSku'])){
                $encryptSku = new encryptSku();
                $parent_sku[] = '';
                $is_parent  = array();
                $variationName = trim($saveData['variationName']);
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $encrypt_list = array();

                    foreach ($saveData['variationSku'] as $key => $value){
                        if(trim($value) == ''){
                            continue;
                        }

                        if($updateID){
                            $getVariationID = $saveData['variationID'][$key];
                            LazadaProductAddVariation::model()->deleteAll('id = :variationID', array(':variationID'=>$getVariationID));
                            LazadaProductAddVariationAttribute::model()->deleteAll('variation_id = :variation_id', array(':variation_id'=>$getVariationID));
                        }
                        
                        //除了c账号以外的都加密
                        // $not_crystalawaking_list = LazadaAccount::model()->getDbConnection()->createCommand()
                        //         ->from(LazadaAccount::tableName())
                        //         ->select("id")
                        //         ->where("account_id !=1 ")
                        //         ->queryColumn();
                        $explode_sku = explode('.', $saveData['variationSku'][$key]);
                        $listing_type = LazadaProductAdd::LISTING_TYPE_VARIATION;
                        foreach($addIDArr as $id){
                            $variation_info[$id]['product_add_id'] = $id;
                            // 账号crystalawaking以外的SKU都加密
                            // if (in_array($accountID_list[$id], $not_crystalawaking_list)){
                                //如果是第一个子sku，直接加密，并且保存小数点前的部分
                                if( !isset($encrypt_list[$explode_sku[0]][$id])  ){
                                    $variation_info[$id]['seller_sku'] = $encryptSku->getEncryptSku(trim($saveData['variationSku'][$key]));
                                    $encrypt_explode_sku = explode('.', $variation_info[$id]['seller_sku']);
                                    $encrypt_list[ $explode_sku[0]][$id] = $encrypt_explode_sku[0];
                                } else{
                                    //如果不是该账号第一个子sku，调用小数点前的加密，并截取小数点后的组成新加密sku
                                    if(isset($explode_sku[1])){
                                        $sub_sku = '.' . $explode_sku[1];
                                    } else {
                                        $sub_sku = '';
                                    }
                                    $variation_info[$id]['seller_sku'] = $encrypt_list[$explode_sku[0]][$id] . $sub_sku;
                                }

                                if($updateID && isset($saveData['variationSellersku'][$key]) && $saveData['variationSellersku'][$key]){
                                    $variation_info[$id]['seller_sku'] = $saveData['variationSellersku'][$key];
                                }
  
                            // } else {
                            //     $variation_info[$id]['seller_sku'] = $saveData['variationSku'][$key];
                            // }
                            $variation_info[$id]['sku'] = $saveData['variationSku'][$key];
                            if(!isset($is_parent[ $saveData['variationColor'][$key] ])){
                                $is_parent[ $saveData['variationColor'][$key] ] = 1;
                            }
                            
                            $variation_info[$id]['parent_sku'] = '';
                            $variation_info[$id]['is_parent'] = 0;
                            $variation_info[$id]['sale_price'] = floatval($saveData['variationSalePrice'][$key]);
                            $variation_info[$id]['variation_size'] = $saveData['variationSize'][$key];
                            $variation_info[$id]['sale_price_start'] = $saveData['variationSalePriceStart'][$key];
                            $variation_info[$id]['sale_price_end'] = $saveData['variationSalePriceEnd'][$key];
                            $variation_info[$id]['price'] = $saveData['variationPrice'][$key];

                            $variationID = LazadaProductAddVariation::model()->saveRecord(
                                $id, 
                                $variation_info[$id]['sku'], 
                                $variation_info[$id]['seller_sku'], 
                                $variation_info[$id]['parent_sku'], 
                                $variation_info[$id]['price'],
                                $variation_info[$id]['sale_price'],
                                $variation_info[$id]['sale_price_start'],
                                $variation_info[$id]['sale_price_end'],
                                $variation_info[$id]['variation_size'],
                                $variation_info[$id]['is_parent']
                            );
                            LazadaProductAddVariationAttribute::model()->saveRecord($variationID, $variationName, $saveData['variationValue'][$key], $id);
                            //某种颜色的第一个sku设为父sku，按颜色设置，有多少个颜色就有多少个父sku
                            // if($is_parent[ $saveData['variationColor'][$key] ] == 1){
                            //     $parent_sku[ $saveData['variationColor'][$key] ] = $variation_info[$id]['seller_sku'];
                            // }

                            //添加子sku图片
                            $lazadaProductImageAdd = new LazadaProductImageAdd();
                            $lazadaProductImageAdd->addGetJavaProductImageBySku($variation_info[$id]['sku'], $accountID_list[$id], Platform::CODE_LAZADA);
                        }
                        $is_parent[ $saveData['variationColor'][$key] ] = 0;
                    }
                    $transaction->commit();
                }
                catch (Exception $e) {
                    $transaction->rollback();
                    $flag = false;
                    $message .= '<li>'.Yii::t('common','Save Failed').'</li>';
                }
            }
            //后台根据多属性sku自动判断类型，有多属性sku即为多属性，没有为一口价
            LazadaProductAdd::model()->dbConnection->createCommand()->update(LazadaProductAdd::model()->tableName(), array(
                'listing_type'    => $listing_type,
                ), 'id IN ('.MHelper::simplode($addIDArr).')');
        }
        //返回结果
        $result = new stdClass();
        if( !$flag ){
            $result->status = false;
            $result->message = $message;
        }else{
            $result->status = true;
        }
        
        echo json_encode($result);exit;
    }
    
    /**
     * @desc 待刊登列表
     * @author Michael
     */
    public function actionList(){
        $model = UebModel::model('LazadaProductAdd');
        $this->render('list',array(
        	'model'		=> $model
        ));
    }
    
    /**
     * @desc 删除lazada待刊登列表
     * @author Michael
     */
    public function actionDelete()
    {
    	if (Yii::app()->request->isAjaxRequest && isset($_REQUEST['ids'])) {
    		try {
    			$flag =UebModel::model('LazadaProductAdd')->deleteEntireLazadaById($_REQUEST['ids']);
    			if (!$flag ) {
    				throw new Exception('Delete failure');
    			}
    			$jsonData = array(
    					'message' => Yii::t('system', 'Delete successful'),
    			);
    			echo $this->successJson($jsonData);
    		} catch (Exception $exc) {
    			$jsonData = array(
    					'message' => Yii::t('system', 'Delete failure')
    			);
    			echo $this->failureJson($jsonData);
    		}
    		Yii::app()->end();
    	}
    }
    
    /**
     * @desc 编辑lazada待刊登信息
     * @param int $id
     * @author Michael
     */
    public function actionUpdate(){
        $id = Yii::app()->request->getParam('id');

        //通过id查询产品是否存在
        $lazadaProductAddModel = new LazadaProductAdd();
        $addInfo = $lazadaProductAddModel->findByPk($id);
        if(!$addInfo){
            echo $this->failureJson(array('message'=>'产品不存在'));
            Yii::app()->end();
        }

        //如果上传成功的产品不能修改
        if($addInfo['status'] == LazadaProductAdd::UPLOAD_STATUS_SUCCESS){
            echo $this->failureJson(array('message'=>'此产品已上传成功，不能进行修改'));
            Yii::app()->end();
        }

        //1.验证sku
        $skuInfo = Product::model()->getProductInfoBySku($addInfo['sku']);
        if(!$skuInfo){
            echo $this->failureJson(array('message' => 'Sku Not Exists'));
            Yii::app()->end();
        }

        //1.准备刊登信息(分单品和多属性)
        /**@ 获取刊登参数*/
        //获取刊登类型
        $listingTypeArr = LazadaProductAdd::getListingType();
        //获取站点
        $listingSiteArr = LazadaSite::getSiteList();
        //获取平台可用价格促销方案
        $pricePromotionArr = UebModel::model('PricePromotionScheme')->getPricePromotionScheme(Platform::CODE_LAZADA, true);
        $pricePromotionList = array();
        if (!empty($pricePromotionArr)) {
            foreach ($pricePromotionArr as $pricePromotion)
                $pricePromotionList[$pricePromotion['id']] = $pricePromotion['name'];
        }

        $listingParam = array(
                'listing_type'      => array('id' => $addInfo['listing_type'], 'text' => $listingTypeArr[$addInfo['listing_type']]),
                'listing_site'      => array('id' => $addInfo['site_id'],      'text' => $listingSiteArr[$addInfo['site_id']]),
                'promotion_list'    => $pricePromotionList,
        );

        //获取类目名称
        $categoryInfo = LazadaCategory::model()->getCategotyInfoByID($addInfo['category_id']);
        $addInfo['category_name'] = isset($categoryInfo['category_name'])?$categoryInfo['category_name']:'';

        /**@ 获取产品属性*/
        $listingAttribute = array();
        $attributeListData = UebModel::model('ProductAttributeMap')->getListValueData(3);
        if(isset($attributeListData[3])){
            $listingAttribute = $attributeListData[3];
        }
        $selectAttrPairs = ProductSelectAttribute::model()->getAttrList($skuInfo['id']);

        //获取账号名称
        $accountInfo = LazadaAccount::getAccountInfoById($addInfo['account_id']);
        $addInfo['account_name'] = isset($accountInfo['seller_name'])?$accountInfo['seller_name']:'';

        //获取图片
        $skuImgArr = array();
        //使用java组api获取产品图片显示
        $skuImgJava = ProductImageAdd::getImageUrlFromRestfulBySku($addInfo['sku'], $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_LAZADA);
        if (isset($skuImgJava['ft'])) {
            $skuImg = $skuImgJava['ft'];
        }

        $lazadaImageAddInfo = LazadaProductImageAdd::model()->getImageBySku($addInfo['sku'], $addInfo['account_id']);
        if($lazadaImageAddInfo && isset($lazadaImageAddInfo[1]) && !empty($lazadaImageAddInfo[1])){
            foreach ($lazadaImageAddInfo[1] as $imgVal) {
                if(!isset($skuImg[$imgVal['image_name']])){
                    continue;
                }

                $skuImgArr[$imgVal['image_name']] = $skuImg[$imgVal['image_name']];
            }
        }else{
            $skuImgArr = $skuImg;
        }

        //判断是否是多属性组合
        $size_multi_variation = false;
        if($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
            $size_multi_variation = true;
        }

        //取出类目属性
        $productAttributeArr = array();
        $productAttributeInfo = LazadaProductAddAttribute::model()->getAttributesByAddID($id);
        if($productAttributeInfo){
            foreach ($productAttributeInfo as $attrInfo) {
                //全部转换为小写字母
                $lowerName = strtolower($attrInfo['name']);
                $productAttributeArr[$lowerName] = $attrInfo['value'];
            }

            $productAttributeArr['seller_name'] = $addInfo['account_name'];
            $productAttributeArr['seller_sku']  = $addInfo['seller_sku'];
            $productAttributeArr['account_id']  = $addInfo['account_id'];
        }

        //查询类目属性
        $categoryAttribute = LazadaCategoryAttribute::model()->getCategoryAttributeToHtml($addInfo['category_id'], $size_multi_variation, $productAttributeArr);

        //查询是否是多属性
        $productAddVariationHtml = '';
        if($addInfo['listing_type'] == 3){
            $variationInfo = LazadaProductAddVariation::model()->getVariationByAddID($id);
            $variationCount = 1;
            foreach ($variationInfo as $variations) {
                //成功的子sku排除
                if($variations['status'] == LazadaProductAdd::UPLOAD_STATUS_SUCCESS){
                    continue;
                }

                $productAddVariationHtml .= '<tr type="add">';
                $productAddVariationHtml .= '<td><div id="multiVariation"><input  style="line-height:20px;font-size:20px"  type="text" name="variationValue[]" value="'.$variations['value'].'"/></div></td>';
                //子sku尺寸
                $productAddVariationHtml .= '<td><input type="text" class="text_price"  style="line-height:20px;font-size:20px"  name="variationSize[]" value="'.$variations['size'].'"/></td>';

                $productAddVariationHtml .= '<td><input style="line-height:20px;font-size:20px" type="text"  class="text_sku"  name="variationSku[]" value="'.$variations['sku'].'" /><input type="hidden" name="variationColor[]" value="0"/>
                <input type="hidden" name="variationID[]" value="'.$variations['variation_id'].'"/><input type="hidden" name="variationSellersku[]" value="'.$variations['seller_sku'].'"/></td>';                                                                                        
                //价格、卖价、销售开始日期、销售截止日期自动填充
                $productAddVariationHtml .= '<td><input type="text" class="text_price"  style="line-height:20px;font-size:20px"  name="variationPrice[]" value="'.$variations['price'].'"/></td>';
                $productAddVariationHtml .= '<td><input type="text"  class="text_price"   style="line-height:20px;font-size:20px"  name="variationSalePrice[]" value="'.$variations['sale_price'].'"/></td>';
                $productAddVariationHtml .= '<td><input type="text"  style="line-height:20px;font-size:20px"  name="variationSalePriceStart[]" value="'.$variations['sale_price_start'].'"/></td>';
                $productAddVariationHtml .= '<td><input type="text"  style="line-height:20px;font-size:20px"  name="variationSalePriceEnd[]" value="'.$variations['sale_price_end'].'"/></td>';
                if($variationCount != 1){
                    $productAddVariationHtml .= '<td><input type="button" onclick="removeVariation(this);" value="remove this row" /></td>';
                } else {
                    $productAddVariationHtml .= '<td><input type="button" style="display:none" onclick="removeVariation(this);" value="remove this row" /></td>';
                }
                $productAddVariationHtml .= '</tr>';

                $variationCount += 1;
            }
        }

        $this->render(
            '_form',
            array(
                'model'             => $lazadaProductAddModel, 
                'listingProduct'    => $addInfo, 
                'listingParam'      => $listingParam,
                'listingAttribute'  => $listingAttribute,
                'selectAttrPairs'   => $selectAttrPairs,
                'skuImg'            => $skuImgArr,
                'categoryAttribute' => $categoryAttribute,
                'variationHtml'     => $productAddVariationHtml
            )
        );
    }
    
    /**
     * @desc 上传刊登
     */
    public function actionUpload(){
        $addRecordIDs = Yii::app()->request->getParam('ids');

        LazadaProductAdd::model()->dbConnection->createCommand()->update(LazadaProductAdd::model()->tableName(), array(
                'upload_user_id'    => Yii::app()->user->id,
        ), 'id IN ('.$addRecordIDs.')');
        $addRecordIDs = explode(',', $addRecordIDs);
        $model = LazadaProductAdd::model();
        $status_default = LazadaProductAdd::UPLOAD_STATUS_DEFAULT;
        $status_failure = LazadaProductAdd::UPLOAD_STATUS_FAILURE;
        $status_parent_success = LazadaProductAdd::UPLOAD_STATUS_PARENT_SUCCESS;
        //一口价的0,5状态
        $add_list = LazadaProductAdd::model()->dbConnection->createCommand()
                        ->from(LazadaProductAdd::model()->tableName())
                        ->select('id')
                        ->where('id IN ('.MHelper::simplode($addRecordIDs) .')')
                        ->andWhere("status in ('{$status_default}','{$status_failure}')")
                        ->queryColumn();
        $return = false;
        if($add_list){
            //一口价
            $return = $model->uploadProduct($add_list);
        }

        if($return){
            $jsonData = array(
                'message' => Yii::t('system', 'Success And Wait For Feedback'),
            );
            echo $this->successJson($jsonData);
        } else {
            $jsonData = array(
                'message' => 'fail',
            );
            echo $this->failureJson($jsonData);
        }
        Yii::app()->end();
    }
    
    /**
     * @desc 待上传自动上传
     */
    public function actionAutoupload(){
        set_time_limit(2*3600);
        ini_set("display_errors", true);
        error_reporting(E_ALL);

        $accountID = Yii::app()->request->getParam('account_id');

        if( $accountID ){
            $model = new LazadaProductAdd();
            $adds = $model->getNeedUploadRecord($accountID);
            $model->uploadProduct($adds);
        } else {//循环可用账号，多线程抓取
            $lazadaAccounts = LazadaAccount::model()->getAbleAccountList();
            foreach($lazadaAccounts as $account){
                MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id']);
                sleep(120);
            }
        }
    }
    
    /**
     * @desc 自动上传到图片服务器
     */
    public function actionAutoUploadimage(){
        set_time_limit(2*3600);
        $accountID = Yii::app()->request->getParam('account_id');
        if( $accountID ){
            $skuLine = Yii::app()->request->getParam('sku_line');
            $records = LazadaProductAdd::model()->getUploadRecordByStatus($accountID, LazadaProductAdd::UPLOAD_STATUS_IMGFAIL, $skuLine);
            foreach($records as $record){
                LazadaProductImageAdd::model()->uploadImageOnline($record['sku'], $accountID);
            }
        }else{//循环可用账号，多线程抓取
            $lazadaAccounts = LazadaAccount::model()->getAbleAccountList();
            foreach($lazadaAccounts as $account){
                for($i=0;$i<=9;$i++){
                    MHelper::runThreadSOCKET('/'.$this->route.'/account_id/'.$account['id'].'/sku_line/'.$i);
                }
            }
        }
    }
    
    
    public function actionUploadimg() {
    	set_time_limit(3*3600);
    	ini_set('display_errors', true);
    	error_reporting(E_ALL);
    	//上传文件
    	if($_POST){
    		$isCheckAddList = Yii::app()->request->getParam("is_check_add");//是否通过检查待刊登列表，如果为0则直接对加密sku解密
    		$accountID = Yii::app()->request->getParam("account_id");//账号表的主键id
    		$isForceDel = $isCheckAddList == 2 ? true : false;
    		if($isForceDel){
    			$isCheckAddList = 0;
    		}
    		if(empty($accountID)){
    			echo $this->failureJson(array('message'=>"选择账号"));
    			Yii::app()->end();
    		}
    		if(empty($_FILES['csvfilename']['tmp_name'])){
    			echo $this->failureJson(array('message'=>"文件上传失败"));
    			Yii::app()->end();
    		}
    		$filename = $_FILES['csvfilename']['name'];
    		$filename = substr($filename, 0, strrpos($filename, "."));
    		$outputFile = UPLOAD_DIR . date("Y-m-dHis") . '-' . $filename .'-upload_image_result.csv';
    		$file = $_FILES['csvfilename']['tmp_name'];
    		$accountID = Yii::app()->request->getParam("account_id");//账号表的主键id
    		//根据账号的主键id获取
    		$accountInfo = LazadaAccount::model()->getAccountInfoById($accountID);
    		if(!$accountInfo){
    			echo $this->failureJson(array('message'=>"账号信息不存在！"));
    			Yii::app()->end();
    		}
    		$apiAccountID = $accountInfo['account_id'];
    		$siteId = $accountInfo['site_id'];
    		$groupNumber = 100;
    		$groupSkus = array();
    		Yii::import('application.vendors.MyExcel');
    		$PHPExcel = new MyExcel();
    		$data = $PHPExcel->get_excel_con($file);
    		//获取sku
    		$sellerSkus = array();
    		$skuList = array();
            $lazadaProductAdd = new LazadaProductAdd();
            $lazadaProductAddVariation = new LazadaProductAddVariation();
            $productImageAdd     = new ProductImageAdd();
            //查询账号表里的站点和老系统帐号ID
            $accountInfo = LazadaAccount::getAccountInfoById($accountID);
            if(!$accountInfo){
                echo $this->failureJson(array('message'=>'没有找到账号数据'));
                Yii::app()->end();
            }

            $platformCode = Platform::CODE_LAZADA;
            $siteId       = 1;

    		foreach ($data as $key => $rows) {
                $errorMessage = '';
    			$sellerSku = trim($rows['A']);
    			$sellerSku = trim($sellerSku, "'");
    			if ($key == 1){
    				if(strtolower($sellerSku) == 'sku' || strtolower($sellerSku) == 'sellersku') continue;
    			}
    			if (empty($sellerSku)) continue;
				$sku = encryptSku::getRealSku($sellerSku);
                $sku = trim($sku);
                $lazadaProductImageAdd = new LazadaProductImageAdd();
                $isImageList = $lazadaProductImageAdd->getImageBySku($sku, $accountID);
                if(!$isImageList){
                    $lazadaProductImageAdd->addGetJavaProductImageBySku($sku, $accountID, Platform::CODE_LAZADA);
                }else{
                    foreach($isImageList[LazadaProductImageAdd::IMAGE_ZT] as $delImage){
                        if($delImage['lazada_upload_status'] == 1){
                            continue;
                        }

                        //如果图片表里image_name字段没有.jpg
                        $getImageName = $delImage['image_name'];
                        $imagePosition = strpos($getImageName, '.jpg');
                        if(!$imagePosition){
                            $getImageName = $getImageName.'.jpg';
                        }

                        $imgUrl = '';
                        $response = $productImageAdd->getSkuImageUpload($accountID,$sku,array($getImageName), $platformCode, $siteId);
                        if(isset($response['result']['imageInfoVOs'][0]['remotePath'])){
                            $imgUrl = $response['result']['imageInfoVOs'][0]['remotePath'];
                        }

                        $lazadaProductImageAdd->getDbConnection()->createCommand()->update(
                            $lazadaProductImageAdd->tableName(), array(
                                'upload_status' => LazadaProductImageAdd::UPLOAD_STATUS_DEFAULT,
                                'local_path'    => $imgUrl,
                                'remote_path'   => $imgUrl,
                                'remote_type'   => LazadaProductImageAdd::REMOTE_TYPE_IMAGESERVER,
                        ),"id = '".$delImage['id']."'");
                    }
                }

                // $uploadServer = $lazadaProductImageAdd->uploadImageOnline($sku, $accountID, $isForceDel);//将图片上传至图片服务器
                // if(!$uploadServer){
                //     $errorMessage = '图片上传至图片服务器失败';
                //     continue;
                // }else{
                    $imageRequest = new ImageRequestNew();
                    $imageList = $lazadaProductImageAdd->getImageBySku($sku, $accountID);
                    if(empty($imageList) && !$isForceDel){
                        $imageList = $lazadaProductImageAdd->getImageBySku($sku, null);
                    }
                    if (empty($imageList)) {
                        continue;
                    }

                    $isUpload = true;
                    $hadPushImg = array();
                    foreach($imageList[LazadaProductImageAdd::IMAGE_ZT] as $image){
                        if (empty($image['remote_path']))
                            continue;

                        //判断是否成功上传了8张
                        // $imgCount = $lazadaProductImageAdd->getOneByCondition('count(0) as nums',"sku = '{$image['sku']}' and lazada_upload_status = 1 and type = 1 and account_id = ".$accountID);
                        // if($imgCount && (int)$imgCount['nums'] >= 8){
                        //     $isUpload = false;
                        //     break;
                        // }

                        $imgInfo = $lazadaProductImageAdd->getOneByCondition('img_url',"id = '{$image['id']}' and lazada_upload_status = 1");
                        if($imgInfo){
                            $hadPushImg[] = $imgInfo['img_url'];
                            continue;
                        }

                        $migrateImageRequest = new MigrateImageRequest();
                        $migrateImageRequest->pushImage($image['remote_path']);
                        $migrateImageRequest->push();
                        $response = $migrateImageRequest->setApiAccount($accountID)->setRequest()->sendRequest()->getResponse();
                        if ($migrateImageRequest->getIfSuccess()) {
                            if(isset($response->Body->Image->Url)){
                                $remotePath = (string)$response->Body->Image->Url;
                                $updateData = array(
                                    'img_url'              => $remotePath,
                                    'lazada_upload_status' => 1
                                );
                                $lazadaProductImageAdd->updateData($updateData, 'id = :id', array(':id'=>$image['id']));
                                // $imageRequest->pushImage($remotePath);
                                $hadPushImg[] = $remotePath;
                            }
                        }else{
                            break;
                        }        
                    }

                    if($hadPushImg){
                        $imageRequest->setSellerSku($sellerSku);
                        $imageRequest->pushImage($hadPushImg);
                        $imageRequest->push();

                        $response = $imageRequest->setApiAccount($accountID)->setRequest()->sendRequest()->getResponse();
                        if ($imageRequest->getIfSuccess()) {

                        } else {
                            $errorMessage = $imageRequest->getErrorMsg();
                        }
                    }
                // }
    		}

    		echo $this->successJson(array('message'=>'执行完成x！'));
    		Yii::app()->end();
    	}
    	//@TODO 暂时都是MY站点的账号其他的站点后续再说
    	$newaccounts = array();
    	$accounts = LazadaAccount::model()->getAbleAccountList(null);
    	if($accounts){
    		foreach ($accounts as $val){
    			$newaccounts[$val['id']] = $val['short_name'];
    		}
    	}
    	asort($newaccounts);
    	$this->render("uploadimg", array(
    		'accounts'=>$newaccounts
    	));
    	
    }
    /**
     * @desc 
     * @todo 待确定图片服务器存储机制，可以改动一个sku只上传一次图片，其他共用，直接调用地址即可
     */
    public function actionUploadimage() {
        set_time_limit(3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);
                
    	$filename = Yii::app()->request->getParam("filename");
    	$accountID = Yii::app()->request->getParam("id");//账号表的主键id

    	if(!$accountID){
    		exit("not account id ");
    	}
    	if(empty($filename)){
    		exit("使用filename指定文件名称，不带后缀");
    	}else{
    		$file = UPLOAD_DIR . $filename. '.xlsx';
    		$outputFile = UPLOAD_DIR . $filename . date("YmdHis").'-upload_image_result.csv';
    	}
    	Yii::import('application.vendors.MyExcel');
    	//根据账号的主键id获取
    	$accountInfo = LazadaAccount::model()->getAccountInfoById($accountID);
    	if(!$accountInfo){
    		exit("not found the account info ");
    	}
    	/*
    	$apiAccountID = 3;
    	$accountID = 15;
    	$siteId = 1;
    	*/
		$apiAccountID = $accountInfo['account_id'];
    	$siteId = $accountInfo['site_id'];
    	$groupNumber = 100;
    	$groupSkus = array();
    	$PHPExcel = new MyExcel();
    	$data = $PHPExcel->get_excel_con($file);
    	$isCheckAddList = 0;
    	//获取sku
    	$sellerSkus = array();
    	$skuList = array();
    	foreach ($data as $key => $rows) {
    		$sellerSku = trim($rows['A']);
    		$sellerSku = trim($sellerSku, "'");
    		if ($key == 1){
    			if($sellerSku == 'sku' || $sellerSku == 'SellerSku') continue;
    		}
    		if (empty($sellerSku)) continue;
    		$sellerSkus[] = $sellerSku;
    		if(!$isCheckAddList){
    			$sku = encryptSku::getRealSku($sellerSku);
    			$skuList[] = array('sku'=>$sku, 'seller_sku'=>$sellerSku);
    		}
    	}
    	/* $sellerSkuStr = "'" . implode("','", $skuS) . "'";
    	 echo $sellerSkuStr;
    	exit; */
    	if($isCheckAddList && $sellerSkus){
    		//$sellerSkuStr = "'".implode("','", $sellerSkus) . "'";
    		$lazadaProductAdd = LazadaProductAdd::model();
    		$skuList = $lazadaProductAdd->getDbConnection()->createCommand()
    		->from($lazadaProductAdd->tableName())
    		->select('sku,seller_sku')
    		->where(array("IN", "seller_sku", $sellerSkus))
    		->queryAll();
    	}
    	unset($sellerSkus);
    	//将SKU分成若干组
    	$groupSkus = array();
    	$skuCount = count($skuList);
    	for ($i = 0; $i < $skuCount; $i+=$groupNumber){
    		$diffCount = $skuCount - $i;
    		if($diffCount<100){
    			$groupSkus[] = array_slice($skuList, $i);
    		}else{
    			$groupSkus[] = array_slice($skuList, $i, $groupNumber);
    		}
    	}
    	
    	//上传图片
    	$fp = fopen($outputFile, 'w');
    	foreach ($groupSkus as $rows) {
    		$skuArr = array();
    		$imageRequest = new ImageRequestNew();
    		foreach ($rows as $k => $skus) {
    			$sku = trim($skus['sku'], "'");
    			$sellerSku = trim($skus['seller_sku'], "'");
    			$outputData = array();
    			$lazadaProductImageAdd = LazadaProductImageAdd::model();
    			if(!$isCheckAddList){
    				$lazadaProductImageAdd->addProductImageBySku($sku, $accountID);
    			}
    			$uploadServer = $lazadaProductImageAdd->uploadImageOnline($sku, $accountID);//将图片上传至图片服务器
    			if (!$uploadServer) {
    				$outputData[] = "'".$sku;
    				$outputData[] = "'".$sellerSku;
    				$outputData[] = 'upload failure';
    				$outputData[] = $lazadaProductImageAdd->getErrorMessage();
    				fputcsv($fp, $outputData);
    				continue;
    			} else {
    				$imageList = $lazadaProductImageAdd->getImageBySku($sku, $accountID, Platform::CODE_LAZADA);
    				if(empty($imageList)){
    					$imageList = $lazadaProductImageAdd->getImageBySku($sku, null, Platform::CODE_LAZADA);
    				}
    				if (empty($imageList)) {
    					fputcsv($fp, array("'".$sku, "'".$sellerSku, 'Failure', 'No Pictur'));
    					continue;
    				}
    				$count = 1;
    				$hadPushImg = array();
    				foreach($imageList[LazadaProductImageAdd::IMAGE_ZT] as $image){
    					if ($count >= LazadaProductImageAdd::MAX_IMAGE_NUMBER)
    						break;
    					if (empty($image['remote_path']))
    						continue;
    					if(in_array($image['remote_path'], $hadPushImg)){
    						continue;
    					}
    					$hadPushImg[] = $image['remote_path'];
    					$imageRequest->pushImage($image['remote_path']);
    					$count++;
    				}
    				if ($count == 1) {
    					fputcsv($fp, array("'".$sku, "'".$sellerSku, 'Failure', 'No Pictur'));
    					continue;
    				}
    				$imageRequest->setSellerSku($sellerSku);
    				$imageRequest->push();
    				$skuArr[$k][0] = $sku;
    				$skuArr[$k][1] = $sellerSku;
    			}
    		}
    		// $imageRequest->setSiteID($siteId)->setAccount($apiAccountID);
    		$response = $imageRequest->setApiAccount($accountID)->setRequest()->sendRequest()->getResponse();
    		if (!$imageRequest->getIfSuccess()) {
    			foreach ($skuArr as $k => $sku) {
    				fputcsv($fp, array("'".$sku[0], "'".$sku[1], 'Failure', $imageRequest->getErrorMsg()));
    			}
    		} else {
    			$outputData = array();
    			foreach ($skuArr as $k => $sku) {
    				fputcsv($fp, array("'".$sku[0], "'".$sku[1], 'Success', ''));
    			}
    		}
    		fputcsv($fp, $outputData);
    	}
    	fclose($fp);
    	exit('DONE');
    }
    
    /**
     * @desc 根据主sku和站点获取子sku以及对应的属性和销售价格
     * 
     */
    public function getVariationinfo($sku, $site_id, $edit_id = null){
        $sku_info = Product::model()->getBySku($sku);
        $multi_product_id = $sku_info['id'];
        $discountTpl = LazadaProductAdd::model()->getDiscountTpl();
        //var_dump($discountTpl);exit;
        //$multi_product_id = 155505;   测试数据
        
        //获取到当前主产品对应下面所有的子产品sku和对应属性
        $productSelectAttribute = new ProductSelectAttribute();
        $attributeSkuList = $productSelectAttribute->getSelectedAttributeValueSKUListByMainProductId($multi_product_id);
        //var_dump($attributeSkuList);exit;
        $variationinfo = array();
        $variationSkuIDs = array();

        //编辑产品时，判断子sku是否已经上传成功
        if($edit_id){
            $getVariationInfo = LazadaProductAddVariation::model()->getSonVariationByAddID($edit_id);
            if($getVariationInfo){
                foreach ($getVariationInfo as $variationVal) {
                    $variationSkuIDs[$variationVal['sku']] = array('var_id'=>$variationVal['id'],'var_status'=>$variationVal['status']);
                }
            }
        }

        foreach ($attributeSkuList as $key => $value){
            $sku = $value['sku'];
            
            if(isset($variationSkuIDs[$sku]['var_status']) && $variationSkuIDs[$sku]['var_status']==LazadaProductAdd::UPLOAD_STATUS_SUCCESS){
                continue;
            }

            $variationinfo[$sku]['variation_id'] = isset($variationSkuIDs[$sku]['var_id'])?$variationSkuIDs[$sku]['var_id']:'';

            if(!isset($variationinfo[$sku])){
                $variationinfo[$sku] = array();
            }
            
            //颜色
            if(!isset($variationinfo[$sku]['color_id'])){
                $variationinfo[$sku]['color_id'] = 0;
            }

            if( $value['attribute_id'] == 22){
                $variationinfo[$sku]['color_id'] = $value['attribute_value_id'];
            }
            
            if($value['attribute_id'] == 23){
                //size 列表选值
                $variationinfo[$sku]['list_value'] = trim($value['attribute_value_name']);
            } else {
                //input 自动填充
                if(!isset($variationinfo[$sku]['input_value'])){
                    $variationinfo[$sku]['input_value'] = '';
                    $variationinfo[$sku]['list_value'] = '';
                }
                if($variationinfo[$sku]['input_value'] == ''){
                    $variationinfo[$sku]['input_value'] = trim($value['attribute_value_name']);
                } else {
                    $variationinfo[$sku]['input_value'] .= ',' . trim($value['attribute_value_name']);
                }
                
            }
            
            if(!isset($variationinfo[$sku]['sku'])){
                $variationinfo[$sku]['sku'] = '';
            }
            if($variationinfo[$sku]['sku'] == $sku){
                continue;
            }
            $variationinfo[$sku]['sku'] = $sku;
            //根据sku和site_id获取价格
            if(!isset($variationinfo[$sku]['sale_price'])){
                $variationinfo[$sku]['sale_price'] = 0;
            }
            if($variationinfo[$sku]['sale_price'] > 0){
                continue;
            } else {
                $variationinfo[$sku]['sale_price'] = LazadaProduct::model()->getPriceBySku($sku, $site_id);
                $variationinfo[$sku]['sale_price_start'] = $discountTpl['start_date'];
                $variationinfo[$sku]['sale_price_end'] = $discountTpl['end_date'];
                $variationinfo[$sku]['price'] = round($variationinfo[$sku]['sale_price'] / $discountTpl['discount'], 2);
            }
        }

        return $variationinfo;
    }


    /**
     * 自动上传图片
     * /lazada/lazadaproductadd/autouploadimages/account_id/20/sku/127277
     */
    public function actionAutouploadimages(){
        set_time_limit(3*3000);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $setSku = Yii::app()->request->getParam("sku");
        $accountID = Yii::app()->request->getParam("account_id");//账号表的主键id

        $lazadaAccountModel  = new LazadaAccount();
        $lazadaImageAddModel = new LazadaProductImageAdd();
        $lazadaProductModel  = new LazadaProduct();
        $logModel            = new LazadaLog();
        $platformCode        = Platform::CODE_LAZADA;
        $siteId              = 1;
        $productImageAdd     = new ProductImageAdd();

        if($accountID){

            //创建运行日志        
            $logId = $logModel->prepareLog($accountID, LazadaLog::AUTO_UPLOAD_IMAGES);
            if(!$logId) {
                echo Yii::t('wish_listing', 'Log create failure');
                Yii::app()->end();
            }
            //检查账号是可以提交请求报告
            $checkRunning = $logModel->checkRunning($accountID, LazadaLog::AUTO_UPLOAD_IMAGES);
            if(!$checkRunning){
                $logModel->setFailure($logId, Yii::t('systems', 'There Exists An Active Event'));
                echo Yii::t('systems', 'There Exists An Active Event');
                Yii::app()->end();
            }
            //设置日志为正在运行
            $logModel->setRunning($logId);

            try{
                $command = $lazadaImageAddModel->getDbConnection()->createCommand()
                    ->from($lazadaImageAddModel->tableName() . " as i")
                    ->leftJoin($lazadaProductModel->tableName() . ' p', 'p.sku=i.sku AND p.account_auto_id = i.account_id')
                    ->select("i.id,i.sku,(SELECT a.seller_sku FROM `ueb_lazada_product_add` a WHERE a.account_id = i.account_id AND a.sku = i.sku AND a.status = 1 ORDER BY a.id DESC LIMIT 1) AS seller_sku, p.seller_sku AS online_sku")
                    ->where('i.account_id = '.$accountID)
                    ->andWhere("i.lazada_upload_status = 0");
                    if($setSku){
                        $command->andWhere("i.sku = '".$setSku."'");
                    }
                $command->group('i.sku');
                $variantListing = $command->queryAll(); 
                if(!$variantListing){
                    $logModel->setFailure($logId, '此账号无数据');
                    exit("此账号无数据");
                }

                foreach ($variantListing as $info) {
                    $isForceDel = false;
                    $sku = $info['sku'];
                    $sellerSku = $info['seller_sku'];
                    if(!$sellerSku){
                        $sellerSku = $info['online_sku'];
                    }

                    if(!$sellerSku){
                        continue;
                    }

                    $lazadaProductImageAdd = new LazadaProductImageAdd();
                    $isImageList = $lazadaProductImageAdd->getImageBySku($sku, $accountID);
                    if(!$isImageList){
                        $lazadaProductImageAdd->addProductImageBySku($sku, $accountID);
                    }else{
                        foreach($isImageList[LazadaProductImageAdd::IMAGE_ZT] as $delImage){
                            if($delImage['lazada_upload_status'] == 1){
                                continue;
                            }

                            //如果图片表里image_name字段没有.jpg
                            $getImageName = $delImage['image_name'];
                            $imagePosition = strpos($getImageName, '.jpg');
                            if(!$imagePosition){
                                $getImageName = $getImageName.'.jpg';
                            }

                            $imgUrl = '';
                            $response = $productImageAdd->getSkuImageUpload($accountID,$sku,array($getImageName), $platformCode, $siteId);
                            if(isset($response['result']['imageInfoVOs'][0]['remotePath'])){
                                $imgUrl = $response['result']['imageInfoVOs'][0]['remotePath'];
                            }

                            $lazadaProductImageAdd->getDbConnection()->createCommand()->update(
                                $lazadaProductImageAdd->tableName(), array(
                                    'upload_status' => LazadaProductImageAdd::UPLOAD_STATUS_DEFAULT,
                                    'local_path' => $imgUrl,
                                    'remote_path'   => $imgUrl,
                                    'remote_type'   => LazadaProductImageAdd::REMOTE_TYPE_IMAGESERVER,
                            ),"id = '".$delImage['id']."'");
                        }
                    }

                    // $uploadServer = $lazadaProductImageAdd->uploadImageOnline($sku, $accountID, $isForceDel);//将图片上传至图片服务器
                    // if(!$uploadServer){
                    //     $errorMessage = '图片上传至图片服务器失败';
                    //     continue;
                    // }else{
                        $imageRequest = new ImageRequestNew();
                        $imageList = $lazadaProductImageAdd->getImageBySku($sku, $accountID);
                        if(empty($imageList) && !$isForceDel){
                            $imageList = $lazadaProductImageAdd->getImageBySku($sku, null);
                        }
                        if (empty($imageList)) {
                            continue;
                        }

                        $isUpload = true;
                        $remotePathArr = array();
                        foreach($imageList[LazadaProductImageAdd::IMAGE_ZT] as $image){
                            if (empty($image['remote_path']))
                                continue;

                            //判断是否成功上传了8张
                            $imgCount = $lazadaProductImageAdd->getOneByCondition('count(0) as nums',"sku = '{$image['sku']}' and lazada_upload_status = 1 and type = 1 and account_id = ".$accountID);
                            if($imgCount && (int)$imgCount['nums'] >= 8){
                                $isUpload = false;
                                break;
                            }

                            $imgInfo = $lazadaProductImageAdd->getOneByCondition('img_url',"id = '{$image['id']}' and lazada_upload_status = 1");
                            if($imgInfo){
                                $remotePathArr[] = $imgInfo['img_url'];
                                continue;
                            }

                            $migrateImageRequest = new MigrateImageRequest();
                            $migrateImageRequest->pushImage($image['remote_path']);
                            $migrateImageRequest->push();
                            $response = $migrateImageRequest->setApiAccount($accountID)->setRequest()->sendRequest()->getResponse();
                            if ($migrateImageRequest->getIfSuccess()) {
                                if(isset($response->Body->Image->Url)){
                                    $remotePath = (string)$response->Body->Image->Url;
                                    $updateData = array(
                                        'img_url'              => $remotePath,
                                        'lazada_upload_status' => 1
                                    );
                                    $lazadaProductImageAdd->updateData($updateData, 'id = :id', array(':id'=>$image['id']));
                                    $remotePathArr[] = $remotePath;
                                }
                            }else{
                                break;
                            }        
                        }

                        if($isUpload){
                            $imageRequest->setSellerSku($sellerSku);
                            $imageRequest->pushImage($remotePathArr);
                            $imageRequest->push();

                            $response = $imageRequest->setApiAccount($accountID)->setRequest()->sendRequest()->getResponse();
                            if ($imageRequest->getIfSuccess()) {

                            } else {
                                $errorMessage = $imageRequest->getErrorMsg();
                            }
                        }
                    // }
                }

            $logModel->setSuccess($logId);

            }catch (Exception $e){
                $logModel->setFailure($logId, $e->getMessage());
                echo $e->getMessage()."<br/>";
            }

        }else{
            $accountList = $lazadaAccountModel->getListByCondition('id','`status` = 1 and is_lock = 0');
            foreach($accountList as $value){
                MHelper::runThreadSOCKET('/'.$this->route.'/account_id/' . $value['id']);
                sleep(50);
            }
        }       
    }


    /**
     * 通过输入sku查看链接
     * /lazada/lazadaproductadd/viewskuimages/sku/64166
     */
    public function actionViewskuimages(){
        $sku = Yii::app()->request->getParam("sku");
        $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku);
        if(!isset($skuImg['ft']) || empty($skuImg['ft'])){
            exit('此sku暂时没有查询到图片');
        }
        
        $imgArr = '';
        foreach ($skuImg['ft'] as $key => $value) {
            $imgArr .= str_replace('?width=100&height=100', '', $value).'<br>';
        }

        echo $imgArr;
    }


    /**
     * @desc 成功产品查看lazada待刊登信息
     * @author hanxy
     */
    public function actionView(){
        $id = Yii::app()->request->getParam('id');

        //通过id查询产品是否存在
        $lazadaProductAddModel = new LazadaProductAdd();
        $addInfo = $lazadaProductAddModel->findByPk($id);
        if(!$addInfo){
            echo $this->failureJson(array('message'=>'产品不存在'));
            Yii::app()->end();
        }

        //如果上传成功的产品不能修改
        if($addInfo['status'] != LazadaProductAdd::UPLOAD_STATUS_SUCCESS){
            echo $this->failureJson(array('message'=>'此产品不是上传成功产品，不能进行查看'));
            Yii::app()->end();
        }

        // //1.验证sku
        $skuInfo = Product::model()->getProductInfoBySku($addInfo['sku']);
        if(!$skuInfo){
            echo $this->failureJson(array('message' => 'Sku Not Exists'));
            Yii::app()->end();
        }

        //1.准备刊登信息(分单品和多属性)
        /**@ 获取刊登参数*/
        //获取刊登类型
        $listingTypeArr = LazadaProductAdd::getListingType();
        //获取站点
        $listingSiteArr = LazadaSite::getSiteList();
        //获取平台可用价格促销方案
        $pricePromotionArr = UebModel::model('PricePromotionScheme')->getPricePromotionScheme(Platform::CODE_LAZADA, true);
        $pricePromotionList = array();
        if (!empty($pricePromotionArr)) {
            foreach ($pricePromotionArr as $pricePromotion)
                $pricePromotionList[$pricePromotion['id']] = $pricePromotion['name'];
        }

        $listingParam = array(
                'listing_type'      => array('id' => $addInfo['listing_type'], 'text' => $listingTypeArr[$addInfo['listing_type']]),
                'listing_site'      => array('id' => $addInfo['site_id'],      'text' => $listingSiteArr[$addInfo['site_id']]),
                'promotion_list'    => $pricePromotionList,
        );

        //获取类目名称
        $categoryInfo = LazadaCategory::model()->getCategotyInfoByID($addInfo['category_id']);
        $addInfo['category_name'] = isset($categoryInfo['category_name'])?$categoryInfo['category_name']:'';

        /**@ 获取产品属性*/
        $listingAttribute = array();
        $attributeListData = UebModel::model('ProductAttributeMap')->getListValueData(3);
        if(isset($attributeListData[3])){
            $listingAttribute = $attributeListData[3];
        }
        $selectAttrPairs = ProductSelectAttribute::model()->getAttrList($skuInfo['id']);

        //获取账号名称
        $accountInfo = LazadaAccount::getAccountInfoById($addInfo['account_id']);
        $addInfo['account_name'] = isset($accountInfo['seller_name'])?$accountInfo['seller_name']:'';

        //获取图片
        $skuImgArr = array();
        //使用java组api获取产品图片显示
        $skuImgJava = ProductImageAdd::getImageUrlFromRestfulBySku($addInfo['sku'], $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = Platform::CODE_LAZADA);
        if (isset($skuImgJava['ft'])) {
            $skuImg = $skuImgJava['ft'];
        }

        $lazadaImageAddInfo = LazadaProductImageAdd::model()->getImageBySku($addInfo['sku'], $addInfo['account_id']);
        if($lazadaImageAddInfo && isset($lazadaImageAddInfo[1]) && !empty($lazadaImageAddInfo[1])){
            foreach ($lazadaImageAddInfo[1] as $imgVal) {
                if(!isset($skuImg[$imgVal['image_name']])){
                    continue;
                }

                $skuImgArr[$imgVal['image_name']] = $skuImg[$imgVal['image_name']];
            }
        }else{
            $skuImgArr = $skuImg;
        }

        //判断是否是多属性组合
        $size_multi_variation = false;
        if($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
            $size_multi_variation = true;
        }

        //取出类目属性
        $productAttributeArr = array();
        $productAttributeInfo = LazadaProductAddAttribute::model()->getAttributesByAddID($id);
        if($productAttributeInfo){
            foreach ($productAttributeInfo as $attrInfo) {
                //全部转换为小写字母
                $lowerName = strtolower($attrInfo['name']);
                $productAttributeArr[$lowerName] = $attrInfo['value'];
            }

            $productAttributeArr['seller_name'] = $addInfo['account_name'];
            $productAttributeArr['seller_sku']  = $addInfo['seller_sku'];
            $productAttributeArr['account_id']  = $addInfo['account_id'];
        }

        //查询类目属性
        $categoryAttribute = LazadaCategoryAttribute::model()->getCategoryAttributeToHtml($addInfo['category_id'], $size_multi_variation, $productAttributeArr);

        //查询是否是多属性
        $productAddVariationHtml = '';
        if($addInfo['listing_type'] == 3){
            $variationInfo = LazadaProductAddVariation::model()->getVariationByAddID($id);
            foreach ($variationInfo as $variations) {
                $productAddVariationHtml .= '<tr type="add">';
                $productAddVariationHtml .= '<td><div id="multiVariation"><input  style="line-height:20px;font-size:20px"  type="text" name="variationValue[]" value="'.$variations['value'].'"/></div></td>';
                //子sku尺寸
                $productAddVariationHtml .= '<td><input type="text" class="text_price"  style="line-height:20px;font-size:20px"  name="variationSize[]" value="'.$variations['size'].'"/></td>';

                $productAddVariationHtml .= '<td><input style="line-height:20px;font-size:20px" type="text"  class="text_sku"  name="variationSku[]" value="'.$variations['sku'].'" /></td>';                                                                                        
                //价格、卖价、销售开始日期、销售截止日期自动填充
                $productAddVariationHtml .= '<td><input type="text" class="text_price"  style="line-height:20px;font-size:20px"  name="variationPrice[]" value="'.$variations['price'].'"/></td>';
                $productAddVariationHtml .= '<td><input type="text"  class="text_price"   style="line-height:20px;font-size:20px"  name="variationSalePrice[]" value="'.$variations['sale_price'].'"/></td>';
                $productAddVariationHtml .= '<td><input type="text"  style="line-height:20px;font-size:20px"  name="variationSalePriceStart[]" value="'.$variations['sale_price_start'].'"/></td>';
                $productAddVariationHtml .= '<td><input type="text"  style="line-height:20px;font-size:20px"  name="variationSalePriceEnd[]" value="'.$variations['sale_price_end'].'"/></td>';
                $productAddVariationHtml .= '</tr>';
            }
        }

        $this->render(
            'view',
            array(
                'model'             => $lazadaProductAddModel, 
                'listingProduct'    => $addInfo, 
                'listingParam'      => $listingParam,
                'listingAttribute'  => $listingAttribute,
                'selectAttrPairs'   => $selectAttrPairs,
                'skuImg'            => $skuImgArr,
                'categoryAttribute' => $categoryAttribute,
                'variationHtml'     => $productAddVariationHtml
            )
        );
    }
}