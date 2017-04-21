<div class="pikachoose">
    <ul id="pikame">
        <?php foreach($imgList as $k=>$img):?>
		<li class="clip">
            <a href="javascript:void(0);">
                <img src="<?php echo $img;?>"/>
            </a>
            <span><?php echo $k;?></span>
        </li>
		<?php endforeach;?>
	</ul>
</div>

<script language="javascript">
	$(function(){
		$("#pikame").PikaChoose();
	});
</script>

