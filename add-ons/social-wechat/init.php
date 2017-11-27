<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

require_once 'class-xh-social-channel-wechat.php';
require_once XH_SOCIAL_DIR.'/includes/abstracts/abstract-xh-add-ons.php';

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
     * 插件目录
     * @var string
     * @since 1.0.0
     */
    private $dir;
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
        $this->version='1.0.4';
        $this->setting_uri = admin_url('admin.php?page=social_page_default&section=menu_default_channel&sub=social_wechat');
        $this->min_core_version = '1.0.0';
        $this->author=__('xunhuweb',XH_SOCIAL);
        $this->author_uri='https://www.wpweixin.net';
        $this->dir= rtrim ( trailingslashit( dirname( __FILE__ ) ), '/' );
    }

    public function on_update($old_version){
//         if(version_compare($old_version,'1.0.1','<' )){
//             try {
//                 $db = new XH_Social_Channel_Wechat_Model();
//                 $db->on_version_101();
//             } catch (Exception $e) {
//                 XH_Social::instance()->WP->wp_die($e->getMessage());
//             }
//         }
        
//         if(version_compare($old_version,'1.0.2','<' )){
//             //把之前的跨域登录设置移植过来
//             $api = XH_Social::instance()->get_available_addon('wechat_social_add_ons_social_wechat_ext');
//             if($api){
//                 XH_Social_Channel_Wechat::instance()->update_option_array(array(
//                     'mp_enabled_cross_domain'=>$api->get_option('enabled_cross_domain')=='enabled_cross_domain'?'mp_enabled_cross_domain':'mp_cross_domain_disabled',
//                     'mp_cross_domain_url'=>$api->get_option('cross_domain_url')
//                 ));
//             }
//         }
    }
    
    public function on_load(){
        add_filter('xh_social_ajax', array($this,'ajax'),10,1);
        add_filter('xh_social_channels', array($this,'add_channels'));
        add_filter('xh_social_admin_menu_menu_default_channel', array($this,'add_channel_menus'),10,1);
        add_filter('xh_social_channel_wechat_login_get_authorization_uri', array($this,'wechat_login_get_authorization_uri'),10,5);
        add_filter('xh_social_shortcodes',function($m){
            $m['wsocial_wechat']=array(XH_Social_Add_On_Social_Wechat::instance(),'wsocial_wechat');
            return $m;
        },10,1);
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

    public function wsocial_wechat($atts = array(),$content = null){
        $atts = shortcode_atts(array(
            'user_id'=>get_current_user_id(),
            'id'=>null,
            'ID'=>null,
            'meta'=>null
        ), $atts);
        
        if(empty($atts['user_id'])){
            $atts['user_id']=$atts['id'];
        }
        if(empty($atts['user_id'])){
            $atts['user_id']=$atts['ID'];
        }
        
        return wsocial_wechat($atts['meta'],$atts['user_id'],$content,false);
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
        if(isset($_REQUEST['s'])){
            $datas['s']=stripslashes($_REQUEST['s']);
        }
        if(isset($_REQUEST['uuid'])){
            $datas['uuid']=stripslashes($_REQUEST['uuid']);
        }
        
        if(!XH_Social::instance()->WP->ajax_validate($datas,isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
            if($_SERVER['REQUEST_METHOD']=='GET'){
                XH_Social::instance()->WP->wp_die(XH_Social_Error::err_code(701));
                exit;
            }else{
                echo (XH_Social_Error::err_code(701)->to_json());
                exit;
            }
        }
       
        switch ($datas['tab']){
            case 'share_qrcode':
                ob_start();
                require XH_Social::instance()->WP->get_template($this->dir, 'share/wechat/qrcode-content.php');
                echo ob_get_clean();
                exit;
                
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

if(!function_exists('wsocial_wechat')){
    function wsocial_wechat($meta_key,$user_id=null,$default=null,$echo = true){
        if(!$user_id){
            $user_id = get_current_user_id();
        }
        
        if(empty($meta_key)){
            if($echo){echo $default;return;}else{return $default;} 
        }
        $user_id = intval($user_id);
        if($user_id<=0){
            if($echo){echo $default;return;}else{return $default;}
        }
        
        $ext_user_info = XH_Social_Temp_Helper::get($user_id,'user_wechat_metas',null);
        if(!$ext_user_info){
            global $wpdb;
            $ext_user_info = $wpdb->get_row("select u.*,
                                   w.*,
                                   u.ID as user_ID,
                                   w.id as ext_user_ID
                from {$wpdb->prefix}users u
                inner join {$wpdb->prefix}xh_social_channel_wechat w on w.user_id=u.ID
                where u.ID={$user_id}
                limit 1;");
            if($ext_user_info){
                XH_Social_Temp_Helper::set($user_id, $ext_user_info,'user_wechat_metas');
            }
        }
        
        if(!$ext_user_info){ if($echo){echo $default;return;}else{return $default;}}
        
        if($meta_key=='openid'){
            $meta_key='mp_openid';
        }
        
        if($meta_key=='ID'){
            $meta_key=='user_ID';
        }
        $val =isset($ext_user_info->{$meta_key})?$ext_user_info->{$meta_key}:$default;
        if($echo){
            echo $val;return;
        } else{
            return $val;
        }
    }
}

return XH_Social_Add_On_Social_Wechat::instance();
?>