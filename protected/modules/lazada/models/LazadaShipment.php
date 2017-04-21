<?php
/**
 * @desc Lazada物流
 * @author Gordon
 * @since 2015-09-04
 */
class LazadaShipment extends LazadaModel{
    
    const EVENT_NAME = 'upload_ship';
    const EVENT_TRACK = 'upload_track';
    const UPLOAD_SHIP_YES = 2;//上传平台SellingID 是
    const UPLOAD_SHIP_NO = 1;//上传平台SellingID 否
    const GET_TRACK_YES = 2;//获取跟踪号 是
    const GET_TRACK_NO = 1;//获取跟踪号 否
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules(){}
    
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order_package';
    }

    public function getDbKey(){
        return 'db_oms_order';
    }
    
    /**
     * 设置站点
     */
//     public static function getProvider($site) {
//     	return array('ph'=>'LGS-PH1','id'=>'LGS-LEX-ID','my'=>'AS-Poslaju','sg1'=>'LGS-SG1','sg2'=>'LGS-SG2','th1'=>'LGS-TH1');
//     }
    
    /**
     * @desc 获取需要上传跟踪号的包裹
     */
    public function getPackageReadyToShip($shipCode){
    	if (empty($shipCode)) {
    		return false;
    	}
       return $this->dbConnection->createCommand()
                ->select('op.package_id as package_id,op.ship_code')
                ->from(self::tableName().' AS op')
                ->leftJoin(OrderPackageDetail::model()->tableName().' AS pd', 'op.package_id = pd.package_id')
                ->leftJoin(OrderDetail::model()->tableName().' AS od', 'od.id = pd.order_detail_id')
                ->leftJoin(Order::model()->tableName().' AS o', 'o.order_id = od.order_id')
                //->leftJoin('ueb_system.'.LazadaAccount::model()->tableName().' AS la', 'la.id = o.account_id')
                ->where('op.platform_code = "'.Platform::CODE_LAZADA.'"')
                ->andWhere('od.site = "'.LazadaSite::getSiteShortName(LazadaSite::SITE_PH).'"')
                ->andWhere(array('in','op.ship_code',$shipCode))
                ->andWhere('op.track_num = ""')
                ->andWhere('op.upload_ship = '.OrderPackage::UPLOAD_SHIP_NO)
                ->andWhere('op.ship_status != '.OrderPackage::SHIP_STATUS_CANCEL)
                ->andWhere('o.created_time >= "2015-09-01" ')
                //->andWhere('op.package_id = "PK151111027350" ')
                ->group('op.package_id')
                ->order('op.package_id asc')
                ->limit('2')
                ->queryAll();
         //echo $obj->text;
    }
    
    /**
     * 根据站点获取跟踪号
     * @param	string	$site	站点
     */
    public function getBatchPackageReadyToShip($site, $shipCode, $packageID){
    	if (empty($shipCode)) {
    		return false;
    	}
        $startTime = date('Y-m-d H:i:s', strtotime('-14 days'));
    	$obj = $this->dbConnection->createCommand()
			    	->select('op.package_id as package_id,op.ship_code,op.track_num')
			    	->from(self::tableName().' AS op')
			    	->leftJoin(OrderPackageDetail::model()->tableName().' AS d', 'op.package_id = d.package_id')
			    	->leftJoin(OrderDetail::model()->tableName().' AS od', 'od.id = d.order_detail_id')
			    	->leftJoin(Order::model()->tableName().' AS o', 'o.order_id = d.order_id')
			    	->where('op.platform_code = "'.Platform::CODE_LAZADA.'"')
			    	->andWhere('od.site = "'.$site.'"')//站点改变
 			    	->andWhere(array('in','op.ship_code',$shipCode))
 			    	->andWhere('op.track_num = ""')
 			    	->andWhere('op.upload_ship = '.OrderPackage::UPLOAD_SHIP_NO.' or op.upload_time = "0000-00-00 00:00:00" ')
 			    	->andWhere('op.ship_status != '.OrderPackage::SHIP_STATUS_CANCEL)
 			    	->andWhere('o.created_time >= ".$startTime." ')
// 			    	->andWhere('o.order_id = "CO151126034129"')
			    	->group('op.package_id')
			    	->order('op.upload_time,op.package_id')
			    	->limit('300');
    	!empty($packageID) && $obj->andWhere("op.package_id='{$packageID}'");
    	//echo $obj->text;
		return $obj->queryAll();
    }
    
    
    /**
     * 根据需要获取
     * @param	string	$site	站点
     */
    public function getMultipleOrderItems($site,$shipCode, $packageID){
        $startTime = date('Y-m-d H:i:s', strtotime('-30 days'));
    	$obj = $this->dbConnection->createCommand()
			    	->select('op.package_id as package_id,op.ship_code,op.is_repeat')
			    	->from(self::tableName().' AS op')
			    	->leftJoin(OrderPackageDetail::model()->tableName().' AS d', 'op.package_id = d.package_id')
			    	->leftJoin(OrderDetail::model()->tableName().' AS od', 'od.id = d.order_detail_id')
			    	->leftJoin(Order::model()->tableName().' AS o', 'o.order_id = d.order_id')
			    	->where('op.platform_code = "'.Platform::CODE_LAZADA.'"')
			    	->andWhere('od.site = "'.$site.'"')//站点改变
			    	->andWhere(array('in','op.ship_code',$shipCode))
 					->andWhere('op.track_num = ""')
 			    	->andWhere('op.ship_status != '.OrderPackage::SHIP_STATUS_CANCEL)
 					->andWhere('o.created_time >= ".$startTime." ')
					->group('op.package_id')
					->order('op.upload_time,op.package_id')
			    	->limit('400');
    	!empty($packageID) && $obj->andWhere("op.package_id='{$packageID}'");
    	return $obj->queryAll();
    }
    
}