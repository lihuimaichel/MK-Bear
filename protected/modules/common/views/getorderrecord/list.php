<div class="grid">
    <div class="gridHeader">
        <div class="gridThead">
            <table style="width:693px;">
                <thead>
                    <tr>
                        <th style="width:80px;" id="wish_product_statistic_c1" class="center"><div title="平台" class="gridCol">平台</div></th>
                        <th style="width: 80px; cursor: default;" id="wish_product_statistic_c2" class="center"><div title="时间" class="gridCol">时间</div></th>
                        <?php foreach( $date_list as $k => $date): ?>
                        <th style="width: 120px;" id="wish_product_statistic_c4" class="center"><div title="" class="gridCol"><?php echo $k; ?></div></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <div style="width: 1543px; height: 254.083px; overflow: auto;" layouth="150" class="gridScroller" id="gridScroller">
        <div class="gridTbody">
            <table style="width:693px;">
                <tbody>
                    <?php foreach( $list as $platform => $value_list): ?>
                    <tr class="odd">
                        <td style="width:80px" rowspan="2" ><?php echo $platform; ?></td>
                        <td style="width:80px" >失败次数</td>
                        <?php foreach( $value_list as $date => $value): ?>
                        <td style="width:120px"><?php echo $value['fail_times']; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr class="even">
                        <td style="width:80px" >拉单次数</td>
                        <?php foreach( $value_list as $date => $value): ?>
                        <td style="width:120px"><?php echo $value['order_num']; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>