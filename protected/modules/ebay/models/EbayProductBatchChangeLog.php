<?php
/**
 * @desc ebay批量更改日志
 * @since 2016-08-23
 */
class EbayProductBatchChangeLog extends EbayModel{

	public $account_name;
	public $type_text;
	public $status_text;
	public $item_id_link;
	public $visiupload;

    const STATUS_DEFAULT     = 0; // 默认，待处理
    const STATUS_OPERATING   = 1; //处理中
    const STATUS_IMG_FAILURE = 2; //获取图片失败
    const STATUS_FAILURE     = 3; //失败
    const STATUS_SUCCESS     = 4; //成功

    /*1:更新详情内容,2:更新主图,4:更新标题,8:更新dispatchtime*/
    const TYPE_UPDATE_DESC         = 1;//更新详情内容
    const TYPE_UPDATE_ZT           = 2;//更新主图
    const TYPE_UPDATE_TITLE        = 4;//更新标题
    const TYPE_UPDATE_DISPATCHTIME = 8;//更新dispatchtime
    const TYPE_UPDATE_LOCATION     = 16;//更新location

    public function tableName(){
    	return "ueb_ebay_product_batch_change_log";
    }
    
    /**
     * @desc 添加数据
     * @param unknown $data
     * @return string|boolean
     */
    public function addData($data){
    	$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    	if($res)
    		return $this->getDbConnection()->getLastInsertID();
    	return false;
    }
    
    /**
     * @desc 更新数据
     * @param unknown $pkId
     * @return Ambigous <number, boolean>
     */
    public function updateDataByPK($pkId, $data){
    	return $this->getDbConnection()->createCommand()->update($this->tableName(), $data, "id=:id", array(":id"=>$pkId));
    }
    /**
     * @desc 检测状态
     * @param unknown $accountID
     * @param unknown $itemID
     * @param unknown $status
     * @return mixed
     */
    public function checkStatusByAccountIDAndItemID($accountID, $itemID, $status = array(0, 1, 2),$type=null){
    	$cmd = $this->getDbConnection()->createCommand()
    		->from($this->tableName())
    		->where("account_id=:account_id AND item_id=:item_id", array(':account_id'=>$accountID, ':item_id'=>$itemID))
    		->andWhere(array("IN", "status", $status));
        if (!empty($type)) {
            if (is_array($type)) {
                $cmd->andWhere("type in (".implode(',',$type).")");
            } else {
                $cmd->andWhere("type={$type}");
            }
        }
    	return $cmd->queryRow();
    }
    
    
    public function getPenndingUpdateListByAccountID($accountID, $itemIDArr = array(), $limit = 100, $status = array(0, 2), $maxUploadCount = 5, $type=null){
    	$cmd = $this->getDbConnection()->createCommand()
			    	->from($this->tableName())
			    	->where("account_id=:account_id", array(':account_id'=>$accountID))
			    	->andWhere(array("IN", "status", $status))
			    	->andWhere("upload_count<=:uploadCount", array(":uploadCount"=>$maxUploadCount))
			    	->andWhere(($itemIDArr && is_array($itemIDArr)) ? array("IN", "item_id", $itemIDArr) : "1")
			    	->limit($limit)
					->order(" id asc");
        if (!empty($type)) {
            if (is_array($type)) {
                $cmd->andWhere("type in (".implode(',',$type).")");
            } else {
                $cmd->andWhere("type={$type}");
            }
        }
		return $cmd->queryAll();
    }

    // ============================= search ========================= //
    
    public function search(){
    	$sort = new CSort();
    	$sort->attributes = array('defaultOrder'=>'t.id');
        $dataProvider = UebModel::search($this, $sort, '', $this->_setdbCriteria());
    	$dataProvider->setData($this->_additions($dataProvider->data));
    	return $dataProvider;
    }
    
    /**
     * @desc  设置条件
     * @return CDbCriteria
     */
    private function _setdbCriteria(){
    	$cdbcriteria = new CDbCriteria();
        $sku = Yii::app()->request->getParam('sku');  	
        $type = Yii::app()->request->getParam('type');
        if($sku){
            $ebayProductInfo = EbayProduct::model()->getListByCondition('item_id', "sku = '{$sku}'");
            if($ebayProductInfo){
                $itemIDArr = array();
                foreach ($ebayProductInfo as $val) {
                    $itemIDArr[] = $val['item_id'];
                }
                $cdbcriteria->addInCondition("t.item_id", $itemIDArr);
            }
        }
		if($type){
			$cdbcriteria->addCondition("t.type&{$type}");
		}
    	return $cdbcriteria;
    }
    
    private function _additions($datas){
    	if(!empty($datas)){
    		$ebayAccountList = UebModel::model("EbayAccount")->getIdNamePairs();
    		foreach ($datas as &$data){
    			$data['account_name'] = isset($ebayAccountList[$data['account_id']]) ? $ebayAccountList[$data['account_id']] : '';
    			$data['type_text'] = $this->getTypeTextOption($data['type']);
    			$data['status_text'] = $this->getStatusOptions($data['status']);
    			$data['item_id_link'] = $this->getItemlink($data['item_id']);
    			$data['visiupload'] = in_array($data['status'], array(self::STATUS_DEFAULT, self::STATUS_FAILURE, self::STATUS_IMG_FAILURE)) ? 1 : 0;
    		}
    	}
    	return $datas;
    }
    /**
     * @desc  获取item链接
     * @param unknown $itemID
     * @param unknown $siteID
     * @return Ambigous <string, unknown>
     */
    public function getItemlink($itemID){
    	$return = $itemID;
    	if($itemID){
    		$url = "http://www.ebay.com/itm/{$itemID}";
    		$return = '<a href="'.$url.'" target="__blank">'.$itemID.'</a>';
    	}
    	return $return;
    }
    public function getStatusOptions($status = null){
    	$statusOptions = array(
    		
    			0=> '待处理',
    			1=> '处理中',
    			2=> '图片上传失败',
    			3=> '更新失败',
    			4=> '更新成功'
    	);
    	if(is_null($status)) return $statusOptions;
    	if(isset($statusOptions[$status])) return $statusOptions[$status];
    	return "";
    }
    
    public function getTypeTextOption($type){
    	$typeText = "";
    	if($type&0x01){
    		$typeText .= "更新详情内容<br/>";
    	}
    	
    	if($type&0x02){
    		$typeText .= "更新主图<br/>";
    	}
    	
    	if($type&0x04){
    		$typeText .= "更新标题<br/>";
    	}
		if($type==8){
			$typeText = "更新dispatchtime";
		}
		if($type==16){
			$typeText = "更新location";
		}
		if($type==32){
			$typeText = "更新送货方式";
		}
    	return $typeText;
    }

	public function getTypeText($type = null){
		$typeText = array(

			1=> '更新详情内容',
			2=> '更新主图',
			4=> '更新标题',
			8=> '更新dispatchtime',
			16=> '更新location',
			32=> '更新送货方式'
		);
		if(is_null($type)) return $typeText;
		if(isset($typeText[$type])) return $typeText[$type];
		return "";
	}

	public function getCreateUserOptions(){
		return UebModel::model('user')
			->queryPairs('id,user_full_name', "department_id in(".MHelper::simplode(Department::getDepartmentByPlatform(Platform::CODE_EBAY)).") and user_status=1");   //ebay部门
	}
    
    public function filterOptions(){
    	$status = Yii::app()->request->getParam('status');
    	$type = Yii::app()->request->getParam('type');
    	return array(
    			
    			array(
    					'name'=>'item_id',
    					'type'=>'text',
    					'search'=>'=',
    					'alias'=>'t'
    			),
    			


    			array(
    					'name'		=>	'account_id',
    					'type'		=>	'dropDownList',
    					'search'	=>	'=',
    					'data'		=>	UebModel::model("EbayAccount")->getIdNamePairs(),
    					'alias'=>'t'
    			),
    			
    			
    			array(
    					'name'		=>	'status',
    					'type'		=>	'dropDownList',
    					'search'	=>	'=',
    					'data'		=>	$this->getStatusOptions(),
    					'value'		=>	$status,
    					'alias'		=>'t'
    			),


                array(
                        'name'=>'sku',
                        'type'=>'text',
                        'search'=>'=',
                        'alias'=>'',
                        'rel' => true
                ),
				array(
					'name'		=>	'type',
					'type'		=>	'dropDownList',
					'search'	=>	'=',
					'data'		=>	$this->getTypeText(),
					'value'		=>	$type,
					'rel' 		=> 	true
				),

				array(
					'name'		=>	'create_user_id',
					'type'		=>	'dropDownList',
					'data'		=>	$this->getCreateUserOptions(),
					'search'	=>	'=',

				),

    	);
    }
    
    
    public function attributeLabels(){
    	return array(
    			'item_id'	    =>	'Product ID',
    			 
    			'account_id'	=>	'账号',
    			
    			'type'			=>	'更新类型',
    			
    			'status'		=>	'状态',
    			
    			'last_msg'		=>	'最后消息',

                'sku'           =>  '主SKU',

				'create_user_id' => '操作人'
 
    			
    	);
    }
    
    // ============================= end search ====================//
}