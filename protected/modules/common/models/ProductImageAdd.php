<?php

/**
 * @desc 刊登图片记录
 * @author Gordon
 */
class ProductImageAdd extends CommonModel
{

    /**@var 上传状态 */
    const UPLOAD_STATUS_DEFAULT = 0;//未上传
    const UPLOAD_STATUS_SUCCESS = 1;//上传成功
    const UPLOAD_STATUS_FAILURE = 2;//上传失败

    const IMAGE_ZT = 1;//主图
    const IMAGE_FT = 2;//附图
    const IMAGE_ZT_ALIAS = 'zt';
    const IMAGE_FT_ALIAS = 'ft';

    const REMOTE_TYPE_IMAGESERVER = 1;//远程图片服务器
    const REMOTE_TYPE_IMAGEBANK = 2;    //平台图片银行

    public $_maxImage = null;//主图最大上传张数

    /**@var 消息提示 */
    private $_errorMessageImg = null;

    /**
     * @desc 获取模型
     * @param system $className
     * @return Ambigous <CActiveRecord, unknown, multitype:>
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @desc 设置表名
     * @see CActiveRecord::tableName()
     */
    public function tableName()
    {
        return 'ueb_product_image_add';
    }

    /**
     * @desc 获取指定图片的本地存储路径(相对)
     * @param string $sku
     * @param string $type
     * @param string $imgName
     */
    public static function getImageLocalPathBySkuAndName($sku, $type, $imgName)
    {
        $imageConfig = SysConfig::getPairByType('image');
        $typePath = $type == self::IMAGE_ZT ? $imageConfig['img_local_main_path'] : $imageConfig['img_local_assistant_path'];
        $path = $imageConfig['img_local_path'] . $typePath;
        $first = substr($sku, 0, 1);
        $second = substr($sku, 1, 1);
        $third = substr($sku, 2, 1);
        $fourth = substr($sku, 3, 1);
        $filePath = $path . '/' . $first . '/' . $second . '/' . $third . '/' . $fourth;
        return $filePath . '/' . $imgName . '.jpg';
    }

    /**
     * @desc 查找要上传的图片
     * @param string $sku
     */
    public function getImageBySku($sku, $accountID = null, $platformCode = null, $siteId = null)
    {
        $command = $this->dbConnection->createCommand()
            ->select('*')
            ->from($this->tableName())
            ->where('sku = "' . $sku . '"')
            ->order("id ASC");
        if (!empty($accountID))
            $command->andWhere('account_id = ' . $accountID);
        if (!empty($platformCode))
            $command->andWhere('platform_code = "' . $platformCode . '"');
        if ($siteId !== null && intval($siteId) >= 0)
            $command->andWhere('site_id=' . intval($siteId));
        $list = $command->queryAll();
        $imageList = array();
        foreach ($list as $item) {
            $imageList[$item['type']][] = $item;
        }
        return $imageList;
    }


    /**
     * @desc 上传本地图片到图片服务器
     * @param string $absolutePath
     * @param string $remoteName
     * @param string $remotePath
     * @return string
     */
    public function uploadImageServer($absolutePath, $remoteName, $remotePath)
    {
        $configs = ConfigFactory::getConfig('serverKeys');
        $config = $configs['image'];
        $url = $config['url'];
        $key = $config['key'];
        $fields['f'] = '@' . $absolutePath;
        $fields['key'] = $key;
        $fields['image_name'] = $remoteName;
        $fields['image_url'] = $remotePath;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        ob_start();
        curl_exec($ch);
        $result = ob_get_contents();
        ob_end_clean();
        curl_close($ch);
        return $result;
    }

    /**
     * @desc 保存远程图片到临时路径
     * @param string $path
     */
    public function saveTempImage($path)
    {
        $config = ConfigFactory::getConfig('serverKeys');
        $savePath = Yii::getPathOfAlias('webroot') . '/tmp/';
        if (!file_exists($savePath)) {
            mkdir($savePath, 0777, true);
        }
        $savePath .= rand(0, 1000) . '-' . end(explode('/', $path));//加上随机数，避免同时生成同一图片
        ob_start();
        readfile($config['oms']['host'] . $path);
        $image = ob_get_contents();
        ob_end_clean();
        $fp = @fopen($savePath, "w");
        fwrite($fp, $image);
        fclose($fp);
        return $savePath;
    }

    /**
     * @desc 获取上传图片远程信息
     */
    public function getImageRemoteInfo($image, $accountID, $platformCode)
    {
        $ps = explode('/', $image);
        $imageName = end($ps);
        $index = strrpos($imageName, '.');
        $name = substr($imageName, 0, $index);
        $fileType = substr($imageName, $index);//文件类型
        $config = Productimagesetting::model()->getImageConfigByAccountID($accountID, $platformCode);
        /*         if( empty($config) ){
                    throw new CException(Yii::t('common', 'Can Not Find Config Data'));
                } */
        if (empty($config))
            $remoteName = $config['filename_prefix'] . $name . $config['filename_suffix'] . $fileType;//文件名
        else
            $remoteName = $name . $fileType;//文件名
        //TODO 做成配置
        $remotePath = '/img/' . $config['saved_directory'] . '/' . $ps[3] . '/';
        return array($remoteName, $remotePath);
    }

    /**
     * @desc 检查图片是否存在
     * @param unknown $image
     * @param unknown $type
     * @param unknown $platformCode
     * @param unknown $accountID
     */
    public function checkImageExists($image, $type, $platformCode, $accountID)
    {
        return $this->getDbConnection()->createCommand()
            ->from($this->tableName())
            ->where("image_name = :image_name", array(':image_name' => $image))
            ->andWhere("type = :type", array(':type' => $type))
            ->andWhere("platform_code = :platform_code", array(':platform_code' => $platformCode))
            ->andWhere("account_id = :account_id", array(':account_id' => $accountID))
            ->queryRow();
    }

    /**
     * 检查产品图片是否存在
     * @param unknown $sku
     * @param unknown $image
     * @param unknown $type
     * @param unknown $platformCode
     * @param unknown $accountID
     * @return mixed
     */
    public function checkSkuImageExists($sku, $image, $type, $platformCode, $accountID)
    {
        return $this->getDbConnection()->createCommand()
            ->from($this->tableName())
            ->where("image_name = :image_name", array(':image_name' => $image))
            ->andWhere("sku = :sku", array(':sku' => $sku))
            ->andWhere("type = :type", array(':type' => $type))
            ->andWhere("platform_code = :platform_code", array(':platform_code' => $platformCode))
            ->andWhere("account_id = :account_id", array(':account_id' => $accountID))
            ->queryRow();
    }

    /**
     * @desc 获取产品远程图片地址
     * @param unknown $sku
     * @param unknown $accountID
     * @param unknown $platformCode
     * @param unknown $type
     * @return multitype:Ambigous <>
     */
    public function getRemoteImages($sku, $accountID, $platformCode, $type, $siteId = '')
    {
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
     * @desc 删除对应平台账号sku已经添加的图片
     * @param unknown $sku
     * @param unknown $accountID
     * @param unknown $platformCode
     */
    public function deleteSkuImages($sku, $accountID, $platformCode, $siteID = null, $type = null)
    {
        $conditions = "sku = '" . addslashes($sku) . "' and account_id = " . (int)$accountID . " and platform_code = '" . addslashes($platformCode) . "'";
        if ($siteID !== null) {
            $siteID = (int)$siteID;
            $conditions .= " and site_id={$siteID} ";
        }
        if ($type !== null) {
            $conditions .= " and type='{$type}'";
        }
        return $this->dbConnection->createCommand()->delete($this->tableName(), $conditions);
    }

    /**
     * @desc 打水印
     * @param unknown $source 原图地址
     * @param unknown $water 水印图
     * @param string $savename 保存名称
     * @param number $posX 水印x位置
     * @param number $posY 水印Y位置
     * @param number $alpha 透明度
     * @param string $newWidth 新宽度
     * @param string $newHeight 新高度
     * @return boolean
     */
    public function watermark($source, $water, $savename = null, $posX = 0, $posY = 0, $alpha = 30, $newWidth = null, $newHeight = null)
    {
        //检查文件是否存在
        if (!file_exists($source) || !file_exists($water))
            return false;

        //图片信息
        $sInfo = getImageInfo($source);
        $wInfo = getImageInfo($water);

        //如果图片小于水印图片，不生成图片
        if ($sInfo["width"] < $wInfo["width"] || $sInfo['height'] < $wInfo['height'])
            return false;

        //建立图像
        $sCreateFun = "imagecreatefrom" . $sInfo['type'];
        $sImage = $sCreateFun($source);
        $wCreateFun = "imagecreatefrom" . $wInfo['type'];
        $wImage = $wCreateFun($water);

        //设定图像的混色模式
        imagealphablending($wImage, true);

        //图像位置,默认为右下角右对齐
        !$posY && $posY = $sInfo["height"] - $wInfo["height"];
        !$posX && $posX = $sInfo["width"] - $wInfo["width"];

        //生成混合图像
        imagecopymerge($sImage, $wImage, $posX, $posY, 0, 0, $wInfo['width'], $wInfo['height'], $alpha);

        //输出图像
        $ImageFun = 'Image' . $sInfo['type'];
        //如果没有给出保存文件名，默认为原图像名
        if (!$savename) {
            $savename = $source;
            @unlink($source);
        }
        //保存图像
        $ImageFun($sImage, $savename);
        imagedestroy($sImage);

        if (!empty($newWidth) && !empty($newHeight) && $newWidth != $sInfo["width"] && $newHeight != $sInfo["height"]) {
            $this->changeImageSize($savename, $sInfo['type'], $sInfo['width'], $sInfo['height']);
        }
        return true;
    }

    /**
     * @desc 改变图片尺寸大小
     * @param unknown $sourcePath
     * @param unknown $type
     * @param unknown $width
     * @param unknown $height
     * @param string $targetPath
     */
    public function changeImageSize($sourcePath, $type, $width, $height, $targetPath = null)
    {
        switch ($type) {
            case 'jpeg':
            case 'jpg':
                $temp_img = imagecreatefromjpeg($sourcePath);
                $o_width = imagesx($temp_img);
                $o_height = imagesy($temp_img);
                $new_img = imagecreatetruecolor($width, $height);
                imagecopyresampled($new_img, $temp_img, 0, 0, 0, 0, $width, $height, $o_width, $o_height);
                $filePath = empty($targetPath) ? $sourcePath : $targetPath;
                imagejpeg($new_img, $filePath);
                imagedestroy($new_img);
                break;
            case 'gif':
                $temp_img = imagecreatefromgif($sourcePath);
                $o_width = imagesx($temp_img);
                $o_height = imagesy($temp_img);
                $new_img = imagecreatetruecolor($width, $height);
                imagecopyresampled($new_img, $temp_img, 0, 0, 0, 0, $width, $height, $o_width, $o_height);
                $filePath = empty($targetPath) ? $sourcePath : $targetPath;
                imagegif($new_img, $filePath);
                imagedestroy($new_img);
                break;
            case 'png':
                $temp_img = imagecreatefrompng($sourcePath);
                $o_width = imagesx($temp_img);
                $o_height = imagesy($temp_img);
                $new_img = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($new_img, 255, 255, 255);
                imagefill($new_img, 0, 0, $white);
                imagecolortransparent($new_img, $white);
                imagecopyresampled($new_img, $temp_img, 0, 0, 0, 0, $width, $height, $o_width, $o_height);
                $filePath = empty($targetPath) ? $sourcePath : $targetPath;
                imagepng($new_img, $filePath);
                imagedestroy($new_img);
                break;
        }

    }


    /**
     * @desc  获取sku的图片远程地址
     * @param  integer $accountID 销售账号ID
     * @param  string $sku sku
     * @param  array $imageNameList sku名称列表
     * @param  string $platformCode 平台
     * @param  int $site 站点
     * @return array
     */
    public static function getSkuImageUpload($accountID = null, $sku = null, $imageNameList = array(), $platformCode = null, $site = null, $assistantImage = false, $moreParams = array())
    {
        $config = ConfigFactory::getConfig('imageKeys');
        // if( !isset($config[ 'COMMON' ]) ){
        //     throw new CException(Yii::t('system', 'Server Does Not Exists'));
        // }
        $url = $config['COMMON'] ['url'] [__FUNCTION__];
        $data = array(
            'platform' => $platformCode,
            'site' => $site,
            'account' => $accountID,
            'sku' => $sku,
            'assistantImage' => $assistantImage
        );
        if ($moreParams) {
            $data = array_merge($data, $moreParams);
        }
        if (!empty($imageNameList)) {
            $data['imageNameList'] = $imageNameList;
        }
        if (isset($_REQUEST['bug'])) {
            var_dump($data);
        }
        $curl = new Curl();
        $curl->init();
        $response = $curl->setOption(CURLOPT_TIMEOUT, 300)
            ->setOption(CURLOPT_CONNECTTIMEOUT, 300)
            ->postByJson($url, $data);
        return json_decode($response, true);
    }

    /**
     * @desc 上传sku下所有图片
     * @param integer $accountID 销售账号ID
     * @param array $skulist sku列表
     * @param integer $status 1:普通 0:紧急
     * @param string $platformCode 平台
     * @param  int $site 站点
     * @param array $watermarkImgNameList 要加水印的图片名称
     * @return array
     */
    public static function addSkuImageUpload($accountID = null, $skulist = null, $status = 0, $platformCode = null, $site = null, $watermarkImgNameList = null)
    {
        $config = ConfigFactory::getConfig('imageKeys');
        // if( !isset($config[ 'COMMON' ]) ){
        //     throw new CException(Yii::t('system', 'Server Does Not Exists'));
        // }
        if (!is_array($skulist)) {
            $skulist = array($skulist);
        }
        $url = $config['COMMON'] ['url'] [__FUNCTION__];
        $data = array(
            'platform' => $platformCode,
            'site' => $site,
            'account' => $accountID,
            'status' => $status,
            'skulist' => $skulist,
            'creater' => 'erp_market',
            'watermarkImgNameList' => $watermarkImgNameList
        );
        $curl = new Curl();
        $curl->init();
        $response = $curl->setOption(CURLOPT_TIMEOUT, 300)
            ->setOption(CURLOPT_CONNECTTIMEOUT, 300)
            ->postByJson($url, $data);
        return json_decode($response, true);
    }

    /**
     * @desc 发送图片上传请求（最终验证图片库远程地址是否已存入库）
     * @param integer $accountID 销售账号ID
     * @param array $skulist sku列表
     * @param string $platformCode 平台
     * @param int $site 站点
     * @return bool
     */
    public function sendImageUploadRequest($sku = null, $accountID = null, $siteId = null, $platformCode = null, $assistantImage = false)
    {
        //图片名称列表
        $imageNameList = array();
        //获取待上传图片
        $list = $this->getImageBySku($sku, $accountID, $platformCode, $siteId);
        // echo $sku.'+'.$accountID.'+'.$platformCode.'+'.$siteId.'+';
        // MHelper::printvar($list);
        if (empty($list)) {
            //$this->setErrorMessageImg('No Pic Data on Common Pic Server,Params:SKU' . $sku . '+accountID:' . $accountID . '+platformCode' . $platformCode . '+siteID' . $siteId);
            $this->setErrorMessageImg($sku.'本地无数据');
            return false;
        }


        //获取账号图片设置
        //$imageSettings = Productimagesetting::model()->getImageConfigByAccountID($accountID, Platform::CODE_EBAY);
        foreach ($list as $type => $images) {
            foreach ($images as $image) {
                $imageNameList[$image['id']] = $image['image_name'];
                //图片打水印,暂时不用考虑打水印
                // $addWatermark = false;
                // $waterPath = "";
                // if (!empty($imageSettings)) {
                //  //判断是否需要添加水印
                //  if($type == self::IMAGE_ZT && $imageSettings['zt_watermark'] || $type==self::IMAGE_FT && $imageSettings['ft_watermark']){
                //      $addWatermark = true;
                //      //$waterPath = UPLOAD_DIR . $imageSettings['watermark_path'];
                //  }else{
                //      $addWatermark = false;
                //  }
                // }
            }
        }

        //图片路径列表
        $imagePathList = array();

        $response = self::getSkuImageUpload($accountID, $sku, array_values($imageNameList), $platformCode, $siteId, $assistantImage);
        if(empty($response)){
            $this->setErrorMessageImg($sku."Get Sku Images Failure");
            return false;
        }
        if($response['status']=='failure' || empty($response['result']) || empty($response['result']['imageInfoVOs'])){
            $this->setErrorMessageImg($response['errormsg']);
            return false;
        }
        if(count($imageNameList) != count($response['result']['imageInfoVOs'])){
            if(count($imageNameList)>count($response['result']['imageInfoVOs'])){
                $this->setErrorMessageImg($sku."接口数据有缺少");
            }else{
                $this->setErrorMessageImg($sku."接口数据和本地数据不一致");
            }
            return false;
        }

        // MHelper::printvar($response);
        /*if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs']) || count($imageNameList) != count($response['result']['imageInfoVOs'])) {
            $this->setErrorMessageImg('Remote get Sku images failure');
            // self::addSkuImageUpload($accountID,$sku,0,$platformCode,$siteId);//发送图片上传请求
            return false;
        }*/
        //判断图片名称是否存在
        $isAllRight = true;
        $pic = '';
        foreach ($imageNameList as $id => $imageName) {
            $flag = false;
            foreach ($response['result']['imageInfoVOs'] as $v) {
                if ($v['imageName'] == $imageName && $v['remotePath'] != '') {
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
            $this->setErrorMessageImg($pic . 'Remote image name is not exist');
            // self::addSkuImageUpload($accountID, $sku, 0, $platformCode, $siteId);//发送图片上传请求
            return false;
        }
        $isOk = true;
        $remoteType = 0;
        if ($platformCode == Platform::CODE_EBAY) {
            $remoteType = self::REMOTE_TYPE_IMAGEBANK;
        }

        foreach ($imagePathList as $id => $imagePath) {
            $flag = $this->dbConnection->createCommand()->update($this->tableName(), array(
                'upload_status' => self::UPLOAD_STATUS_SUCCESS,
                'remote_path' => $imagePath,//EPS图片链接地址
                'remote_type' => $remoteType,
            ), 'id = ' . $id);
            if (!$flag) {
                $isOk = false;
            }
        }
        return $isOk;
    }


    // ============ S:设置错误消息提示 =================
    public function getErrorMessageImg()
    {
        return $this->_errorMessageImg;
    }

    public function setErrorMessageImg($errorMsg)
    {
        $this->_errorMessageImg = $errorMsg;
    }
    // ============ E:设置错误消息提示 =================    


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


    /**
     * @param $sku
     * @param $accountId
     * @param $platformCode
     * @param bool $assistantImage
     * @param null $siteCode
     * @param array $fileList
     * @param array $options
     * @return array
     * @author ketu.lai
     */
    public static function getImagesFromRemoteAddress($sku, $accountId, $platformCode, $assistantImage = false, $siteCode = null, $fileList = array(), $options = array())
    {
        $response = self::getSkuImageUpload($accountId, $sku, $fileList, $platformCode, $siteCode, $assistantImage, $options);
        $imageInfo = array();
        if (isset($response['result']['imageInfoVOs'])) {
            $imageInfo = $response['result']['imageInfoVOs'];
        }
        return $imageInfo;
    }

    /**
     * @param $fileName
     * @param $sku
     * @param $accountId
     * @param $platform
     * @param bool $assistantImage
     * @param null $site
     * @param array $options
     * @return array
     * @author ketu.lai
     */
    public static function getImagesFromRemoteAddressByFileName($fileName, $sku, $accountId, $platform, $assistantImage = false, $site = null, $options = array())
    {
        $imageList = array();
        $fileName = (array)$fileName;
        $imageInfo = self::getImagesFromRemoteAddress($sku, $accountId, $platform, $assistantImage, $site, $fileName, $options);
        foreach ($imageInfo as $image) {
            if ($image['remotePath']) {
                $imageList[$image['imageName']] = $image['remotePath'];
            }
        }
        return $imageList;
    }


    public static function getImagesPathFromRestfulBySku($sku, $platform = null)
    {
//        $host = 'http://172.16.2.63:8084';
//        $path = 'image';

        $host = self::getRestfulAddress('image', $platform);

        $params = array(
            'pagenum' => 1,
            'pagesize' => 100
        );
        // $sku = '100010';
        $sku = trim($sku);
        $query = http_build_query($params);
        $uri = join("/", array($host, $sku)) . "?" . $query;
        //echo $uri;
        $response = \application\vendors\Restful\Request::get($uri)->send();
        $body = $response->getBody();

        $imageList = array();
        if ($body['status'] == 'succ' && isset($body['result']['list'])) {

            foreach ($body['result']['list'] as $info) {

                $imageList[] = array(
                    'filename' => $info['imgPath'],
                    //'main' => $info['main'],
                    // 接口main字段不准，不再使用这个字段，默认为false
                    'main'=> false,
                    'type' => strtolower($info['imgType']),
                    'realPath' => $info['dfsPath']
                );
            }
        }
        return $imageList;
    }

    public static function getImagesPathFromRestfulBySkuAndType($sku, $typeAlisa = null, $platform= null)
    {
        $imageList = array();
        $imagePathList = self::getImagesPathFromRestfulBySku($sku, $platform);
        if (!$imagePathList) {
            return $imageList;
        }

        foreach ($imagePathList as $info) {
            $imageList[$info['main'] ? self::IMAGE_ZT_ALIAS : self::IMAGE_FT_ALIAS][] = $info['filename'];
        }

        if ($typeAlisa && isset($imageList[$typeAlisa])) {
            return $imageList[$typeAlisa];
        } elseif ($typeAlisa) {
            return array();
        }
        return $imageList;
    }


    public static function getImageUrlFromRestfulByFileName($filename, $sku, $type = 'normal', $width = 100, $height = 100, $platform = null)
    {

        //$host = 'http://172.16.2.63:8084';

        $host = self::getRestfulAddress('image', $platform);

        //$path = 'image';
        $params = array(
            'width' => $width,
            'height' => $height
        );

        //$sku = 100010;
        $query = http_build_query($params);
        $uri = join("/", array($host, $sku, $type, $filename)) . "?" . $query;

        return $uri;

    }

    public static function recheckImageFromRestfulBySku($sku, $imgType = 'NORMAL', $platform=null)
    {
        $sku = (array) $sku;

        $host = self::getRestfulAddress('image', $platform);
        $path ='skuimg';
        $action = 'recheck';
        $uri = join("/", array($host, $path, $action));
        $postData = array();
        foreach($sku as $k) {
            $postData[] = array(
                'sku'=> trim($k),
                'imgType'=> trim($imgType),
            );
        }
        $jsonHeaders = array(
            'Content-Type'=> 'application/json'
        );

        try {
            $response = \application\vendors\Restful\Request::post($uri, $postData)->withHeaders($jsonHeaders)->send();
            return $response->getStatusCode() == 200 ? true: false;
        }catch (\Exception $e){
            //
        }

        return false;

    }

    public static function getOrPushImageUrlFromRestfulBySku(array $skuInfo, $pushWithChild = true, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = null)
    {
        $parentSku = $skuInfo['sku'];
        $imageUrlList = self::getImageUrlFromRestfulBySku($parentSku, $typeAlisa, $type, $width, $height, $platform);
        if ($imageUrlList){
            return $imageUrlList;
        }
        $pushInfo = array(
            $parentSku
        );
        if ($pushWithChild) {
            $children = ProductSelectAttribute::model()->getChildSKUListByProductID($skuInfo['id']);
            foreach($children as $child) {
                // 由于接口不会检测SKU图片是否已经同步，都会进行先删除再推送，会导致已经同步的图片被删除
                // 这里子SKU也需要进行检测是否有图片再推送
                $childImageList = self::getImageUrlFromRestfulBySku($child, $typeAlisa, $type, $width, $height, $platform);
                if (!$childImageList) {
                    $pushInfo[] = $child;
                }
            }
        }

        self::recheckImageFromRestfulBySku($pushInfo);

        return $imageUrlList;
    }


    public static function getImageUrlFromRestfulBySku($sku, $typeAlisa = null, $type = 'normal', $width = 100, $height = 100, $platform = null)
    {

        $imageUrlList = array();
        $imagePathList = self::getImagesPathFromRestfulBySku($sku, $platform);
        if (!$imagePathList) {
            //self::recheckImageFromRestfulBySku($sku);
            return $imageUrlList;
        }

        foreach ($imagePathList as $info) {
            $imageUrlList[$info['main'] ? self::IMAGE_ZT_ALIAS : self::IMAGE_FT_ALIAS][$info['filename']] = self::getImageUrlFromRestfulByFileName($info['filename'], $sku, $type, $width, $height, $platform);
        }
        if ($typeAlisa && isset($imageUrlList[$typeAlisa])) {
            return $imageUrlList[$typeAlisa];
        } elseif ($typeAlisa) {
            return array();
        }

        return $imageUrlList;
    }

    /**
     * @return mixed
     * @desc 返回java restful api图片接口地址
     */
    public static function getRestfulAddress($path = null, $platform = null)
    {
        $config = ConfigFactory::getConfig('imageKeys');
        // if( !isset($config[ 'COMMON' ]) ){
        //     throw new CException(Yii::t('system', 'Server Does Not Exists'));
        // }
/*        if (in_array($platform, array(
            Platform::CODE_JOOM,
            Platform::CODE_WISH
        ))){

            $url = $config['COMMON'] ['url'] ['getPlatformImageDomain'][$platform];
        } else {
            $url = $config['COMMON'] ['url'] ['getSkuImageView'];
        }*/

        $url = $config['COMMON'] ['url'] ['getPlatformImageDomain'][$platform];

        if (!$url) {
            $url = $config['COMMON'] ['url'] ['getSkuImageView'];
        }

        //echo $url;

        if ($path) {
            $url = join('/', array($url, $path));
        }
        return $url;
    }

}