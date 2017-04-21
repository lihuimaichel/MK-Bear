<?php Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl.'/js/custom/ueb.system.js');?>
<style type="text/css">
    ul.rightTools {float:right; display:block;}
    ul.rightTools li{float:left; display:block; margin-left:5px}
</style>
<script type="text/javascript">
    $('#siderBarMenubox').siderBarMenu();
</script>
<div class="pageContent" style="padding:5px;">                       
    <div>
        <div layoutH="10" style="float:left; display:block; overflow:auto; width:240px; border:solid 1px #CCC; line-height:21px; background:#fff">
            <div class="panelBar">
                <ul class="toolBar">
                    <li></li>          
                </ul>
            </div>
            <?php echo $this->renderPartial('logs.components.views.SiderBarMenubox'); ?>                                   
        </div>
        <div id="apilogBox" class="unitBox" style="margin-left:246px;"></div>
    </div>                     
</div>
<script type="text/javascript">
    $(function(){           
        setTimeout(function(){           
            $('#siderBarMenubox div a:first').trigger('click');
        }, 10);
    });
</script>





