<?php
/**
 * @desc Aliexpress刊登图片记录
 * @author Gordon
 */
class AliexpressProductImageAdd extends ProductImageAdd {

	/**@var 报错信息*/
	public $errorMeaage = null;



	public $_maxImage = 8;

	/**
	 * @desc 获取模型
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

    public function getDbKey() {
        return 'db_aliexpress';
    }


	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_image_add';
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
        	            $this->errorMeaage .= $image['image_name'].' Not Exists.';
        	        }
        	    }else{
        	        $this->errorMeaage .= $api->getErrorMsg();
        	    } */
        	    $serverConfig = include(CONF_PATH . 'serverKeys.php');
        	    $imageUrl = $serverConfig['oms']['host']  . ltrim($image['local_path'], '/');
        	    $filename = basename($image['local_path']);
        	    $content = file_get_contents($imageUrl);

        	    //图片打水印
        	    if (!empty($imageSettings)) {
        	    	if (($image['type'] == AliexpressProductImageAdd::IMAGE_ZT && $imageSettings['zt_watermark'] == 1) ||
                                                ($image['type'] == AliexpressProductImageAdd::IMAGE_FT && $imageSettings['ft_watermark'] == 1)){
                                is_dir(UPLOAD_DIR . Platform::CODE_ALIEXPRESS) or mkdir(UPLOAD_DIR . Platform::CODE_ALIEXPRESS, 0700);

                                $water_file = UPLOAD_DIR . $imageSettings['watermark_path'];
                                $return_array = Productimagesetting::model()->waterImage($imageUrl, $water_file, $imageSettings['watermark_position_x'], $imageSettings['watermark_position_y'], $imageSettings['watermark_alpha'], true);

                                if(is_file($return_array['path'])){
                                    $content = file_get_contents($return_array['path']);
                                }
        	    	}
        	    }

	            //上传图片到图片银行
        	    //将图片保存在临时目录
        	    $tmpFilePath = UPLOAD_DIR . Platform::CODE_ALIEXPRESS . '/' . $filename;
        	    $extension = strstr($filename, '.');
				$basename = str_replace($extension, '', $filename);
        	    if (!file_exists(dirname($tmpFilePath)))
        	    	mkdir(dirname($tmpFilePath));
        	    $flag = file_put_contents($tmpFilePath, $content);
        	    $resizeFilePath = UPLOAD_DIR . Platform::CODE_ALIEXPRESS . '/' . $basename . '_800x800' . $extension;
	            if ($flag) {
	            	Productimage::model()->img2thumb($tmpFilePath, $resizeFilePath, 800, 800);
	            } else {
	            	$this->errorMeaage .= 'Save Image Error';
	            	continue;
	            }
	            $imageBytes = file_get_contents($resizeFilePath);
	            $imageBytes = base64_encode($imageBytes);
	            unlink($tmpFilePath);
	            unlink($resizeFilePath);
        	    $uploadImageRequest = new UploadImageRequest();
	            $uploadImageRequest->setFileName($filename);
	            $uploadImageRequest->setFileStream($content);
	            $response = $uploadImageRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
	            if(!$uploadImageRequest->getIfSuccess() || ($response->status != 'SUCCESS' && $response->status != 'DUPLICATE')) {
	            	$this->errorMeaage .= "Upload Image Failure API error response: " . $uploadImageRequest->getErrorMsg();
	            	return false;
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
// 		$images = Product::model()->getImgList($sku, 'zt');
// 		$imagesFt = Product::model()->getImgList($sku, 'ft');
		$publishType = $publishType ? $publishType : AliexpressProductAdd::PRODUCT_PUBLISH_TYPE_FIXEDPRICE;
		$maxCount = $this->_maxImage ? $this->_maxImage : count($images);
		$imageData = array();
		if($publishType==AliexpressProductAdd::PRODUCT_PUBLISH_TYPE_FIXEDPRICE){//一口价
			shuffle($images);//打乱顺序
			$count = 0;
			foreach($images as $k=>$image){
				$imageData[] = array(
						'image_name'    => end(explode('/', $image)),
						'sku'           => $sku,
						'type'          => self::IMAGE_ZT,
						'local_path'    => $image,
						'platform_code' => Platform::CODE_ALIEXPRESS,
						'account_id'    => $accountID,
						'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
						'create_user_id'=> Yii::app()->user->id,
						'create_time'   => date('Y-m-d H:i:s'),
				);
				$count++;
				if($count>=$maxCount){
					break;
				}
			}

			foreach($imagesFt as $k=>$image){
				$imageData[] = array(
						'image_name'    => end(explode('/', $image)),
						'sku'           => $sku,
						'type'          => self::IMAGE_FT,
						'local_path'    => $image,
						'platform_code' => Platform::CODE_ALIEXPRESS,
						'account_id'    => $accountID,
						'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
						'create_user_id'=> Yii::app()->user->id,
						'create_time'   => date('Y-m-d H:i:s'),
				);
			}
		}else{//多属性，找寻子sku的图片 TODO

		}
		if( !empty($imageData) ){
			foreach($imageData as $data){
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
	 * @desc 发送图片上传请求
	 */
	public function sendImageUploadRequest($sku, $accountID, $siteId,$platformCode = Platform::CODE_ALIEXPRESS,  $assistantImage = false){

	    //return parent::sendImageUploadRequest($sku, $accountID, $siteId, $platformCode, $assistantImage);

		$productImageAdd = new self();
		$platformCode    = Platform::CODE_ALIEXPRESS;
		//图片名称列表
		$imageNameList   = array();
		//如果有水印，存入此数组
		$watermarkImgNameList = array();
		//获取待上传图片
	    $list = $this->getImageBySku($sku, $accountID, $platformCode, $siteId);
	    if (empty($list)) {
	    	$this->errorMeaage .= 'No Data for Upload';
	    	return false;
	    }

	    //判断是否加水印
	    $watermarkArr = Productimagesetting::model()->watermarkImages($accountID, $platformCode);

	    //获取账号图片设置
	    $imageNameTypeList = array();
	    $imagesFt = Product::model()->getImgList($sku, 'ft');
	    foreach($list as $type=>$images){
	        foreach($images as $image){
	        	$tmpname = basename($image['image_name']);
	        	// $tmpname = substr($tmpname,0,strrpos($tmpname,'.'));

        	    $imageNameList[$image['id']] = $image['image_name'];
        	    $imageNameTypeList[$image['id']] = $image['type'];
	        }
	    }


	    //检测图片是否有EPS图片链接
	    if (empty($imageNameList)) {
	    	return true;
	    }
	    //图片路径列表
	    $imagePathList = array();
		$response = $productImageAdd->getSkuImageUpload($accountID,$sku,array(), $platformCode, $siteId);
    	if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs'])) {
    		$this->errorMeaage .= "Get Sku Images Failure";
    		$productImageAdd->addSkuImageUpload($accountID,$sku,0,$platformCode,$siteId);//发送图片上传请求
    		return false;
    	}
    	//循环出加水印的图片
    	if($watermarkArr){
    		foreach ($response['result']['imageInfoVOs'] as $v) {
    			//取出有水印的图片地址
				if($v['watermark']){
					$watermarkImgNameList[strtolower($v['imageName'])] = $v['remotePath'];
				}
    		}
    	}

    	//判断图片名称是否存在
    	$isAllRight = true;
    	$pic = '';
    	foreach ($imageNameList as $id => $imageName) {
    		$flag = false;
    		foreach ($response['result']['imageInfoVOs'] as $v) {
    			if (strtolower($v['imageName']) == strtolower($imageName) && $v['remotePath'] != '' ) {
					$imagePathList[$id] = $v['remotePath'];
    				$flag = true;
    				break;
    			}
    		}

    		if (!$flag) {
				$isAllRight = false;
    			$pic = $imageName;
    			break;
    		}
    	}
    	if (!$isAllRight) {
			$this->errorMeaage .= $pic. " Image name is not exist";
			$productImageAdd->addSkuImageUpload($accountID,$sku,0,$platformCode,$siteId);//发送图片上传请求
    		return false;
    	}
    	$isOk = true;
    	foreach ($imagePathList as $id => $imagePath) {
			$flag = $this->dbConnection->createCommand()->update($this->tableName(), array(
	               'upload_status' => ProductImageAdd::UPLOAD_STATUS_SUCCESS,
	               'remote_path'   => $imagePath,//EPS图片链接地址
	               'remote_type'   => ProductImageAdd::REMOTE_TYPE_IMAGEBANK,
	        ),'id = '.$id);
	        if (!$flag) {
	        	$isOk = false;
	        }
    	}

    	//判断图片是否加水印
		if(in_array(self::IMAGE_ZT, $watermarkArr) && $watermarkImgNameList){
			foreach ($watermarkImgNameList as $imgName => $watermarkImagePath) {
				$imgName = str_replace('.jpg', '', $imgName);
				$flag = $this->dbConnection->createCommand()->update($this->tableName(), array(
		               'remote_path' => $watermarkImagePath //EPS图片链接地址
		        ),'image_name = \''.$imgName.'\' AND type = '.self::IMAGE_ZT);
		        if (!$flag) {
		        	$isOk = false;
		        }
	    	}
		}
    	return $isOk;
	}


	/**
	 * @desc 上传图片到图片服务器
	 * @see ProductImageAdd::uploadImageOnline()
	 */
	public function newUploadImageOnline($sku, $accountID){
		$platformCode         = Platform::CODE_ALIEXPRESS;
		$productImageAddModel = new self();
		$watermarkImgNameList = null;
		$remotePath           = '';
		//判断图片是否加水印
		$watermarkArr = Productimagesetting::model()->watermarkImages($accountID, $platformCode);
	    $list = $this->getImageBySku($sku, $accountID, $platformCode);
	    foreach($list as $type=>$images){
	        foreach($images as $image){
	            if( $image['upload_status']==ProductImageAdd::UPLOAD_STATUS_SUCCESS ){
	                continue;
	            }

	            //判断是否要加水印的图片名称
	            if(in_array($type, $watermarkArr)){
	            	$watermarkImgNameList[] = basename($image['local_path']);
	            }

	            $response = $productImageAddModel->getSkuImageUpload($accountID,$sku,array($image['image_name']),$platformCode, 0);
	            if(empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs'])) {
	                $responseStr = isset($response['errormsg']) ? $response['errormsg'] : '';
	            	$productImageAddModel->addSkuImageUpload($accountID,$sku,0,$platformCode,0,$watermarkImgNameList);
	            	$this->errorMeaage .= "{$sku}_{$accountID}_{$platformCode}_{$image['image_name']}_图片接口上传失败_{$responseStr}，  ";
	            }else{
	            	//如果加水印
	            	if(in_array($type, $watermarkArr)){
	            		$remotePath = $response['result']['imageInfoVOs'][1]['remotePath'];
	            	}else{
	            		$remotePath = $response['result']['imageInfoVOs'][0]['remotePath'];
	            	}

	                $this->dbConnection->createCommand()->update($this->tableName(), array(
	                       'upload_status' => self::UPLOAD_STATUS_SUCCESS,
	                       'remote_path'   => $remotePath,
	                       'remote_type'   => self::REMOTE_TYPE_IMAGEBANK,
	                ),'id = '.$image['id']);
	            }
	        }
	    }
	    if( $this->errorMeaage==''){
	        return true;
	    }else{
	        return false;
	    }
	}


	/**
     * @desc 查找要上传的图片
     * @param string $sku
     */
    public function getAliexpressImageBySku($sku, $accountID = null)
    {
        $command = $this->getDbConnection()->createCommand()
            ->select('*')
            ->from($this->tableName())
            ->where('sku = "' . $sku . '"')
            ->order("id ASC");
        if (!empty($accountID))
            $command->andWhere('account_id = ' . $accountID);
        $list = $command->queryAll();
        $imageList = array();
        if($list){
	        foreach ($list as $item) {
	            $imageList[$item['type']][] = $item;
	        }
	    }
        return $imageList;
    }


    /**
     * @desc 删除对应平台账号sku已经添加的图片
     * @param unknown $sku
     * @param unknown $accountID
     * @param unknown $platformCode
     */
    public function deleteAliexpressSkuImages($sku, $accountID, $platformCode, $siteID = null, $type = null)
    {
        $conditions = "sku = '" . addslashes($sku) . "' and account_id = " . (int)$accountID . " and platform_code = '" . addslashes($platformCode) . "'";
        if ($siteID !== null) {
            $siteID = (int)$siteID;
            $conditions .= " and site_id={$siteID} ";
        }
        if ($type !== null) {
            $conditions .= " and type='{$type}'";
        }
        return $this->getDbConnection()->createCommand()->delete($this->tableName(), $conditions);
    }


    /**
     * @desc 复制刊登添加图片公共方法
     * @param unknown $sku
     * @param unknown $accountID
     */
    public function aliexpressAutoImagesAdd($sku, $accountID){
    	//获取产品图片
    	$platformCode = Platform::CODE_ALIEXPRESS;
		$skuImages = array();
		$skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($sku, null, 'normal', 100, 100, $platformCode);
		if(!isset($skuImg['ft']) || !$skuImg['ft']){
			return false;
		}

		$images = $skuImg['ft'];

		$getImageConfig = new Productimage();
        $config = $getImageConfig->get_img_config();
        $ass_path = $config['img_local_path'].$config['img_local_assistant_path'];
        $first = substr($sku,0,1);
        $second = substr($sku,1,1);
        $third = substr($sku,2,1);
        $four = substr($sku,3,1);
        $filePath = '/'.$first.'/'.$second.'/'.$third.'/'.$four.'/';
        $ztFilePath = $config['img_local_path'].$config['img_local_main_path'].$filePath;
        $ftFilePath = $ass_path.$filePath;

		$countNum = 1;
		foreach($images as $k=>$img){
			$imageUrl = reset(explode('?', $img));
			$imageSku = basename($imageUrl, '.jpg');
			if($imageSku == $sku){
				continue;
			}

			$skuImages['ft'][$k] = $imageUrl;
			if($countNum <= 6){
				$skuImages['zt'][$k] = $imageUrl;
			}

			$countNum += 1;
		}

    	//删除以前的图片
		$this->deleteAliexpressSkuImages($sku, $accountID, $platformCode);
		foreach ($skuImages as $type => $images) {
			foreach ($images as $image) {
				$typeId = 0;
				$imagename = basename($image);

				if($type == 'zt'){
					$typeId = ProductImageAdd::IMAGE_ZT;
					$image = $ztFilePath.$imagename;
				}elseif ($type == 'ft') {
					$typeId = ProductImageAdd::IMAGE_FT;
					$image = $ftFilePath.$imagename;
				}
						
				$imageAddData = array(
					'image_name'    => $imagename,
					'sku'           => $sku,
					'type'          => $typeId,
					'local_path'    => $image,
					'platform_code' => Platform::CODE_ALIEXPRESS,
					'account_id'    => $accountID,
					'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
					'create_user_id'=> Yii::app()->user->id,
					'create_time'   => date('Y-m-d H:i:s'),					
				);
				$imageModel = new AliexpressProductImageAdd();
                $imageModel->setAttributes($imageAddData,false);
                $imageModel->setIsNewRecord(true);
                $flag = $imageModel->save();
                if (!$flag){
                	return false;
                }
			}
		}
		unset($imageAddData);

		//推送图片
		$productImageAdd = new ProductImageAdd();
		$productImageAdd->addSkuImageUpload($accountID,$sku,0,$platformCode);

		return true;
    }


    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where [description]
     * @param  mixed $order [description]
     * @return [type]         [description]
     * @author yangsh
     */
    public function getListByCondition($fields = '*', $where = '1', $order = '')
    {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }
}