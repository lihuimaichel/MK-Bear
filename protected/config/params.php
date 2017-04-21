<?php
/**
 * @desc 系统全局参数
 * @author Gordon
 */
const CN = 'Chinese';
const EN = 'english';
const DE = 'German';
const FR = 'French';


return array(

    //is admin
    'isAdmin' => 0,
    // this is used in error pages
    'adminEmail' => 'webmaster@example.com',
    // number of posts displayed per page
    'per_page_num' => 20,
    // maximum number of comments that can be displayed in recent comments portlet
    'recentCommentCount' => 10,
    // maximum number of tags that can be displayed in tag cloud portlet
    'tagCloudCount' => 20,  
    // the copyright information displayed in the footer section
    'copyrightInfo' => 'Copyright &copy; '.date('Y').' <a href="http://www.u-e-b.com" target="_blank">www.u-e-b.com</a> Inc. All Rights Reserved.',
    
    // default theme
    'theme' => 'default',
    // default timezone
    'timezone' => 'PRC',
    //default profile timing limit
    'profileTimingLimit' => 1,   
    //default Message notification interval 
    'msg_notify_interval' => 60,
    // default Message notification count
    'msg_notify_show_count' => 6,
    'filter_modules' => 'services',
    'multi_language' => array(CN=>CN,EN=>EN,DE=>DE),
    'tableToModel' => array('ueb_product_description'=>'Productdesc'),
    'old_Oa_System'          => '172.16.1.8'
    
);
