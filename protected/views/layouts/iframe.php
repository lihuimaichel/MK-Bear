<?php
if( isset($url) && isset($server) ):
    $htmlOpt = '';
    if(isset($htmlOption)){
        foreach($htmlOption as $key=>$option){
            $htmlOpt .= ' '.$key.'='.$option;
        }
    }
?>
<style>
#iframe{border:none;}
</style>
<iframe id="iframe" <?php echo $htmlOpt; ?> src="<?php echo Yii::app()->createRemoteUrl($url,$server,isset($params) ? $params : array()); ?>"></iframe>
<?php else:?>
<script type="text/javascript">
$(function(){
	alertMsg.warn('<?php echo Yii::t('system','Url Is Requeird') ?>');
});
</script>
<?php endif;?>