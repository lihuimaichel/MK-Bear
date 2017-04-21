<?php
/**
 * @desc 刊登图片记录
 * @author Gordon
 */
class ProductImages extends CommonModel {
	
    /**@var 上传状态 */
    const UPLOAD_STATUS_DEFAULT = 0;//未上传
    const UPLOAD_STATUS_SUCCESS = 1;//上传成功
    const UPLOAD_STATUS_FAILURE = 2;//上传失败
    
    const IMAGE_ZT = 1;//主图
    const IMAGE_FT = 2;//附图
    
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
	 * @desc 获取指定图片的本地存储路径(相对)
	 * @param string $sku
	 * @param string $type
	 * @param string $imgName
	 */
    public static function getImageLocalPathBySkuAndName($sku, $type, $imgName) {
        $imageConfig = SysConfig::getPairByType('image');
        $path = $imageConfig['img_local_path'].$imageConfig['img_local_main_path'];
        $first = substr($sku,0,1);
        $second = substr($sku,1,1);
        $filePath = $path.'/'.$first.'/'.$second;
        return $filePath.'/'.$imgName.'.jpg';
    }
    
    /**
     * @desc 查找要上传的图片
     * @param string $sku
     */
    public function getImageBySku($sku, $accountID, $platformCode){
        return $this->dbConnection->createCommand()
                    ->select('*')
                    ->from(self::tableName())
                    ->where('sku = "'.$sku.'"')
                    ->andWhere('account_id = '.$accountID)
                    ->andWhere('platform_code = "'.$platformCode.'"')
                    ->queryAll();
    }
}