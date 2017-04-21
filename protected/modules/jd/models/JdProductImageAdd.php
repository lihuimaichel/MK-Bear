<?php
/**
 * @desc 京东刊登图片记录
 * @author Gordon
 */
class JdProductImageAdd extends ProductImageAdd {
	
	/**@var 报错信息*/
	public $errorMeaage = null;
	
	const MAX_IMAGE_NUMBER = 6;
	
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
		return 'ueb_product_image_add';
	}
    
	/**
	 * @desc 上传指定图片到图片服务器
	 * @param unknown $imageAddID
	 * @return boolean|string
	 */
	public function uploadImageToImageServer($imageAddID) {
		$config = ConfigFactory::getConfig('serverKeys');
		$imageDomain = $config['image']['domain'];
		$imageInfo = $this->dbConnection->createCommand()
			->from(self::tableName())
			->select("*")
			->where("id = " . (int)$imageAddID)
			->queryRow();
		if (empty($imageInfo)) {
			$this->errorMeaage = Yii::t('jd', 'Not Find Image');
			return false;
		}
		if (!empty($imageInfo['remote_path']))
			return $imageDomain . $imageInfo['remote_path'];
		
		if( $imageInfo['upload_status']==self::UPLOAD_STATUS_SUCCESS ){
			$this->errorMeaage = Yii::t('jd', 'Upload Image Running');
			return false;
		}
		//判断OMS本地文件是否存在
		$result = UebModel::model("Productimage")->checkImageExist($imageInfo['local_path']);
		if( !$result ){
			$this->errorMeaage .= $imageInfo['image_name'].' Not Exists.';
		}
		
		/* $param = array('path'=>$imageInfo['local_path']);
		$api = Yii::app()->erpApi;
		$result = $api->setServer('oms')->setFunction('Products:Productimage:checkImageExist')->setRequest($param)->sendRequest()->getResponse();
		if( $api->getIfSuccess() ){
			if( !$result ){
				$this->errorMeaage .= $imageInfo['image_name'].' Not Exists.';
			}
		}else{
			$this->errorMeaage .= $api->getErrorMsg().'.';
		} */
		//判断配置是否要打水印 
		 
		//上传图片到指定文件夹,返回路径
		$absolutePath = $this->saveTempImage($imageInfo['local_path']);
		list($remoteName, $remotePath) = $this->getImageRemoteInfo($imageInfo['local_path'], $imageInfo['account_id'], Platform::CODE_JD);
		$uploadResult = $this->uploadImageServer($absolutePath, $remoteName, $remotePath);
		unlink($absolutePath);
		if( $uploadResult != 1 ){
			$this->errorMeaage .= Yii::t('common', 'Upload Connect Error');
			return false;
		}else{
			$remoteImageUrl = $imageDomain.$remotePath.$remoteName;
			$this->dbConnection->createCommand()->update(self::tableName(), array(
					'upload_status' => self::UPLOAD_STATUS_SUCCESS,
					'remote_path'   => $remoteImageUrl,
					'remote_type'   => self::REMOTE_TYPE_IMAGESERVER,
			),'id = ' . $imageAddID['id']);
			return $remoteImageUrl;
		}
	}

	/**
	 * @desc 上传图片到图片服务器
	 * @see ProductImageAdd::uploadImageOnline()
	 */
	public function uploadImageOnline($sku, $accountID){
	    $list = $this->getImageBySku($sku, $accountID, Platform::CODE_ALIEXPRESS);
	    //获取账号图片设置
	    $imageSettings = Productimagesetting::model()->getImageConfigByAccountID($accountID, Platform::CODE_ALIEXPRESS);
	    foreach($list as $type=>$images){
	        foreach($images as $image){
	            if( $image['upload_status']==self::UPLOAD_STATUS_SUCCESS ){
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
        	        $this->errorMeaage .= $api->getErrorMsg();
        	    } */
        	    $serverConfig = include(CONF_PATH . 'serverKeys.php');
        	    $imageUrl = $serverConfig['oms']['host']  . ltrim($image['local_path'], '/');
        	    $filename = basename($image['local_path']);
        	    
        	    //图片打水印 @TODO
        	    if (!empty($imageSettings)) {
        	    	if (($images['type'] == AliexpressProductImageAdd::IMAGE_ZT && $imageSettings['zt_watermark'] == 1) ||
	            	AliexpressProductImageAdd::IMAGE_FT && $imageSettings['ft_watermark'] == 1) 
        	    	{
        	    		$filenamePrefix	= $imageSettings['filename_prefix'];
        	    		$filenameSuffix = $imageSettings['filename_suffix'];
        	    	}
        	    }
        	    
	            //上传图片到图片银行
        	    $content = file_get_contents($imageUrl);
	            $uploadImageRequest = new UploadImageRequest();
	            $uploadImageRequest->setFileName($filename);
	            $uploadImageRequest->setFileStream($content);
	            $response = $uploadImageRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
	            if(!$uploadImageRequest->getIfSuccess() || ($response->status != 'SUCCESS' && $response->status != 'DUPLICATE')) {
	            	$this->errorMeaage .= "Upload Image Failure";
	            }else{
	                $this->dbConnection->createCommand()->update(self::tableName(), array(
	                       'upload_status' => self::UPLOAD_STATUS_SUCCESS,
	                       'remote_path'   => $response->photobankUrl,
	                       'remote_type'   => self::REMOTE_TYPE_IMAGEBANK,
	                ),'id = '.$image['id']);
	            }
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
	 * @param string $publishType
	 */
	public function addProductImageBySku($sku, $accountID, $publishType = ''){
 		$images = Product::model()->getImgList($sku, 'zt');
 		$imagesFt = Product::model()->getImgList($sku, 'ft');
 		if (empty($images) && empty($imagesFt)) {
 			return false;
 		}
		$imageData = array();
		shuffle($images);//打乱顺序
		$count = 1;
		$existsImageArr = array();
		foreach($images as $k=>$image){
			if ($count > self::MAX_IMAGE_NUMBER) break;
			$imageName = end(explode('/', $image));
			$imageData[] = array(
				'image_name'    => $imageName,
				'sku'           => $sku,
				'type'          => self::IMAGE_ZT,
				'local_path'    => $image,
				'platform_code' => Platform::CODE_JD,
				'account_id'    => $accountID,
				'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
				'create_user_id'=> Yii::app()->user->id,
				'create_time'   => date('Y-m-d H:i:s'),
			);
			$existsImageArr[] = $imageName;
			$count++;
		}
		//当主图数量小于最大图片限制时，去附图取图片
		if ($count < self::MAX_IMAGE_NUMBER) {
			foreach ($imagesFt as $image) {
				if ($count > self::MAX_IMAGE_NUMBER) break;
				$imageName = end(explode('/', $image));
				//过滤文件名像同的图片
				if (in_array($imageName, $existsImageArr)) continue;
				$imageData[] = array(
						'image_name'    => $imageName,
						'sku'           => $sku,
						'type'          => self::IMAGE_ZT,
						'local_path'    => $image,
						'platform_code' => Platform::CODE_JD,
						'account_id'    => $accountID,
						'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
						'create_user_id'=> Yii::app()->user->id,
						'create_time'   => date('Y-m-d H:i:s'),
				);
				$count++;				
			}			
		}
		//获取附图
		foreach($imagesFt as $k=>$image){
			$imageData[] = array(
				'image_name'    => end(explode('/', $image)),
				'sku'           => $sku,
				'type'          => self::IMAGE_FT,
				'local_path'    => $image,
				'platform_code' => Platform::CODE_JD,
				'account_id'    => $accountID,
				'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
				'create_user_id'=> Yii::app()->user->id,
				'create_time'   => date('Y-m-d H:i:s'),
			);
		}
		//保存图片
		if( !empty($imageData) ){
			foreach($imageData as $data){
				//检查图片是否存在
				$checkExists = ProductImageAdd::model()->checkImageExists($data['image_name'], $data['type'], $data['platform_code'], $data['account_id']);
				if (!empty($checkExists)) continue;
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
}