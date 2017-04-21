<div id="leftside">
    <div id="sidebar_s">
        <div class="collapse">
            <div class="toggleCollapse"><div></div></div>
        </div>
    </div>
    <div id="sidebar">
        <div class="toggleCollapse"><h2>主菜单</h2><div>收缩</div></div>
        <div class="accordion" fillSpace="sidebar" >
            <?php echo $this->renderPartial('systems.components.views.NomalSider'); ?>	
            <?php //echo $this->renderPartial('systems.components.views.HistorySider'); ?>         

        </div>   
    </div>
</div>