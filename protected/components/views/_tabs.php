<div class="tabs" currentIndex=<?php echo $currentIndex;?> eventType=<?php echo $eventType;?>  >
    <div class="tabsHeader">
        <div class="tabsHeaderContent">
            <ul>
                <?php foreach ( $tabsHeader as $key => $val ):?>
                <?php $htmlOptions = isset( $val['htmlOptions']) ? $val['htmlOptions'] : array();?>
                <li>
                    <?php echo CHtml::link('<span>'.$val['text'].'</span>', $val['url'], $htmlOptions)?>
                </li>              
                <?php endforeach;?>
            </ul>
        </div>
    </div>
    <div class="tabsContent" style="height:150px;" layoutH="40">
        <?php foreach ($tabsContent as $key => $val ):?>
        <?php $htmlOptions = isset( $val['htmlOptions']) ? $val['htmlOptions'] : array();?>
        <?php echo CHtml::openTag('div', $htmlOptions);?>
            <?php echo $val['content'];?>
        <?php echo CHtml::closeTag('div');?>
        <?php endforeach;?>             
    </div>
    <div class="tabsFooter">
        <div class="tabsFooterContent"></div>
    </div>
</div>
