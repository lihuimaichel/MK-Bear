<?php
/**
 * @desc 产品图片设置控制器
 * @author zhangF
 *
 */
class ProductimagesettingController extends UebController {
	
	/**
	 * @desc Productimagesetting 模型
	 * @var Productimagesetting Instance
	 */
	protected $_model = null;

        /**
	 * (non-PHPdoc)
	 * @see CommonModule::init()
	 */
	public function init() {
		$this->_model = new Productimagesetting();
		parent::init();
	}
	
	/**
	 * @desc 产品图片设置列表
	 */
	public function actionList() {
		$this->render('list', array(
			'model' => $this->_model
		));
	}
	
	/**
	 * 创建图片设置
	 */
	public function actionCreate() {
                defined('UPLOAD_DIR') || define('UPLOAD_DIR', 'C:\xampp\htdocs\vakind\uploads\\');
		if (Yii::app()->request->isPostRequest) {
			$this->_model->attributes = $_POST['Productimagesetting'];
			$watermarkPath = $_POST['Productimagesetting']['watermark_path'];
			//检查水印文件是否存在
			if (empty($watermarkPath) || !file_exists(UPLOAD_DIR . $watermarkPath)) {
				echo $this->failureJson(array(
						'message' => Yii::t('product_image_setting', 'Watermark Could Not Empty'),
						'callbackType' => 'navTabAjaxDone',
				));
				Yii::app()->end();
			}
			$this->_model->setAttribute('watermark_path', $watermarkPath);
			$platformCode = $_POST['Productimagesetting']['platform_code'];
			$accountId = $_POST['Productimagesetting']['account_id'];
			//检查当前平台的账号是否已经有记录了
			if ($this->_model->exists("platform_code = :code and account_id = :id", array(':code' => $platformCode, ':id' => $accountId))) {
				echo $this->failureJson(array(
						'message' => Yii::t('product_image_setting', 'The Account OF Platform Has Exists'),
						'callbackType' => 'navTabAjaxDone',
				));
				Yii::app()->end();				
			}
			$this->_model->isNewRecord = true;
			if ($this->_model->save(true)) {
				echo $this->successJson(array(
					'message' => Yii::t('system', 'Add Successful'),
					'callbackType' => 'closeCurrent',
					'navTabId' => 'page' . self::getIndexNavTabId(),
				));
				Yii::app()->end();
			} else {
				echo $this->failureJson(array(
					'message' => Yii::t('system', 'Add failure'),
					'callbackType' => 'navTabAjaxDone',
				));
				Yii::app()->end();
			}
		}
		$this->_model->zt_watermark = 1;
		$this->_model->ft_watermark = 1;
		$this->render('create', array(
			'model' => $this->_model,
		));
	}
	
	/**
	 * @desc 根据平台CODE获取账号listing
	 */
	public function actionGetaccountlist() {
		$platformCode = Yii::app()->request->getParam('platform_code');
		$accountInfos = array();
		switch ($platformCode) {
			case Platform::CODE_ALIEXPRESS:
				$accountInfos = AliexpressAccount::getAbleAccountList();
				if (!empty($accountInfos)) {
					foreach ($accountInfos as $accountInfo){
						echo CHtml::tag('option', array('value' => $accountInfo['id']), CHtml::encode($accountInfo['short_name']));
					}
				}				
				break;
			case Platform::CODE_AMAZON:
				$accountInfos = AmazonAccount::getAbleAccountList();
				if (!empty($accountInfos)) {
					foreach ($accountInfos as $accountInfo){
						echo CHtml::tag('option', array('value' => $accountInfo['id']), CHtml::encode($accountInfo['account_name']));
					}
				}				
				break;
			case Platform::CODE_EBAY:
				$accountInfos = EbayAccount::getAbleAccountList();
				if (!empty($accountInfos)) {
					foreach ($accountInfos as $accountInfo){
						echo CHtml::tag('option', array('value' => $accountInfo['id']), CHtml::encode($accountInfo['short_name']));
					}
				}
				break;
			case Platform::CODE_WISH:
				$accountInfos = WishAccount::getAbleAccountList();
				if (!empty($accountInfos)) {
					foreach ($accountInfos as $accountInfo){
						echo CHtml::tag('option', array('value' => $accountInfo['id']), CHtml::encode($accountInfo['account_name']));
					}
				}				
				break;
			case Platform::CODE_NEWFROG:
				$accountInfos = WebsiteAccount::getAbleAccountList();
				if (!empty($accountInfos)) {
					foreach ($accountInfos as $accountInfo){
						echo CHtml::tag('option', array('value' => $accountInfo['id']), CHtml::encode($accountInfo['website_name']));
					}
				}				
				break;
			default:
				$model = null;	
		}
	}
	
	/**
	 * @desc 获取菜单对应ID
	 * @return integer
	 */
	public static function getIndexNavTabId() {
		return Menu::getIdByUrl('/common/productimagesetting/list');
	}
	
	/**
	 * @desc 处理上传图片
	 */
	public function actionUploadimage() {
		//检查上传文件是否符合规则
		if(!$this->_model->validate(array('watermark_path'))) {
			$errorStr = '';
			foreach ($this->_model->errors['watermark_path'] as $error)
				$errorStr .= $error . ' ';
			echo $this->failureJson(array(
				'message' => $errorStr
			));
			Yii::app()->end();
		}
		$uploadFile = CUploadedFile::getInstance($this->_model, 'watermark_path');
		if (is_object($uploadFile) && get_class($uploadFile) === 'CUploadedFile') {
			$name = $uploadFile->getName();
                        defined('UPLOAD_DIR') || define('UPLOAD_DIR', 'C:\xampp\htdocs\vakind\uploads\\');
                        //创建水印图片目录,按月保存
                        $water_img_dir = 'water_img';
                        $month = date('Y-m', time());
                        is_dir(UPLOAD_DIR . $water_img_dir) or mkdir(UPLOAD_DIR . $water_img_dir,0700);
                        is_dir(UPLOAD_DIR . $water_img_dir . '/' .$month) or mkdir(UPLOAD_DIR . $water_img_dir . '/' .$month,0700);
			$newfilename = $water_img_dir . '/' .$month . '/' .time().'_'. rand(100000,999999) .'_'. $name;
			$newFilePath = UPLOAD_DIR  . $newfilename;
			//保存图片
			if ($uploadFile->saveAs($newFilePath, true)) {
				echo $this->successJson(array(
					'message' => Yii::t('system', 'Upload Files Successful'),
					'file' => $newfilename
				));
				Yii::app()->end();
			}
		}
		echo $this->failureJson(array(
				'message' => Yii::t('system', 'Upload Files Failure')
		));
		Yii::app()->end();
	}
	
	/**
	 * @desc 删除产品图片设置
	 */
	public function actionDelete() {
		$ids = Yii::app()->request->getParam('ids');
		$idsArr = explode(',', $ids);
		$ids = array_filter($idsArr);
		if (!empty($ids) && $this->_model->deleteAll("id in (" . implode(',', $ids) . ")")) {
			echo $this->successJson(array(
				'message' => Yii::t('system', 'Delete successful'),
				'navTabId' => 'page' . Productimagesetting::getIndexNavTabId(),
			));
			Yii::app()->end();
		}
		echo $this->failureJson(array(
			'message' => Yii::t('system', 'Delete failure'),
		));
	}
	
	/**
	 * @desc 更新图片参数设置
	 */
	public function actionUpdate() {
		$id = Yii::app()->request->getParam('id');
		$model = $this->_model->findByPk($id);
		if (empty($model)) {
			echo $this->failureJson(array(
				'message' => Yii::t('system', 'The Record Not Exists'),
				'callbackType' => 'closeCurrent',
			));
			Yii::app()->end();
		}
		//处理POST数据
		if (Yii::app()->request->isPostRequest) {
			$oldWatermarkPath = $model->watermark_path;
			$newWatermarkPath = $_POST['Productimagesetting']['watermark_path'];
			$watermarkPath = $newWatermarkPath == '' ? $oldWatermarkPath : $newWatermarkPath;
			$model->attributes = $_POST['Productimagesetting'];
			$model->setAttribute('watermark_path', $watermarkPath);
			if ($model->save(true)) {
				echo $this->successJson(array(
					'message' => Yii::t('system', 'Update successful'),
					'callbackType' => 'closeCurrent',
					'navTabId' => 'page' . Productimagesetting::getIndexNavTabId(),
				));
				Yii::app()->end();
			} else {
				echo $this->failureJson(array(
					'message' => Yii::t('system', 'Update failure')
				));
				Yii::app()->end();
			}
		}
		
		$accountName = '';
		switch ($model->platform_code) {
			case Platform::CODE_ALIEXPRESS:
				$accountName = AliexpressAccount::getAccountNameById($model->account_id);
				break;
			case Platform::CODE_AMAZON:
				$accountName = AmazonAccount::getAccountNameById($model->account_id);
				break;
			case Platform::CODE_EBAY:
				$accountName = EbayAccount::getAccountNameById($model->account_id);
				break;
			case Platform::CODE_WISH:
				$accountName = WishAccount::getAccountNameById($model->account_id);
				break;
			case Platform::CODE_NEWFROG:
				$accountName = WebsiteAccount::getAccountNameById($model->account_id);
				break;
			default:
				$accountName = '';
		}
		$model->account_name = $accountName;
		$this->render('update', array(
			'model' => $model,
		));
	}
        
        /**
	 * @desc 处理上传的预览图片
	 */
	public function actionUploadtestimage() {
            
		//检查上传文件是否符合规则
		if(!$this->_model->validate(array('image_path'))) {
			$errorStr = '';
			foreach ($this->_model->errors['image_path'] as $error)
				$errorStr .= $error . ' ';
			echo $this->failureJson(array(
				'message' => $errorStr
			));
			Yii::app()->end();
		}
		$uploadFile = CUploadedFile::getInstance($this->_model, 'image_path');
		if (is_object($uploadFile) && get_class($uploadFile) === 'CUploadedFile') {
			$name = $uploadFile->getName();
                        defined('UPLOAD_DIR') || define('UPLOAD_DIR', 'C:\xampp\htdocs\vakind\uploads\\');
                        
                        //创建测试图片目录,按月保存
                        $water_test_img_dir = 'water_test_img';
                        $month = date('Y-m', time());
                        
                        //删除上月数据
                        $last_month = date('Y-m', time() - 3600 * 24 * 31);
                        is_dir(UPLOAD_DIR . $water_test_img_dir . '/' . $last_month) && Productimage::model()->rmdirs(UPLOAD_DIR . $water_test_img_dir . '/' . $last_month);
                        
                        is_dir(UPLOAD_DIR . $water_test_img_dir) or mkdir(UPLOAD_DIR . $water_test_img_dir,0700);
                        is_dir(UPLOAD_DIR . $water_test_img_dir . '/' .$month) or mkdir(UPLOAD_DIR . $water_test_img_dir . '/' .$month,0700);
                        $newfilename = $water_test_img_dir . '/' .$month . '/' .time().'_'. rand(100000,999999) .'_'. $name;
			$newFilePath = UPLOAD_DIR  . $newfilename;
			//保存图片
			if ($uploadFile->saveAs($newFilePath, true)) {
                            
				echo $this->successJson(array(
					'message' => Yii::t('system', 'Upload Files Successful'),
					'file' => $newfilename
				));
				Yii::app()->end();
			}
		}
		echo $this->failureJson(array(
				'message' => Yii::t('system', 'Upload Files Failure')
		));
		Yii::app()->end();
	}
        
        /**
	 * @desc 水印效果预览
	 */
        public function actionPreviewImage(){
            $water_file = Yii::app()->request->getParam('water_file');
            $test_file = Yii::app()->request->getParam('test_file');
            $position_x = Yii::app()->request->getParam('position_x');
            $position_y = Yii::app()->request->getParam('position_y');
            $alpha = Yii::app()->request->getParam('alpha');

            if(empty($water_file) || empty($test_file)){
                echo $this->failureJson(array(
                    'message' => Yii::t('system', '请上传水印图片和sku图片')
                ));
                Yii::app()->end();
            }
            
            defined('UPLOAD_DIR') || define('UPLOAD_DIR', 'C:\xampp\htdocs\vakind\uploads\\');
            $sku_file = UPLOAD_DIR . $test_file;
            
            $return_array = Productimagesetting::model()->waterImage($sku_file, $water_file, $position_x, $position_y, $alpha, true);
            $newFilePath = $return_array['path'];
            $newFileName = $return_array['name'];
            
            if(is_file($newFilePath)){
                echo $this->successJson(array(
                        'message' => Yii::t('system', 'Upload Files Successful'),
                        'file' => $newFileName
                ));
            } else {
                echo $this->failureJson(array(
                    'message' => Yii::t('system', 'Upload Files Failure')
                ));
            }
            Yii::app()->end();
        }
}