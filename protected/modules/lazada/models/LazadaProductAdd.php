<?php
/**
 * @desc Lazada刊登
 * @author Gordon
 * @since 2015-08-12
 */
class LazadaProductAdd extends LazadaModel{
    
    /**@var 事件名称*/
    const EVENT_NAME = 'lazada_product_add';
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var int 站点ID*/
    public $_siteID = 0;
    
    /** @var string 异常信息*/
    public $_exception = null;
    
    /** @var string 刊登任务ID*/
    public $addID = null;
    
    /** @var string 多属性id*/
    public $variationID = null;
    
    /** @var string 分类名称*/
    public $category_name;
    /** @var string 上传结果*/
    public $upload_result;
    /** @var string 状态说明*/
    public $status_desc;
    
    /** @var string 站点名 **/
    public $site_name = null;
    
    /** @var string 账号名 **/
    public $account_name = null;

    public $discountTpl = array();
    public $detail;
    public $modelString;
    public $is_visible;
    public $success_is_visible;
    
    private $_errorMsg = "";
    
    /**@var 刊登类型*/
    const LISTING_TYPE_FIXEDPRICE   = 2;//一口价
    const LISTING_TYPE_VARIATION    = 3;//多属性
    
    /**@var 刊登模式*/
    const LISTING_MODE_EASY = 1;//简单模式
    const LISTING_MODE_ALL = 2;//详细模式
    
    /**@var 上传状态*/
    const UPLOAD_STATUS_DEFAULT_MAPPING = -1;//映射到待上传
    const UPLOAD_STATUS_DEFAULT         = 0;//待上传
    const UPLOAD_STATUS_RUNNING         = 1;//上传中
    const UPLOAD_STATUS_IMGFAIL         = 2;//等待上传图片
    const UPLOAD_STATUS_IMGRUNNING      = 3;//图片上传中
    const UPLOAD_STATUS_SUCCESS         = 4;//上传成功
    const UPLOAD_STATUS_FAILURE         = 5;//上传失败
    const UPLOAD_STATUS_PARENT_SUCCESS  = 6;//父sku已上传
    
    const MAX_NUM_PER_TASK = 50;
    
    const ADD_TYPE_DEFAULT = 0;//默认
    const ADD_TYPE_BATCH   = 1;//批量
    const ADD_TYPE_PRE     = 2;//预刊登
    const ADD_TYPE_COPY    = 3;//复制刊登

    //替换的栏目名
    private $_ReplaceLabelName = array(
                            'Platform'=>'PlatformType',
                            'StorageCapacityNew'=>'StorageCapacity',
                            'FaPattern' => 'Pattern',
                            'OsCompatibility' => 'CompatibleOperatingSystem',
                            'SmartwearSize' => 'WatchSize',
                            'SmartwearModelCompa' => 'ModelCompatibility',
                            'CompatibilityByModel' => 'CompatibilitybyModel',
                            'PtbSpeakerFeatures' => 'PortableSpeakerFeatures',
                            'CableTypeTagg' => 'CableType',
                            'BeddingSize2' => 'BeddingSize_2',
                            'PantiesStyles' => 'PantyType',
                            'BrasTypes' => 'BraType',
                            'PantsLength' => 'Length'
                        );
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 验证规则匹配
     * @see CModel::rules()
     */
    public function rules(){
    	return array(
    		array('sku,title,category_id','required'),
    		array('listing_type','in','range'=>array(2,3)),
    		array('price,sale_price','numerical'),
    	);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_lazada_product_add';
    }
    
    /**
     * @desc 获取刊登方式
     * @param string $addType
     * @return multitype:string |string
     */
    public function getProductAddTypeOptions($addType = null){
    	$addTypeOptions = array(
    			self::ADD_TYPE_DEFAULT	=>	'默认',
    			self::ADD_TYPE_BATCH	=>	'批量',
    			self::ADD_TYPE_PRE		=>	'预刊登',
                self::ADD_TYPE_COPY     =>  '复制刊登'
    	);
    	if(is_null($addType)) return $addTypeOptions;
    	return isset($addTypeOptions[$addType]) ? $addTypeOptions[$addType] : '';
    }
    
    /**
     * @desc 获取刊登类型
     * @param int $type
     */
    public static function getListingType($type = ''){
    	if($type != ''){
    		switch ($type){
    			case '2':
    				return Yii::t('lazada', 'FixedFrice');
    				break;
    			case '3':
    				return Yii::t('lazada', 'Variation');
    				break;
    		}
    	}
        return array(
            self::LISTING_TYPE_FIXEDPRICE   => Yii::t('lazada', 'FixedFrice'),
            self::LISTING_TYPE_VARIATION    => Yii::t('lazada', 'Variation'),
        );
    }
    
    /**
     * @desc 获取刊登模式
     */
    public static function getListingMode(){
        return array(
            self::LISTING_MODE_EASY     => Yii::t('lazada', 'Easy'),
//             self::LISTING_MODE_ALL      => Yii::t('lazada', 'All'),
        );
    }
    
    /**
     * @desc 根据sku获取可刊登账号
     * @param string $sku
     */
    public function getAbleAccountsBySku($sku, $siteID){
        $excludeAccounts = array();
        //获取sku在线listing
        $listOnline = LazadaProduct::model()->getOnlineListingBySku($sku);
        if ($listOnline) {
            foreach($listOnline as $item){
                if ($item['site_id'] == $siteID) {
                    $accountInfo = LazadaAccount::model()->getApiAccountByIDAndSite($item['account_id'], $item['site_id']);
                    $excludeAccounts[$accountInfo['id']] = $accountInfo['id'];
                }
            } 
        }

        //获取待刊登记录并和线上sku记录合并
        $listTask = $this->getListingPrepareUploadBySku($sku);
        if ($listTask) {
            foreach($listTask as $item){
                if ($item['site_id'] == $siteID) {
                    $excludeAccounts[$item['account_id']] = $item['account_id'];
                }
            }
        }
        
        //查询账户列表
        $accountAll = LazadaAccount::getAbleAccountList($siteID);
        $accounts = array();$accountInfo = array();
        foreach($accountAll as $account){
            //TODO 排除锁定状态设定为无法刊登的账号
            $accounts[$account['id']] = $account['id'];
        }
        $ableAccounts = array_diff($accounts,$excludeAccounts);
        foreach($accountAll as $account){
            if( in_array($account['id'], $ableAccounts) ){
                $accountInfo[$account['account_id']] = $account['seller_name'];
            }
        }
        return $accountInfo;
    }
    
    /**
     * @desc 根据sku获取待刊登信息
     * @param unknown $sku
     * @return multitype:
     */
    public function getListingPrepareUploadBySku($sku){
        return $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('sku = "'.$sku.'"')
                //->andWhere('status != '.self::UPLOAD_STATUS_SUCCESS)
                ->queryAll();
    }
    
    /**
     * @desc 属性规则
     * @see CModel::attributeLabels()
     */
    public function attributeLabels(){
    	return array(
    	       'id'                => Yii::t('system', 'No.'),
    	       'sku'               => Yii::t('lazada', 'Sku'),
    		   'seller_sku'		   => Yii::t('lazada', 'Seller Sku'),
    	       'listing_type'              => Yii::t('lazada', 'Listing Type'),
    	       'title'      	   => Yii::t('lazada', 'Title'),
    	       'categoryname'      => Yii::t('lazada', 'Product Category'),
    	       'create_time'	   => Yii::t('system', 'Create Time'),
    	       'upload_time'	   => Yii::t('system', 'Modify Time'),
    	       'create_user_id'	   => Yii::t('system', 'Create User'),
    	       'upload_time'	   => Yii::t('system', 'Upload Time'),
    	       'upload_user_id'	   => Yii::t('system', 'Upload User'),
    	       'status'	           => Yii::t('lazada', 'Status'),
    	       'sub_upload_status' => Yii::t('lazada', 'Sub Status'),
    	       'parent_sku'	   => Yii::t('lazada', 'Parent Sku'),
    	       'status_text'	   => Yii::t('lazada', 'Sub Status'),
    	       'price'			   => Yii::t('lazada', 'Price'),
    	       'productId'		   => Yii::t('lazada', 'ProductID'),
    	       'upload_message'	   => Yii::t('lazada', 'Message'),
    		   'site_name'		   => Yii::t('lazada', 'Site Name'),
    		   'account_name'	   => Yii::t('lazada', 'Account Name'),
    		   'account_id'		   => Yii::t('lazada', 'Account Name'),
    		   'add_type'		   => '添加方式',
               'modelString'       => 'model',
               'sale_price'        => Yii::t('lazada', 'Sale Price'),
    	);
    }
    
    /**
     * @desc 生成条件搜索输入框模糊匹配查询
     */
    public function filterOptions(){
    	$result = array(
    			array(
    					'name'      => 'sku',
    					'type'      => 'text',
    					'search'    => '=',
    					'alias'     => 't',
    				),
    			array(
    					'name'       => 'listing_type',
    					'type'	     => 'dropDownList',
    					'search'     => '=',
    					'data'		 => self::getListingType(),
    					'htmlOptions'=> array(),
    					'alias'	     => 't',
    			),
    			array(
    					'name'       => 'account_id',
    					'type'	     => 'dropDownList',
    					'search'     => '=',
    					'data'		 => LazadaAccount::model()->getAccountListWithID(1),
    					'htmlOptions'=> array(),
    					'alias'	     => 't',
    			),
    			array(
    					'name'		 => 'title',
    					'type'		 => 'text',
    					'search'	 => 'LIKE',
    					'alias'	     => 't',
    			),
    			array(
    					'name'          => 'create_time',
    					'type'          => 'text',
    					'search'        => 'RANGE',
    					'htmlOptions'   => array(
    							'class'    => 'date',
    							'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
    					),
    					'alias'			=> 't',
    			),
    			array(
    					'name'          => 'upload_time',
    					'type'          => 'text',
    					'search'        => 'RANGE',
    					'htmlOptions'   => array(
    							'class'    => 'date',
    							'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
    					),
    					'alias'			=> 't',
    			),
//    			array(
//    					'name'       => 'create_user_id',
//    					'type'	     => 'dropDownList',
//    					'search'     => '=',
//    					'data'		 => MHelper::getUserInfoList(),
//    					'htmlOptions'=> array(),
//    					'alias'	     => 't',
//    			),
//    			array(
//    					'name'       => 'upload_user_id',
//    					'type'	     => 'dropDownList',
//    					'search'     => '=',
//    					'data'		 => MHelper::getUserInfoList(),
//    					'htmlOptions'=> array(),
//    					'alias'	     => 't',
//    			),
    			array(
    					'name'       => 'status',
    					'type'	     => 'dropDownList',
    					'search'     => '=',
    					'data'       => self::getStatusList(),
						'htmlOptions'=> array(),
    					'alias'	     => 't',
                                        'rel'		=>	true,
                                        //'value'      => self::UPLOAD_STATUS_DEFAULT, 
                                        //'notAll'     => true,
    			),
                        array(
    					'name'		 => 'upload_message',
    					'type'		 => 'text',
    					'search'	 => 'LIKE',
    					'alias'          => 't',
    			),
                        array(
                                        'name'		=>	'sub_upload_status',
                                        //'label'		=>	Yii::t('wish_listing', 'Upload status'),
                                        'search'	=>	'=',
                                        'type'		=>	'dropDownList',
                                        'data'		=>	self::getStatusList(),
                                        'rel'		=>	true,
                                        //'notAll'     => true,
                        ),
    			array(
    					'name'		=>	'add_type',
    					'search'	=>	'=',
    					'type'		=>	'dropDownList',
    					'data'		=>	self::getProductAddTypeOptions(),
    			),
    			
    	);
    	return $result;
    }
    
    /**
     * @desc 获取状态列表
     * @param string $status
     */
    public static function getStatusList($status = null){
        $statusArr = array(
                //self::UPLOAD_STATUS_DEFAULT     => Yii::t('lazada', 'UPLOAD_STATUS_DEFAULT'),
                self::UPLOAD_STATUS_DEFAULT_MAPPING     => Yii::t('lazada', 'UPLOAD_STATUS_DEFAULT'),
                self::UPLOAD_STATUS_RUNNING     => Yii::t('lazada', 'UPLOAD_STATUS_RUNNING'),
                self::UPLOAD_STATUS_IMGFAIL     => Yii::t('lazada', 'UPLOAD_STATUS_IMGFAIL'),
                self::UPLOAD_STATUS_IMGRUNNING  => Yii::t('lazada', 'UPLOAD_STATUS_IMGRUNNING'),
                self::UPLOAD_STATUS_SUCCESS     => Yii::t('lazada', 'UPLOAD_STATUS_SUCCESS'),
                self::UPLOAD_STATUS_FAILURE     => Yii::t('lazada', 'UPLOAD_STATUS_FAILURE'),
                self::UPLOAD_STATUS_PARENT_SUCCESS     => Yii::t('lazada', 'UPLOAD_STATUS_PARENT_SUCCESS'),
        ); 
        if($status===null){
            return $statusArr;
        }else{
            if($status == 0){
                $status = -1;
            }
            return $statusArr[$status];
        }
    }
    
    /**
     * @desc 关联查询
     * @see UebModel::search()
     */
    public function search(){
    	$sort = new CSort();
    	$sort->attributes = array('defaultOrder'=>'t.id');
    	$dataProvider = parent::search(get_class($this), $sort,array(),$this->_setCDbCriteria());
    	$data = $this->addition($dataProvider->data);
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
    
    /**
     * @desc 创建Criteria对象
     * @return CDbCriteria
     */
    protected function _setCDbCriteria(){
    	$criteria = new CDbCriteria;
    	$criteria->select = 't.id,t.site_id,t.account_id,t.sku,t.seller_sku,t.currency,t.price,t.listing_type,t.title,
    			t.create_user_id,t.create_time,t.modify_user_id,t.modify_time,t.status,t.upload_user_id,t.upload_time,
    			t.upload_message,t.product_id, t.category_id,t.add_type';
    	//$criteria->join   = 'left join ueb_lazada_category as c on(t.category_id = c.category_id)';
    	$criteria->order = 't.id';

        $account_id = '';
        $accountIdArr = array();
        if(isset(Yii::app()->user->id)){
            $accountIdArr = LazadaAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.Yii::app()->user->id);
        }

        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id']) ){
            $account_id = (int)$_REQUEST['account_id'];
        }

        if($accountIdArr && !in_array($account_id, $accountIdArr)){
            $selectAccountArr = LazadaAccount::model()->getListByCondition('id','account_id IN('.implode(',', $accountIdArr).')');
            if($selectAccountArr){
                $accountArr = array();
                foreach ($selectAccountArr as $accountIdVal) {
                    $accountArr[] = $accountIdVal['id'];
                }

                $account_id = implode(',',$accountArr);
            }
        }

        if($account_id){
            $criteria->condition = "t.account_id IN(".$account_id.")";
        }
        
        if(isset($_REQUEST['status']) && $_REQUEST['status']){
                $uploadStatus = $_REQUEST['status'];
                if($uploadStatus == self::UPLOAD_STATUS_DEFAULT_MAPPING){
                        $uploadStatus = self::UPLOAD_STATUS_DEFAULT;
                }
                $criteria->addCondition('status='.$uploadStatus);
        }
        //子sku查询
        $variantCon = $variantParam = array();
        //var_dump($_REQUEST['sub_upload_status']);
        if(isset($_REQUEST['sub_upload_status']) && $_REQUEST['sub_upload_status']){
                $subuploadStatus = $_REQUEST['sub_upload_status'];
                if($subuploadStatus == -1){
                    $subuploadStatus = 0;
                }
                $variantCon[] = 'status=:status';
                $variantParam[':status'] = $subuploadStatus;
        }
        if(isset($variantParam[':sku']) || isset($variantParam[':status'])){
                $lazadaVariationModel = new LazadaProductAddVariation;
                $variantCons = implode(" AND ", $variantCon);
                $variantList = $lazadaVariationModel->getDbConnection()->createCommand()->from($lazadaVariationModel->tableName())->where($variantCons, $variantParam)->group('product_add_id')->select('product_add_id')->queryColumn();
                if($variantList){
                        $criteria->addInCondition('id', $variantList);
                }else{
                        $criteria->addCondition('1=0 AND id=-1');
                }
        }
        //var_dump($criteria);
    	return $criteria;
    }
    
    /**
     * @desc 附加查询条件
     * @param unknown $data
     */
    public function addition($data){
    	$accountList = LazadaAccount::model()->queryPairs('id,seller_name');
        foreach($data as $k=>$item){
            $data[$k]->status_desc = self::getStatusList($data[$k]->status);
            $data[$k]->account_name = $accountList[$item['account_id']];
            $data[$k]->add_type = $this->getProductAddTypeOptions($item['add_type']);
            $sku = $data[$k]->sku;
            if($data[$k]->category_name==''){
                $data[$k]->category_name = LazadaCategory::model()->getBreadcrumbCategory($data[$k]->category_id);
            }
            $data[$k]->sku = CHtml::link($sku, '/products/product/viewskuattribute/sku/'.$sku,
                array('style'=>'color:blue;','target'=>'dialog','width'=>'900','height'=>'600','mask'=>true, 'rel' => 'external', 'external' => 'true'));
            if( $item['status']==self::UPLOAD_STATUS_SUCCESS || $item['status']==self::UPLOAD_STATUS_IMGFAIL ){
                $data[$k]->upload_result = $item['product_id'];
            }elseif( $item['status']==self::UPLOAD_STATUS_FAILURE ){
                $data[$k]->upload_result = $item['upload_message'];
            }
            $data[$k]->site_name = LazadaSite::getSiteShortName($item->site_id);
            
            //获取多属性表数据
            if($item->listing_type == LazadaProductAdd::LISTING_TYPE_VARIATION){
                $variation_list = LazadaProductAddVariation::model()->getVariationByAddID($item->id);
                foreach ($variation_list as $variation){
                    $status_text = self::getStatusList($variation['status']);
                    $variation['status_text'] = $status_text;
                    $data[$k]->detail[] = $variation;
                }
            }

            //判断显示编辑按钮
            $data[$k]->is_visible = 0;
            if(in_array($item->status, array(self::UPLOAD_STATUS_DEFAULT, self::UPLOAD_STATUS_FAILURE))){
                $data[$k]->is_visible = 1;
            }

            //判断显示成功查看按钮
            $data[$k]->success_is_visible = 0;
            if($item->status == self::UPLOAD_STATUS_SUCCESS){
                $data[$k]->success_is_visible = 1;
            }
            
        }
    	return $data;
    }
    
    /**
     * @desc 保存刊登数据
     * @param array $param
     */
    public function saveRecord($param){
        $flag = $this->dbConnection->createCommand()->insert(self::tableName(), $param);
        if( $flag ){
            return $this->dbConnection->getLastInsertID();
        }else{
            return false;
        }
    }
    
    /**
     * @desc 按站点账号分组上传产品
     * @param array $IDs
     */
    public function uploadProduct($IDs){
        $productInfos = array();
        $Infos = $this->dbConnection->createCommand()->select('*')->from(self::tableName())->where('id IN ('.MHelper::simplode($IDs).')')->queryAll();
        if($Infos){
            foreach ($Infos as $productInfos) {

                $apiAccountID = $productInfos['account_id'];
                $categoryID   = $productInfos['category_id'];
                $listing_type = self::LISTING_TYPE_FIXEDPRICE;
                $ID = $productInfos['id'];
                $isSize = 0;
                $attributeArr = array();

                 //产品描述
                $description = $productInfos['description'];
                //产品简介
                $shortDesc   = $productInfos['highlight'];
                //产品标题
                $titles = str_replace(array('（','）','－','°',"’",'℃'), array('(',')','-',' ',"'",' degrees'), $productInfos['title']);

                //获取属性
                $attibutes = LazadaProductAddAttribute::model()->getAttributesByAddID($ID);
                $attributeArr = array(
                    'name'              => $titles,
                    'description'       => '<![CDATA[' . $description . ']]>',
                    'short_description' => '<![CDATA[' . $shortDesc . ']]>',
                    'brand'             => $productInfos['brand'],
                    'kid_years'         => 'Kids (6-10yrs)',
                    'name_ms'           => $titles,
                    'description_ms'    => '<![CDATA[' . $description . ']]>',
                );

                if(!$attibutes){
                    $this->setFailure('Lazada刊登主属性表为空', $listing_type, $ID);
                    continue;
                }

                //取出类目属性名称
                $attributesLabelArr = array();
                $response = LazadaCategoryAttribute::model()->getCategoryAttributeOnlineNew($apiAccountID, $categoryID);
                if(isset($response->Body)){
                    $body = $response->Body;
                    $body = (array)$body;
                    $attr = $body['Attribute'];
                    foreach($attr as $detail){
                        $labelName = (string)$detail->label;
                        $labelName = str_replace(' ', '', $labelName);
                        $valueName = (string)$detail->name;
                        if($valueName == 'storage_storage'){
                            $valueName = 'storage_capacity_new';
                        }

                        //判断是否有size属性
                        if($valueName == 'size'){
                            $isSize = 1;
                        }

                        $attributesLabelArr[$labelName] = $valueName;
                    }
                }

                if(empty($attributesLabelArr)){
                    $this->setFailure('获取线上属性值失败', $listing_type, $ID);
                    continue;
                }

                foreach($attibutes as $item){
                    $lowerName = $item['name'];
                    $lowerNameArr = $this->_ReplaceLabelName;
                    if(isset($lowerNameArr[$lowerName])){
                        $lowerName = $lowerNameArr[$lowerName];
                    }
                    if(isset($attributesLabelArr[$lowerName])){
                        $attributeArr[$attributesLabelArr[$lowerName]] = $item['value'];
                    }
                }

                //判断是否是单品
                if($productInfos['listing_type'] == self::LISTING_TYPE_FIXEDPRICE){
                    $this->uploadProductByAccountNew($productInfos,$attributeArr,$productInfos['listing_type']);
                }elseif ($productInfos['listing_type'] == self::LISTING_TYPE_VARIATION && $isSize == 1) {
                    $this->uploadProductByAccountNew($productInfos,$attributeArr,$productInfos['listing_type'],$isSize);
                }else{
                    $variationInfo = LazadaProductAddVariation::model()->getSonVariationByAddID($ID);
                    foreach ($variationInfo as $varInfos) {
                        if($varInfos['status'] == self::UPLOAD_STATUS_SUCCESS) continue;

                        $varInfos['site_id'] = $productInfos['site_id'];
                        $varInfos['category_id'] = $categoryID;
                        $varInfos['account_id'] = $apiAccountID;
                        $varInfos['listing_type'] = $productInfos['listing_type'];
                        $varInfos['currency'] = $productInfos['currency'];
                        $this->uploadProductByAccountNew($varInfos,$attributeArr,$productInfos['listing_type']);
                    }
                } 
            }
        }

        return true;
    }
    
    /**
     * @desc 根据保存记录上传广告
     * @param array $IDs
     */
    public function uploadProductByAccount($IDs, $siteID, $accountID, $listing_type){
    	$accountInfo = LazadaAccount::getAccountInfoById($accountID);
    	$siteID = $accountInfo['site_id'];
    	$apiAccountID = $accountInfo['account_id'];
        $productAdd = new ProductCreateRequest();
        $imageRequest = new ImageRequest();
        $productAddArr = array();//记录为产品资料上传还是图片上传
        $is_variation = $listing_type == self::LISTING_TYPE_VARIATION ? 1 : 0;

        foreach($IDs as $ID){
            /**@ 1.查询有关信息*/
            //刊登信息
            if($listing_type == self::LISTING_TYPE_FIXEDPRICE){
                $this->addID = $ID;
                $addID = $ID;
                $addInfo = $this->getAddInfoById($ID);
                $public_sku = $addInfo['sku'];
                $addID2 = $ID;
            } elseif ($listing_type == self::LISTING_TYPE_VARIATION) {
                $addInfo = LazadaProductAddVariation::model()->getVariationAddByVariationID($ID);
                $this->addID = $addInfo['product_add_id'];
                $this->variationID = $addInfo['id'];
                $addID = $addInfo['product_add_id'];
                $public_sku = $addInfo['a_sku'];
                $addID2 = $addInfo['id'];
            }
            if( empty($addInfo)) {
                $this->setFailure(Yii::t('common', 'Upload Info Are Invalid'), $listing_type, $ID);continue;
            }
            if (in_array($addInfo['status'], array(self::UPLOAD_STATUS_RUNNING, self::UPLOAD_STATUS_SUCCESS, self::UPLOAD_STATUS_IMGRUNNING))) {
                continue;
            }
            if (in_array($addInfo['sku'],array('77748.01','77748.02'))){
                continue;
            }
            if ($listing_type == self::LISTING_TYPE_VARIATION) {
                if($addInfo['status'] == self::UPLOAD_STATUS_DEFAULT and $addInfo['is_parent'] == 0){
                    //父sku未上传
                    continue;
                }
            }
            //var_dump($public_sku);exit;
            if($addInfo['status'] != self::UPLOAD_STATUS_IMGFAIL ){
                //未上传sku
                //产品信息
                $skuInfo = Product::model()->getProductInfoBySku($addInfo['sku']);
                if( empty($addInfo) ){
                    $this->setFailure(Yii::t('common', 'Can Not Find Sku'), $listing_type, $ID);continue;
                }
                
                $this->setRunning($listing_type,$addID2);
                //图片信息
                $imageInfo = ProductImageAdd::model()->getImageBySku($public_sku, $addInfo['account_id'], Platform::CODE_LAZADA);
                
                //账号配置信息 TODO
                $accountInfo = LazadaAccount::getAccountInfoById($addInfo['account_id']);
                //$accountConfig = LazadaAccountConfig::getAccountConfigById($addInfo['account_id']);
                $accountConfig = array('add_qty' => 200,);
				//获取产品的包装尺寸
                $packageSize = $this->packageSizeRules($skuInfo);
                //获取产品尺寸
                $productSize = $this->productSizeRules($skuInfo);
                //配置信息 TODO (条件匹配最佳配置)
                $description = $addInfo['description'];
                //$shortDesc = $this->highlightRules($skuInfo['description'][LazadaSite::getLanguageBySite($addInfo['site_id'])]);
                $shortDesc = $addInfo['highlight'];
                //$addInfo['title'] = str_replace(array('（','）'), array('(',')'), $addInfo['title']);
                //检查中文字符
/*                 $checkCN = VHelper::checkLang($addInfo['title']);
                if( $checkCN ){
                    $this->setFailure(Yii::t('common', 'Include Chinese Chars'));continue;
                } */
/*                 if(htmlentities($addInfo['title']) != $addInfo['title']){
                    $this->setFailure(Yii::t('common', 'filter'));continue;
                } */
                $addInfo['title'] = str_replace(array('（','）','－','°',"’",'℃'), array('(',')','-',' ',"'",' degrees'), $addInfo['title']);
                //$packageWeight = $skuInfo['gross_product_weight'] > 0 ? $skuInfo['gross_product_weight']/1000 : $skuInfo['product_weight'] * 1.1 /1000;
                $productWeight = round($skuInfo['product_weight'] / 1000, 2);
                //包裹重量取产品重量的1.1倍
                $packageWeight = $productWeight * 1.1;
                $packageWeight = round($packageWeight , 2);
                $param = array(
                	'ProductWeight'		=> $productWeight,
                	'ProductMeasures'	=> $productSize,
                    'PackageWeight'     => $packageWeight,
                    'PackageWidth'      => $packageSize[1],
                    'PackageLength'     => $packageSize[0],
                    'PackageHeight'     => $packageSize[2],
                    'NameMs'            => '<![CDATA['.$addInfo['title']. ']]>',
                    //'DescriptionMs'     => '<![CDATA['.$skuInfo['description'][LazadaSite::getLanguageBySite($addInfo['site_id'])].']]>',
                	'DescriptionMs'     => '<![CDATA[' . $description . ']]>',
                	'ShortDescription'  => '<![CDATA[' . $shortDesc . ']]>',
                	'PackageContent' 	=> '<![CDATA['.$skuInfo['included'][LazadaSite::getLanguageBySite($addInfo['site_id'])].']]>',
                );
                if( $param['PackageWeight'] < 0.01 ){
                    $param['PackageWeight'] = 0.01;
                }
                //描述模板信息 TODO
                $descriptionTemplate = array();
                //价格模板信息 TODO
                //$model_lazadaproduct = new LazadaProduct();
                //$priceParam = $model_lazadaproduct->tplParam;                
                /**@ 2.检查是否能刊登(侵权，利润等)*/
                //判断是否已有在线广告
                $existListing = LazadaProduct::model()->getOnlineListingBySku($public_sku, $addInfo['account_id']);
                if( !empty($existListing) ){
                    $this->setFailure(Yii::t('lazada', 'Exist Product'), $listing_type, $ID);continue;
                }
                //判断账号是否被冻结，不允许刊登
                //             if( $accountInfo['is_lock']==LazadaAccount::STATUS_ISLOCK ){
                //                 if( isset($accountConfig['disabled_function']) && in_array(AccountFactory::FUNCTION_PRODUCT_ADD, explode(',', $accountConfig['disabled_function'])) ){
                //                     $this->setFailure(Yii::t('lazada', 'Account Is Lock And Can Not Publish Product'));return false;
                //                 }
                //             }
                //判断产品是否侵权
                $checkInfringe = ProductInfringe::model()->getProductIfInfringe($public_sku);
                if( $checkInfringe ){
                    $this->setFailure(Yii::t('lazada', 'SKU Is Infringe'), $listing_type, $ID);continue;
                }
                //判断主sku不能刊登一口价
                if( $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN && $addInfo['listing_type'] != self::LISTING_TYPE_VARIATION ){
                    $this->setFailure(Yii::t('lazada', 'Main SKU Can Not Be Published As Not Variation'), $listing_type, $ID);continue;
                }
                
                //判断参数和图片是否完整
                if( empty($imageInfo[ProductImageAdd::IMAGE_ZT]) && empty($imageInfo[ProductImageAdd::IMAGE_FT]) ){
                    $this->setFailure(Yii::t('lazada', 'Zt Or Ft Lack'), $listing_type, $ID);continue;
                }
                
                //判断利润情况
                $isLowest = Product::model()->checkProfitRate($addInfo['currency'],Platform::CODE_LAZADA, $addInfo['sku'], $addInfo['sale_price']);
                if(!$isLowest){
                    $this->setFailure($addInfo['sku'].' Profit is less than the minimum set profit');
                    continue;
                }
                
                /**@ 3.组合刊登信息*/
                $productAddArr['product'][$ID] = $ID;
                $encryptSku = new encryptSku();

                //TODO
                //$description = DescriptionTemplate::model()->getDescriptionByTemplate($descriptionTemplate['template_content'], $skuInfo);
                //$description = $skuInfo['description'][LazadaSite::getLanguageBySite($addInfo['site_id'])];
                //$sellerSku = $encryptSku->getEncryptSku($addInfo['sku']);

                $sellerSku = $addInfo['seller_sku'];
                $productAdd->setName('<![CDATA['.$addInfo['title'].']]>');//title
                $productAdd->setDescription('<![CDATA['.$description.']]>');//描述
                $productAdd->setPrimaryCategory($addInfo['category_id']);//分类
                $productAdd->setBrand($addInfo['brand']);//设置品牌
                $productAdd->setQuantity($accountConfig['add_qty']);//设置在线库存
                //获取属性
                $attibutes = LazadaProductAddAttribute::model()->getAttributesByAddID($addID);
                $attributeArr = array();
                if( !empty($attibutes) ){
                    foreach($attibutes as $item){
                        $attributeArr[$item['name']] = '<![CDATA[' . $item['value'] . ']]>';
                    }
                }

                //获取系统配置
                $attributeArr = array_merge($attributeArr, $param);
                $productAdd->setProductData($attributeArr);//设置属性
                
                ////设置多属性值
                if($addInfo['listing_type']  == self::LISTING_TYPE_VARIATION){
                    $variation = LazadaProductAddVariationAttribute::model()->getAttributeByVariationID($ID);
                    $productAdd->setVariation($variation['value']);
                    if(trim($addInfo['parent_sku']) != ''){
                        $productAdd->setParentSku(trim($addInfo['parent_sku']));
                    }
                }
                $productAdd->setPrice($addInfo['price']);//设置卖价
                $productAdd->setSellerSku($sellerSku);
                $productAdd->setSalePrice($addInfo['sale_price'] > 0 ? $addInfo['sale_price'] : '');
                $productAdd->setSalePriceStartTime($addInfo['sale_price'] > 0 ? $addInfo['sale_price_start'] : '');
                $productAdd->setSalePriceEndTime($addInfo['sale_price'] > 0 ? $addInfo['sale_price_end'] : '');
                $productAdd->push();
                //更新sellerSku
                if($listing_type == self::LISTING_TYPE_FIXEDPRICE){
                    $this->dbConnection->createCommand()->update(self::tableName(), array('product_id'=>$sellerSku), 'id = '.$ID);
                } elseif($listing_type == self::LISTING_TYPE_VARIATION) {
                    LazadaProductAddVariation::model()->dbConnection->createCommand()->update(LazadaProductAddVariation::tableName(), array('product_id'=>$sellerSku), 'id = '.$ID);
                }
            } else {
                /**@ 6.添加图片*/
                // $this->setImageRunning($listing_type,$addID2);
                // $lazadaProductImageAdd = LazadaProductImageAdd::model();
                // $uploadServer = $lazadaProductImageAdd->uploadImageOnline($public_sku, $addInfo['account_id']);//将图片上传至图片服务器

                // if( !$uploadServer ){
                //     if($listing_type == self::LISTING_TYPE_FIXEDPRICE){
                //         $this->dbConnection->createCommand()->update(self::tableName(), array(
                //             'upload_message'    => $lazadaProductImageAdd->getErrorMessage(),
                //             'status'            => self::UPLOAD_STATUS_IMGFAIL,
                //         ), 'id = '.$addInfo['id']);
                //     } elseif ($listing_type == self::LISTING_TYPE_VARIATION) {
                //         LazadaProductAddVariation::model()->dbConnection->createCommand()->update(LazadaProductAddVariation::tableName(), array(
                //             'upload_message'    => $lazadaProductImageAdd->getErrorMessage(),
                //             'status'            => self::UPLOAD_STATUS_IMGFAIL,
                //         ), 'id = '.$addInfo['id']);
                //     }
                    
                // } else {
                //     $productAddArr['image'][$ID] = $ID;
                //     $imageList = $lazadaProductImageAdd->getImageBySku($public_sku, $addInfo['account_id'], Platform::CODE_LAZADA);
                //     $count = 1;
                //     foreach($imageList[LazadaProductImageAdd::IMAGE_ZT] as $image){
                //     	if ($count >= LazadaProductImageAdd::MAX_IMAGE_NUMBER)
                //     		break;
                //     	if (empty($image['remote_path']))
                //     		continue;
                //         $imageRequest->pushImage($image['remote_path']);
                //         $count++;
                //     }
                //     $imageRequest->setSellerSku($addInfo['product_id']);
                //     $imageRequest->push();
                // }
            }
        }

        /**@ 4.交互刊登*/
        foreach($productAddArr as $type=>$idArr){
            if($type=='product'){
                $request = $productAdd;
                $action = LazadaFeed::ACTION_PRODUCT_CREATE;
            }else{
                $request = $imageRequest;
                $action = LazadaFeed::ACTION_IMAGE;
            }
            if( !empty($idArr) )  {
                $response = $request->setSiteID($siteID)->setAccount($apiAccountID)->setRequest()->sendRequest()->getResponse();
                if( $request->getIfSuccess() ){//上传成功，等待回复
                    $updateArr = array(
                        //'status'        => self::UPLOAD_STATUS_IMGFAIL,
                        'feed_id'       => $response->Head->RequestId,
                        'upload_time'   => date('Y-m-d H:i:s'),
                    );
                    //添加feed
                    $isOk = LazadaFeed::model()->addRecord(array(
                        //'type'          => 'api',
                        'feed_id'       => $response->Head->RequestId,
                        'site_id'       => $siteID,
                        'status'        => LazadaFeed::STATUS_QUEUED,
                        'create_time'   => date('Y-m-d H:i:s'),
                        'account_id'    => $accountID,
                        'action'        => $action,
                        'is_variation'  => $is_variation,
                    ));
                    if ($isOk) {
                        if($listing_type == self::LISTING_TYPE_FIXEDPRICE){
                            $is_variation = 0;
                            $this->dbConnection->createCommand()->update(self::tableName(), $updateArr, 'id IN ('.MHelper::simplode($idArr).')');
                        } elseif($listing_type == self::LISTING_TYPE_VARIATION) {
                            $is_variation = 1;
                            LazadaProductAddVariation::model()->dbConnection->createCommand()->update(LazadaProductAddVariation::tableName(), $updateArr, 'id IN ('.MHelper::simplode($idArr).')');
                        }
                    }
                } else {
                    $updateArr = array(
                        'status'        => self::UPLOAD_STATUS_FAILURE,
                        'upload_time'   => date('Y-m-d H:i:s'),
                        'upload_message'=> $request->getErrorMsg(),
                    );
                    if($listing_type == self::LISTING_TYPE_FIXEDPRICE){
                        $this->dbConnection->createCommand()->update(self::tableName(), $updateArr, 'id IN ('.MHelper::simplode($idArr).')');
                    } elseif($listing_type == self::LISTING_TYPE_VARIATION) {
                        LazadaProductAddVariation::model()->dbConnection->createCommand()->update(LazadaProductAddVariation::tableName(), $updateArr, 'id IN ('.MHelper::simplode($idArr).')');
                    }
                }
            }
        }
    }
    
    /**
     * @desc 根据规则获取产品刊登的长，宽，高
     * @param unknown $skuInfo
     * @return multitype:number
     */
    public function packageSizeRules($skuInfo) {
    	/* 如果产品有包装尺寸并且尺寸大于等于1cm则优先包装尺寸，否则使用产品尺寸，如果产品尺寸小于1cm，则根据如下规则设置
    	 * weight（0-50g）      尺寸计5 x 3 x 2cm
   		 * weight（50-100g）  尺寸计10 x 8 x 7cm
         * weight（>100g）    尺寸计20 x 10 x 1cm
    	 */
    	$packageSize = array();
    	$packageSizePreSetting = array();
    	$weight = $skuInfo['gross_product_weight'] > 0 ? $skuInfo['gross_product_weight'] : $skuInfo['product_weight'] * 1.1;
    	if ($weight >= 0 && $weight < 50)
    		$packageSizePreSetting = array(5, 3, 2);
    	else if ($weight >= 50 && $weight < 100)
    		$packageSizePreSetting = array(10, 8, 7);
    	else
    		$packageSizePreSetting = array(20, 10, 1);
    	//包装长
    	$packageSize[0] = $skuInfo['pack_product_length'] / 10 >= 1 ? $skuInfo['pack_product_length'] / 10 :
    		($skuInfo['product_length'] / 10 >= 1 ? $skuInfo['product_length'] * 1.1 / 10 : $packageSizePreSetting[0]);
    	$packageSize[0] = round($packageSize[0], 2);
    	//包装宽
    	$packageSize[1] = $skuInfo['pack_product_width'] / 10 >= 1 ? $skuInfo['pack_product_width'] / 10 :
    		($skuInfo['product_width'] / 10 >= 1 ? $skuInfo['product_width'] * 1.1 / 10 : $packageSizePreSetting[1]);
    	$packageSize[1] = round($packageSize[1], 2);
    	//包装高
    	$packageSize[2] = $skuInfo['pack_product_height'] / 10 >= 1 ? $skuInfo['pack_product_height'] / 10 :
    		($skuInfo['product_height'] / 10 >= 1 ? $skuInfo['product_height'] * 1.1 / 10 : $packageSizePreSetting[2]);    	
    	$packageSize[2] = round($packageSize[2], 2);
    	return $packageSize;
    }

    /**
     * @desc 根据规则获取产品刊登的长，宽，高
     * @param unknown $skuInfo
     * @return multitype:number
     */
    public function productSizeRules($skuInfo) {
    	/* 如果产品有尺寸并且尺寸大于等于1cm则使用产品尺寸，如果产品尺寸小于1cm，则根据如下规则设置
    	 * weight（0-50g）      尺寸计5 x 3 x 2cm
    	* weight（50-100g）  尺寸计10 x 8 x 7cm
    	* weight（>100g）    尺寸计20 x 10 x 1cm
    	*/
    	$productSizePreSetting = array();
    	$weight = $skuInfo['product_weight'];
    	if ($weight >= 0 && $weight < 50)
    		$productSizePreSetting = array(5, 3, 2);
    	else if ($weight >= 50 && $weight < 100)
    		$productSizePreSetting = array(10, 8, 7);
    	else
    		$productSizePreSetting = array(20, 10, 1);
    	//产品长
    	$packageSize[0] = $skuInfo['product_length'] / 10 >= 1 ? $skuInfo['product_length'] / 10 : $productSizePreSetting[0];
    	//产品宽
    	$packageSize[1] = $skuInfo['product_width'] / 10 >= 1 ? $skuInfo['product_width'] / 10 : $productSizePreSetting[1];
    	//产品高
    	$packageSize[2] = $skuInfo['product_height'] / 10 >= 1 ? $skuInfo['product_height'] / 10 : $productSizePreSetting[2];
    	return implode('x', $packageSize);
    }    
    
    /**
     * @desc 产品highlight规则
     */
    public function highlightRules($description) {
    	return $description;
    	//取描述的前五句话
    	$parts = explode("\n", $description);
    	//避免获取整个描述部分
    	if (sizeof($parts) <= 1)
    		$parts = array();
    	//去掉首尾换行符，去掉空值
    	array_filter($parts, function($value){
    		$value = str_replace(array("\r", "\n"), array(' '), $value);
    		if ($value != '')
    			return $value;
    	});
    	$highlightArr = array_slice($parts, 0, 5);
    	return implode(' ', $highlightArr);
    }
    
    /**
     * @desc 添加待刊登任务
     */
    public function productAdd($sku, $accountID, $site=''){
    	$encryptSku = new encryptSku();
        /**@ 1.获取需要的参数*/
        //产品信息
        $skuInfo = Product::model()->getProductInfoBySku($sku);
        //货币
        $site = $site !='' ? $site : LazadaSite::SITE_MY;
        $currency = LazadaSite::model()->getCurrencyBySite($site);
        //刊登类型
        $listingType = isset($listingType) ? $listingType : self::LISTING_TYPE_FIXEDPRICE;
        
        /**@ 2.检测是否能添加*/
        //检测sku信息是否存在
        if( empty($skuInfo) ){
            return array(
                    'status'    => 0,
                    'message'   => Yii::t('common','SKU Does Not Exists.'),
            );
        }
        
        //检测待刊登列表里是否已存在
        $checkExists = $this->dbConnection->createCommand()
                    ->select('*')
                    ->from(self::tableName())
                    ->where('sku = "'.$sku.'"')->andWhere('account_id = '.$accountID)
                    ->andWhere('listing_type = '.$listingType)->andWhere('site_id = '.$site)
                    ->andWhere('status != '.self::UPLOAD_STATUS_SUCCESS)
                    ->queryRow();
        if( !empty($checkExists) ){
            return array(
                'status'    => 0,
                'message'   => Yii::t('common','Record Exists.'),
            );
        }    
        //检测是否存在在线广告
        $checkOnline = LazadaProduct::model()->getProductByParam(array(
                'sku'           => $sku,
                'account_id'    => $accountID,
                'site_id'       => $site,
                'status'        => 1
        ));
/*         if( !empty($checkOnline) ){
            return array(
                'status'    => 0,
                'message'   => Yii::t('common','Listing Exists.'),
            );
        } */
        
        /**@ 3.匹配分类*/
        //检测之前有无已刊登过的分类
        $listings = $this->getRecordBySku($sku);
        $categoryID = 0;
        foreach($listings as $listing){
            $categoryID = $listing['category_id'];
            $historyAddID = $listing['id'];
            break;
        }
        if( !$categoryID ){//之前没刊登过
            //匹配产品词库和其他销售平台分类词库
            $title = $skuInfo['title'][LazadaSite::getLanguageBySite($site)];
            $keyWordsTitle = explode(' ', $title);
            //获取ebay的分类名
            $keyWordsEbay = Product::model()->getPlatformCategoryBySku($sku, Platform::CODE_EBAY);
            $keyWords = array_merge($keyWordsTitle,$keyWordsEbay);
            $categoryID = LazadaCategory::model()->getCategoryIDByKeyWords($keyWords);
        }
        if( !$categoryID ){
            return array(
                'status'    => 0,
                'message'   => Yii::t('common','Can Not Match Category.'),
            );
        }
        /**@ 4.计算价格*/
        $finalPrice = 0;
        //获取已刊登广告的卖价
        /*$onlineProduct = LazadaProduct::model()->getProductByParam(array(
                'sku'           => $sku,
                'site_id'       => $site,
                'status'        => 1,
        ));
        if( !empty($onlineProduct) ){
            $finalPrice = $onlineProduct[0]['price'];
            if($onlineProduct[0]['sale_price'] > 0){
                $salePrice = $onlineProduct[0]['sale_price'];
            }
        }*/
        
        /*if( $finalPrice <= 0 ){
            //获取待刊登列表的卖价
            $addProduct = $this->dbConnection->createCommand()
                        ->select('*')
                        ->from(self::tableName())
                        ->where('sku = "'.$sku.'"')
                        ->andWhere('listing_type = '.$listingType)
                        ->andWhere('site_id = '.$site)
                        ->andWhere('status != '.self::UPLOAD_STATUS_SUCCESS)
                        ->order('upload_time DESC')
                        ->queryRow();
            if( !empty($addProduct) ){
                $finalPrice = $addProduct['price'];
                if($addProduct['sale_price'] > 0){
                    $salePrice = $addProduct['sale_price'];
                }
            }
        }*/
        //TODO
        $model_lazadaproduct = new LazadaProduct();
        $tplParam = $model_lazadaproduct->tplParam;
        
        $specDone = true;   
        if( $specDone ){//特殊处理
            if( $finalPrice <= 0 ){
                //获取建议卖价
                $priceCal = new CurrencyCalculate();
                $priceCal->setProfitRate($tplParam['standard_profit_rate']);    //设置利润率
                $priceCal->setCurrency($currency);//币种
                $priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
                $priceCal->setSku($sku);//设置sku
                $salePrice = $priceCal->getSalePrice();//获取卖价
                if($accountID==1){
                    $rate = 0.5;
                }else{
                    $rate = 0.5;
                }
                $price = $salePrice / $rate;
            } else {
            	$price = $finalPrice;
            }
        }else{
            if( $finalPrice <= 0 ){
                //获取建议卖价
                $priceCal = new CurrencyCalculate();
                $priceCal->setProfitRate($tplParam['standard_profit_rate']);//设置利润率
                $priceCal->setCurrency($currency);//币种
                $priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
                $priceCal->setSku($sku);//设置sku
                $finalPrice = $priceCal->getSalePrice();//获取卖价
            }
            
            //获取最低卖价
            $priceCal = new CurrencyCalculate();
            $priceCal->setProfitRate($tplParam['lowest_profit_rate']);//设置利润率
            $priceCal->setCurrency($currency);//币种
            $priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
            $priceCal->setSku($sku);//设置sku
            $lowestPrice = $priceCal->getSalePrice();//获取卖价
            if( $lowestPrice > $finalPrice ){
                return array(
                    'status'    => 0,
                    'message'   => Yii::t('common','Can Not Get Sale Price Or Sale Price Is Lower Than Config'),
                );
            }
        }
        
        /**@ 5.获取属性值*/
        if( isset($historyAddID) ){//有之前的刊登记录
            $attributes = LazadaProductAddAttribute::model()->getAttributesByAddID($historyAddID);
        }
        /**@ 6.获取Title*/
        $descriptionTemplate = array(
        	'title_prefix' => '',
        	'title_suffix' => '',
        );//TODO
        $name = $skuInfo['title'][LazadaSite::getLanguageBySite($site)];
        $name = trim($descriptionTemplate['title_prefix'].' '.$name.' '.$descriptionTemplate['title_suffix']);
        
        $description = $skuInfo['description'][LazadaSite::getLanguageBySite($site)];
        $highlight = $description;
        
        /**@ 7.插入刊登任务*/
        //图片信息
        $saveImage = LazadaProductImageAdd::model()->addProductImageBySku($sku,$accountID);
        if( !$saveImage ){
            return array(
                'status'    => 0,
                'message'   => Yii::t('common','No Image Found'),
            );
        }
        $sellerSku = $sku;
            //除了c账号以外的都加密
            $not_crystalawaking_list = LazadaAccount::model()->getDbConnection()->createCommand()
                    ->from(LazadaAccount::tableName())
                    ->select("id")
                    ->where("account_id !=1 ")
                    ->queryColumn();
 	    if (in_array($accountID, $not_crystalawaking_list))
        	$sellerSku = $encryptSku->getEncryptSku($sku);
        //主信息
        $brand = 'VAKIND';
        $addID = $this->saveRecord(array(
                'account_id'        => $accountID,
                'sku'               => $sku,
        		'seller_sku'		=> $sellerSku,
                'site_id'           => $site,
                'currency'          => $currency,
                'listing_type'      => $listingType,
                'title'             => $name,
                'price'             => isset($salePrice) ? $price : $finalPrice,
                'sale_price'        => isset($salePrice) ? $salePrice : 0,
                'sale_price_start'  => isset($salePrice) ? date('Y-m-d H:i:s') : '',
                'sale_price_end'    => isset($salePrice) ? date('Y-m-d H:i:s',strtotime('+10 year')) : '',
                'brand'             => $brand,
                'category_id'       => $categoryID,
                'create_user_id'    => Yii::app()->user->id,
                'create_time'       => date('Y-m-d H:i:s'),
        		'description'		=> $description,
        		'highlight'			=> $highlight,
        ));
        if( $addID > 0 ){
            //属性信息
            if( $attributes ){
                foreach($attributes as $attribute){
                    LazadaProductAddAttribute::model()->saveRecord($addID, $attribute['name'], $attribute['value']);
                }
            }
        }
        return array(
            'status'    => 1,
            'addID'     => $addID,
        );
    }
    
    /**
     * @desc 通过sku获取刊登记录(按上传时间排序)
     * @param string $sku
     */
    public function getRecordBySku($sku){
        return $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('sku = "'.$sku.'"')
                ->order('upload_time DESC')
                ->queryAll();
    }
    
    /**
     * @desc 设置任务成功
     */
    public function setSuccess($listing_type,$addID,$parentAddID = null){
        $updateArr = array(
            'status'        => self::UPLOAD_STATUS_SUCCESS,
            'upload_user_id'=> isset(Yii::app()->user->id)?Yii::app()->user->id:0,
            'upload_time'   => date('Y-m-d H:i:s'),
        );
        if($listing_type == self::LISTING_TYPE_FIXEDPRICE){
            $this->dbConnection->createCommand()->update(self::tableName(), $updateArr, 'id = '.$addID);
        } elseif ($listing_type == self::LISTING_TYPE_VARIATION){
            LazadaProductAddVariation::model()->dbConnection->createCommand()->update(LazadaProductAddVariation::tableName(), $updateArr, 'id = '.$addID);

            if($parentAddID){
                $this->dbConnection->createCommand()->update(self::tableName(), $updateArr, 'id = '.$parentAddID);
            }
        }
    }
    
    /**
     * @desc 设置任务运行
     */
    public function setRunning($listing_type,$addID){
        if($listing_type == self::LISTING_TYPE_FIXEDPRICE){
            $this->dbConnection->createCommand()->update(self::tableName(), array(
                'status'        => self::UPLOAD_STATUS_RUNNING,
            ), 'id = '.$addID);
        } elseif ($listing_type == self::LISTING_TYPE_VARIATION){
            LazadaProductAddVariation::model()->dbConnection->createCommand()->update(LazadaProductAddVariation::tableName(), array(
                'status'        => self::UPLOAD_STATUS_RUNNING,
            ), 'id = '.$addID);
        }
    }
    
    /**
     * @desc 设置正在上传图片
     */
    public function setImageRunning($listing_type,$addID){
        if($listing_type == self::LISTING_TYPE_FIXEDPRICE){
            $this->dbConnection->createCommand()->update(self::tableName(), array(
                'status'        => self::UPLOAD_STATUS_IMGRUNNING,
            ), 'id = '.$addID);
        } elseif ($listing_type == self::LISTING_TYPE_VARIATION){
            LazadaProductAddVariation::model()->dbConnection->createCommand()->update(LazadaProductAddVariation::tableName(), array(
                'status'        => self::UPLOAD_STATUS_IMGRUNNING,
            ), 'id = '.$addID);
        }
    }
    
    
    /**
     * @desc 设置任务失败
     * @param string $message
     */
    public function setFailure($message, $listing_type, $ID){
        if($listing_type == self::LISTING_TYPE_FIXEDPRICE){
            $this->dbConnection->createCommand()->update(self::tableName(), array(
                    'status'        => self::UPLOAD_STATUS_FAILURE,
                    'upload_user_id'=> intval(Yii::app()->user->id),
                    'upload_time'   => date('Y-m-d H:i:s'),
                    'upload_message'=> $message,
            ), 'id = '.$ID);
        } elseif ($listing_type == self::LISTING_TYPE_VARIATION){
            LazadaProductAddVariation::model()->dbConnection->createCommand()->update(LazadaProductAddVariation::tableName(), array(
                    'status'        => self::UPLOAD_STATUS_FAILURE,
                    'upload_user_id'=> Yii::app()->user->id,
                    'upload_time'   => date('Y-m-d H:i:s'),
                    'upload_message'=> $message,
            ), 'id = '.$ID);
        }
    }
    
    /**
     * @desc 标记产品刊登状态
     * @param string $feedID
     * @param tinyInt $status
     * @param string $message
     */
    public function markStatusByFeedID($feedID, $status, $message = ''){
        return $this->dbConnection->createCommand()->update(self::tableName(), array(
                'status'            => $status,
                'upload_message'    => $message,
        ),'feed_id = "'.$feedID.'"');
    }
    
    /**
     * @desc 根据sku标记状态
     * @param array $skus
     * @param int $accountID
     * @param tinyint $status
     * @param string $message
     */
    public function markStatusBySkus($skus, $accountID, $siteID, $status, $message = ''){
        return $this->dbConnection->createCommand()->update(self::tableName(), array(
                'status'            => $status,
                'upload_message'    => $message,
        ),'product_id IN ('.MHelper::simplode($skus).') AND account_id = '.$accountID . ' and site_id = ' . $siteID);
    }
    
    /**
     * @desc 标记多属性产品刊登状态
     * @param string $ID
     * @param tinyInt $status
     * @param string $message
     */
    public function markVariationAddStatusByID($ID, $status, $message = ''){
        $upload_fail  = LazadaProductAddVariation::model()->dbConnection->createCommand()
                ->select('id')
                ->from(LazadaProductAddVariation::tableName())
                ->where( 'product_add_id = "'. $ID . '"  and status in (0,1,5,6)' )
                ->queryRow();
        if( $upload_fail ){
            return $this->dbConnection->createCommand()->update(self::tableName(), array(
                    'status'            => $status,
                    'upload_message'    => $message,
            ),'id = "'.$ID.'"');
        }
    }
    
    /**
     * @desc 根据ID获取刊登信息
     * @param int $id
     */
    public function getAddInfoById($id){
        return $this->dbConnection->createCommand()
                    ->select('*')
                    ->from(self::tableName())
                    ->where('id = '.$id)
                    ->queryRow();
    }
    
    /**
     * @desc 页面的跳转链接地址
     */
    public static function getIndexNavTabId() {
    	return Menu::model()->getIdByUrl('/lazada/lazadaproductadd/list');
    }
    

    
    /**
     * @desc 获取需要上传的待刊登记录add表id
     */
    public function getNeedUploadRecord($accountID){
    return $this->dbConnection->createCommand()
            ->select('id')
            ->from(self::tableName())
            ->where('status = '.self::UPLOAD_STATUS_DEFAULT)
            ->andWhere('account_id = ' . $accountID)
            ->limit(self::MAX_NUM_PER_TASK)
            ->queryColumn();
    }
    
    
    
    /**
     * @desc 根据feedID查找刊登记录
     */
    public function getRecordByFeedID($feedID){
        return $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('feed_id = "'.$feedID.'"')
                ->queryAll();
    }
    
    /**
     * @desc 根据状态获取刊登信息
     * @param int $accountID
     * @param tinyint $status
     */
    public function getUploadRecordByStatus($accountID, $status, $skuLine = ''){
        $where = '';
        if($skuLine!==''){
            $where = 'sku LIKE "'.$skuLine.'%"';
        }
        return $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('account_id = "'.$accountID.'"')
                ->andWhere('status = '.$status)
                ->andWhere($where)
                ->limit(self::MAX_NUM_PER_TASK)
                ->queryAll();
    }
    
    public function getDiscountTpl(){
        if(!isset($this->discountTpl)){
            $this->discountTpl = array();
        }
        if($this->discountTpl == array()){
             $discountTpl = array(
                'discount' => 0.5,
                'start_date' => date('Y-m-d H:i:s'),
                'end_date' => date('Y-m-d H:i:s' ,strtotime('+10 year'))
            );
             $this->setDiscountTpl($discountTpl);
        }
        return $this->discountTpl;
    }
    public function setDiscountTpl($discountTpl = array()){
        $this->discountTpl = $discountTpl;
    }
    
    /**
     * @desc 删除指定的待刊登add列表及其属性和多属性信息
     * @param array $ids add_id组成的字符串
     * @return $result 操作成功返回true,操作失败返回false
     */
    public function deleteEntireLazadaById($ids){
        
        // 事务
        $transaction = Yii::app()->db->beginTransaction();
        try {
            //删除add表数据
            UebModel::model('LazadaProductAdd')->getDbConnection()->createCommand()->delete(self::model()->tableName(), " id IN (" . ($ids) .")");
            //删除add_attribute表数据
            UebModel::model('LazadaProductAddAttribute')->getDbConnection()->createCommand()->delete(LazadaProductAddAttribute::model()->tableName(), " add_id IN (" . ($ids) .")");
            //删除add_variation表数据
            UebModel::model('LazadaProductAddVariation')->getDbConnection()->createCommand()->delete(LazadaProductAddVariation::model()->tableName(), " product_add_id IN (" . ($ids) .")");
            //删除add_variation_attribute表数据
            UebModel::model('LazadaProductAddVariationAttribute')->getDbConnection()->createCommand()->delete(LazadaProductAddVariationAttribute::model()->tableName(), " add_id IN (" . ($ids) .")");
            $transaction->commit();
            $result = true;
        } catch (Exception $e) {
            $transaction->rollback();
            $result = false;
        }
        return $result;
    }
    
    /**
     * @desc 添加待刊登任务(带分类参数)
     */
    public function productAddByCategory($sku, $accountID, $site='', $categoryID = null, $addType = null){
    	if(is_null($addType)){
    		$addType = self::ADD_TYPE_DEFAULT;
    	}
    	try {
    		$encryptSku = new encryptSku();
    		/**@ 1.获取需要的参数*/
    		//产品信息
    		$skuInfo = Product::model()->getProductInfoBySku($sku);
    		//货币
    		$site = $site !='' ? $site : LazadaSite::SITE_MY;
    		$currency = LazadaSite::model()->getCurrencyBySite($site);
    		//刊登类型
    		$listingType = isset($listingType) ? $listingType : self::LISTING_TYPE_FIXEDPRICE;
    		
    		/**@ 2.检测是否能添加*/
    		//检测sku信息是否存在
    		if( empty($skuInfo) ){
    			$this->throwE(Yii::t('common','SKU Does Not Exists.'));
    		}
    		
    		//如果多属性产品
    		if($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
    			$this->throwE("暂时不支持批量刊登多属性");
    		}
    		//检测待刊登列表里是否已存在
    		$checkExists = $this->dbConnection->createCommand()
					    		->select('*')
					    		->from(self::tableName())
					    		->where('sku = "'.$sku.'"')
					    		->andWhere('account_id = '.$accountID)
					    		->andWhere('listing_type = '.$listingType)
					    		->andWhere('site_id = '.$site)
					    		->andWhere('status != '.self::UPLOAD_STATUS_SUCCESS)
					    		->queryRow();
    		if( !empty($checkExists) ){
    			$this->throwE(Yii::t('common','Record Exists.'));
    		}
    		//检测是否存在在线广告
    		$checkOnline = LazadaProduct::model()->getProductByParam(array(
    				'sku'           => $sku,
    				'account_id'    => $accountID,
    				'site_id'       => $site,
    				'status'        => 1
    		));
    		if( !empty($checkOnline) ){
    			
    			$this->throwE(Yii::t('common','Listing Exists.'));
    		}
    		
    		/**@ 3.匹配分类*/
    		//检测之前有无已刊登过的分类
    		$listings = $this->getRecordBySku($sku);
    		//$categoryID = 0;
    		foreach($listings as $listing){
    			if(!$categoryID){
    				$categoryID = $listing['category_id'];
    			}
    			$historyAddID = $listing['id'];
    			break;
    		}
    		
    		if( !$categoryID ){//之前没刊登过
    			//匹配产品词库和其他销售平台分类词库
    			$title = $skuInfo['title'][LazadaSite::getLanguageBySite($site)];
    			$keyWordsTitle = explode(' ', $title);
    			//获取ebay的分类名
    			$keyWordsEbay = Product::model()->getPlatformCategoryBySku($sku, Platform::CODE_EBAY);
    			$keyWords = array_merge($keyWordsTitle,$keyWordsEbay);
    			$categoryID = LazadaCategory::model()->getCategoryIDByKeyWords($keyWords);
    		}
    		if( !$categoryID ){
    			$this->throwE(Yii::t('common','Can Not Match Category.'));
    		}
    		/**@ 4.计算价格*/
    		$finalPrice = 0;
    		
    		//TODO
    		$model_lazadaproduct = new LazadaProduct();
    		$tplParam = $model_lazadaproduct->tplParam;
    		
    		$specDone = true;
    		if( $specDone ){//特殊处理
    			if( $finalPrice <= 0 ){
    				//获取建议卖价
    				$priceCal = new CurrencyCalculate();
    				$priceCal->setProfitRate($tplParam['standard_profit_rate']);    //设置利润率
    				$priceCal->setCurrency($currency);//币种
    				$priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
    				$priceCal->setSku($sku);//设置sku
    				$salePrice = $priceCal->getSalePrice();//获取卖价
    		
    				$error_msg = $priceCal->getErrorMessage();
    				if($error_msg || ($salePrice <= 0) ){
    					$this->throwE(Yii::t('common','Get Price fail'));
    				}
    				$rate = 0.5;
    				$price = $salePrice / $rate;
    			} else {
    				$price = $finalPrice;
    			}
    		}
    		
    		/**@ 5.获取属性值*/
    		$attributes = null;
    		if( isset($historyAddID) ){//有之前的刊登记录
    			$attributes = LazadaProductAddAttribute::model()->getAttributesByAddID($historyAddID);
    		}
    		/**@ 6.获取Title*/
    		$descriptionTemplate = array(
    				'title_prefix' => '',
    				'title_suffix' => '',
    		);//TODO
    		$name = $skuInfo['title'][LazadaSite::getLanguageBySite($site)];
    		$name = trim($descriptionTemplate['title_prefix'].' '.$name.' '.$descriptionTemplate['title_suffix']);
    		
    		$description = $skuInfo['description'][LazadaSite::getLanguageBySite($site)];
    		$highlight = $description;
    		
    		/**@ 7.插入刊登任务*/
    		//图片信息
    		$saveImage = LazadaProductImageAdd::model()->addProductImageBySku($sku,$accountID);
    		if( !$saveImage ){
    			$this->throwE(Yii::t('common','No Image Found'));
    		}
    		$sellerSku = $sku;
    		//除了c账号以外的都加密
    		$not_crystalawaking_list = LazadaAccount::model()->getDbConnection()->createCommand()
						    		->from(LazadaAccount::tableName())
						    		->select("id")
						    		->where("account_id !=1 ")
						    		->queryColumn();
    		if (in_array($accountID, $not_crystalawaking_list))
    			$sellerSku = $encryptSku->getEncryptSku($sku);
    		//主信息
    		$brand = 'VAKIND';
    		$data = array(
    				'account_id'        => $accountID,
    				'sku'               => $sku,
    				'seller_sku'        => $sellerSku,
    				'site_id'           => $site,
    				'currency'          => $currency,
    				'listing_type'      => $listingType,
    				'title'             => $name,
    				'price'             => isset($salePrice) ? $price : $finalPrice,
    				'sale_price'        => isset($salePrice) ? $salePrice : 0,
    				'sale_price_start'  => isset($salePrice) ? date('Y-m-d H:i:s') : '',
    				'sale_price_end'    => isset($salePrice) ? date('Y-m-d H:i:s',strtotime('+10 year')) : '',
    				'brand'             => $brand,
    				'category_id'       => $categoryID,
    				'create_user_id'    => intval(Yii::app()->user->id),
    				'create_time'       => date('Y-m-d H:i:s'),
    				'description'       => $description,
    				'highlight'         => $highlight,
    				'add_type'			=> $addType
    		);
    		try{
    			$dbTransaction = $this->getDbConnection()->beginTransaction();
    			$addID = $this->saveRecord($data);
    			if( $addID > 0 ){
    				//属性信息
    				if( $attributes ){
    					foreach($attributes as $attribute){
    						LazadaProductAddAttribute::model()->saveRecord($addID, $attribute['name'], $attribute['value']);
    					}
    				}
    			}
    			$dbTransaction->commit();
    		}catch (Exception $e){
    			$dbTransaction->rollback();
    			$this->throwE($e->getMessage());
    		}
    		return true;
    	}catch (Exception $e){
    		$this->setErrorMsg($e->getMessage());
    		return false;
    	}
    }

    
    private function throwE($message,$code=null){
    	throw new Exception($message,$code);
    }
    
    public function setErrorMsg($errorMsg){
    	$this->_errorMsg = $errorMsg;
    	return $this;
    }
    
    public function getErrorMsg(){
    	return $this->_errorMsg;
    }

    /**
     * @param string $fields
     * @param string $where
     * @param string $order
     * @return mixed
     */
    public function getAllByCondition($fields='*', $where='1',$order='')
    {
        $sql = "SELECT {$fields} FROM ".$this->tableName()." WHERE {$where} ";
        $cmd = $this->dbConnection->createCommand($sql);
        return $cmd->queryAll();
    }
	
    /**
     * @desc 根据条件获取单条数据
     * @param unknown $fields
     * @param unknown $conditions
     * @param string $param
     * @return mixed
     */
    public function getProductAddInfo($fields, $conditions, $param = null){
        return $this->getDbConnection()->createCommand()
                                ->select($fields)
                                ->from(self::tableName())
                                ->where($conditions, $param)
                                ->queryRow();
    }


    /**
     * @desc 根据保存记录上传广告
     * @param array $addInfo
     */
    public function uploadProductByAccountNew($addInfo, $attributeArr, $listingType, $isSize = 0){
        
        $accountInfo  = LazadaAccount::getAccountInfoById($addInfo['account_id']);
        $siteID       = $addInfo['site_id'];
        $apiAccountID = $addInfo['account_id'];
        $accountName  = $accountInfo['seller_name'];
        $listing_type = $addInfo['listing_type'];
        $ID           = $addInfo['id'];
        $sku          = $addInfo['sku'];
        $categoryID   = $addInfo['category_id'];
        $parentAddID  = isset($addInfo['product_add_id'])?$addInfo['product_add_id']:null;

        if(in_array($addInfo['status'], array(self::UPLOAD_STATUS_RUNNING, self::UPLOAD_STATUS_SUCCESS, self::UPLOAD_STATUS_IMGRUNNING))){
            return false;
        }

        //产品信息
        $skuInfo = Product::model()->getProductInfoBySku($sku);
        if( empty($addInfo) ){
            $this->setFailure(Yii::t('common', 'Can Not Find Sku'), $listing_type, $ID);
            return false;
        }

        //判断主sku不能刊登一口价
        if($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN && $addInfo['listing_type'] != self::LISTING_TYPE_VARIATION){
            $this->setFailure(Yii::t('lazada', 'Main SKU Can Not Be Published As Not Variation'), $listing_type, $ID);
            return false;
        }

        $this->setRunning(self::LISTING_TYPE_FIXEDPRICE,$ID);

        //获取产品的包装尺寸
        $packageSize = $this->packageSizeRules($skuInfo);
        //获取产品尺寸
        $productSize = $this->productSizeRules($skuInfo);
        //产品重量
        $productWeight = round($skuInfo['product_weight'] / 1000, 2);
        //包裹重量取产品重量的1.1倍
        $packageWeight = $productWeight * 1.1;
        $packageWeight = round($packageWeight , 2);
        if( $packageWeight < 0.01 ){
            $packageWeight = 0.01;
        }

        //取出佣金
        $lazadaCategoryCommissionModel = new LazadaCategoryCommissionRate();
        $commissionRate = $lazadaCategoryCommissionModel->getCommissionRate($categoryID, $siteID);
        
        //判断利润情况
        $isLowest = Product::model()->checkProfitRate($addInfo['currency'],Platform::CODE_LAZADA, $sku, $addInfo['sale_price'],$commissionRate, null, $siteID);
        if(!$isLowest){
            $this->setFailure($sku.' Profit is less than the minimum set profit', $listing_type, $ID);
            continue;
        }

        $insertParam = array();
        $insertParam = array(
            'SellerSku'          => $addInfo['seller_sku'],
            'color_family'       => '',
            'size'               => '',
            'quantity'           => 200,
            'price'              => $addInfo['price'],
            'special_price'      => $addInfo['sale_price'],
            'tax_class'          => 'default',
            'product_weight'     => $productWeight,
            'product_measures'   => $productSize,
            'package_weight'     => $packageWeight,
            'package_width'      => $packageSize[1],
            'package_length'     => $packageSize[0],
            'package_height'     => $packageSize[2],
            'package_content'    => '<![CDATA['.$skuInfo['included'][LazadaSite::getLanguageBySite($siteID)].']]>',
            'special_from_date'  => $addInfo['sale_price'] > 0 ? $addInfo['sale_price_start'] : '',
            'special_to_date'    => $addInfo['sale_price'] > 0 ? $addInfo['sale_price_end'] : '',
        );

        $attibutesInfoArr = array();

        foreach ($attributeArr as $attKey => $attValue) {
            if(in_array($attKey, array('tax_class','size','color_family','storage_capacity_new','ring_size','smartwear_size','size_baby_clothing','frame_color','bedding_size_2','chain_size', 'watch_strap_color','pants_length','glove_size','shoes_size','m_underwear_style','lens_color'))){
                $insertParam[$attKey] = $attValue;
                if($listing_type == self::LISTING_TYPE_VARIATION && $isSize == 0){
                    $attibutesInfoOne = LazadaProductAddVariationAttribute::model()->getAttributeByVariationID($ID);
                    $attibutesInfoArr[$attibutesInfoOne['name']] = $attibutesInfoOne['value'];
                    if(!in_array($attKey, array('size','glove_size','size_baby_clothing','storage_capacity_new','shoes_size'))){
                        $insertParam[$attKey] = isset($attibutesInfoArr['Variation'])?ucfirst($attibutesInfoArr['Variation']):$attValue;
                    }else{
                        $insertParam[$attKey] = isset($attibutesInfoArr['size'])?ucfirst($attibutesInfoArr['size']):$attValue;
                    }
                }
            }
        }


        //多属性标题加颜色
        if($listing_type == self::LISTING_TYPE_VARIATION && $isSize == 0){
            $variationSizeAddTitle = '';
            if($addInfo['size']){
                $variationSizeAddTitle = '-'.$addInfo['size'];
            }
            $attibutesInfos = LazadaProductAddVariationAttribute::model()->getAttributeByVariationID($ID);
            $variationTitles = isset($attibutesInfos['value'])?ucfirst($attibutesInfos['value']):'';
            if($variationTitles){
                $attributeArr['name'] = $attributeArr['name'].'('.$variationTitles.')'.$variationSizeAddTitle;
                $attributeArr['name_ms'] = $attributeArr['name_ms'].'('.$variationTitles.')'.$variationSizeAddTitle;
                $attributeArr['color_family'] = $variationTitles;
            }

            //判断多属性model
            if(isset($attributeArr['model'])){
                $attributeArr['model'] = $accountName.'-'.$sku;
            }

            if(isset($attributeArr['watch_strap_color'])){
                $attributeArr['watch_strap_color'] = $variationTitles;
            }
        }
        
        $param = array();
        
        if($listingType == self::LISTING_TYPE_VARIATION && $isSize == 1){
            $child_listing_type = $addInfo['listing_type'];
            $variationInfo = LazadaProductAddVariation::model()->getSonVariationByAddID($ID);
            if(!$variationInfo){
                $this->setFailure('获取子sku失败', $listing_type, $ID);
                continue;
            }

            foreach ($variationInfo as $variationVal) {
                //判断子sku是否已经上传成功和上传中
                if(in_array($variationVal['status'], array(self::UPLOAD_STATUS_RUNNING,self::UPLOAD_STATUS_SUCCESS))){
                    continue;
                }

                $childID = $variationVal['id'];
                //产品信息
                $childSkuInfo = Product::model()->getProductInfoBySku($variationVal['sku']);
                if(!$childSkuInfo){
                    $this->setFailure(Yii::t('common', 'Can Not Find Child Sku'), $child_listing_type, $childID);continue;
                }

                //判断是否是子sku
                if($childSkuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
                    $this->setFailure(Yii::t('lazada', 'Main SKU Can Not Be Published As Not Variation'), $child_listing_type, $childID);continue;
                }

                $insertParam = array(
                    'SellerSku'          => $variationVal['seller_sku'],
                    'color_family'       => isset($variationVal['value'])?$variationVal['value']:'',
                    'size'               => isset($variationVal['size'])?$variationVal['size']:'',
                    'quantity'           => 200,
                    'price'              => $variationVal['price'],
                    'special_price'      => $variationVal['sale_price'],
                    'tax_class'          => 'default',
                    'product_weight'     => $productWeight,
                    'product_measures'   => $productSize,
                    'package_weight'     => $packageWeight,
                    'package_width'      => $packageSize[1],
                    'package_length'     => $packageSize[0],
                    'package_height'     => $packageSize[2],
                    'package_content'    => '<![CDATA['.$skuInfo['included'][LazadaSite::getLanguageBySite($siteID)].']]>',
                    'special_from_date'  => $variationVal['sale_price'] > 0 ? $variationVal['sale_price_start'] : '',
                    'special_to_date'    => $variationVal['sale_price'] > 0 ? $variationVal['sale_price_end'] : '',
                );
                $param[] = $insertParam;
            }
        }else{
            $param[] = $insertParam;
        }

        /**@ 4.交互刊登*/
        $request = new ProductCreateRequestNew();
        $request->setPrimaryCategory($categoryID);//分类
        $request->setSPUId('');
        $request->setAssociatedSku('');
        $request->setAttributes($attributeArr);
        $request->setSkus($param);
        $request->push();
        $response = $request->setApiAccount($apiAccountID)->setRequest()->sendRequest()->getResponse();
        if( $request->getIfSuccess() ){//上传成功，等待回复
            $updateArr = array(
                'status'        => self::UPLOAD_STATUS_SUCCESS,
                'feed_id'       => $response->Head->RequestId,
                'upload_time'   => date('Y-m-d H:i:s'),
            );

            $this->setSuccess($listingType, $ID, $parentAddID);
            if($isSize == 1){
                $this->setSuccess(self::LISTING_TYPE_FIXEDPRICE, $ID);
                LazadaProductAddVariation::model()->getDbConnection()->createCommand()->update(LazadaProductAddVariation::tableName(), $updateArr, 'product_add_id = '.$ID);
            }
            $params = LazadaProductAdd::model()->findByAttributes(array('id' => $ID));
            //上传成功，修改待刊登及历史记录中的状态
            LazadaWaitListing::model()->updateWaitingListingStatus($params, LazadaWaitListing::STATUS_SCUCESS);
            LazadaHistoryListing::model()->updateWaitingListingStatus($params, LazadaHistoryListing::STATUS_SCUCESS);

        } else {
            $fieldNums = isset($response->Body->Errors->ErrorDetail->Field)?$response->Body->Errors->ErrorDetail->Field:'';
            $errorMessage = isset($response->Body->Errors->ErrorDetail->Message)?$response->Body->Errors->ErrorDetail->Message:'';
            $errors = $request->getErrorMsg().'--'.$fieldNums.'--'.$errorMessage;
            $updateArr = array(
                'status'        => self::UPLOAD_STATUS_FAILURE,
                'upload_time'   => date('Y-m-d H:i:s'),
                'upload_message'=> $errors,
            );

            $this->setFailure($errors, $listingType, $ID);
            if($isSize == 1){
                $this->setFailure($errors, self::LISTING_TYPE_FIXEDPRICE, $ID);
                LazadaProductAddVariation::model()->getDbConnection()->createCommand()->update(LazadaProductAddVariation::tableName(), $updateArr, 'product_add_id = '.$ID);
            }
        }

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


    /**
     * 更新数据
     */
    public function updateData($data, $conditions, $params){
        return $this->getDbConnection()->createCommand()->update(self::tableName(), $data, $conditions, $params);
    }
}