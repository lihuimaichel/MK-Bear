<?php
$baseUrl = Yii::app()->request->baseUrl;
Yii::app()->clientScript->registerCssFile($baseUrl . '/css/dashboard/task_management.css', 'screen');
Yii::app()->clientScript->registerCssFile($baseUrl . '/css/dashboard/base.css', 'screen');
Yii::app()->clientScript->registerCssFile($baseUrl . '/css/dashboard/style.css', 'screen');
Yii::app()->getClientScript()->registerScriptFile($baseUrl . "/js/task/task.js");
Yii::app()->getClientScript()->registerScriptFile($baseUrl . "/js/task/tipso.min.js");
?>
<script type="text/javascript">
$(function() {
    $('.tip1').tipso({
        useTitle: false
    });
});
</script>
<!-- 暂时屏蔽
<div class="head_nav">
        <a href="#" class="navcur" onclick="changeClass(this)">
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
               <span class="hide_page"><a href="<?php echo Yii::app()->createUrl('/task/taskdashboard/index/job_id/2/role/seller'); ?>" target="navTab" rel="page<?php echo $page_id;?>" title="销售人员">销售人员</a></span>
               <span class="display_page"><a href="<?php echo Yii::app()->createUrl('/task/taskdashboard/index/job_id/1/role/leader'); ?>" target="navTab" rel="page<?php echo $page_id;?>" title="组长">组长</a></span>
                <?php } else {
                        $check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, $this->userPlatform());
                        if ($check_result && (1 == $role_num)) {
                            ?>
                            <span class="display_page"><a href="<?php echo Yii::app()->createUrl('/task/taskdashboard/index/job_id/1/role/leader'); ?>" target="navTab" rel="page<?php echo $page_id;?>" title="组长">组长</a></span>
                            <span class="hide_page"><a href="<?php echo Yii::app()->createUrl('/task/taskdashboard/index/role/manager'); ?>" target="navTab" rel="page<?php echo $page_id;?>" title="主管">主管</a></span>
                            <?php
                        }
                    }
                 ?>
            </h3>
        </div>
        <div class="header_top">
            <div class="one">
                <?php if (1 == $header_icon['exists']) { ?>
                <img title="相片信息" src="<?php echo $header_icon['url']; ?>"/>
                <?php } else { ?>
                <div style="width: 180px; height: 215px">
                    &nbsp;
                </div>
                <?php } ?>
                <div class="two">
                    <p><?php echo Yii::app()->user->full_name; ?></p>
                </div>
            </div>
            <div class="blessing">
                <!--<p>组长：<?php echo $leaderName; ?></p>-->
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
            <h2>本月销售状况</h2>
            <a href="<?php echo Yii::app()->createUrl('/systems/salesprofit/index'); ?>" target="navTab"
            rel="sales_profit_index" title="销售报告_组长">每月销售报告</a>
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
                        <td><span class="tip1" data-tipso="RMB:<?php echo number_format($sales_target); ?>">￥<?php echo (10000 < $sales_target) ?  number_format($sales_target/10000 ,2).'W' : number_format($sales_target); ?></span></td>
                        <td><span class="tip1" data-tipso="RMB:<?php echo number_format($profit_target); ?>">￥<?php echo (10000 < $profit_target) ? number_format($profit_target/10000 ,2).'W' : number_format($profit_target); ?></span></td>
                    </tr>
                    <tr>
                        <td>累计销售额</td>
                        <td>累计净利额</td>
                    </tr>
                    <tr>
                        <td>
                            <span class="tip1" data-tipso="RMB:<?php echo isset($yearSalesInfo['sales_total']) ? number_format($yearSalesInfo['sales_total'], 2) : 0; ?>">￥
                                <?php echo isset($yearSalesInfo['sales_total']) ? ((10000 < $yearSalesInfo['sales_total']) ? number_format($yearSalesInfo['sales_total']/10000, 2).'W' : number_format($yearSalesInfo['sales_total'], 2)) : 0; ?></span>
                        </td>
                        <td>
                            <span class="tip1" data-tipso="RMB:<?php echo isset($yearSalesInfo['profit_total']) ? number_format($yearSalesInfo['profit_total'], 2) : 0; ?>">￥
                                <?php echo isset($yearSalesInfo['profit_total']) ? ((10000 < $yearSalesInfo['profit_total']) ? number_format($yearSalesInfo['profit_total']/10000, 2).'W' : number_format($yearSalesInfo['profit_total'], 2)) : 0; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td>达成率</td>
                        <td>达成率</td>
                    </tr>
                    <tr>
                        <td>
                            <span><?php echo (0 < $sales_target && $yearSalesInfo['sales_total']) ? round(($yearSalesInfo['sales_total'] / $sales_target) * 100, 2) : 0 ?>
                            %</span></td>
                            <td>
                                <span><?php echo (0 < $profit_target && $yearSalesInfo['profit_total']) ? round(($yearSalesInfo['profit_total'] / $profit_target) * 100, 2) : 0 ?>
                                %</span></td>
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
                                <td><span class="tip1" data-tipso="RMB:<?php echo number_format($sales_amount, 2); ?>">￥<?php echo (10000 < $sales_amount) ?  number_format($sales_amount/10000, 2).'W' : number_format($sales_amount, 2); ?></span></td>
                                <td><span class="tip1" data-tipso="RMB:<?php echo number_format($profit_amount, 2); ?>">￥<?php echo (10000 < $profit_amount) ? number_format($profit_amount/10000, 2).'W' : number_format($profit_amount, 2); ?></span></td>
                            </tr>
                            <tr>
                                <td>累计销售额</td>
                                <td>累计净利额</td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="tip1" data-tipso="RMB:<?php echo isset($monthInfo['sales_amount_rmb']) ? number_format($monthInfo['sales_amount_rmb'], 2) : 0; ?>">￥
                                        <?php echo isset($monthInfo['sales_amount_rmb']) ? ((10000 < $monthInfo['sales_amount_rmb']) ? number_format($monthInfo['sales_amount_rmb']/10000, 2).'W' : number_format($monthInfo['sales_amount_rmb'], 2)) : 0; ?></span>
                                </td>
                                <td>
                                    <span class="tip1" data-tipso="RMB:<?php echo isset($monthInfo['retained_profits']) ? number_format($monthInfo['retained_profits'], 2) : 0; ?>">￥
                                        <?php echo isset($monthInfo['retained_profits']) ? ((10000 < $monthInfo['retained_profits']) ? number_format($monthInfo['retained_profits']/10000, 2).'W' : number_format($monthInfo['retained_profits'], 2)) : 0; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td>达成率</td>
                                <td>达成率</td>
                            </tr>
                            <tr>
                                <td>
                                    <span><?php echo (0 < $monthInfo['sales_amount_rmb'] && 0 < $sales_amount) ? round(($monthInfo['sales_amount_rmb'] / $sales_amount) * 100, 2) : 0 ?>
                                    %</span></td>
                                    <td>
                                        <span><?php echo (0 < $monthInfo['retained_profits'] && 0 < $profit_amount) ? round(($monthInfo['retained_profits'] / $profit_amount) * 100, 2) : 0 ?>
                                        %</span></td>
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
                                        <td>目标利润额</td>
                                    </tr>
                                    <tr>
                                        <td><span class="tip1" data-tipso="RMB:<?php echo number_format($avg_sales_amount, 2); ?>">￥
                                                <?php echo (10000 < $avg_sales_amount) ? number_format($avg_sales_amount/10000, 2).'W' : number_format($avg_sales_amount, 2); ?></span></td>
                                        <td><span class="tip1" data-tipso="RMB:<?php echo number_format($avg_profit_amount, 2); ?>">￥
                                                <?php echo (10000 < $avg_profit_amount) ? number_format($avg_profit_amount/10000, 2).'W' : number_format($avg_profit_amount, 2); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>累计销售额</td>
                                        <td>累计利润额</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="tip1" data-tipso="RMB:<?php echo isset($yesterdaySales['total_rmb']) ? number_format($yesterdaySales['total_rmb'], 2) : 0; ?>">￥
                                                <?php echo isset($yesterdaySales['total_rmb']) ? ((10000 < $yesterdaySales['total_rmb']) ? number_format($yesterdaySales['total_rmb']/10000, 2).'W' : number_format($yesterdaySales['total_rmb'], 2)) : 0; ?></span>
                                        </td>
                                        <td>
                                            <span class="tip1" data-tipso="RMB:<?php echo isset($yesterdayProfit['retained_profits']) ? number_format($yesterdayProfit['retained_profits'], 2) : 0; ?>">￥
                                                <?php echo isset($yesterdayProfit['retained_profits']) ? ((10000 < $yesterdayProfit['retained_profits']) ? number_format($yesterdayProfit['retained_profits']/10000, 2).'W' : number_format($yesterdayProfit['retained_profits'], 2)) : 0; ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>达成率</td>
                                        <td>达成率</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span>
                                                <?php
                                                $yesterdaySales_total = isset($yesterdaySales['total_rmb']) ? $yesterdaySales['total_rmb'] : 0;
                                                echo (0 < $avg_sales_amount) ? round(($yesterdaySales_total / $avg_sales_amount) * 100, 2) : 0;
                                                ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span>
                                                <?php
                                                $yesterdayRetained_profits = isset($yesterdayProfit['retained_profits']) ? $yesterdayProfit['retained_profits'] : 0;
                                                echo (0 < $avg_profit_amount) ? round(($yesterdayRetained_profits / $avg_profit_amount) * 100, 2) : 0;
                                                ?>%
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!--
                        <div class="hide_r view_sales_status">
                            <h3 title="查看成员">查看成员</h3>
                        </div>
                        -->
                    </div>
                    <div class="header_center">
                        <div class="header_center_head">
                            <div class="header_center_head_leader">
                                <h3>我的组员</h3>
                                <?php if (!empty($teamer)) {
                                foreach ($teamer as $tk => $tv) {
                                ?>
                                <h4><?php echo $tv['user_full_name']; ?></h4>
                                <?php }
                                }
                                ?>
                            </div>
                            <!--
                            <h3>部门销售排名<span class="cs2">1</span></h3>
                            <section class="container">
                                <div class="progress">
                                    <span class="green" style="width: 80%;"></span>
                                </div>
                            </section>
                            -->
                            <h3>团队排名<span class="cs3"><?php echo isset($groupRank['rank']) ? $groupRank['rank'] : ''; ?></span></h3>
                            <section class="container">
                                <div class="progress">
                                    <?php if ((0 < $groupRank['rank']) && (0 < $groupRank_amount)) {
                                    $rate = round(($groupRank['rank'] / $groupRank_amount) * 100, 2);
                                    } else {
                                    $rate = 100;
                                    } ?>
                                    <span class="red" style="width: <?php echo $rate; ?>%;"></span>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
            <div  class="content_top_hide show_status">
                <div class="right_top"><img src="images/u564.png"/></div>
                <div class="tasks_hide">
                    <div class="tasks_left_hide">
                        <div class="person_status">
                            <div class="person_img">
                                <img src="images/pk.png"/><br/>
                                <h3>张可欣张可欣</h3>
                            </div>
                            <div class="person_year">
                                <table>
                                    <tr><th rowspan="4" class="tHead">年度</th><th>目标销售额</th><th>累积销售额</th><th class="rate">达成率</th><th rowspan="2" class="tRank">1</th></tr>
                                    <tr><td>-</td><td>-</td><td>-</td></tr>
                                    <tr><th>目标销售额</th><th>累积销售额</th><th>达成率</th><th rowspan="2" class="tRank">2</th></tr>
                                    <tr><td>-</td><td>-</td><td>-</td></tr>
                                </table>
                            </div>
                            <div class="person_month">
                                <table>
                                    <tr><th rowspan="4" class="tHead">当月</th><th>目标销售额</th><th>累积销售额</th><th class="rate">达成率</th><th rowspan="2" class="tRank">1</th></tr>
                                    <tr><td>-</td><td>-</td><td>-</td></tr>
                                    <tr><th>目标销售额</th><th>累积销售额</th><th>达成率</th><th rowspan="2" class="tRank">2</th></tr>
                                    <tr><td>-</td><td>-</td><td>-</td></tr>
                                </table>
                            </div>
                            <div class="person_yesterday">
                                <table>
                                    <tr><th rowspan="4" class="tHead">昨天</th><th>目标销售额</th><th>累积销售额</th><th class="rate">达成率</th><th rowspan="2" class="tRank">1</th></tr>
                                    <tr><td>-</td><td>-</td><td>-</td></tr>
                                    <tr><th>目标销售额</th><th>累积销售额</th><th>达成率</th><th rowspan="2" class="tRank">2</th></tr>
                                    <tr><td>-</td><td>-</td><td>-</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hide_r">
                    <h3 href="" title="收起"><span class="hide_sales_status">收起</span></h3>
                </div>
            </div>
            <div class="content_top">
                <div class="tasks">
                    <h2 class="tasksH">任务池<a href="<?php echo Yii::app()->createUrl('/task/task/record'); ?>" target="navTab"
                    rel="page_task_record" title="任务报告_组长">任务报告</a></h2>
                    <div class="tasks_left">
                        <table>
                            <tr>
                                <td>需刊登</td>
                                <td>已刊登</td>
                                <td>异常需处理</td>
                                <td>刊登标准速度</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>
                                    <span>
                                        <a href="<?php echo Yii::app()->createUrl('/task/task/index'); ?>" target="navTab"
                                            rel="page_task_index" title="刊登任务_组长">
                                        <?php echo isset($data['listing_num']) ? number_format($data['listing_num'] * $days) : 0; ?></a>
                                    </span>
                                </td>
                                <td>
                                    <span>
                                        <a href="<?php echo Yii::app()->createUrl('/task/task/listinghistory'); ?>" target="navTab"
                                            rel="page_listing_history" title="已刊登_组长">
                                            <?php echo $data['listing_count']; ?>
                                        </a>
                                    </span>
                                </td>
                                <td><span><a href="<?php echo Yii::app()->createUrl('/task/task/exception');?>" target="navTab"
                                             rel="page_listing_exception" title="待处理异常">
                                                <?php echo $exception_num; ?>
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
                                    <td>
                                        <span>
                                            <a href="<?php echo Yii::app()->createUrl('/task/task/optimization'); ?>" target="navTab"
                                                rel="page_task_optimization" title="优化任务记录">
                                                <?php echo isset($data['optimization_num']) ? number_format($data['optimization_num'] * $days) : 0; ?>
                                            </a>
                                        </span>
                                    </td>
                                    <td>
                                        <span>
                                            <a href="<?php echo Yii::app()->createUrl('/task/task/optimization/optimization_type/1'); ?>"
                                                target="navTab" rel="page_yesterday_optimization" title="已优化_组长">
                                            <?php echo $data['optimization_count']; ?></a>
                                        </span>
                                    </td>
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
                        <div class="hide_r view_sales_talk">
                            <h3 title="查看成员">查看成员</h3>
                        </div>
                        </div><div class="right_topT"><img src="images/u564.png"/></div>
                        <div  class="content_two_hide">
                            <div class="tasks_hide_talk">
                                <div class="tasks_left_hide">
                                    <div class="person_data">
                                        <div class="person_data_img">
                                            <img src="images/pk.png"/><br/>
                                            <h3>张可欣张可欣</h3>
                                        </div>
                                        <div class="person_data_table">
                                            <table>
                                                <tr>
                                                    <th>目标销售额</th>
                                                    <th>累积销售额</th>
                                                    <th>达成率</th>
                                                    <th rowspan="2" class="tRank">1</th>
                                                </tr>
                                                <tr>
                                                    <td>-</td><td>-</td><td>-</td></tr>
                                                    <tr>
                                                        <th>目标销售额</th><th>累积销售额</th><th>达成率</th><th rowspan="2" class="tRank">2</th>
                                                    </tr>
                                                    <tr>
                                                        <td>-</td><td>-</td><td>-</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="person_data">
                                            <div class="person_data_img">
                                                <img src="images/pk.png"/><br/>
                                                <h3>张可欣张可欣</h3>
                                            </div>
                                            <div class="person_data_table">
                                                <table>
                                                    <tr>
                                                        <th>目标销售额</th>
                                                        <th>累积销售额</th>
                                                        <th>达成率</th>
                                                        <th rowspan="2" class="tRank">1</th>
                                                    </tr>
                                                    <tr>
                                                        <td>-</td><td>-</td><td>-</td></tr>
                                                        <tr>
                                                            <th>目标销售额</th><th>累积销售额</th><th>达成率</th><th rowspan="2" class="tRank">2</th>
                                                        </tr>
                                                        <tr>
                                                            <td>-</td><td>-</td><td>-</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="person_data">
                                                <div class="person_data_img">
                                                    <img src="images/pk.png"/><br/>
                                                    <h3>张可欣张可欣</h3>
                                                </div>
                                                <div class="person_data_table">
                                                    <table>
                                                        <tr>
                                                            <th>目标销售额</th>
                                                            <th>累积销售额</th>
                                                            <th>达成率</th>
                                                            <th rowspan="2" class="tRank">1</th>
                                                        </tr>
                                                        <tr>
                                                            <td>-</td><td>-</td><td>-</td></tr>
                                                            <tr>
                                                                <th>目标销售额</th><th>累积销售额</th><th>达成率</th><th rowspan="2" class="tRank">2</th>
                                                            </tr>
                                                            <tr>
                                                                <td>-</td><td>-</td><td>-</td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="person_data">
                                                    <div class="person_data_img">
                                                        <img src="images/pk.png"/><br/>
                                                        <h3>张可欣张可欣</h3>
                                                    </div>
                                                    <div class="person_data_table">
                                                        <table>
                                                            <tr>
                                                                <th>目标销售额</th>
                                                                <th>累积销售额</th>
                                                                <th>达成率</th>
                                                                <th rowspan="2" class="tRank">1</th>
                                                            </tr>
                                                            <tr>
                                                                <td>-</td><td>-</td><td>-</td></tr>
                                                                <tr>
                                                                    <th>目标销售额</th><th>累积销售额</th><th>达成率</th><th rowspan="2" class="tRank">2</th>
                                                                </tr>
                                                                <tr>
                                                                    <td>-</td><td>-</td><td>-</td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="person_data">
                                                        <div class="person_data_img">
                                                            <img src="images/pk.png"/><br/>
                                                            <h3>张可欣张可欣</h3>
                                                        </div>
                                                        <div class="person_data_table">
                                                            <table>
                                                                <tr>
                                                                    <th>目标销售额</th>
                                                                    <th>累积销售额</th>
                                                                    <th>达成率</th>
                                                                    <th rowspan="2" class="tRank">1</th>
                                                                </tr>
                                                                <tr>
                                                                    <td>-</td><td>-</td><td>-</td></tr>
                                                                    <tr>
                                                                        <th>目标销售额</th><th>累积销售额</th><th>达成率</th><th rowspan="2" class="tRank">2</th>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>-</td><td>-</td><td>-</td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="hide_r">
                                                    <h3 title="收起"><span class="hide_sales_talk">收起</span></h3>
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
                                                                            <td><span>
                                                                                <?php echo isset($sale_stats['sku_count']) ? number_format($sale_stats['sku_count']) : 0 ; ?>(子) /
                                                                                <?php echo isset($sale_stats['sku_main_count']) ? number_format($sale_stats['sku_main_count']) : 0 ; ?>(主)
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <span>
                                                                                <?php echo isset($sale_stats['pre_listing']) ? number_format($sale_stats['pre_listing']) : 0 ; ?>(子) /
                                                                                <?php echo isset($sale_stats['pre_main_listing']) ? number_format($sale_stats['pre_main_listing']) : 0 ; ?>(主)
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
                                                                                <?php echo isset($sale_stats['pre_count']) ? number_format($sale_stats['pre_count']) : 0 ; ?>(子) /
                                                                                <?php echo isset($sale_stats['pre_main_count']) ? number_format($sale_stats['pre_main_count']) : 0 ; ?>(主)
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <span>
                                                                                <?php echo isset($sale_stats['was_listing']) ? number_format($sale_stats['was_listing']) : 0 ; ?>(子) /
                                                                                <?php echo isset($sale_stats['was_main_listing']) ? number_format($sale_stats['was_main_listing']) : 0 ; ?>(主)
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
                                                                                <?php echo isset($sale_stats['sales_count']) ? number_format($sale_stats['sales_count']) : 0 ; ?>(子) /
                                                                                <?php echo isset($sale_stats['sales_main_count']) ? number_format($sale_stats['sales_main_count']) : 0 ; ?>(主)
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <span>
                                                                                <?php echo isset($sale_stats['clean_count']) ? number_format($sale_stats['clean_count']) : 0 ; ?>(子) /
                                                                                <?php echo isset($sale_stats['clean_main_count']) ? number_format($sale_stats['clean_main_count']) : 0 ; ?>(主)
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
                                                                        <td>
                                                                            <span><?php echo isset($ordersInfo['order_num']) ? number_format($ordersInfo['order_num']) : 0; ?></span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>当月取消订单数</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <span><?php echo isset($cancelOrdersInfo['cancel_num']) ? number_format($cancelOrdersInfo['cancel_num']) : 0; ?></span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>当月包裹重发数</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <span><?php echo isset($repeatInfo['repeat_qty']) ? number_format($repeatInfo['repeat_qty']) : 0; ?></span>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="total_day">
                                                            <table>
                                                                <tbody>
                                                                    <tr>
                                                                        <td>昨日新订单</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <span><?php echo isset($yesterdayOrders['order_quantity']) ? number_format($yesterdayOrders['order_quantity']) : 0; ?></span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>待处理亏损订单</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <span><?php echo isset($exceptionOrders['exp_qty']) ? number_format($exceptionOrders['exp_qty']) : 0; ?></span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>待发货订单</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <span><?php echo isset($noShippedOrders['order_num']) ? number_format($noShippedOrders['order_num']) : 0; ?></span>
                                                                        </td>
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
                                                                        <td><span>-</span></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>优化七日动销率</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><span><?php echo isset($optimizationRate['mov_rate']) ? number_format($optimizationRate['mov_rate']*100, 2) : 0?>%</span></td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="condition_table">
                                                <table border="1">
                                                    <tr>
                                                        <th rowspan="2">销售人员</th>
                                                        <th colspan="9">刊登状况</th>
                                                        <th colspan="4">昨日销售状况</th>
                                                        <th colspan="4">30天销售状况</th>
                                                    </tr>
                                                    <tr>
                                                        <th>分配数（主）</th>
                                                        <th>分配数（子）</th>
                                                        <th>已刊数</th>
                                                        <th>未刊数</th>
                                                        <th>刊登率</th>
                                                        <th>收藏量</th>
                                                        <th>浏览量</th>
                                                        <th>刊登七日动销率</th>
                                                        <th>优化七日动销率</th>
                                                        <th>销售额</th>
                                                        <th>净利润</th>
                                                        <th>订单量</th>
                                                        <th>动销率</th>
                                                        <th>销售额</th>
                                                        <th>净利润</th>
                                                        <th>订单量</th>
                                                        <th>动销率</th>
                                                    </tr>
                                                    <?php if (!empty($data_list)) {
                                                    foreach ($data_list as $ke => $va) {
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $va['seller_name']; ?></td>
                                                        <td><?php echo number_format($va['sku_main_amount']); ?></td>
                                                        <td><?php echo number_format($va['sku_amount']); ?></td>
                                                        <td><?php echo number_format($va['was_listing']); ?></td>
                                                        <td><?php echo number_format($va['wait_listing']); ?></td>
                                                        <td><?php echo (0 < $va['was_listing'] && $va['sku_amount']) ? round(($va['was_listing'] / $va['sku_amount'])*100, 2) : 0 ; ?>%</td>
                                                        <td><?php echo number_format($va['collect_amount']); ?></td>
                                                        <td><?php echo number_format($va['view_amount']); ?></td>
                                                        <td><?php echo number_format($va['listing_sale_rate']*100, 2); ?>%</td>
                                                        <td><?php echo number_format($va['optimization_sale_rate']*100, 2); ?>%</td>
                                                        <td><?php echo number_format($va['y_sales'],2); ?></td>
                                                        <td><?php echo number_format($va['y_profit'],2); ?></td>
                                                        <td><?php echo number_format($va['y_orders']); ?></td>
                                                        <td><?php echo number_format($va['y_sales_rate']*100, 2); ?>%</td>
                                                        <td><?php echo number_format($va['t_sales'],2); ?></td>
                                                        <td><?php echo number_format($va['t_profit'],2); ?></td>
                                                        <td><?php echo number_format($va['t_orders']); ?></td>
                                                        <td><?php echo number_format($va['t_sales_rate']*100, 2); ?>%</td>
                                                    </tr>
                                                    <?php
                                                    }
                                                    } ?>
                                                </table>
                                                <br />
                                                <h3 class="leader_link"><a href="<?php echo Yii::app()->createUrl('/products/userplatformpublishreports/list'); ?>"
                                                target="navTab" rel="userplatformpublishreports_list" title="查看部门销售状态统计">查看部门销售状态统计</a></h3>
                                            </div>
                                        </div>