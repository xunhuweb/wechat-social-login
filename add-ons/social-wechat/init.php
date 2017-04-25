<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

require_once 'class-xh-social-channel-wechat.php';

/**
 * 微信登录
 * 
 * @author ranj
 * @since 1.0.0
 */
class XH_Social_Add_On_Social_Wechat extends Abstract_XH_Social_Add_Ons{
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var XH_Social_Add_On_Social_Wechat
     */
    private static $_instance = null;
    /**
     * Main Social Instance.
     *
     * @since 1.0.0
     * @static
     * @return XH_Social_Add_On_Social_Wechat
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private function __construct(){
        $this->id='add_ons_social_wechat';
        $this->title=__('Wechat',XH_SOCIAL);
        $this->description=__('微信登录：PC端开放平台登录，微信端公众号登录。(支持公众平台(PC+微信端)登录，请使用<a href="https://www.wpweixin.net/product/1135.html">微信扩展高级版</a>)',XH_SOCIAL);
        $this->version='1.0.2';
        $this->setting_uri = admin_url('admin.php?page=social_page_default&section=menu_default_channel&sub=social_wechat');
        $this->min_core_version = '1.0.0';
        $this->author=__('xunhuweb',XH_SOCIAL);
        $this->author_uri='https://www.wpweixin.net';
    }

    public function on_update($old_version){
        if(version_compare($old_version,'1.0.1','<' )){
            try {
                $db = new XH_Social_Channel_Wechat_Model();
                $db->on_version_101();
            } catch (Exception $e) {
                XH_Social::instance()->WP->wp_die($e->getMessage());
            }
        }
        
        if(version_compare($old_version,'1.0.2','<' )){
            //把之前的跨域登录设置移植过来
            $api = XH_Social::instance()->get_available_addon('wechat_social_add_ons_social_wechat_ext');
            if($api){
                XH_Social_Channel_Wechat::instance()->update_option_array(array(
                    'mp_enabled_cross_domain'=>$api->get_option('enabled_cross_domain')=='enabled_cross_domain'?'mp_enabled_cross_domain':'mp_cross_domain_disabled',
                    'mp_cross_domain_url'=>$api->get_option('cross_domain_url')
                ));
            }
        }
    }
    
    public function on_load(){
        add_filter('xh_social_ajax', array($this,'ajax'),10,1);
        add_filter('xh_social_channels', array($this,'add_channels'));
        add_filter('xh_social_admin_menu_menu_default_channel', array($this,'add_channel_menus'),10,1);
        add_filter('xh_social_channel_wechat_login_get_authorization_uri', array($this,'wechat_login_get_authorization_uri'),10,5);
    }

    public function on_install(){
        $model =new XH_Social_Channel_Wechat_Model();
        $model->init();
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

    public function wechat_login_get_authorization_uri($uri,$redirect_uri,$state,$uid,$wp_user_id){
        $api = XH_Social_Channel_Wechat::instance();
        if("{$state}_cross_domain_enabled"!=$api->get_option("{$state}_enabled_cross_domain")){
            return $uri;
        }

        $params = array();
        $cross_domain_url = XH_Social_Helper_Uri::get_uri_without_params( $api->get_option("{$state}_cross_domain_url"),$params);
        $params['callback']=$redirect_uri;
        $params['hash'] = XH_Social_Helper::generate_hash(array('callback'=>$redirect_uri), $api->get_option("{$state}_secret"));
    
        return $cross_domain_url."?".http_build_query($params);
    }
    
    public function do_ajax(){
        $datas = array(
            'notice_str'=>isset($_REQUEST['notice_str'])?XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['notice_str']):'',
            'action'=>isset($_REQUEST['action'])?XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['action']):'',
            'tab'=>isset($_REQUEST['tab'])?XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['tab']):'',
            'uid'=>isset($_REQUEST['uid'])?XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['uid']):'',
            's'=>isset($_REQUEST['s'])?XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['s']):''
        );
        
        if(isset($_REQUEST['uuid'])){
            $datas['uuid']=XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['uuid']);
        }
        
        $hash=XH_Social_Helper::generate_hash($datas, XH_Social::instance()->get_hash_key());
        if(!isset($_REQUEST['hash'])||$hash!=XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['hash'])){
            echo (XH_Social_Error::err_code(701)->to_json());
            exit;
        }
        
        switch ($datas['tab']){
            case 'authorization':
                $wp_user_id = isset($datas['uuid'])?$datas['uuid']:0;
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
                $redirect_uri=apply_filters('xh_social_channel_wechat_authorization',$redirect_uri,$datas);
                if(!empty($redirect_uri)){
                    wp_redirect($redirect_uri);
                    exit;
                }
                
                $login_location_uri=XH_Social::instance()->session->get('social_login_location_uri');
                if(empty($login_location_uri)){
                    $login_location_uri = home_url('/');
                }
                
                if(isset($_POST['userdata'])&&isset($_POST['user_hash'])){
                    $userdata = isset($_POST['userdata'])? base64_decode($_POST['userdata']):null;
                    $user_hash = isset($_POST['user_hash'])?$_POST['user_hash']:'';
                    $api = XH_Social_Channel_Wechat::instance();
                    
                    $userdata =$userdata?json_decode($userdata,true):null;
                    if(!$userdata){
                        wp_redirect($login_location_uri);
                        exit;
                    }
                    
                    $ohash =XH_Social_Helper::generate_hash($userdata, $api->get_option($datas['s'].'_secret'));
                    if($user_hash!=$ohash){
                        XH_Social::instance()->WP->wp_die(__('Please check cross-domain app secret config(equal to current website app secret)!',XH_SOCIAL));
                    }
                     
                  try {
                      $ext_user_id =$api->create_ext_user_info($datas['s'],$userdata,$wp_user_id, $datas['uid']);
                      $login_location_uri =$api->process_login($ext_user_id);
                  } catch (Exception $e) {
                      XH_Social_Log::error($e);
                      XH_Social::instance()->WP->wp_die($e);
                      exit;
                  }
                    $error = XH_Social::instance()->WP->get_wp_error($redirect_uri);
                    if(!empty($error)){
                        XH_Social::instance()->WP->wp_die($error);
                        exit;
                    }
                    
                    wp_redirect($login_location_uri);
                    exit;
                }
                
                $redirect_uri = XH_Social_Channel_Wechat::instance()->process_authorization_callback($wp_user_id,$datas['uid']);
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
        $channels[]=XH_Social_Channel_Wechat::instance();
        return $channels;
    }
   
    /**
     * 注册管理菜单
     * @param array $menus
     * @return array
     */
    public function add_channel_menus($menus){
        $menus[]=XH_Social_Channel_Wechat::instance();
        return $menus;
    }
    
}

return XH_Social_Add_On_Social_Wechat::instance();
?>