<style type="text/css">
div#PreviewBox{
  position:absolute;
  padding-left:6px;
  display: none;
  Z-INDEX:2006;
}
div#PreviewBox span{
  width:7px;
  height:13px;
  position:absolute;
  left:0px;
  top:9px;
  background:url() 0 0 no-repeat;
}
div#PreviewBox div.Picture{
  float:left;
  border:1px #ccc solid;
  background:#FFF;
}
div#PreviewBox div.Picture div{
  border:4px #e8e8e8 solid;
}
div#PreviewBox div.Picture div a img{
  margin:9px;
  /*border:1px #b6b6b6 solid;*/
  display: block;
  max-height: 635px;
  max-width: 635px;
}
</style>
<div id="PreviewBox" onmouseout="hidePreview(event);">
    <div class="Picture" onmouseout="hidePreview(event);">
        <span></span>
        <div>
            <a id="previewUrl" class="product_image" href="javascript:void(0)" target="_blank"><img oncontextmenu="return(false)" id="PreviewImage" src="about:blank" border="0" onmouseout="hidePreview(event);" /></a>
        </div>
    </div>
</div>
<script language="javascript" type="text/javascript">
var previewBox = document.getElementById('PreviewBox');
var previewImage = document.getElementById('PreviewImage');
var previewUrl = document.getElementById('previewUrl');
var previewFrom = null;
var previewTimeoutId = null;
var loadingImg = '';
</script>
