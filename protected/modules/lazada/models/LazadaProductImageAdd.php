<?php
/**
 * @desc Lazada刊登图片记录
 * @author Gordon
 */
class LazadaProductImageAdd extends LazadaModel {
    
    /**@var 上传状态 */
    const UPLOAD_STATUS_DEFAULT = 0;//未上传
    const UPLOAD_STATUS_SUCCESS = 1;//上传成功
    const UPLOAD_STATUS_FAILURE = 2;//上传失败
    
    const IMAGE_ZT = 1;//主图
    const IMAGE_FT = 2;//附图
    
    const REMOTE_TYPE_IMAGESERVER = 1;//远程图片服务器
    const REMOTE_TYPE_IMAGEBANK = 2;	//平台图片银行

    /**@var 报错信息*/
    public $errorMeaage = null;
    
    const MAX_IMAGE_NUMBER = 8;
    public $_maxImage = 8;
    
	/**
	 * @desc 获取模型
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_lazada_image_add';
	}


	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableNameLog() {
		return 'ueb_lazada_image_add_log';
	}
    
	/**
	 * @desc 上传图片到图片服务器
	 * @see ProductImageAdd::uploadImageOnline()
	 */
	public function uploadImageOnline($sku, $accountID, $isforce = false){
        set_time_limit(3600);
		//上传图片机制修改，因为LAZADA每个账号同一个SKU得图片都一样，所有一个SKU得图片只上传一次到图片服务器
		if($isforce){
	    	$list = self::getImageBySku($sku, $accountID);
		}else{
			$list = self::getImageBySku($sku, null);
		}
	    //TODO 图片服务器域名做成系统配置
	    $domain = 'http://www.vakind.info';
	    $skuImageArr = array();
	    foreach($list as $type=>$images){
	        foreach($images as $image){
	            if( $image['upload_status']==self::UPLOAD_STATUS_SUCCESS ){
	            	$skuImageArr[$image['local_path']] = $image['remote_path'];
	                continue;
	            }
	            if (array_key_exists($image['local_path'], $skuImageArr)) {
	            	//将该记录的图片设置成已上传
	            	if (empty($image['remote_path']))
		            	$this->dbConnection->createCommand()->update(self::tableName(), array(
		            			'upload_status' => self::UPLOAD_STATUS_SUCCESS,
		            			'remote_path'   => $skuImageArr[$image['local_path']],
		            			'remote_type'   => self::REMOTE_TYPE_IMAGESERVER,
		            	),'id = '.$image['id']);
	            	continue;
	            }
	            //判断OMS本地文件是否存在
	            $result = UebModel::model("Productimage")->checkImageExist($image['local_path']);
	            if( !$result ){
	            	$this->errorMeaage .= $image['image_name'].' Not Exists.';
	            }
	            
	            /* $param = array('path'=>$image['local_path']);
	            $api = Yii::app()->erpApi;
	            $result = $api->setServer('oms')->setFunction('Products:Productimage:checkImageExist')->setRequest($param)->sendRequest()->getResponse();
                if( $api->getIfSuccess() ){
        	        if( !$result ){
        	            $this->errorMeaage .= $images['image_name'].' Not Exists.';
        	        }
        	    }else{
        	        $this->errorMeaage .= $api->getErrorMsg().'.';
        	    } */
	            //判断配置是否要打水印 (lazada暂时不要水印)
	            
	            //上传图片到指定文件夹,返回路径
	            $productImageAddModel = new ProductImageAdd();
	            $absolutePath = $productImageAddModel->saveTempImage($image['local_path']);
	            list($remoteName, $remotePath) = $productImageAddModel->getImageRemoteInfo($image['local_path'], $accountID, Platform::CODE_LAZADA);
	            $uploadResult = $productImageAddModel->uploadImageServer($absolutePath, $remoteName, $remotePath);
	            unlink($absolutePath);
	            if( $uploadResult != 1 ){
	                $this->errorMeaage .= Yii::t('common', 'Upload Connect Error');
	            }else{
	            	$skuImageArr[$type][$image['image_name']] = $domain.$remotePath.$remoteName;
 	                $this->getDbConnection()->createCommand()->update(self::tableName(), array(
	                       'upload_status' => self::UPLOAD_STATUS_SUCCESS,
	                       'remote_path'   => $domain.$remotePath.$remoteName,
	                       'remote_type'   => self::REMOTE_TYPE_IMAGESERVER,
	                ),'id = '.$image['id']);
	            }

//         	    $type = $image['type']==1 ? 'main' : 'assistant';
//         	    //直接读取图片映射盘
//         	    $this->dbConnection->createCommand()->update(self::tableName(), array(
//         	        'upload_status' => self::UPLOAD_STATUS_SUCCESS,
//         	        'remote_path'   => 'http://vakind.f3322.org:3555/imgs/'.$type.'/'.substr($sku, 0, 1).'/'.substr($sku, 1, 1).'/'.$image['image_name'],
//         	        'remote_type'   => self::REMOTE_TYPE_IMAGESERVER,
//         	    ),'id = '.$image['id']);
	        }
	    }
	    if( $this->errorMeaage=='' ){
	        return true;
	    }else{
	        return false;
	    }
	}
	
	/**
	 * @desc 根据sku添加图片
	 * @param string $sku
	 * @param string $listingType
	 */
	public function addProductImageBySku($sku, $accountID, $listingType = ''){
	    $images = Product::model()->getImgList($sku, 'zt');
	    $imagesFt = Product::model()->getImgList($sku, 'ft');
	    sort($imagesFt, 6);
	    $listingType = $listingType ? $listingType : LazadaProductAdd::LISTING_TYPE_FIXEDPRICE;
	    $maxCount = self::MAX_IMAGE_NUMBER ? self::MAX_IMAGE_NUMBER : count($images);
	    $imageData = array();
	   // if($listingType==LazadaProductAdd::LISTING_TYPE_FIXEDPRICE){//一口价
	    	//lazada的产品取附图里面的图片
	        //shuffle($imagesFt);//打乱顺序
	        $count = 1;
	        foreach($imagesFt as $k=>$image){
	        	if($count>$maxCount) break;
	            $imageData[] = array(
	                'image_name'    => end(explode('/', $image)),
	                'sku'           => $sku,
	                'type'          => self::IMAGE_ZT,
	                'local_path'    => $image,
	                'account_id'    => $accountID,
	                'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
	                'create_user_id'=> Yii::app()->user->id,
	                'create_time'   => date('Y-m-d H:i:s'),
	            );
	            $count++;
	        }
/* 	        foreach($imagesFt as $k=>$image){
	        	if ($count > $this->_maxImage) break;
	        	$count++;
	            $imageData[] = array(
	                'image_name'    => end(explode('/', $image)),
	                'sku'           => $sku,
	                'type'          => self::IMAGE_FT,
	                'local_path'    => $image,
	                'platform_code' => Platform::CODE_LAZADA,
	                'account_id'    => $accountID,
	                'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
	                'create_user_id'=> Yii::app()->user->id,
	                'create_time'   => date('Y-m-d H:i:s'),
	            );
	        } */
	   // }else{//多属性，找寻子sku的图片 TODO
	        
	   // }
	    if( !empty($imageData) ){
    	    foreach($imageData as $data){
    	    	//查询图片是否已经添加
    	    	$checkExites = $this->getDbConnection()->createCommand()
    	    		->from(self::tableName())
    	    		->where("image_name = :image_name", array(':image_name' => $data['image_name']))
    	    		->andWhere("type = :type", array(':type' => $data['type']))
    	    		->andWhere("account_id = :account_id", array(':account_id' => $data['account_id']))
    	    		->queryRow();
    	    	if (!empty($checkExites)) continue;
    	        $imageModel = new self();
    	        $imageModel->setAttributes($data,false);
    	        $imageModel->setIsNewRecord(true);
    	        $imageModel->save();
    	    }
    	    return true;
	    }else{
	        return false;
	    }
	}
	
	/**
	 * @desc 设置上传报错信息
	 * @param string $message
	 */
	public function setErrorMessage($message){
	    $this->errorMeaage = $message;
	}
	
	/**
	 * @desc 获取报错信息
	 */
	public function getErrorMessage(){
	    return $this->errorMeaage;
	}


	/**
     * @desc 查找要上传的图片
     * @param string $sku
     */
    public function getImageBySku($sku, $accountID = null, $siteId = null){      
        $command = $this->dbConnection->createCommand()
                    ->select('*')
                    ->from(self::tableName())
                    ->where('sku = "'.$sku.'"')
        			->order("id ASC");
        if (!empty($accountID))
        	$command->andWhere('account_id = '.$accountID);
        if ($siteId !== null && intval($siteId) >= 0)
        	$command->andWhere('site_id='.intval($siteId));
        $list = $command->queryAll();
        $imageList = array();
        foreach($list as $item){
            $imageList[$item['type']][] = $item;
        }
        return $imageList;
    }


    /**
     * @desc 检查图片是否存在
     * @param unknown $image
     * @param unknown $type
     * @param unknown $platformCode
     * @param unknown $accountID
     */
    public function checkImageExists($image, $type, $accountID) {
    	return $this->getDbConnection()->createCommand()
  					->from(self::tableName())
  					->where("image_name = :image_name", array(':image_name' => $image))
  					->andWhere("type = :type", array(':type' => $type))
  					->andWhere("account_id = :account_id", array(':account_id' => $accountID))
  					->queryRow();
    }


    /**
	 * [getOneByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where [description]
	 * @param  mixed $order [description]
	 * @return [type]         [description]
	 * @author yangsh
	 */
	public function getOneByCondition($fields = '*', $where = '1', $order = '')
	{
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
	 * @param  array $data 数据集合
	 */
	public function updateData($data, $conditions, $params){
        return $this->getDbConnection()->createCommand()->update(self::tableName(), $data, $conditions, $params);
    }


    /**
	 * @desc 根据sku通过java接口获取图片自动添加图片
	 * @param string $sku
	 * @param string $listingType
	 */
	public function addGetJavaProductImageBySku($sku, $accountID, $platformCode = '', $listingType = ''){
		$sku = trim($sku);
	    $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku, null, 'normal', 100, 100, $platformCode);
	    if(!isset($skuImg['ft']) || !$skuImg['ft']){
    		return false;
    	}

    	$imagesFt = $skuImg['ft'];

	    // sort($imagesFt, 6);
	    $listingType = $listingType ? $listingType : LazadaProductAdd::LISTING_TYPE_FIXEDPRICE;
	    $maxCount = self::MAX_IMAGE_NUMBER;
	    $imageData = array();

        $count = 1;
        foreach($imagesFt as $k=>$image){
        	if($count>$maxCount) break;
        	$imageUrl = reset(explode('?', $image));
        	$imageName = basename($imageUrl);
        	$imageNameSku = basename($imageUrl , '.jpg');
        	if($sku == $imageNameSku){
        		continue;
        	}

            $imageData[] = array(
                'image_name'    => $imageName,
                'sku'           => $sku,
                'type'          => self::IMAGE_ZT,
                'local_path'    => $imageUrl,
                'account_id'    => $accountID,
                'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
                'create_user_id'=> Yii::app()->user->id,
                'create_time'   => date('Y-m-d H:i:s'),
            );
            $count++;
        }

	    if( !empty($imageData) ){
    	    foreach($imageData as $data){
    	    	//查询图片是否已经添加
    	    	$checkExites = $this->getDbConnection()->createCommand()
    	    		->from(self::tableName())
    	    		->where("image_name = :image_name", array(':image_name' => $data['image_name']))
    	    		->andWhere("type = :type", array(':type' => $data['type']))
    	    		->andWhere("account_id = :account_id", array(':account_id' => $data['account_id']))
    	    		->queryRow();
    	    	if (!empty($checkExites)) continue;
    	        $imageModel = new self();
    	        $imageModel->setAttributes($data,false);
    	        $imageModel->setIsNewRecord(true);
    	        $imageModel->save();
    	    }
    	    return true;
	    }else{
	        return false;
	    }
	}
}