<?php 
$baseUrl = Yii::app()->request->baseUrl;
Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/highcharts/js/highcharts.js", CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/highcharts/js/modules/exporting.js", CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/highstock/js/highstock.js", CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/highcharts/js/export-csv.js", CClientScript::POS_HEAD);
Yii::import('application.modules.lazada.models.*');
// $orderCount = LazadaCharts::model()->getOrderCount();
$date = array();$orders = array();
// foreach($orderCount as $item){
//     $date[$item['date']] = $item['date'];
//     $orders[$item['platform_code']][$item['date']] = intval($item['count']);
// }
// foreach($orders as $platformCode=>$item){
//     ksort($item);
//     $a[] = array(
//             'name'  => $platformCode,
//             'data'  => array_values($item),
//     );
// }
// $date = array_values(array_unique($date));
?>
<div id="chart_order" style="margin:10px;">
    
</div>
<script>
$(function () {
	var x = '<?php echo json_encode($date)?>';
	var data = '<?php /* echo json_encode($a)*/;?>';
	var width = $('#layout').css('width');
	width = width.replace(/px/, '');
	var chartWidth = parseInt(width) - 300;
    $('#chart_order').highcharts({
        chart: {
            type: 'column',
            width: chartWidth
        },
        title: {
            text: '订单销量'
        },
        subtitle: {
            text: '平台日销量统计'
        },
        xAxis: {
            categories: eval((x))
        },
        yAxis: {
            min: 0,
            title: {
                text: '订单数'
            }
        },
        credits:{enabled:false},
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0
            }
        },
        series: 
//             eval((data))
            [
                 {name:"EB",
                     data:[638,1829,1767,1537,900,207,241,167,4625,7406,7553,7430,7419,2665]},
                 {name:"KF",data:[2,2,3,4,1565,2011,918,847,2176,3720,1511,437,411,2]},
                 {name:"LAZADA",data:[1,1,2,6,3,11,14,21,19,12]},
                 {name:"NF",data:[2,6]}
               ]
    });
});
</script>