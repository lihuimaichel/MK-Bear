<?php
/**
 * @desc 分类树视图
 * @author Gordon
 */
?>
<div class="pageContent">
    <div class="pageFormContent" layoutH="58">
        <ul class="tree expand categoryTree">
            <?php foreach($categories as $categoryID=>$category):?>
            <li>
                <a href="javascript:void(0)" onClick="lookNextCategory(this)" categoryid="<?php echo $categoryID; ?>" level="1"><?php echo $category['category_name'];?></a>
			</li>
			<?php endforeach;?>
		</ul>
	</div>
	
	<div class="formBar">
		<ul>
			<li><div class="button"><div class="buttonContent"><button class="close" type="button"><?php echo Yii::t('system', 'close');?></button></div></div></li>
		</ul>
	</div>
	<script>
		function lookNextCategory(self){
			var parentId = $(self).attr('categoryid');
			var $parent = $(self).parent().parent();
			var level = $(self).attr('level');
			$.ajax({
        		type: 'post',
        		data: {category_id:parentId,site_id:<?php echo $siteID;?>},
        		url: 'ebay/ebaycategory/categorytree',
        		success:function(result){
            		var node = '';
            		for(var j=0;j<level;j++){
            			node += '<div class="node"></div>';
                	}
                	var html = '';
                	$.each(result, function(i,item){
                		html += '<li>'
                						+ '<div class="">'
                						+ '<div class="line"></div>'
                						+ node
                						+ '<a onClick="lookNextCategory(this)" href="javascript:void(0)" categoryid="'+item.category_id+'" level="'+(parseInt(level)+1)+'">'+item.category_name+'</a>'
                					+ '</div>'	
                				+ '</li>';
                    });
        			$parent.append('<ul>'+html+'</ul>');
        		},
        		dataType: 'json'
        	});
			//$.bringBack({id:'1', districtName:'222', cityName:'222'})
		}
    </script>
</div>