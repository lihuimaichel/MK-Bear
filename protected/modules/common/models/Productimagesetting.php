<?php
/**
 * @desc 产品图片设置模型
 * @author zhangF
 *
 */
class Productimagesetting extends CommonModel {
	
	const STATUS_OPEN = 1;			//状态-开启
	const STATUS_CLOSED = 0;		//状态-关闭
	
	/* @var string 账号名  */
	public $account_name = null;
	public $image_path;
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
		return 'ueb_product_image_setting';
	}
	
	/**
	 * @desc 设置列的规则
	 * @see CModel::rules()
	 */
	public function rules() {
		return array(
			array('platform_code, account_id, zt_watermark, ft_watermark', 'required'),
			array('zt_watermark, ft_watermark, watermark_position_x, watermark_position_y, watermark_alpha', 'numerical'),
			array('filename_prefix, filename_suffix, saved_directory', 'length'),
			array('watermark_path', 'file', 'allowEmpty'=>true, 'types'=>'jpg,gif,png', 
					'maxSize'=>1024 * 1024 * 1, 
					'tooLarge'=> Yii::t('product_image_setting', 'Upload File Too Large'), 
					'wrongType' => Yii::t('product_image_setting', 'Upload File Type Error')),
		);
	}
	
	/**
	 * @desc 设置属性标签
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels() {
		return array(
			'id' => Yii::t('system', 'No.'),
			'platform_code' => Yii::t('system', 'Platform'),
			'account_id' => Yii::t('product_image_setting', 'Account'),
			'zt_watermark' => Yii::t('product_image_setting', 'Main Image Watermark'),
			'ft_watermark' => Yii::t('product_image_setting', 'Additional Image Watermark'),
			'watermark_position' => Yii::t('product_image_setting', 'Watermark Postion'),
			'watermark_position_x' => Yii::t('product_image_setting', 'Watermark Postion X'),
			'watermark_position_y' => Yii::t('product_image_setting', 'Watermark Postion Y'),
			'watermark_alpha' => Yii::t('product_image_setting', 'Watermark Alpha'),
			'filename_prefix' => Yii::t('product_image_setting', 'Filename Prefix'),
			'filename_suffix' => Yii::t('product_image_setting', 'Filename Suffix'),
			'watermark_path' => Yii::t('product_image_setting', 'Watermark Picture'),
			'create_user_id' 		=> Yii::t('system', 'Create User'),
			'create_time' 			=> Yii::t('system', 'Create Time'),
			'modify_user_id' 		=> Yii::t('system', 'Modify User'),
			'modify_time' 			=> Yii::t('system', 'Modify Time'),
			'watermark_view' => Yii::t('product_image_setting', 'Watermark View'),	
			'image_path' => Yii::t('product_image_setting', '测试sku图片'),	
		);
	}
	
	/**
	 * @desc 过滤选项
	 * @return array
	 */
	public function filterOptions() {
		$filterOptions = array();
		return $filterOptions;
	}
	
	/**
	 * @desc 设置查询条件
	 * @return CDbCriteria
	 */
	protected function _setCDbCriteria() {
		$CDbCriteria = new CDbCriteria();
		return $CDbCriteria;
	}
	
	/**
	 * @desc 列表数据
	 * @see UebModel::search()
	 */
	public function search() {
		$sort = new CSort('Productimagesetting');
		$sort->attributes = array(
			'defaultOrder' => 'create_time',
		);
		$dataProvider = parent::search(get_class($this), $sort, null, $this->_setCDbCriteria());
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	/**
	 * @desc 处理列表数据
	 * @param unknown $data
	 * @return unknown
	 */
/* 	public function addtion($data) {
		return $data;
	} */
	
	public function getOpenStateList($key = null) {
		$list = array(
			self::STATUS_OPEN => Yii::t('product_image_setting', 'Open'),
			self::STATUS_CLOSED => Yii::t('product_image_setting', 'Close'),
		);
		if (!is_null($key) && array_key_exists($key, $list))
			return $list[$key];
		return $list;
	}
	
	/**
	 * @desc 处理列表数据
	 * @param unknown $datas
	 * @return string
	 */
	public function addition($datas) {
		$accountLists = array();
		foreach ($datas as $key => $data) {
			$platformCode = $data['platform_code'];
			$accountId = $data['account_id'];
			//如果没有提取过该平台的账号列表就去取一次
			if (!array_key_exists($platformCode, $accountLists)) {
				$accountList = array();
				switch ($platformCode) {
					case Platform::CODE_ALIEXPRESS:
						$accountList = AliexpressAccount::getIdNamePairs();
						break;
					case Platform::CODE_AMAZON:
						$accountList = AmazonAccount::getIdNamePairs();
						break;
					case Platform::CODE_EBAY:
						$accountList = EbayAccount::getIdNamePairs();
						break;
					case Platform::CODE_WISH:
						$accountList = WishAccount::getIdNamePairs();
						break;
					case Platform::CODE_NEWFROG:
						$accountList = WebsiteAccount::getIdNamePairs();
						break;
					default:
						$accountList = array();
				}
				if (empty($accountList))
					$accountList = array();
				$accountLists[$platformCode] = $accountList;
			}
			$datas[$key]['account_name'] = array_key_exists($accountId, $accountLists[$platformCode]) ? $accountLists[$platformCode][$accountId] : '';
			$datas[$key]['zt_watermark'] = $data['zt_watermark'] == 1 ? ('<font color="green">' . Yii::t('product_image_setting', 'Open') . '</span>') : ('<font color="red">' . Yii::t('product_image_setting', 'Close') . '</font>');
			$datas[$key]['ft_watermark'] = $data['ft_watermark'] == 1 ? ('<font color="green">' . Yii::t('product_image_setting', 'Open') . '</span>') : ('<font color="red">' . Yii::t('product_image_setting', 'Close') . '</font>');
		}
		return $datas;
	}
	
	/**
	 * @desc 获取图片配置
	 */
	public function getImageConfigByAccountID($accountID,$platformCode){
	    return $this->dbConnection->createCommand()
	           ->select('*')->from(self::tableName())->where('platform_code = "'.$platformCode.'"')->andWhere('account_id = '.$accountID)
	           ->queryRow();
	} 
	
	/**
	 * @desc 获取菜单对应ID
	 * @return integer
	 */
	public static function getIndexNavTabId() {
		return Menu::getIdByUrl('/common/productimagesetting/list');
	}
        
        /**
	 * @desc 加水印
	 * @return integer
	 */
	public static function waterImage($sku_file, $water_file, $position_x, $position_y, $alpha, $save_file = true) {
		
            if(empty($water_file) || empty($sku_file)){
                return false;
            }
            $position_x ? $position_x:0;
            $position_y ? $position_y:0;
            $alpha ? $alpha:0;
            
            defined('UPLOAD_DIR') || define('UPLOAD_DIR', 'C:\xampp\htdocs\vakind\uploads\\');
            //创建预览图片目录
            $preview_img_dir = 'preview_img';
            is_dir(UPLOAD_DIR . $preview_img_dir) or mkdir(UPLOAD_DIR . $preview_img_dir, 0700);
            $current_month = date('Y-m', time());
            is_dir(UPLOAD_DIR . $preview_img_dir . '/' . $current_month) or mkdir(UPLOAD_DIR . $preview_img_dir . '/' . $current_month, 0700);
            
            //删除上月数据
            $last_month = date('Y-m', time() - 3600 * 24 * 31);
            is_dir(UPLOAD_DIR . $preview_img_dir . '/' . $last_month) && Productimage::model()->rmdirs(UPLOAD_DIR . $preview_img_dir . '/' . $last_month);
            
            
            //加水印预览
            $dst_path = $sku_file;
            $src_path = $water_file;

            //创建图片的实例
            $dst = imagecreatefromstring(file_get_contents($dst_path));
            $src = imagecreatefromstring(file_get_contents($src_path));

            //获取水印图片的宽高
            list($src_w, $src_h) = getimagesize($src_path);

            //将水印图片复制到目标图片上，最后1个参数是设置透明度
            imagecopymerge($dst, $src, $position_x, $position_y, 0, 0, $src_w, $src_h, $alpha);
                            
            //输出图片
            list($dst_w, $dst_h, $dst_type) = getimagesize($dst_path);
            switch ($dst_type) {
                case 1://GIF
                    $newFileName = $preview_img_dir. '/' . $current_month . '/' . time().'_'. rand(100000,999999)  .'.gif';
                    $newFilePath = UPLOAD_DIR . $newFileName;
                    header('Content-Type: image/gif');
                    if($save_file){ imagegif($dst, $newFilePath); } else { imagegif($dst);  }
                    break;
                case 2://JPG
                    $newFileName = $preview_img_dir. '/' . $current_month . '/' . time().'_'. rand(100000,999999)  .'.jpg';
                    $newFilePath = UPLOAD_DIR . $newFileName;
                    header('Content-Type: image/jpeg');
                    if($save_file){ imagejpeg($dst, $newFilePath); } else { imagejpeg($dst);  }
                    break;
                case 3://PNG
                    $newFileName = $preview_img_dir. '/' . $current_month . '/' . time().'_'. rand(100000,999999)  .'.png';
                    $newFilePath = UPLOAD_DIR . $newFileName;
                    header('Content-Type: image/png');
                    if($save_file){ imagepng($dst, $newFilePath); } else { imagepng($dst);  }
                    break;
                default:
                    break;
            }
            
            
            if($save_file){
                return array(
                    'name' => $newFileName,
                    'path' => $newFilePath
                );
            }
            
	}


	/**
	 * 判断账号是否可以添加水印，以及加水印的zt和ft
	 * @param int     $accountID     账号ID
	 * @param string  $platformCode  平台
	 * @return array
	 */
	public function watermarkImages($accountID, $platformCode){
		$watermarkArr = array();
		$watermarkInfo = $this->getImageConfigByAccountID($accountID, $platformCode);
		if($watermarkInfo){
			//判断主图是否加水印
			if($watermarkInfo['zt_watermark'] == 1){
				$watermarkArr[] = ProductImageAdd::IMAGE_ZT;
			}
			//判断副图是否加水印
			if($watermarkInfo['ft_watermark'] == 1){
				$watermarkArr[] = ProductImageAdd::IMAGE_FT;
			}
		}

		return $watermarkArr;
	}
}