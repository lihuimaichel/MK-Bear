<?php
/**
 * @desc 搜索账号
 * @author ketu.lai
 * @date 2017/02/20
 */
?>
<form id="pagerForm" action="<?php echo Yii::app()->createUrl($this->route);?>">
    <input type="hidden" name="pageNum" value="1" />
    <input type="hidden" name="numPerPage" value="${model.numPerPage}" />
    <input type="hidden" name="orderField" value="${param.orderField}" />
    <input type="hidden" name="orderDirection" value="${param.orderDirection}" />
</form>

<div class="pageHeader">
    <form rel="pagerForm" method="post" action="<?php echo Yii::app()->createUrl($this->route);?>" onsubmit="return dwzSearch(this, 'dialog');">
        <div class="searchBar">
            <ul class="searchContent">
                <li>
                    <label><?php echo Yii::t("ebay", "Account Name");?>:</label>
                    <input class="textInput" name="short_name" type="text">
                </li>
                <li><div class="buttonActive"><div class="buttonContent"><button type="submit"><?php echo Yii::t("system", "Search");?></button></div></div></li>
            </ul>
        </div>
    </form>
</div>
<div class="pageContent">

    <table class="table" layoutH="97" targetType="dialog">
        <thead>
        <tr>
            <th orderfield="orgName"><?php echo Yii::t("ebay", "Account Name");?></th>
            <th width="80"><?php echo Yii::t("system", "Select");?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($accountList as $account):?>
        <tr>
            <td><?php echo $account['short_name'];?></td>

            <td>
                <a class="btnSelect" href="javascript:$.bringBack({id:'<?php echo $account['id'];?>', storeName:'<?php echo $account['short_name']?>'})" title="<?php echo Yii::t("system", "Select");?>"><?php echo Yii::t("system", "Select");?></a>
            </td>
        </tr>
        <?php endforeach;?>

        </tbody>
    </table>

    <div class="panelBar">
        <div class="pages">

        </div>
        <div class="pagination" targetType="dialog" totalCount="2" numPerPage="10" pageNumShown="1" currentPage="1"></div>
    </div>
</div>