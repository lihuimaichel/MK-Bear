<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$was_listing_total = 0;
$pre_listing_total = 0;
$wait_listing_total = 0;
$collect_amount_total = 0;
$view_amount_total = 0;
$y_sales_total = 0;
$y_earn_total = 0;
$y_orders_total = 0;
$t_sales_total = 0;
$t_earn_total = 0;
$t_orders_total = 0;
$category = array(1,2,3,4,5,6);
$productStatus = "3,4,6";
?>
<style>
    .row label {
        padding: 4px;
    }

    .selectType {
        margin-left: 8px;
        font-size: 14px;
        color: red;
    }
</style>
<div class="pageFormContent">
	<?php if (!empty($data)) { ?>
        <div>
            <div class="row gridThead">
                <table class="dataintable" width="100%" cellspacing="1" cellpadding="1" border="0"
                       style="text-align: center;">
                    <thead>
                    <tr>
                        <td colspan="2">账号信息</td>
                        <td colspan="8">刊登情况</td>
                        <td colspan="2">任务动销率</td>
                        <td colspan="4">昨日</td>
                        <td colspan="4">30天</td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>账号</td>
                        <td>站点</td>
                        <td>分配数（主）</td>
                        <td>分配数（子）</td>
                        <td>已刊登</td>
                        <td>未刊登</td>
                        <td>预刊登</td>
                        <td>刊登率</td>
                        <td>浏览量</td>
                        <td>收藏量</td>
                        <td>刊登七日动销率</td>
                        <td>优化七日动销率</td>
                        <td>销售额</td>
                        <td>净利润</td>
                        <td>订单量</td>
                        <td>动销率</td>
                        <td>销售额</td>
                        <td>净利润</td>
                        <td>订单量</td>
                        <td>动销率</td>
                    </tr>
					<?php
					foreach ($data as $k => $v) {?>
                        <tr>
                            <td><?php echo $v['account_name']; ?></td>
                            <td><?php echo $v['site_name']; ?></td>
                            <td><?php echo $v['sku_main_amount']; ?></td>
                            <td><?php echo $v['sku_amount']; ?></td>
                            <td>
                                <?php echo number_format($v['was_listing']); ?>
                                <!--
                                <a href="javascript:;" onclick="showUserPlatformSkuCount('<?php echo join(",", $category);?>','EB','<?php echo $v['account_id'];?>','<?php echo $v['site_name'];?>','<?php echo ProductPlatformListing::ONLINE_STATUS;?>','<?php echo $productStatus;?>')" style="color:blue" title="">
									<?php $was_listing_total += $v['was_listing'];
									echo $v['was_listing']; ?>
                                </a>
                                -->
                            </td>
                            <td>
                                <a href="<?php echo Yii::app()->createUrl('/task/taskexport/exportWaitListing/account_id/'.$v['account_id'].'/account_name/'.$v['account_name'].'/site/'.$v['site_name']); ?>"><?php echo number_format($v['wait_listing']); ?></a>
                                <!--
                                <a href="javascript:;" onclick="showUserPlatformSkuCount('<?php echo join(",", $category);?>','EB','<?php echo $v['account_id'];?>','<?php echo $v['site_name'];?>','<?php echo ProductPlatformListing::UMLINE_STATUS;?>','<?php echo $productStatus;?>')" style="color:blue" title="">
									<?php $wait_listing_total += $v['wait_listing'];
									echo $v['wait_listing']; ?>
                                </a>
                                -->
                            </td>
                            <td>
                                <?php echo number_format($v['pre_listing']); ?>
                                <!--
                                <a href="javascript:;" onclick="showUserPlatformSkuCount('<?php echo join(",", $category);?>','EB','<?php echo $v['account_id'];?>','<?php echo $v['site_name'];?>','<?php echo ProductPlatformListing::FAILURE_STATUS;?>','<?php echo $productStatus;?>')" style="color:blue" title="">
									<?php $pre_listing_total += $v['pre_listing'];
									echo $v['pre_listing']; ?>
                                </a>
                                -->
                            </td>
                            <td><?php echo (0 < $v['sku_amount']) ? round(($v['was_listing'] / $v['sku_amount']) * 100, 2) : 0; ?>%</td>
                            <td><?php $view_amount_total += $v['view_amount'];
								echo $v['view_amount']; ?></td>
                            <td><?php $collect_amount_total += $v['collect_amount'];
								echo $v['collect_amount']; ?></td>
                            <td><?php echo number_format($v['listing_sale_rate']*100, 2); ?>%</td>
                            <td><?php echo number_format($v['optimization_sale_rate']*100, 2); ?>%</td>
                            <td><?php $y_sales_total += $v['y_sales'];
								echo $v['y_sales']; ?></td>
                            <td><?php $y_earn_total += $v['y_earn'];
								echo $v['y_earn']; ?></td>
                            <td><?php $y_orders_total += $v['y_orders'];
								echo $v['y_orders']; ?></td>
                            <td><?php echo number_format($v['y_sales_rate']*100, 2); ?>%</td>
                            <td><?php $t_sales_total += $v['t_sales'];
								echo $v['t_sales']; ?></td>
                            <td><?php $t_earn_total += $v['t_earn'];
								echo $v['t_earn']; ?></td>
                            <td><?php $t_orders_total += $v['t_orders'];
								echo $v['t_orders']; ?></td>
                            <td><?php echo number_format($v['t_sales_rate']*100,2); ?>%</td>
                        </tr>
						<?php
					} ?>
                    <tr>
                        <td colspan="2">小计</td>
                        <td>-</td>
                        <td>-</td>
                        <td><?php echo number_format($was_listing_total); ?></td>
                        <td><?php echo number_format($wait_listing_total); ?></td>
                        <td><?php echo number_format($pre_listing_total); ?></td>
                        <td>-</td>
                        <td><?php echo number_format($view_amount_total); ?></td>
                        <td><?php echo number_format($collect_amount_total); ?></td>
                        <td>-</td>
                        <td>-</td>
                        <td><?php echo number_format($y_sales_total,2); ?></td>
                        <td><?php echo number_format($y_earn_total,2); ?></td>
                        <td><?php echo number_format($y_orders_total,2); ?></td>
                        <td>-</td>
                        <td><?php echo number_format($t_sales_total,2); ?></td>
                        <td><?php echo number_format($t_earn_total,2); ?></td>
                        <td><?php echo number_format($t_orders_total,2); ?></td>
                        <td>-</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="clear"></div>
        </div>
		<?php
	}
	?>
</div>

<script type="text/javascript">
    function showUserPlatformSkuCount(category_id, platformCode, account_id, site, online_status, productStatus) {
        var urls = '';
        if (!productStatus) {
            productStatus = '3,4,6';
        }
        var seller_user_id = $("#seller_user_id").val();
        if (undefined == seller_user_id) {
            seller_user_id = "<?php echo Yii::app()->user->id;?>";
        }
        if (online_status == 3) {
            urls = '/products/producttoaccountrelation/list/target/dialog/category_id/' + 1 + '/platform_code/' + platformCode + '/site/' + site + '/account_id/' + account_id + '/seller_user_id/'+seller_user_id+
            '/dept/'+3+
            '/ispublish/' + online_status + '/product_status_str/' + productStatus + '/product_status/' + productStatus;
        } else {
            urls = '/products/productplatform/list/target/dialog/category_id/' + category_id + '/platform_code/' + platformCode + '/site/' + site + '/account_id/' + account_id + '/seller_user_id/'+seller_user_id+
            '/ispublish/' + online_status + '/product_status_str/' + productStatus;

        }

        $.pdialog.open(urls, 'showUserPlatformSkuCount', '平台SKU数量明细', {
            width: 900,
            height: 600,
            mask: true,
            fresh: true,
            'rel': 'showUserPlatformSkuCount'
        });
        return;
    }
</script>