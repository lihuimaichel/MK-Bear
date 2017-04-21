<?php
/**
 * @desc 分类选择视图
 * @author Gordon
 */
?>
<style>
.pageFormContent .categorySelect label{float:none;width:auto;}
.categorySelect {margin:5px 0;}
</style>
<div class="pageContent">
    <div class="pageFormContent" layoutH="58">
        <?php if(!empty($categories)):?>
            <?php foreach($categories as $categoryID=>$category):?>
            <div class="row categorySelect">
                <input class="categorySel" value="<?php echo $categoryID;?>" type="radio" id="categorySel_<?php echo $categoryID; ?>" />
                <label for="categorySel_<?php echo $categoryID; ?>"><?php echo $category;?></label>
            </div>
            <?php endforeach;?>
        <?php else:?>
        <div><?php echo Yii::t('ebay', 'No History Category') ?></div>
        <?php endif;?>
	</div>
	
	<div class="formBar">
		<ul>
			<li><div class="button"><div class="buttonContent"><button class="close categorySelClose" type="button"><?php echo Yii::t('system', 'close');?></button></div></div></li>
		</ul>
	</div>
</div>
<script>
$('input.categorySel').click(function(){
	var categoryName = $(this).next('label').html();
	$.bringBack({category_id:$(this).val(), category_name:categoryName});
	$('#category_name').html(categoryName);
});
</script>