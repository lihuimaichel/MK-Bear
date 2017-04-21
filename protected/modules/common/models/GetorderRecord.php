<?php
/**
 * @desc 拉单报表记录 
 * @author liuj
 * @since 2016-05-10
 */
class GetorderRecord extends CommonModel {
	
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
            return 'ueb_getorder_record';
    }
	
    /**
     * @desc 保存数据
     * 
     */
    public function addData($data) {
        return $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
    }

    /**
     * @desc 根据平台和时间段查询订单数量
     * 
     */
    public function getOrderCountByCodeAndTime($code, $time_begin, $time_end, $order_date = null) {
        
        
        $query = Order::model()->getDbConnection()->createCommand()
                    ->select('count(*)')
                    ->from(Order::model()->tableName())
                    ->where("platform_code = :code", array(':code'=>$code));                
                
        if($order_date){
            $query->andWhere("order_id like 'CO{$order_date}%'");
        } else {
            $query->andWhere("timestamp>=:time_begin and timestamp<=:time_end", array(':time_begin'=>$time_begin, ':time_end'=>$time_end) );
        }
        
        return $query->queryScalar();
    }
    
    /**
     * @desc 根据平台和日期查询订单拉取记录
     * 
     */
    public function getRecordByCodeAndDate($code, $date) {
        return GetorderRecord::model()->getDbConnection()->createCommand()
                    ->select('*')
                    ->from(GetorderRecord::model()->tableName())
                    ->where("date = :date", array(':date'=>$date))
                    ->andWhere("platform = :code", array(':code'=>$code))
                    ->queryRow();
    }
    
    /**
     * @desc 根据平台和时间段查询失败次数
     * 
     */
    public function getFailtimesByCodeAndTime($code, $time_begin, $time_end) {
        $andWhere = null;
        switch ($code){
            case 'LAZADA':
                $model = new LazadaLog();
                $event = LazadaLog::EVENT_GETORDER;
                $andWhere = "message !='No Order!'";
            break;
            case 'EB':
                $model = new EbayLog();
                $event = EbayLog::EVENT_GETORDER;
            break;
            case 'ALI':
                $model = new AliexpressLog();
                $event = AliexpressLog::EVENT_GETORDER;
                $andWhere = "message !='No Order!'";
            break;
            case 'AMAZON':
                $model = new AmazonLog();
                $event = AmazonLog::EVENT_GETORDER;
            break;
            case 'KF':
                $model = new WishLog();
                $event = WishLog::EVENT_GETORDER;
            break;
            case 'JDGJ':
                $model = new JdLog();
                //$event = JdLog::EVENT_GETORDER;
                $event = 'get_order';
                $andWhere = "message !='No Order!'";
            break;
            case 'JM':
                $model = new JoomLog();
                $event = JoomLog::EVENT_GETORDER;
            break;
            default :
                return 0;
        }
         //aliexpress、lazada、(amazon、ebay，wish,joom没有no order)、 jd(get_order有no order)
        $query =  $model->getDbConnection()->createCommand()
                    ->select('count(*)')
                    ->from($model->tableName())
                    ->where("event = :event", array(':event'=>$event))
                    ->andWhere("status != 2")
                    ->andWhere("start_time>=:time_begin and start_time<=:time_end", array(':time_begin'=>$time_begin, ':time_end'=>$time_end) );
        
        if($andWhere){
            $query->andWhere($andWhere);
        }
        return $query->queryScalar();
    }
    
}