<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

require_once 'class-xh-social-channel-qq.php';
require_once 'class-xh-social-channel-qzone.php';
/**
 * 微信登录
 * 
 * @author ranj
 * @since 1.0.0
 */
class XH_Social_Add_On_Social_QQ extends Abstract_XH_Social_Add_Ons{
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var XH_Social_Add_On_Social_QQ
     */
    private static $_instance = null;
    /**
     * Main Social Instance.
     *
     * @since 1.0.0
     * @static
     * @return XH_Social_Add_On_Social_QQ
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private function __construct(){
        $this->id='add_ons_social_qq';
        $this->title=__('QQ',XH_SOCIAL);
        $this->description=__('使用QQ快速登录，请在QQ互联中申请网站应用',XH_SOCIAL);
        $this->version='1.0.3';
        $this->setting_uri = admin_url('admin.php?page=social_page_default&section=menu_default_channel&sub=social_qq');
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
        $api =new XH_Social_Channel_QQ_Model();
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
        $action ="xh_social_{$this->id}";
        $datas=shortcode_atts(array(
            'notice_str'=>null,
            'action'=>$action,
            $action=>null,
            'tab'=>null
        ), stripslashes_deep($_REQUEST));
        
        if(isset($_REQUEST['uid'])){
            $datas['uid']=stripslashes($_REQUEST['uid']);
        }
           
        if(!XH_Social::instance()->WP->ajax_validate($datas,isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
           XH_Social::instance()->WP->wp_die(XH_Social_Error::err_code(701));
           exit;
        }
     
        switch ($datas['tab']){
            case 'authorization':
                $wp_user_id=isset($datas['uid'])?$datas['uid']:0;
                if(
                    //wp_user_id>0 且登录用户id不等于wp_user_id
                    ($wp_user_id>0&&is_user_logged_in()&&$wp_user_id!=get_current_user_id())
                    ||
                    //已登录的情况
                    $wp_user_id<=0&&is_user_logged_in()
                    ){
                    
                    if(isset($_GET['social_logout'])){
                        wp_redirect(wp_logout_url(XH_Social_Helper_Uri::get_location_uri()));
                        exit;
                    }
                    wp_logout();
                    
                    $params = array();
                    $url = XH_Social_Helper_Uri::get_uri_without_params(XH_Social_Helper_Uri::get_location_uri(),$params);
                    $params['social_logout']=1;
                    wp_redirect($url."?".http_build_query($params));
                    exit;
                }
                
                $redirect_uri='';
                $redirect_uri=apply_filters('xh_social_channel_qq_authorization',$redirect_uri,$datas);
                if(!empty($redirect_uri)){
                    wp_redirect($redirect_uri);
                    exit;
                }
                
                $redirect_uri = XH_Social_Channel_QQ::instance()->process_authorization_callback($wp_user_id);
                $error = XH_Social::instance()->WP->get_wp_error($redirect_uri);
                if(!empty($error)){
                    XH_Social::instance()->WP->wp_die($error);
                    exit;
                }
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
        $channels[]=XH_Social_Channel_QQ::instance();
        $channels[]=XH_Social_Channel_Qzone::instance();
        return $channels;
    }
    
    
    /**
     * 注册管理菜单
     * @param array $menus
     * @return array
     */
    public function add_channel_menus($menus){
        $menus[]=XH_Social_Channel_QQ::instance();
        return $menus;
    }
    
}

return XH_Social_Add_On_Social_QQ::instance();
?>