<?php
/**
 * @desc 权限配置文件
 * @author Gordon
 * @since 2015-07-16
 */
class SystemsPriv extends CPrivApplication{
    
    /** @var 模块名 */
    public $_module = 'systems';
    
    public function init(){
        $this->setPriv(array(
            'menu' =>array(
                array(//创建菜单
                    'text'      => Yii::t('priv','Create Menu'),
                    'action'    => 'create'
                ),
             ),
        ));
    }
}