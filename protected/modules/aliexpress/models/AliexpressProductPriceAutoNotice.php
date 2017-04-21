<?php
/**
 * @desc   Aliexpress自动调价监控模型
 * @author AjunLongLive!
 * @since  2017-04-13
 */ 
class AliexpressProductPriceAutoNotice extends AliexpressModel{
    
    public $profit_rate = null;
    public $avg_price = null;
    public $fifty_profit_rate = null;
    
    const AVG_PRICE_TYPE      = 1;      //加权平均价
    const PRODUCT_WEIGHT_TYPE = 2;      //产品毛重
    
    const STATUS_WAITING = 0;      //等待处理
    const STATUS_CHANGED = 1;      //标记处理
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_product_price_auto_notice';
    }
  
    
    /**
     * @desc 属性翻译
     */
    public function attributeLabels() {
    	return array(
    			'sku'					            => '系统sku',
    			'online_sku'         		 	    => '在线sku',
    			'aliexpress_product_id'				=> '产品id',
    			'account_id'		                => '账号',
    			'seller_name'		                => '销售人员',
    			'standard_price'		            => '标准售价',
    			'standard_profit_rate'		        => '标准盈利率',
    			'status'		                    => '状态',
    			'log_date'		                    => '监控日期',
    			'change_time'		                => '标记时间',
    			'change_user_id'		            => '标记用户',
    			'change_now_price'		            => '标记后售价',
    			'profit_rate'		                => '利润率',
    			'avg_price'		                    => '加权平均价',
    			'setting'		                    => '操作',
    			'five_profit_rate'		            => '',
    			'ten_profit_rate'		            => '',
    			'fifteen_profit_rate'		        => '',
    			'twenty_profit_rate'		        => '',
    			'twenty_five_profit_rate'		    => '',
    			'fifty_profit_rate'		            => '',
    			'log_type'		                    => '参考折扣',

    	);
    }
    
    
	/**
	 * @return array search filter (name=>label)
	 */
	public function filterOptions() {

		$result = array(
				array(
						'name'     		 => 'sku',
						'type'     		 => 'text',
						'search'   		 => '=',
						'htmlOptions'   => array(
        		            'style'    => 'width:66px;',
						    'class'    => 'sku_id',
    		            ),
				),
				array(
						'name'     		 => 'online_sku',
						'type'     		 => 'text',
						'search'   		 => '=',
						'htmlOptions'   => array(
        		            'style'    => 'width:66px;',
						    'class'    => 'online_sku_id',
    		            ),
				),
    		    array(
    		        'name' => 'account_id',
    		        'type' => 'dropDownList',
    		        //'alias'	=>	't',
    		        'data' => AliexpressAccount::model()->getIdNamePairs(),
    		        'search' => '=',
    		        'htmlOptions'   => array(
    		            'class'    => 'account_id_id',
    		        ),
    		    ),		    
    		    array(
    		        'name' => 'status',
    		        'type' => 'dropDownList',
    		        //'alias'	=>	't',
    		        'data' => array(0 => '待处理中',1 => '已处理'),
    		        'search' => '=',
    		        'htmlOptions'   => array(
    		            'class'    => 'status_id',
    		        ),    		        
    		    ),		    
    		    array(
    		        'name' => 'log_type',
    		        'type' => 'dropDownList',
    		        //'alias'	=>	't',
    		        'data' => array(
    		            'profit_rate_five' => '5%折扣',
    		            'profit_rate_ten' => '10%折扣',
    		            'profit_rate_fifteen' => '15%折扣',
    		            'profit_rate_twenty' => '20%折扣',
    		            'profit_rate_twenty_five' => '25%折扣',
    		            'profit_rate_fifty' => '50%折扣',
    		        ),
    		        'htmlOptions'	=> array(
    		            'class' => 'profit_rate_select',
    		        ),
    		        'search' => '!=',
    		    ),		    
    		    array(
    		        'name' 			=> 'five_profit_rate',
    		        'type' 			=> 'text',
    		        'search' 		=> 'RANGE',
    		        'htmlOptions'	=> array(
    		            'size'  => 4,
    		            'class' => 'profit_rate_five',
    		        ),
    		        //'rel' 			=> true,
    		    ),
    		    array(
    		        'name' 			=> 'ten_profit_rate',
    		        'type' 			=> 'text',
    		        'search' 		=> 'RANGE',
    		        'htmlOptions'	=> array(
    		            'size' => 4,
    		            'class' => 'profit_rate_ten',
    		        ),
    		        //'rel' 			=> true,
    		    ),
    		    array(
    		        'name' 			=> 'fifteen_profit_rate',
    		        'type' 			=> 'text',
    		        'search' 		=> 'RANGE',
    		        'htmlOptions'	=> array(
    		            'size' => 4,
    		            'class' => 'profit_rate_fifteen',
    		        ),
    		        //'rel' 			=> true,
    		    ),
    		    array(
    		        'name' 			=> 'twenty_profit_rate',
    		        'type' 			=> 'text',
    		        'search' 		=> 'RANGE',
    		        'htmlOptions'	=> array(
    		            'size' => 4,
    		            'class' => 'profit_rate_twenty',
    		        ),
    		        //'rel' 			=> true,
    		    ),
    		    array(
    		        'name' 			=> 'twenty_five_profit_rate',
    		        'type' 			=> 'text',
    		        'search' 		=> 'RANGE',
    		        'htmlOptions'	=> array(
    		            'size' => 4,
    		            'class' => 'profit_rate_twenty_five',
    		        ),
    		        //'rel' 			=> true,
    		    ),
    		    array(
    		        'name' 			=> 'fifty_profit_rate',
    		        'type' 			=> 'text',
    		        'search' 		=> 'RANGE',
    		        'htmlOptions'	=> array(
    		            'size' => 4,
    		            'class' => 'profit_rate_fifty',
    		        ),
    		        //'rel' 			=> true,
    		    ),
    		    array(
    		        'name'          => 'log_date',
    		        'type'          => 'text',
    		        //'alias' 		=> 't',
    		        'search'        => 'RANGE',
    		        'htmlOptions'   => array(
    		            'class'    => 'date log_date_id',
    		            'dateFmt'  => 'yyyy-MM-dd',
    		            'style'    => 'width:66px;',
    		            'id'       => 'width:66px;',
    		        ),
    		    ),		    
		);
	
		return $result;
	
	}
	

	
    /**
     * search SQL
     * @return $array
     */
    protected function _setCDbCriteria() {

    	return NULL;
    }
    
    /**
     * @return $array
     */
    public function search(){
        $_REQUEST['debug'] = 1;
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'      => 'id',
    			'defaultDirection'	=>	'DESC'
    	);
    	$criteria = null;
    	$criteria = $this->_setCDbCriteria();
    	$dataProvider = parent::search(get_class($this), $sort,array(),$criteria);
    
    	$data = $this->addition($dataProvider->data);
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
    
    /**
     * @return $array
     */
    public function addition($data){
    	foreach ($data as $key => $val){
    	    $statusArray = array('待处理中','已处理');
    	    $data[$key]['account_id'] = AliexpressAccount::model()->getAccountNameById($val['account_id']);
    	    $data[$key]['standard_profit_rate'] = round($val['standard_profit_rate'] * 100) . '%';
    	    $data[$key]['status'] = $statusArray[$val['status']];
    	    !empty($data[$key]['change_user_id']) && $data[$key]['change_user_id'] = AliexpressAccount::model()->getAccountNameById($val['change_user_id']);
    	    ///*
    	    if (!empty($data[$key]['change_now_price'])){
    	        ///*
    	        $productPrice      = $val['change_now_price'];
    	        $productCategoryID = $val['category_id']; 
    	        $productCurrency   = 'USD'; 
    	        //根据刊登条件匹配卖价方案 TODO
    	        $productCost = 0;
    	        //$standardProfitRate = 0.18;  //标准利润率
    	        $dataTmp = array();
    	        
    	        //获取产品信息
    	        $skuInfo = Product::model()->getProductInfoBySku($val['sku']);
    	        if(!$skuInfo){
    	            //echo json_encode($dataTmp);
    	            Yii::app()->end();
    	        }
    	         
    	        if($skuInfo['avg_price'] <= 0){
    	            $productCost = $skuInfo['product_cost'];   //加权成本
    	        } else {
    	            $productCost = $skuInfo['avg_price'];      //产品成本
    	        }
    	         
    	        //产品成本转换成美金
    	        $productCost = $productCost / CurrencyRate::model()->getRateToCny($productCurrency);
    	        $productCost = round($productCost,2);
    	        $shipCode    = AliexpressProductAdd::model()->returnShipCode($productCost,$val['sku']);
    	         
    	        //取出佣金
    	        $commissionRate = AliexpressCategoryCommissionRate::getCommissionRate($productCategoryID);
    	         
    	        //计算卖价，获取描述
    	        $priceCal = new CurrencyCalculate();
    	         
    	        //设置运费code
    	        if($shipCode){
    	            $priceCal->setShipCode($shipCode);
    	        }
    	         
    	        //设置价格
    	        if($productPrice){
    	            $priceCal->setSalePrice($productPrice);
    	        }
    	        
    	        //$priceCal->setProfitRate($standardProfitRate);//设置利润率
    	        $priceCal->setCurrency($productCurrency);//币种
    	        $priceCal->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
    	        $priceCal->setSku($val['sku']);//设置sku
    	        $priceCal->setCommissionRate($commissionRate);//设置佣金比例
    	        $priceCal->setUnionCommission(AliexpressProductAdd::UNION_COMMISSION); //联盟佣金
    	        if($productPrice > 5){
    	            $priceCal2 = new CurrencyCalculate();
    	            $shipCode = AliexpressProductAdd::model()->returnShipCode($productPrice,$val['sku']);
    	            //设置运费code
    	            if($shipCode){
    	                $priceCal2->setShipCode($shipCode);
    	            }
    	            //设置价格
    	            if($productPrice){
    	                $priceCal2->setSalePrice($productPrice);
    	            }
    	             
    	            //$priceCal2->setProfitRate($standardProfitRate);//设置利润率
    	            $priceCal2->setCurrency($productCurrency);//币种
    	            $priceCal2->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
    	            $priceCal2->setSku($val['sku']);//设置sku
    	            $priceCal2->setCommissionRate($commissionRate);//设置佣金比例
    	            $priceCal2->setUnionCommission(AliexpressProductAdd::UNION_COMMISSION); //联盟佣金
    	            //标准利润率
    	            $ProfitRate = $priceCal2->getProfitRate(true);
    	        } else {
    	            $ProfitRate = $priceCal->getProfitRate(true);
    	        }
    	        $ProfitRate = round($ProfitRate * 100) . "%";
    	        $data[$key]['change_now_price'] = "{$val['change_now_price']} (利润率{$ProfitRate})";
    	        //*/
    	    }
    	    //*/
    	}
    	return $data;
    }
    
    /**
     * @return $array
     */
    public function commonHtml($type,$data){
        switch($type){
            case "avg_price":
                echo $data['log_type'] == self::AVG_PRICE_TYPE ? "最新价格：{$data['now_avg_price']}<br />上次价格：{$data['last_avg_price']}" : "";
                break;
        }
    }
       
    /**
     * @return $array
     */
    public function profitRate($data){
        $profitRate  = '';
        if ($data['log_type'] == self::AVG_PRICE_TYPE){
            $data['now_avg_price'] > $data['last_avg_price'] && $profitRate  .= '加权平均价<b>涨价</b><br />';
            $data['now_avg_price'] < $data['last_avg_price'] && $profitRate  .= '加权平均价<b>降价</b><br />';
        }
        if ($data['log_type'] == self::PRODUCT_WEIGHT_TYPE){
            $data['now_weight'] > $data['last_weight'] && $profitRate  .= '产品毛重<b>增加</b><br />';
            $data['now_weight'] < $data['last_weight'] && $profitRate  .= '产品毛重<b>减少</b><br />';
        }
        $roundTmp = round($data['five_profit_rate'] * 100);
        $profitRate .= $data['five_profit_rate'] < 0.05 ? "<span style='color:red'>  5%折扣：{$roundTmp}% 利润率</span>" : "  5%折扣：{$roundTmp}% 利润率";
        $profitRate .= "<br />";
        
        $roundTmp = round($data['ten_profit_rate'] * 100);
        $profitRate .= $data['ten_profit_rate'] < 0.05 ? "<span style='color:red'>10%折扣：{$roundTmp}% 利润率</span>" : "10%折扣：{$roundTmp}% 利润率";
        $profitRate .= "<br />";
        
        $roundTmp = round($data['fifteen_profit_rate'] * 100);
        $profitRate .= $data['fifteen_profit_rate'] < 0.05 ? "<span style='color:red'>15%折扣：{$roundTmp}% 利润率</span>" : "15%折扣：{$roundTmp}% 利润率";
        $profitRate .= "<br />";
        
        $roundTmp = round($data['twenty_profit_rate'] * 100);
        $profitRate .= $data['twenty_profit_rate'] < 0.05 ? "<span style='color:red'>20%折扣：{$roundTmp}% 利润率</span>" : "20%折扣：{$roundTmp}% 利润率";
        $profitRate .= "<br />";
        
        $roundTmp = round($data['twenty_five_profit_rate'] * 100);
        $profitRate .= $data['twenty_five_profit_rate'] < 0.05 ? "<span style='color:red'>25%折扣：{$roundTmp}% 利润率</span>" : "25%折扣：{$roundTmp}% 利润率";
        $profitRate .= "<br />";
        
        $roundTmp = round($data['fifty_profit_rate'] * 100);
        $profitRate .= $data['fifty_profit_rate'] < 0.05 ? "<span style='color:red'>50%折扣：{$roundTmp}% 利润率</span>" : "50%折扣：{$roundTmp}% 利润率";
        $profitRate .= "<br />";
                
        echo $profitRate;
    }
    
    
    /**
     * @desc 更新用户绑定的分类数据
     * @param Array
     * @return boolean  成功返回true，失败返回false
     */
    public function updateUserBindCategory($updateUserID,$updateArray){
        $result = $this->dbConnection->createCommand()
                            ->update($this->tableName(), $updateArray,"account_id = $updateUserID");
    
        if ($result > 0){
            return true;
        } else {
            return false;
        }
    }    
    
    /**
     * @desc 插入用户绑定的分类数据
     * @param Array 
     * @return boolean  成功返回true，失败返回false
     */
    public function insertUserBindCategory($insertArray){
        $result = $this->dbConnection->createCommand()
                            ->insert($this->tableName(),$insertArray);
        
       if ($result > 0){
            return true;
        } else {
            return false;
        }
    }    

    /**
     * @desc 获取用户绑定的分类数据
     * @param integer $userID
     * @return array  成功返回array，失败返回false
     */
    public function getUserBindCategoryByUserID($userID) {
        $result = $this->dbConnection->createCommand()
                            ->select('*')
                            ->from($this->tableName())
                            ->where("account_id = $userID")
                            ->queryRow();
        
        if (!$result){
            return false;
        } else {
            return $result;
        }
    }    
    
    /**
     * @desc 查询是否已有数据
     * @param integer $userID
     * @return boolean  有数据返回true，没有数据返回false
     */
    public function checkHasInsertStatus($userID) {
        $result = $this->dbConnection->createCommand()
                            ->select('account_id')
                            ->from($this->tableName())
                            ->where("account_id = $userID")
                            ->queryRow();
        if (!$result){
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * @desc 组合网址url到navTab
     * @param array  $mergeParams url,title,params(array:key,value;),style(h:height,w:width) 
     * @return 不返回，输出内容
     */
    public function mergeUrl($mergeParams) {
        $divStyleStart = '<div style="width:100%; height:100%; text-align:center;">';
        $listStyle = array('h'=>'<br />','w'=>'&nbsp;&nbsp;');
        $urlTemp = '';
        foreach ($mergeParams as $mergeKey => $mergeEach){
            if (isset($mergeEach['target'])) $target = $mergeEach['target']; else $target = 'target="navTab"';
            $urlTemp .= '<a href="'.$mergeEach['url'];
            if (count($mergeEach['params']) > 0){
                foreach ($mergeEach['params'] as $key => $val){
                    $urlTemp .= "/{$key}/{$val}";
                }
            }
            $urlTemp .= '" '.$target.' id="ajun_666_'.$mergeKey.'" rel="Ajun666'.$mergeKey.'">' . $mergeEach['title'] . '</a>' . $listStyle[$mergeEach['style']];
        }        
        $divStyleEnd = '</div>';
        echo $divStyleStart . $urlTemp . $divStyleEnd;
    }

}