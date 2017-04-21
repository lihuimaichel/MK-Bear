<?php
$baseUrl = Yii::app()->request->baseUrl;
Yii::app()->clientScript->registerCssFile($baseUrl . '/css/dashboard/task_management.css', 'screen');
Yii::app()->clientScript->registerCssFile($baseUrl . '/css/dashboard/base.css', 'screen');
Yii::app()->clientScript->registerCssFile($baseUrl . '/css/dashboard/style.css', 'screen');
Yii::app()->clientScript->registerCssFile($baseUrl . '/css/dashboard/tipso.min.css', 'screen');
Yii::app()->getClientScript()->registerScriptFile($baseUrl . "/js/task/task.js");
Yii::app()->getClientScript()->registerScriptFile($baseUrl . "/js/task/tipso.min.js");
?>
<script type="text/javascript">
$(function() {
    $('.tip1').tipso({
        useTitle: false,
        background: 'tomato'
    });
});
</script>
<!-- 暂时隐藏
    <div class="head_nav">
        <a href="#" class="navcur"  onclick="changeClass(this)">
            <img src="images/u516.png"/>
            <span>Aliexxpress</span>
        </a>
        <a href="#" onclick="changeClass(this)">
            <img src="images/u490.png"/>
            <span>Wish</span>
        </a>
        <a href="#" onclick="changeClass(this)">
            <img src="images/u500.png"/>
            <span>Shopee</span>
        </a>
        <a href="#" onclick="changeClass(this)">
            <img src="images/u506.png"/>
            <span>eBay</span>
        </a>
        <a href="#" onclick="changeClass(this)">
            <img src="images/u518.png"/>
            <span>Amazon</span>
        </a>
        <a href="#" onclick="changeClass(this)">
            <img src="images/u524.png"/>
            <span>Lazada</span>
        </a>
    </div>
-->
        <div class="header">
            <div class="header_content">
                <div class="head_top_nav">
                    <h3>
                    <?php if (2 == $role_num) {?>
                    <span class="display_page"><a href="<?php echo Yii::app()->createUrl('/task/taskdashboard/index/job_id/2'); ?>" target="navTab" rel="page<?php echo $page_id;?>" title="销售人员">销售人员</a></span>
                    <span class="hide_page"><a href="<?php echo Yii::app()->createUrl('/task/taskdashboard/index/job_id/1'); ?>" target="navTab" rel="page<?php echo $page_id;?>" title="组长">组长</a></span>
                    <?php }?>
                    </h3>
                </div>
                <div class="header_top">
                    <div class="one">
                        <?php if (1 == $header_icon['exists']) {?>
                        <img title="相片信息" src="<?php echo $header_icon['url']; ?>"/>
                        <?php } else {?>
                        <div style="width: 180px; height: 215px">
                            &nbsp;
                        </div>
                        <?php } ?>
                        <div class="two">
                            <p><?php echo Yii::app()->user->full_name; ?></p>
                        </div>
                    </div>
                    <div class="blessing">
                        <p>组长：<?php echo $leader; ?></p>
                        <p>主管：<?php echo $manager; ?></p>
                        <p class="cy">祝工作愉快，生活开心！</p>
                        <p class="cb">您已在公司工作<?php if ($service_days <= 365) { ?>
                            <span class="cr"><?php echo $service_days; ?></span>天！
                            <?php } else { ?>
                            <span class="cr"><?php echo floor($service_days / 365); ?></span>年<span
                        class="cr"><?php echo $service_days % 365; ?></span>天！
                        <?php } ?>
                    </p>
                </div>
            </div>
            <div class="header_right">
                <div class="header_center_head">
                    <h2>销售状况</h2>
                    <a href="<?php echo Yii::app()->createUrl('/systems/salesprofit/index'); ?>" target="navTab" rel="sales_profit_index" title="销售报告_销售">
                    每月销售报告</a>
                </div>
                <div class="onYear">
                    <table>
                        <caption> 年 度 </caption>
                        <tbody>
                            <tr>
                                <td>目标销售额</td>
                                <td>目标净利额</td>
                            </tr>
                            <tr>
                                <td><span class="tip1" data-tipso="RMB:<?php echo number_format($sales_info['year_sale_amount'], 2); ?>">￥<?php echo (10000 < $sales_info['year_sale_amount']) ? number_format($sales_info['year_sale_amount']/10000, 2).'W' : number_format($sales_info['year_sale_amount'], 2); ?></span></td>
                                <td><span class="tip1" data-tipso="RMB:<?php echo number_format($sales_info['year_profit_amount'], 2); ?>">￥<?php echo (10000 < $sales_info['year_profit_amount']) ? number_format($sales_info['year_profit_amount']/10000, 2).'W' : number_format($sales_info['year_profit_amount'], 2); ?></span></td>
                            </tr>
                            <tr>
                                <td>累计销售额</td>
                                <td>累计净利额</td>
                            </tr>
                            <tr>
                                <td><span><span class="tip1" data-tipso="RMB:<?php echo isset($sales_info['sale_amount']) ? number_format($sales_info['sale_amount'], 2) : 0; ?>">
                                            ￥<?php echo isset($sales_info['sale_amount']) ? ((10000 < $sales_info['sale_amount']) ? number_format($sales_info['sale_amount']/10000, 2).'W' : number_format($sales_info['sale_amount'], 2)) : 0;?></span></span></td>
                                <td><span><span class="tip1" data-tipso="RMB:<?php echo isset($sales_info['profit_amount']) ? number_format($sales_info['profit_amount'], 2) : 0; ?>">
                                            ￥<?php echo isset($sales_info['profit_amount']) ? ((1000 < $sales_info['profit_amount']) ? number_format($sales_info['profit_amount']/10000, 2).'W' : number_format($sales_info['profit_amount'], 2)) : 0;?></span></span></td>
                            </tr>
                            <tr>
                                <td>达成率</td>
                                <td>达成率</td>
                            </tr>
                            <tr>
                                <td><span><?php echo $sales_info['year_sale_rate']; ?>%</span></td>
                                <td><span><?php echo $sales_info['year_profit_rate'];  ?>%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="onMonth">
                    <table>
                        <caption> 当 月 </caption>
                        <tbody>
                            <tr>
                                <td>目标销售额</td>
                                <td>目标净利额</td>
                            </tr>
                            <tr>
                                <td><span class="tip1" data-tipso="RMB:<?php echo number_format($sales_info['month_sale_amount'], 2);?>">￥<?php echo (10000 < $sales_info['month_sale_amount']) ? number_format($sales_info['month_sale_amount']/10000, 2).'W' : number_format($sales_info['month_sale_amount'], 2); ?></span></td>
                                <td><span class="tip1" data-tipso="RMB:<?php echo number_format($sales_info['month_profit_amount'], 2);?>">￥<?php echo (10000 < $sales_info['month_profit_amount']) ? number_format($sales_info['month_profit_amount']/10000, 2).'W' : number_format($sales_info['month_profit_amount'], 2); ?></span></td>
                            </tr>
                            <tr>
                                <td>累计销售额</td>
                                <td>累计净利额</td>
                            </tr>
                            <tr>
                                <td><span class="tip1" data-tipso="RMB:<?php echo isset($sales_info['month_amount']) ? number_format($sales_info['month_amount'], 2) : 0 ?>">￥<?php echo isset($sales_info['month_amount']) ? ((10000 < $sales_info['month_amount']) ? number_format($sales_info['month_amount']/10000, 2).'W' : number_format($sales_info['month_amount'], 2)) : 0; ?></span></td>
                                <td><span class="tip1" data-tipso="RMB:<?php echo isset($sales_info['month_profit']) ? number_format($sales_info['month_profit'], 2) : 0 ?>">￥<?php echo isset($sales_info['month_profit']) ? ((10000 < $sales_info['month_profit']) ? number_format($sales_info['month_profit']/10000, 2).'W' : number_format($sales_info['month_profit'], 2)) : 0; ?></span></td>
                            </tr>
                            <tr>
                                <td>达成率</td>
                                <td>达成率</td>
                            </tr>
                            <tr>
                                <td><span><?php echo isset($sales_info['month_sale_rate']) ? $sales_info['month_sale_rate'] : 0;?>%</span></td>
                                <td><span><?php echo isset($sales_info['month_profit_rate']) ? $sales_info['month_profit_rate'] : 0;?>%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="onDay">
                    <table>
                        <caption> 昨 日 </caption>
                        <tbody>
                            <tr>
                                <td>目标销售额</td>
                                <td>目标净利额</td>
                            </tr>
                            <tr>
                                <td><span class="tip1" data-tipso="RMB:<?php echo number_format($sales_info['day_sale_amount'], 2); ?>">￥<?php echo (10000 < $sales_info['day_sale_amount']) ? number_format($sales_info['day_sale_amount']/10000, 2).'W' : number_format($sales_info['day_sale_amount'], 2); ?></span></td>
                                <td><span class="tip1" data-tipso="RMB:<?php echo number_format($sales_info['day_profit_amount'], 2);?>">￥<?php echo (10000 < $sales_info['day_profit_amount']) ? number_format($sales_info['day_profit_amount']/10000, 2).'W' : number_format($sales_info['day_profit_amount'], 2); ?></span></td>
                            </tr>
                            <tr>
                                <td>累计销售额</td>
                                <td>累计净利额</td>
                            </tr>
                            <tr>
                                <td><span class="tip1" data-tipso="RMB:<?php echo isset($sales_info['day_amount']) ? number_format($sales_info['day_amount'], 2) : 0 ?>">￥<?php echo isset($sales_info['day_amount']) ? ((10000 < $sales_info['day_amount']) ? number_format($sales_info['day_amount']/10000, 2).'W' : number_format($sales_info['day_amount'], 2)) : 0; ?></span></td>
                                <td><span class="tip1" data-tipso="RMB:<?php echo isset($sales_info['day_profit']) ? number_format($sales_info['day_profit'], 2) : 0 ?>">￥<?php echo isset($sales_info['day_profit']) ? ((10000 < $sales_info['day_profit']) ? number_format($sales_info['day_profit']/10000, 2).'W' : number_format($sales_info['day_profit'], 2)) : 0; ?></span></td>
                            </tr>
                            <tr>
                                <td>达成率</td>
                                <td>达成率</td>
                            </tr>
                            <tr>
                                <td><span>
                                    <?php  echo $sales_info['day_sale_rate']; ?>%</span></td>
                                <td><span>
                                    <?php  echo $sales_info['day_profit_rate']; ?>%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="header_center">
                <div class="header_center_head">
                    <h2>我的排行</h2><a href="#">查看完整榜单</a>
                </div>
                <h3>90天销售排名<span class="cs1"><?php echo isset($rank_info['90_rank']) ? $rank_info['90_rank'] : ''; ?></span></h3>
                <section class="container">
                    <div class="progress">
                        <?php if ((0 < isset($rank_info['90_rank'])) && (0 < $rank_info['90_rank_amount'])) {
                            $rate = round(($rank_info['90_rank'] / $rank_info['90_rank_amount']) * 100, 2);
                        } else {
                            $rate = 100;
                        } ?>
                        <span class="blue" style="width: <?php echo $rate;?>%;"></span>
                    </div>
                </section>
                <h3>部门销售排名<span class="cs2"><?php echo isset($rank_info['dep_rank']) ? $rank_info['dep_rank'] : ''; ?></span></h3>
                <section class="container">
                    <div class="progress">
                        <?php if ((0 < isset($rank_info['dep_rank'])) && (0 < $rank_info['dep_rank_amount'])) {
                            $rate = round(($rank_info['dep_rank'] / $rank_info['dep_rank_amount']) * 100, 2);
                        } else {
                            $rate = 100;
                        } ?>
                        <span class="green" style="width: <?php echo $rate;?>%;"></span>
                    </div>
                </section>
                <h3>团队排名<span class="cs3"><?php echo isset($rank_info['team_rank']) ? $rank_info['team_rank'] : ''; ?></span></h3>
                <section class="container">
                    <div class="progress">
                        <?php if ((0 < $rank_info['team_rank']) && (0 < $rank_info['team_rank_amount'])) {
                            $rate = round(($rank_info['team_rank'] / $rank_info['team_rank_amount']) * 100, 2);
                        } else {
                            $rate = 100;
                        } ?>
                        <span class="red" style="width: <?php echo $rate;?>%;"></span>
                    </div>
                </section>
                <?php if (0 < $rank_info['new_rank']) { ?>
                <h3>新人排名<span class="cs4"><?php echo $rank_info['new_rank']; ?></span></h3>
                <section class="container">
                    <div class="progress">
                        <?php if ((0 < $rank_info['new_rank']) && (0 < $rank_info['new_rank_amount'])) {
                            $rate = round(($rank_info['new_rank'] / $rank_info['new_rank_amount']) * 100, 2);
                        } else {
                            $rate = 100;
                        } ?>
                        <span class="orange" style="width: <?php echo $rate;?>%;"></span>
                    </div>
                </section>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="content_top">
        <div class="tasks">
            <h2 class="tasksH">任务池<a href="<?php echo Yii::app()->createUrl('/task/task/record'); ?>" target="navTab"
            rel="page_task_record" title="任务报告_销售">任务报告</a></h2>
            <div class="tasks_left">
                <table>
                    <tr>
                        <td>需刊登</td>
                        <td>已刊登</td>
                        <td>刊登标准速度</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><span>
                            <a href="<?php echo Yii::app()->createUrl('/task/task/index'); ?>" target="navTab"
                                rel="page_task_index" title="当月需刊登_销售">
                            <?php echo isset($data['listing_num']) ? number_format($data['listing_num'] * $days) : 0; ?></a>
                        </span></td>
                        <td>
                            <span>
                                <a href="<?php echo Yii::app()->createUrl('/task/task/listinghistory'); ?>" target="navTab"
                                    rel="page_listing_history" title="已刊登_销售">
                                    <?php echo $data['listing_count']; ?>
                                </a>
                            </span>
                        </td>
                        <td><span>5min/个</span></td>
                        <td><a href="#">刊登指引</a></td>
                    </td></tr>
                </table>
                <div class="probability">
                    <h2>刊登率</h2>
                    <section class="container">
                        <div class="progress">
                            <?php if (0 < $data['listing_count'] && 0 < $data['listing_num']) {
                            $listing_rate = floor(($data['listing_count'] / ($data['listing_num'] * $days)) * 100);
                            ?>
                            <span class="red"
                                style="width: <?php echo $listing_rate; ?>%;"><span><?php echo $listing_rate; ?>%</span></span>
                                <?php } else { ?>
                                <span class="red" style="width: 0%;"><span>0%</span></span>
                                <?php } ?>
                            </div>
                        </section>
                    </div>
                    <div class="pk_right">
                        <a href="<?php echo Yii::app()->createUrl('/task/task/listingrank'); ?>" target="navTab"
                            rel="task_listing_rank" title="每月刊登排名">
                            <span>去瞧瞧其他小伙伴</span><br/>
                            <img src="images/pk.png"/>
                        </a>
                    </div>
                </div>
                <div class="tasks_right">
                    <table>
                        <tr>
                            <td>需优化</td>
                            <td>已优化</td>
                            <td>优化标准速度</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td> <span>
                                <a href="<?php echo Yii::app()->createUrl('/task/task/optimization'); ?>" target="navTab"
                                    rel="page_task_optimization" title="优化任务记录">
                                    <?php echo isset($data['optimization_num']) ? number_format($data['optimization_num'] * $days) : 0; ?>
                                </a>
                            </span></td>
                            <td><span>
                                <a href="<?php echo Yii::app()->createUrl('/task/task/optimization/optimization_type/1'); ?>"
                                    target="navTab" rel="page_yesterday_optimization" title="已优化_销售">
                                <?php echo $data['optimization_count']; ?></a>
                            </span></td>
                            <td><span>5min/个</span></td>
                            <td><a href="#">优化指引</a></td>
                        </td></tr>
                    </table>
                    <div class="probability">
                        <h2>优化率</h2>
                        <section class="container">
                            <div class="progress">
                                <?php
                                if (0 < $data['optimization_num'] && 0 < $data['optimization_count']) {
                                $optimization_rate = floor(($data['optimization_count'] / ($data['optimization_num'] * $days)) * 100);
                                ?>
                                <span class="red"
                                    style="width:<?php echo $optimization_rate ?>%;max-width:100%"><span><?php echo $optimization_rate ?>%</span></span>
                                    <?php } else { ?>
                                    <span class="red" style="width:0%;"><span>0%</span></span>
                                    <?php } ?>
                                </div>
                            </section>
                        </div>
                        <div class="pk_right">
                            <a href="<?php echo Yii::app()->createUrl('/task/task/optimizationrank'); ?>" target="navTab"
                                rel="task_optimization_rank" title="每月优化排名">
                                <span>去瞧瞧其他小伙伴</span><br/>
                                <img src="images/pk.png"/>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content_buttom">
                <div class="condition">
                    <div class="condition_content_head">
                        <h2>销售概况</h2>
                    </div>
                    <div class="condition_content">
                        <div class="condition_content_foot">
                            <div class="total">
                                <table>
                                    <tbody>
                                        <tr>
                                            <td>负责SKU总数</td>
                                            <td>未刊登SKU总数</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span>
                                                    <?php echo isset($sale_stats['sku_count']) ? $sale_stats['sku_count'] : 0 ; ?>(子) /
                                                    <?php echo isset($sale_stats['sku_main_count']) ? $sale_stats['sku_main_count'] : 0 ; ?>(主)
                                                </span>
                                            </td>
                                            <td>
                                                <span>
                                                    <?php echo isset($sale_stats['pre_listing']) ? $sale_stats['pre_listing'] : 0 ; ?>(子) /
                                                    <?php echo isset($sale_stats['pre_main_listing']) ? $sale_stats['pre_main_listing'] : 0 ; ?>(主)
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>预上线SKU总数</td>
                                            <td>已刊登SKU总数</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span>
                                                    <?php echo isset($sale_stats['pre_count']) ? $sale_stats['pre_count'] : 0 ; ?>(子) /
                                                    <?php echo isset($sale_stats['pre_main_count']) ? $sale_stats['pre_main_count'] : 0 ; ?>(主)
                                                </span>
                                            </td>
                                            <td>
                                                <span>
                                                    <?php echo isset($sale_stats['was_listing']) ? $sale_stats['was_listing'] : 0 ; ?>(子) /
                                                    <?php echo isset($sale_stats['was_main_listing']) ? $sale_stats['was_main_listing'] : 0 ; ?>(主)
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>在售中SKU总数</td>
                                            <td>待清仓SKU总数</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span>
                                                    <?php echo isset($sale_stats['sales_count']) ? $sale_stats['sales_count'] : 0 ; ?>(子) /
                                                    <?php echo isset($sale_stats['sales_main_count']) ? $sale_stats['sales_main_count'] : 0 ; ?>(主)
                                                </span>
                                            </td>
                                            <td>
                                                <span>
                                                    <?php echo isset($sale_stats['clean_count']) ? $sale_stats['clean_count'] : 0 ; ?>(子) /
                                                    <?php echo isset($sale_stats['clean_main_count']) ? $sale_stats['clean_main_count'] : 0 ; ?>(主)
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="total_month">
                                <table>
                                    <tbody>
                                        <tr>
                                            <td>当月累计订单数</td>
                                        </tr>
                                        <tr>
                                            <td><span><?php echo isset($orders_info['orders_num']) ? number_format($orders_info['orders_num']) : 0; ?></span></td>
                                        </tr>
                                        <tr>
                                            <td>当月取消订单数</td>
                                        </tr>
                                        <tr>
                                            <td><span><?php echo isset($orders_info['orders_cancel']) ? number_format($orders_info['orders_cancel']) : 0; ?></span></td>
                                        </tr>
                                        <tr>
                                            <td>当月包裹重发数</td>
                                        </tr>
                                        <tr>
                                            <td><span><?php echo isset($orders_info['orders_repeat']) ? number_format($orders_info['orders_repeat']) : 0; ?></span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="total_day">
                                <table>
                                    <tbody>
                                        <tr>
                                            <td>昨日订单</td>
                                        </tr>
                                        <tr>
                                            <td><span><?php echo isset($orders_info['orders_yesterday_num']) ? number_format($orders_info['orders_yesterday_num']) : 0; ?></span></td>
                                        </tr>
                                        <tr>
                                            <td>待处理亏损订单</td>
                                        </tr>
                                        <tr>
                                            <td><span><?php echo isset($orders_info['orders_loss_num']) ? number_format($orders_info['orders_loss_num']) : 0; ?></span></td>
                                        </tr>
                                        <tr>
                                            <td>待发货订单</td>
                                        </tr>
                                        <tr>
                                            <td><span><?php echo isset($orders_info['orders_wait_send']) ? number_format($orders_info['orders_wait_send']) : 0; ?></span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="total_7day">
                                <table>
                                    <tbody>
                                        <tr>
                                            <td>刊登七日动销率</td>
                                        </tr>
                                        <tr>
                                            <td><span><?php echo isset($orders_info['orders_sales_rate']) ? number_format($orders_info['orders_sales_rate']*100, 2) : 0?>%</span></td>
                                        </tr>
                                        <tr>
                                            <td>优化七日动销率</td>
                                        </tr>
                                        <tr>
                                            <td><span><?php echo isset($orders_info['orders_optimization_rate']) ? number_format($orders_info['orders_optimization_rate']*100, 2) : 0?>%</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="condition_table" id="showReport">
                    
                    </div><p class="hint">提示：金额单位都为人民币</p>
                </div>
                <script>
                $(function () {
                    $("#showReport").load("/products/userplatformpublishreports/show/seller_user_id/<?php echo Yii::app()->user->id?>");
                })
                </script>