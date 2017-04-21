<?php
class AmazonProductStatistic extends UebModel {
	
	/**
	 * Security Level
	 * @var 安全级别
	 */
	const STATUS_SECURITY='A';
	const STATUS_POSSIBLE_INFRINGEMENT='B';
	const STATUS_INFRINGEMENT='C';
	const STATUS_VIOLATION='D';
	const STATUS_UNALLOCATED='E';
	
	/**
	 * @var 侵权种类
	 */
	const INFRINGEMENT_NORMAL_STATUS=1;
	const INFRINGEMENT_WEIGUI_STATUS=2;
	const INFRINGEMENT_QINQUAN_STATUS=3;
	
	/** @var string 产品英文名称 **/
	public $en_title = null;
	//中文标题
	public $cn_title = null;
	
	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_product';
	}
	
	/**
	 * @desc 设置连接的数据库名
	 * @return string
	 */
	public function getDbKey() {
		return 'db_oms_product';
	}

        public function getProductInfringementList($num=null){
		$Infringement= array(
				self::INFRINGEMENT_NORMAL_STATUS     	 =>Yii::t('lazada_product_statistic', 'Nomal'),
				self::INFRINGEMENT_WEIGUI_STATUS    	 =>Yii::t('lazada_product_statistic', 'Is Infringe'),
				self::INFRINGEMENT_QINQUAN_STATUS     	 =>Yii::t('lazada_product_statistic', 'Is Violation'),
		);
		if($num!==null){
			return $Infringement[$num];
		}else{
			return $Infringement;
		}
	}
	
	public function getProductSecurityList(){
		return array(
				self::STATUS_SECURITY 				=> Yii::t('lazada_product_statistic', 'Security'),
				self::STATUS_POSSIBLE_INFRINGEMENT 	=> Yii::t('lazada_product_statistic', 'Possible infringement'),
				self::STATUS_INFRINGEMENT 			=> Yii::t('lazada_product_statistic', 'Tort'),
				self::STATUS_VIOLATION 				=> Yii::t('lazada_product_statistic', 'Violation'),
				self::STATUS_UNALLOCATED 			=> Yii::t('lazada_product_statistic', 'Unallocateds'),
		);
	}
}