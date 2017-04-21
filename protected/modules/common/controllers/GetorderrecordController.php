<?php
/**
 * @desc 拉取订单报表
 * @author liuj
 *
 */
class GetorderrecordController extends UebController {
    /**
     * @desc 拉取订单报表
     */
    public function actionList() {
        
        set_time_limit(0);
        ini_set('memory_limit','2048M');
        
        //a.历史数据先查询是否有，没有就统计后保存到数据库（7天的）；有就直接读取
        $list = array();
        $date_list = array();
        $code_list = array();
        $platform_list = Platform::model()->getPlatformCodesAndNames();
        $GetorderRecord = new GetorderRecord();
        foreach ($platform_list as $code => $platform_name){
            if(in_array($code, array('NF','YF'))){
                continue;
            }
            for ($i = 0; $i <= 7; $i++){
                $date = date('Y-m-d', time() - 3600 * 24 * $i);
                $record = null;
                if($i > 0){
                    $record = $GetorderRecord->getRecordByCodeAndDate($code, $date);
                    $date_list[$date] = $date;
                } else {
                    $date_list['6小时内'] = $date;
                }
                
                if($record){
                    //a记录表有则直接读取
                    $list[$platform_name][$date] = $record;
                } else {
                    //b记录表没有查询原始数据并保存到记录表(6小时内不保存，每次都实时查询) 
                    $order_date = null;
                    if($i > 0){
                        //历史数据
                        $order_date = date('ymd', time() - 3600 * 24 * $i);
                        $time_begin = $date .' 00:00:00';
                        $time_end = $date .' 59:59:59';
                    } else {
                        //6小时内
                        $time_begin = date('Y-m-d H:i:s', time() - 3600 * 6);
                        $time_end = date('Y-m-d H:i:s', time());
                    }
                    //1查询失败次数
                    $fail_times = $GetorderRecord->getFailtimesByCodeAndTime($code, $time_begin, $time_end);
                    
                    //2查询订单数量
                    $order_num = $GetorderRecord->getOrderCountByCodeAndTime($code, $time_begin, $time_end, $order_date);
                    $data = array(
                        'platform'  =>$code,
                        'date'      =>$date,
                        'fail_times'=>$fail_times,
                        'order_num' =>$order_num
                    );
                    if($i > 0){
                        GetorderRecord::model()->addData($data);
                    }
                    $list[$platform_name][$date] = $data;
                }
            }
        }
        $this->render("list", array(
                "list"          =>  $list,
                "date_list"	=>  $date_list,
        ));
    }

}