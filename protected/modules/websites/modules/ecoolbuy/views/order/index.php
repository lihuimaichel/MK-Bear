<style>
fieldset { padding 5px;margin:20 px;}
</style>
<fieldset>
<legend> <?php echo Yii::t('websites', 'Simple order download')?> </legend>
<div class="formBar">
    <ul>
    	<li>
    		<div class="row">
				<span>网站订单号 ：</span><input type="text" value='' id="platformOrderId" name='platformOrderId' />
			</div>
            <div class="buttonActive">
                <div class="buttonContent">
                    <a href="" target="ajax" rel="simple_order_download_result" onclick="downloadOrder(this);"><button type="submit"><?php echo Yii::t('common', 'Download')?></button></a>
                </div>
            </div>
            <script>
            	function downloadOrder(el){
                	//reset
                	$('#simple_order_download_result').html('');
                	var OrderHref = '<?php echo $this->createUrl("DownloadOrder");?>';
                	var OrderId = $('#platformOrderId').val();
            		el.href = OrderHref + '&platformOrderId=' + OrderId;
            	}	
            </script>
        </li>
    </ul>
</div>
<div id='simple_order_download_result'></div>
</fieldset>
<fieldset>
<legend> <?php echo Yii::t('websites', 'List order download')?> </legend>
<div class="formBar">
    <ul>
    	<li>
    		<div class="row">
				<a href="<?php echo $this->createUrl("CronDownload");?>"  target="ajax" rel="list_order_download_result" >批量下载订单</a>
			</div>
        </li>
    </ul>
</div>
<div id='list_order_download_result'></div>
</fieldset>