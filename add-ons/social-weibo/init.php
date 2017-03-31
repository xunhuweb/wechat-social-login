<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

require_once 'class-xh-social-channel-weibo.php';

/**
 * 微信登录
 * 
 * @author ranj
 * @since 1.0.0
 */
class XH_Social_Add_On_Social_Weibo extends Abstract_XH_Social_Add_Ons{
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var XH_Social_Add_On_Social_Weibo
     */
    private static $_instance = null;
    /**
     * Main Social Instance.
     *
     * @since 1.0.0
     * @static
     * @return XH_Social_Add_On_Social_Weibo
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private function __construct(){
        $this->id='add_ons_social_weibo';
        $this->title=__('Weibo',XH_SOCIAL);
        $this->description=__('使用微博帐号快速登录，请在open.weibo.com申请网站应用',XH_SOCIAL);
        $this->version='1.0.0';
        $this->setting_uri = admin_url('admin.php?page=social_page_default&section=menu_default_channel&sub=social_weibo');
        $this->min_core_version = '1.0.0';
        $this->author=__('xunhuweb',XH_SOCIAL);
        $this->author_uri='https://www.wpweixin.net';
    }

    public function on_load(){
        add_filter('xh_social_ajax', array($this,'ajax'),10,1);
        add_filter('xh_social_channels', array($this,'add_channels'));
        add_filter('xh_social_admin_menu_menu_default_channel', array($this,'add_channel_menus'),10,1);
    }

    public function on_install(){
        $api =new XH_Social_Channel_Weibo_Model();
        $api->init();
    }
    
    /**
     * ajax
     * @param array $shortcodes
     * @return array
     * @since 1.0.0
     */
    public function ajax($shortcodes){
        $shortcodes["xh_social_{$this->id}"]=array($this,'do_ajax');
        return $shortcodes;
    }
    
    public function do_ajax(){
        $datas = array(
            'notice_str'=>isset($_REQUEST['notice_str'])?XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['notice_str']):'',
            'action'=>isset($_REQUEST['action'])?XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['action']):'',
            'tab'=>isset($_REQUEST['tab'])?XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['tab']):'',
        );
         
        $hash=XH_Social_Helper::generate_hash($datas, XH_Social::instance()->get_hash_key());
        if(!isset($_REQUEST['hash'])||$hash!=XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['hash'])){
            echo (XH_Social_Error::err_code(701)->to_json());
            exit;
        }
        
        switch ($datas['tab']){
            case 'authorization':
                $redirect_uri = XH_Social_Channel_Weibo::instance()->process_authorization_callback();
                wp_redirect($redirect_uri);
                exit;
        }
    }
    /**
     * 注册登录接口
     * @param array $schames
     * @return array
     */
    public function add_channels($channels){
        $channels[]=XH_Social_Channel_Weibo::instance();
        return $channels;
    }
    
    /**
     * 注册管理菜单
     * @param array $menus
     * @return array
     */
    public function add_channel_menus($menus){
        $menus[]=XH_Social_Channel_Weibo::instance();
        return $menus;
    }
    
}

return XH_Social_Add_On_Social_Weibo::instance();
?>