<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<span id="user_platform_publish">
	<div style="padding: 20px;">
		<form method="post" action="">
                <?php if ('admin' == $role) { ?>
                    <div style="width: 255px;" class="left h25 ml10">
				        销售部门:<select id="ProductMarketersManager_emp_dept"
                             name="ProductMarketersManager_emp_dept" onchange="getEmp(this)">
					    <option value="">请选择</option>
							<?php foreach($depList as $keys=>$values) {?>
                                <option value="<?php echo $keys?>"><?php echo $values;?></option>
							<?php }?>
                        </select>
			        </div>
                    <div style="width: 255px;" class="left h25 ml10">
                            负责人姓名:<select id="seller_user_id" name="seller_user_id">
                            <option value="">请选择</option>
                        </select>
			        </div>
                <?php } else { ?>
                <?php if (1 < count($users)) { ?>
                    <div style="width: 255px;" class="left h25 ml10">
                        负责人姓名:
                        <select id="seller_user_id" name="seller_user_id">
                            <option value="">请选择</option>
							<?php foreach ($users as $k => $v) { ?>
                                <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
							<?php } ?>
                        </select>
                    </div>
				<?php } ?>
            <?php }?>

            <div class="left h25 ml10  filterToggle">
				产品状态：
                <input type="checkbox" name="product_status" id="product_status_0" checked="checked"
                       value="<?php echo Product::STATUS_ON_SALE; ?>">
                    在售中
                <input type="checkbox" name="product_status" checked="checked" id="product_status_1"
                       value="<?php echo Product::STATUS_WAIT_CLEARANCE; ?>">
                    待清仓
                                <input type="checkbox" name="product_status" checked="checked" id="product_status_1"
                                       value="<?php echo Product::STATUS_PRE_ONLINE; ?>">
                    预上线
			</div>
			&nbsp;<input type="button" id="form_submit_sku_user" value="<?php echo Yii::t('system', '查询'); ?>"> <input
                    type="reset">
		</form>
		<br/>
		<div>
			&nbsp;&nbsp;&nbsp;&nbsp;<!--<a class="icon" href="javascript:;" onclick="ReportSelectesUser();"><button>导出统计结果</button></a>-->
                <span style="color: red">任务动销率，昨日，30天三栏数据不随产品状态变动</span>
		</div>
	</div>

	<div id="show_sku_list_user" style="padding-left: 30px;"></div>
</span>

<script>
    $(function () {
        $.ajax({
            type: "post",
            url: "/products/userplatformpublishreports/show",
            data: {'t': Math.random()},
            async: false,
            dataType: 'html',
            success: function (data) {
                if (data) {
                    $('#user_platform_publish #show_sku_list_user').html(data);
                }
            }
        });
    });

    $('#form_submit_sku_user').click(function () {
        var seller_user_id = $("#user_platform_publish #seller_user_id").val();
        var product_status = '';
        $("input:checkbox[name='product_status']:checked").each(function () {
            product_status += $(this).val() + ",";
        });

        $.ajax({
            type: "post",
            url: "/products/userplatformpublishreports/show",
            data: {'seller_user_id': seller_user_id, 'product_status': product_status},
            async: false,
            dataType: 'html',
            success: function (data) {
                if (data) {
                    $('#user_platform_publish #show_sku_list_user').html(data);
                }
            }
        });
    });

    function getAccount(obj){
        var strSite ='<option value="">所有</option>';
        if($(obj).val()){
            $.post('/orders/order/platformsiteoffer',{'platform':$(obj).val()},function(data){
                if(data !=null){
                    $.each(data,function(key,value){
                        strSite +='<option value="'+key+'">'+value+'</option>';
                    });
                }
                $(obj).parent().next().find("select").html(strSite);
            },'json');
        }else{
            $(obj).parent().next().find("select").html(strSite);
        }
    }

    function ReportSelectesUser() {
        var seller_user_id = $("#user_platform_publish #seller_user_id").val();
        var product_status = '';
        $("input:checkbox[name='product_status']:checked").each(function () {
            product_status += $(this).val() + ",";
        });
        if (!seller_user_id) {
            alertMsg.error("请选择销售员");
            return false;
        }
        //var url ="/products/userplatformpublishreports/report/sc_name_id/"+sc_name_id+"/product_status"+product_status;
        var url = "/products/userplatformpublishreports/reportXls/seller_user_id/" + seller_user_id + "/product_status/" + product_status;
        window.open(url);
    }

    function getEmp(obj){
        var strEmp ='<option value="">请选择</option>';
        if($(obj).val()){
            $.post('/users/users/deptempuser',{'dept':$(obj).val()},function(data){
                $.each(data,function(key,value){
                    strEmp +='<option value="'+key+'">'+value+'</option>';
                });
                $(obj).parent().next().find("select").html(strEmp);
            },'json');
        }else{
            $(obj).parent().next().find("select").html(strEmp);
        }
    }
</script>