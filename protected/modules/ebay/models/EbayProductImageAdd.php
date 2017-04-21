<?php
/**
 * @desc Ebay刊登图片记录
 * @author Gordon
 */
class EbayProductImageAdd extends ProductImageAdd {

	/**@var 报错信息*/
	public $errorMeaage = null;
	
	public $_maxImage = 12;
	
	/**
	 * @desc 获取模型
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function getDbKey()
    {
        return 'db_ebay';
    }

	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_ebay_image_add';
	}



	/**
	 * @desc  获取sku的图片远程地址
	 * @param  integer $accountID 销售账号ID
	 * @param  string $sku  sku
	 * @param  array  $imageNameList sku名称列表
	 * @return array
	 */
	public static function getSkuImageUpload($accountID, $sku, $imageNameList=array(), $assistantImage = false) {
		$platformCode = Platform::CODE_EBAY;
		$config = ConfigFactory::getConfig('imageKeys');
        /* if( !isset($config[ $platformCode ]) ){
            throw new CException(Yii::t('system', 'Server Does Not Exists'));
        } */
        $url = $config[ 'COMMON' ] ['url'] [ __FUNCTION__ ]; 
        $data = array(
        	'platform' 	        => $platformCode,
        	//'site' 			=> 'US',//US CA 
        	'account' 	        => $accountID,
        	'sku' 				=> $sku,
        	'assistantImage'	=>	$assistantImage
        );
        if (!empty($imageNameList)) {
        	$data['imageNameList'] = $imageNameList;
        }
        $curl = new Curl();
        $curl->init();
		$response = $curl->setOption(CURLOPT_TIMEOUT,300)
					->setOption(CURLOPT_CONNECTTIMEOUT,300)
					->postByJson($url, $data);
		return json_decode($response,true);
	}

	/**
	 * @desc 上传sku下所有图片
	 * @param integer $accountID 销售账号ID
	 * @param array  $skulist  sku列表
	 * @param integer $status  1:普通 0:紧急 
	 * @return array
	 */
	public static function addSkuImageUpload($accountID,$skulist,$status=0) {
		$platformCode = Platform::CODE_EBAY;
		$config = ConfigFactory::getConfig('imageKeys');
       /*  if( !isset($config[ $platformCode ]) ){
            throw new CException(Yii::t('system', 'Server Does Not Exists'));
        } */
        if (!is_array($skulist)) {
        	$skulist = array($skulist);
        }
        $url = $config[ 'COMMON' ] ['url'] [ __FUNCTION__ ]; 
        $data = array(
        	'platform' 	=> $platformCode,
        	//'site' 		=> 'US',//US CA 
        	'account' 	=> $accountID,
        	'status' 	=> $status,
        	'skulist' 	=> $skulist,
        	'creater'	=> 'erp_market'
        );
        $curl = new Curl();
        $curl->init();
		$response = $curl->setOption(CURLOPT_TIMEOUT,300)
					->setOption(CURLOPT_CONNECTTIMEOUT,300)
					->postByJson($url, $data);
		return json_decode($response,true);
	}

	/**
	 * @desc 发送图片上传请求
	 */
	public function sendImageUploadRequest($sku, $accountID, $siteId, $platformCode = 'EB', $assistantImage = false){
        
		//图片名称列表
		$imageNameList = array();
		//获取待上传图片
	    $list = $this->getImageBySku($sku, $accountID, Platform::CODE_EBAY, $siteId);


	    if (empty($list)) {
	    	$this->errorMeaage .= $sku.'本地无数据';
	    	return false;
	    }
//
//		$imgRes = parent::sendImageUploadRequest($sku, $accountID, $siteId, $platformCode, $assistantImage);
//		if($imgRes == false){
//			$this->errorMeaage .= parent::getErrorMessageImg();
//		}
//		return $imgRes;

	    //获取账号图片设置
	    $imageNameTypeList = array();
	    //$imagesFt = Product::model()->getImgList($sku, 'ft');
	    foreach($list as $type=>$images){
	        foreach($images as $image){
	        	$tmpname = basename($image['image_name']);
//	        	$tmpname = substr($tmpname,0,strrpos($tmpname,'.'));
//	        	if ($tmpname!='' && !isset($imagesFt[$tmpname]) ) {
//	        		continue;//过滤本地图片没有的记录
//	        	}
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
		$response = self::getSkuImageUpload($accountID,$sku,array_values($imageNameList), $assistantImage);
		if(empty($response)){
			$this->errorMeaage .= $sku."Get Sku Images Failure";
			self::addSkuImageUpload($accountID,$sku);//发送图片上传请求
			return false;
		}
		if($response['status']=='failure' || empty($response['result']) || empty($response['result']['imageInfoVOs'])){
			$this->errorMeaage .= $response['errormsg'];
			self::addSkuImageUpload($accountID,$sku);
			return false;
		}
		if(count($imageNameList) != count($response['result']['imageInfoVOs'])){
			if(count($imageNameList)>count($response['result']['imageInfoVOs'])){
				$this->errorMeaage .= $sku."接口数据有缺少图片";
			}else{
				$this->errorMeaage .= $sku."接口数据和本地数据不一致";
			}
			self::addSkuImageUpload($accountID,$sku);
			return false;
		}
    	if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs']) || count($imageNameList) != count($response['result']['imageInfoVOs']) ) {
    		$this->errorMeaage .= "Get Sku Images Failure";
    		self::addSkuImageUpload($accountID,$sku);//发送图片上传请求
    		return false;
    	}
    	//判断图片名称是否存在
    	$isAllRight = true;
    	$pic = '';
    	foreach ($imageNameList as $id => $imageName) {
    		$flag = false;
    		foreach ($response['result']['imageInfoVOs'] as $v) {
    			if (strtolower($v['imageName']) == strtolower($imageName) && $v['remotePath'] != '' ) {
    				if(isset($imageNameTypeList[$id]) && $imageNameTypeList[$id] == ProductImageAdd::IMAGE_FT && $v['uebPath']){
    					$imagePathList[$id] = $v['uebPath'];
    				}else{
    					$imagePathList[$id] = $v['remotePath'];
    				}
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
			self::addSkuImageUpload($accountID,$sku);//发送图片上传请求
    		return false;
    	}
    	$isOk = true;
    	foreach ($imagePathList as $id => $imagePath) {
			$flag = $this->dbConnection->createCommand()->update($this->tableName(), array(
	               'upload_status' => self::UPLOAD_STATUS_SUCCESS,
	               'remote_path'   => $imagePath,//EPS图片链接地址
	               'remote_type'   => self::REMOTE_TYPE_IMAGEBANK,
	        ),'id = '.$id);
	        if (!$flag) {
	        	$isOk = false;
	        }
    	}
    	return $isOk;
	}
    
	/**
	 * @desc 上传图片到图片服务器
	 * @see ProductImageAdd::uploadImageOnline()
	 */
	public function uploadImageOnline($sku, $accountID, $siteId){
	    $list = $this->getImageBySku($sku, $accountID, Platform::CODE_EBAY, $siteId);
	    //获取账号图片设置
	    $imageSettings = Productimagesetting::model()->getImageConfigByAccountID($accountID, Platform::CODE_EBAY);
	    foreach($list as $type=>$images){
	        foreach($images as $image){
	        	//已上传跳过
	            if( $image['upload_status']==self::UPLOAD_STATUS_SUCCESS ){
	                continue;
	            }
	            //判断OMS本地文件是否存在
	            $result = UebModel::model("Productimage")->checkImageExist($image['local_path']);
	            if( !$result ){
	            	$this->errorMeaage .= $image['image_name'].' Not Exists.';
	            }
	            
	           /*  $param = array('path'=>$image['local_path']);
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
        	    $filenamePrefix = $filenameSuffix = "";
        	    //图片打水印 
        	    $addWatermark = false;
        	    $waterPath = "";
        	    if (!empty($imageSettings)) {
        	    	//判断是否需要添加水印
        	    	if($type == self::IMAGE_ZT && $imageSettings['zt_watermark'] || $type==self::IMAGE_FT && $imageSettings['ft_watermark']){
        	    		$addWatermark = true;
        	    		$waterPath = UPLOAD_DIR . $imageSettings['watermark_path'];
        	    	}else{
        	    		$addWatermark = false;
        	    	}
        	    }
	            //上传图片到图片银行
        	    $content = file_get_contents($imageUrl);
        	    //将图片保存在临时目录
        	    $tmpFilePath = UPLOAD_DIR . Platform::CODE_EBAY . '/' . $filename;
        	    $extension = strstr($filename, '.');
				$basename = str_replace($extension, '', $filename);
        	    if (!file_exists(dirname($tmpFilePath)))
        	    	@mkdir(dirname($tmpFilePath));
        	    $flag = file_put_contents($tmpFilePath, $content);
        	    $resizeFilePath = UPLOAD_DIR . Platform::CODE_EBAY . '/' . $basename . '_800x800' . $extension; 
	            if ($flag) {
	            	//Productimage::model()->img2thumb($tmpFilePath, $resizeFilePath, 800, 800);
	            } else {
	            	$this->errorMeaage .= 'Save Image Error<br/>';
	            	continue;
	            }
	            if($addWatermark && file_exists($waterPath)){
	            	$res = $this->watermark($tmpFilePath, $waterPath, $resizeFilePath, $imageSettings['watermark_x'], $imageSettings['watermark_y'], $imageSettings['watermark_alpha']);
	            	if(!$res){
	            		$this->errorMeaage .= 'markwater Image Error';
	            		continue;
	            	}
	            	$tmpFilePath = $resizeFilePath;
	            }
	            if($imageSettings){
	            	$index = strrpos($image['image_name'],'.');
	            	$name = substr($image['image_name'],0,$index);
	            	$type = substr($image['image_name'],$index);
	            	$filename = $imageSettings['filename_prefix'].$name.$imageSettings['filename_suffix'].$type;
	            }
        	    $uploadImageRequest = new UploadSiteHostedPicturesRequest();
	           	$uploadImageRequest->setPicPath($tmpFilePath);
	           	$uploadImageRequest->setImgName($filename);
	            $response = $uploadImageRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
	            if(!$uploadImageRequest->getIfSuccess()) {
	            	$this->errorMeaage .= "Upload Image Failure: " . $uploadImageRequest->getErrorMsg();
	            	return false;
	            }else{
	                $this->dbConnection->createCommand()->update($this->tableName(), array(
	                       'upload_status' => self::UPLOAD_STATUS_SUCCESS,
	                       'remote_path'   => $uploadImageRequest->getEbayHostPic(),
	                       'remote_type'   => self::REMOTE_TYPE_IMAGEBANK,
	                ),'id = '.$image['id']);
	            }
	            @unlink($resizeFilePath);
	            @unlink($tmpFilePath);
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
	public function addProductImageBySku($sku, $accountID, $siteID){
 		$images = Product::model()->getImgList($sku, 'zt');
 		$imagesFt = Product::model()->getImgList($sku, 'ft');
		$maxCount = $this->_maxImage ? $this->_maxImage : count($images);
		$imageData = array();
		$count = 0;
		foreach($images as $k=>$image){
			$imageData[] = array(
					'image_name'    => end(explode('/', $image)),
					'sku'           => $sku,
					'type'          => self::IMAGE_ZT,
					'local_path'    => $image,
					'platform_code' => Platform::CODE_EBAY,
					'account_id'    => $accountID,
					'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
					'create_user_id'=> Yii::app()->user->id,
					'create_time'   => date('Y-m-d H:i:s'),
					'site_id'		=>	$siteID
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
					'platform_code' => Platform::CODE_EBAY,
					'account_id'    => $accountID,
					'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
					'create_user_id'=> Yii::app()->user->id,
					'create_time'   => date('Y-m-d H:i:s'),
					'site_id'		=>	$siteID
			);
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
	 * @desc 添加图片根据SKU VERSION 2
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @param number $siteID
	 * @return boolean
	 */
	public function addProductImageBySku2($sku, $accountID, $siteID = 0){
		$maxCount = 12;
		$imageData = array();
		$count = 1;
		//先获取全部的图片，取出已经上传成功的图片地址
		$newImgList = array();
		/* $imgList = $this->getImageBySku($sku, $accountID, Platform::CODE_EBAY, $siteID);
		if($imgList){
			foreach ($imgList as $type=>$imgs){
				foreach ($imgs as $img){
					if($img['remote_path']){
						$newImgList[$img['image_name']] = $img['remote_path'];
					}
				}
			}
		} */
		//涉及到排序问题，所以决定插入前全部删除
		$this->deleteSkuImages($sku, $accountID, Platform::CODE_EBAY, $siteID);
		
		//$images = Product::model()->getImgList($sku, 'zt');
		$imagesFt = Product::model()->getImgList($sku, 'ft');
		//if(empty($images)){
			$images = $imagesFt;
		//}
		if($images){
			foreach($images as $k=>$image){
				$image = "/" . ltrim(parse_url($image, PHP_URL_PATH), "/");
				$imgname = end(explode('/', $image));
				$imageSKU = substr($imgname, 0, strrpos($imgname, "."));//过滤小图
				if($imageSKU == $sku) continue;
				$remotePath = isset($newImgList[$imgname]) ? $newImgList[$imgname] : '';
				$uploadStatus = isset($newImgList[$imgname]) ? ProductImageAdd::UPLOAD_STATUS_SUCCESS : ProductImageAdd::UPLOAD_STATUS_DEFAULT;
				$imageData[] = array(
						'image_name'    => $imgname,
						'sku'           => $sku,
						'type'          => self::IMAGE_ZT,
						'local_path'    => $image,
						'platform_code' => Platform::CODE_EBAY,
						'account_id'    => $accountID,
						'remote_path'	=> $remotePath,
						'upload_status' => $uploadStatus,
						'create_user_id'=> Yii::app()->user->id,
						'create_time'   => date('Y-m-d H:i:s'),
						'site_id'		=>	$siteID
				);
				$count++;
				if($count>=$maxCount){
					break;
				}
			}
		}
	
		if($imagesFt){
			foreach($imagesFt as $k=>$image){
				$image = "/" . ltrim(parse_url($image, PHP_URL_PATH), "/");
				$imgname = end(explode('/', $image));
				$imageSKU = substr($imgname, 0, strrpos($imgname, "."));//过滤小图
				if($imageSKU == $sku) continue;
				$remotePath = isset($newImgList[$imgname]) ? $newImgList[$imgname] : '';
				$uploadStatus = isset($newImgList[$imgname]) ? ProductImageAdd::UPLOAD_STATUS_SUCCESS : ProductImageAdd::UPLOAD_STATUS_DEFAULT;
				$imageData[] = array(
						'image_name'    => $imgname,
						'sku'           => $sku,
						'type'          => self::IMAGE_FT,
						'local_path'    => $image,
						'platform_code' => Platform::CODE_EBAY,
						'account_id'    => $accountID,
						'remote_path'	=> $remotePath,
						'upload_status' => $uploadStatus,
						'create_user_id'=> Yii::app()->user->id,
						'create_time'   => date('Y-m-d H:i:s'),
						'site_id'		=>	$siteID
				);
			}
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
	 * @desc 根据变种sku添加图片
	 * @param string $sku
	 * @param string $publishType
	 */
	public function addProductImageByVariationSku($variationsku, $accountID, $siteID, $addFirst = true, $ftadd = false){


	    //$images = Product::model()->getImgList($variationsku, 'ft'); //modify by lihy 2016-06-17

        $images = ProductImageAdd::getImageUrlFromRestfulBySku($variationsku, 'ft',  'normal',  100, 100,  Platform::CODE_EBAY);


		$maxCount = $addFirst ? 12 : count($images);
		$imageData = array();
		$count = 0;
		foreach($images as $k=>$image){
			$imageSKU = substr($k, 0, strrpos($k, "."));//过滤小图
			if($imageSKU == $variationsku) continue;
			$count++;
			//$imageName = end(explode('/', $image));
            $imageName = $k;


			if($this->checkImageExistsBySKU($variationsku, $accountID, $siteID, $imageName, self::IMAGE_ZT)){
				continue;
			}

            $localPath = ProductImageAdd::getImageLocalPathBySkuAndName($variationsku,  self::IMAGE_ZT, basename($imageName, '.jpg'));

            $imageData[] = array(
					'image_name'    => $imageName,
					'sku'           => $variationsku,
					'type'          => self::IMAGE_ZT,
					'local_path'    => $localPath,
					'platform_code' => Platform::CODE_EBAY,
					'account_id'    => $accountID,
					'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
					'create_user_id'=> Yii::app()->user->id,
					'create_time'   => date('Y-m-d H:i:s'),
					'site_id'		=>	$siteID
			);
			
			if($count>=$maxCount){
				break;
			}
		}
		
		if($ftadd){
			foreach($images as $k=>$image){
				$imageSKU = substr($k, 0, strrpos($k, "."));//过滤小图
				if($imageSKU == $variationsku) continue;
				$count++;
				//$imageName = end(explode('/', $image));
                $imageName = $k;
				if($this->checkImageExistsBySKU($variationsku, $accountID, $siteID, $imageName, self::IMAGE_FT)){
					continue;
				}
                $localPath = ProductImageAdd::getImageLocalPathBySkuAndName($variationsku,  self::IMAGE_FT, basename($imageName, '.jpg'));

                $imageData[] = array(
						'image_name'    => $imageName,
						'sku'           => $variationsku,
						'type'          => self::IMAGE_FT,
						'local_path'    => $localPath,
						'platform_code' => Platform::CODE_EBAY,
						'account_id'    => $accountID,
						'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
						'create_user_id'=> Yii::app()->user->id,
						'create_time'   => date('Y-m-d H:i:s'),
						'site_id'		=>	$siteID
				);
					
				if($count>=$maxCount){
					break;
				}
			}
		}
	
		if( !empty($imageData) ){
			foreach($imageData as $data){
				$imageModel = new self();
				$imageModel->setAttributes($data, false);
				$imageModel->setIsNewRecord(true);
				$imageModel->save();
			}
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * @desc 根据提交的数据添加图片数据
	 * @param unknown $imgPostData
	 * @param unknown $accountID
	 * @param unknown $sku
	 * @param number $siteID
	 * @return boolean
	 */
	public function addProductImageByPost($imgPostData, $sku, $accountID, $siteID = 0){
		$maxCount = 12;
		$imageData = array();
		$count = 0;
		//先获取全部的图片，取出已经上传成功的图片地址
		$imgList = $this->getImageBySku($sku, $accountID, Platform::CODE_EBAY, $siteID);
		$newImgList = array();
		if($imgList){
			foreach ($imgList as $type=>$imgs){
				foreach ($imgs as $img){
					if($img['remote_path']){
						$newImgList[$img['image_name']] = $img['remote_path'];
					}
				}
			}
		}
		//涉及到排序问题，所以决定插入前全部删除
		$this->deleteSkuImages($sku, $accountID, Platform::CODE_EBAY, $siteID);
		if($imgPostData['zt']){
			foreach($imgPostData['zt'] as $k=>$image){
				$imagepath = "/" . ltrim(parse_url($image, PHP_URL_PATH), "/");
				$imgname = end(explode('/', $imagepath));
				$imageSKU = substr($imgname, 0, strrpos($imgname, "."));//过滤小图
				if($imageSKU == $sku) continue;
                $localPath = ProductImageAdd::getImageLocalPathBySkuAndName($sku, self::IMAGE_ZT, basename($image, '.jpg'));

                $remotePath = isset($newImgList[$image]) ? $newImgList[$image] : '';
				$uploadStatus = isset($newImgList[$image]) ? ProductImageAdd::UPLOAD_STATUS_SUCCESS : ProductImageAdd::UPLOAD_STATUS_DEFAULT;
				$imageData[] = array(
						'image_name'    => $image,
						'sku'           => $sku,
						'type'          => self::IMAGE_ZT,
						'local_path'    => $localPath,
						'platform_code' => Platform::CODE_EBAY,
						'account_id'    => $accountID,
						'remote_path'	=> $remotePath,
						'upload_status' => $uploadStatus,
						'create_user_id'=> Yii::app()->user->id,
						'create_time'   => date('Y-m-d H:i:s'),
						'site_id'		=>	$siteID
				);
				$count++;
				if($count>=$maxCount){
					break;
				}
			}
		}
		
		if($imgPostData['ft']){
			foreach($imgPostData['ft'] as $k=>$image){
				$imagepath = "/" . ltrim(parse_url($image, PHP_URL_PATH), "/");
				$imgname = end(explode('/', $imagepath));
				$imageSKU = substr($imgname, 0, strrpos($imgname, "."));//过滤小图
				if($imageSKU == $sku) continue;
                $localPath = ProductImageAdd::getImageLocalPathBySkuAndName($sku, self::IMAGE_FT, basename($image, '.jpg'));


                $remotePath = isset($newImgList[$image]) ? $newImgList[$image] : '';
				$uploadStatus = isset($newImgList[$image]) ? ProductImageAdd::UPLOAD_STATUS_SUCCESS : ProductImageAdd::UPLOAD_STATUS_DEFAULT;
				$imageData[] = array(
						'image_name'    => $image,
						'sku'           => $sku,
						'type'          => self::IMAGE_FT,
						'local_path'    => $localPath,
						'platform_code' => Platform::CODE_EBAY,
						'account_id'    => $accountID,
						'remote_path'	=> $remotePath,
						'upload_status' => $uploadStatus,
						'create_user_id'=> Yii::app()->user->id,
						'create_time'   => date('Y-m-d H:i:s'),
						'site_id'		=>	$siteID
				);
			}
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
	 * @DESC 更新主图
	 * @param unknown $accountID
	 * @param unknown $siteID
	 * @param unknown $sku
	 * @return boolean
	 */
	public function updateProductZtImgBySku($accountID, $siteID, $sku){
		$maxCount = 12;
		$imageData = array();
		$count = 0;
		//先获取全部的图片，取出已经上传成功的图片地址
		$newImgList = array();
		$this->deleteSkuImages($sku, $accountID, Platform::CODE_EBAY, $siteID, self::IMAGE_ZT);
		
		$images = Product::model()->getImgList($sku, 'zt');
		$imagesFt = Product::model()->getImgList($sku, 'ft');
		if(empty($images)){
			$images = $imagesFt;
		}
		if($images){
			foreach($images as $k=>$image){
				$image = "/" . ltrim(parse_url($image, PHP_URL_PATH), "/");
				$imgname = end(explode('/', $image));
				$remotePath = isset($newImgList[$imgname]) ? $newImgList[$imgname] : '';
				$uploadStatus = isset($newImgList[$imgname]) ? ProductImageAdd::UPLOAD_STATUS_SUCCESS : ProductImageAdd::UPLOAD_STATUS_DEFAULT;
				$imageData[] = array(
						'image_name'    => $imgname,
						'sku'           => $sku,
						'type'          => self::IMAGE_ZT,
						'local_path'    => $image,
						'platform_code' => Platform::CODE_EBAY,
						'account_id'    => $accountID,
						'remote_path'	=> $remotePath,
						'upload_status' => $uploadStatus,
						'create_user_id'=> intval(Yii::app()->user->id),
						'create_time'   => date('Y-m-d H:i:s'),
						'site_id'		=>	$siteID
				);
				$count++;
				if($count>=$maxCount){
					break;
				}
			}
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
	 * @desc 更新附图
	 * @param unknown $accountID
	 * @param unknown $siteID
	 * @param unknown $sku
	 * @return boolean
	 */
	public function updateProductFtImgBySku($accountID, $siteID, $sku){
		$maxCount = 12;
		$imageData = array();
		$count = 0;
		//先获取全部的图片，取出已经上传成功的图片地址
		$newImgList = array();
		$this->deleteSkuImages($sku, $accountID, Platform::CODE_EBAY, $siteID, self::IMAGE_FT);
	
		$imagesFt = Product::model()->getImgList($sku, 'ft');
	
		if($imagesFt){
			foreach($imagesFt as $k=>$image){
				$image = "/" . ltrim(parse_url($image, PHP_URL_PATH), "/");
				$imgname = end(explode('/', $image));
				$remotePath = isset($newImgList[$imgname]) ? $newImgList[$imgname] : '';
				$uploadStatus = isset($newImgList[$imgname]) ? ProductImageAdd::UPLOAD_STATUS_SUCCESS : ProductImageAdd::UPLOAD_STATUS_DEFAULT;
				$imageData[] = array(
						'image_name'    => $imgname,
						'sku'           => $sku,
						'type'          => self::IMAGE_FT,
						'local_path'    => $image,
						'platform_code' => Platform::CODE_EBAY,
						'account_id'    => $accountID,
						'remote_path'	=> $remotePath,
						'upload_status' => $uploadStatus,
						'create_user_id'=> intval(Yii::app()->user->id),
						'create_time'   => date('Y-m-d H:i:s'),
						'site_id'		=>	$siteID
				);
			}
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
	 * @DESC 获取附图本地路径地址
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @param number $siteID
	 * @return mixed
	 */
	public function getFtList($sku, $accountID, $siteID = 0){
		$serverConfig = ConfigFactory::getConfig('serverKeys');
		$command = $this->dbConnection->createCommand()
						->select('*')
						->from($this->tableName())
						->where('sku = "'.$sku.'"');
		if (!empty($accountID))
			$command->andWhere('account_id = '.$accountID);
		$command->andWhere('type='.self::IMAGE_FT);
		$command->andWhere('platform_code = "'.Platform::CODE_EBAY.'"');
		$list = $command->queryAll();
		$newImgList = array();
		if($list){
			foreach ($list as $val){
				$newImgList[] = $serverConfig['oms']['host'].$val['local_path'];
			}
		}
		return $newImgList;
	}
	
	/**
	 * @desc 获取主图本地路径地址
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @return mixed
	 */
	public function getZtList($sku, $accountID){
		$serverConfig = ConfigFactory::getConfig('serverKeys');
		$command = $this->dbConnection->createCommand()
		->select('*')
		->from($this->tableName())
		->where('sku = "'.$sku.'"');
		if (!empty($accountID))
			$command->andWhere('account_id = '.$accountID);
		$command->andWhere('type='.self::IMAGE_ZT);
		$command->andWhere('platform_code = "'.Platform::CODE_EBAY.'"');
		$list = $command->queryAll();
		$newImgList = array();
		if($list){
			foreach ($list as $val){
				$newImgList[] = $serverConfig['oms']['host'].$val['local_path'];
			}
		}
		return $newImgList;
	}
	
	/**
	 * @desc 获取远程图片列表
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @param string $type
	 * @return Ambigous <multitype:Ambigous, multitype:unknown >
	 */
    public function getRemoteImageList($sku, $accountID, $type = '', $siteId = ''){
        if($type == '') $type = self::IMAGE_FT;
        $platformCode = Platform::CODE_EBAY;
        $images = array();
        $command = $this->getDbConnection()->createCommand()
            ->select("*")
            ->from($this->tableName())
            ->where("sku = :sku and account_id = :account_id and platform_code = :platform_code and type = :type", array(':sku' => $sku, ':account_id' => $accountID, ':platform_code' => $platformCode, ':type' => $type))
            ->order('id asc');
        if ($siteId !== '') {
            $command->andWhere("site_id=" . $siteId);
        }

        $res = $command->queryAll();
        if (!empty($res)) {
            foreach ($res as $row) {
                if ($row['remote_path'] != '')
                    $images[] = $row['remote_path'];
            }
        }
        return $images;
    }
	
	/**
	 * @desc 通过sku检测
	 * @param unknown $sku
	 * @param unknown $type
	 * @param unknown $platformCode
	 * @param unknown $accountID
	 * @param string $siteId
	 */
	public function checkImageExistsBySKU($sku, $accountID, $siteId = '0', $imagename = "", $imageType = null){
		$platformCode = Platform::CODE_EBAY;
		$condition = "sku='{$sku}' AND platform_code='{$platformCode}' AND account_id='{$accountID}' AND site_id={$siteId}";
		if(!empty($imagename)){
			$condition .= " AND image_name='{$imagename}' ";
		}
		if(!empty($imageType)){
			$condition .= " AND type='{$imageType}' ";
		}
		$res = $this->getDbConnection()->createCommand()->select('count(1) as total')->from($this->tableName())->where($condition)->queryRow();
		if(!empty($res['total'])) return true;
		return false;
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


	// =========== start: search ==================

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array();
	}

	/**
	 * Declares attribute labels.
	 * @return array
	 */
	public function attributeLabels()
	{
		return array(
			'id' => Yii::t('system', 'No.'),
			'image_name' => '图片名',
			'sku' => 'sku',
			'type' => '类型',
			'local_path' => '本地图片路径',
			'account_id' => '帐号',
			'upload_status' => '状态',
			'remote_path' => '远程路径',
			'create_time' => '创建时间',
			'site_id' => '站点',
		);
	}

	public function getStatusOptions($status = null)
	{
		$statusOptions = array(
			0 => '待上传',
			1 => '成功',
			2 => '失败',
		);
		if ($status !== null)
			return isset($statusOptions[$status]) ? $statusOptions[$status] : '';
		return $statusOptions;
	}

	public function getTypeOptions($type = null)
	{
		$typeOptions = array(
			1 => '主图',
			2 => '副图',
		);
		if ($type !== null)
			return isset($typeOptions[$type]) ? $typeOptions[$type] : '';
		return $typeOptions;
	}


	public function addtions($datas)
	{
		if (empty($datas)) return $datas;
		$accountLists = EbayAccount::model()->getIdNamePairs();
		$siteLists = EbaySite::getSiteList();
		foreach ($datas as &$data) {
			//状态
			$data['upload_status'] = $this->getStatusOptions($data['upload_status']);
			//类型
			$data['type'] = $this->getTypeOptions($data['type']);

			$data['account_id'] = isset($accountLists[$data['account_id']])?$accountLists[$data['account_id']]:'-';
			$data['site_id'] = isset($siteLists[$data['site_id']])?$siteLists[$data['site_id']]:'-';
			if(!empty($data['local_path'])){
				$data['local_path'] = '<img src="http://'.$_SERVER['SERVER_NAME'].$data['local_path'].'" width="100" height="100" />';
			}
		}
		return $datas;
	}


	/**
	 * get search info
	 */
	public function search()
	{
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' => 'id',
		);
		$dataProvider = parent::search(get_class($this), $sort);
		$data = $this->addtions($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}

	/**
	 * filter search options
	 * @return type
	 */
	public function filterOptions()
	{

		$result = array(
			array(
				'name' => 'sku',
				'type' => 'text',
				'search' => 'LIKE',
				'htmlOption' => array(
					'size' => '22',
				),
			),
			array(
				'name' => 'image_name',
				'type' => 'text',
				'search' => '=',
				'htmlOption' => array(
					'size' => '22',
				),
			),
			array(
				'name' => 'account_id',
				'type' => 'dropDownList',
				'value'=> Yii::app()->request->getParam('account_id'),
				'data' => EbayAccount::model()->getIdNamePairs(),
				'search' => '=',
			),
			array(
				'name' => 'site_id',
				'type' => 'dropDownList',
				'value'=> Yii::app()->request->getParam('site_id'),
				'data' => EbaySite::getSiteList(),
				'search' => '=',
			),
			array(
				'name' => 'upload_status',
				'type' => 'dropDownList',
				'search' => '=',
				'data' => $this->getStatusOptions(),
				'value' => Yii::app()->request->getParam('upload_status'),
			),
			array(
				'name' => 'type',
				'type' => 'dropDownList',
				'search' => '=',
				'data' => $this->getTypeOptions(),
				'value' => Yii::app()->request->getParam('type'),
			),
		);
		return $result;
	}

	// =========== end: search ==================

	/**
	 * @desc 批量删除
	 * @param unknown $addIDs
	 * @return boolean
	 */
	public function batchDel($ids){
		if(empty($ids)) return false;

		$idList = $this->getDbConnection()->createCommand()->from($this->tableName())->select("id")
			->where(array('in', 'id', $ids))
			->queryAll();
		$newAddIDs = array();
		if($idList){
			foreach ($idList as $ids){
				$newAddIDs[] = $ids['id'];
			}
		}
		if(empty($newAddIDs)) return false;

		$this->getDbConnection()->createCommand()->delete($this->tableName(), "id in(".MHelper::simplode($newAddIDs).")");
		return true;
	}
}