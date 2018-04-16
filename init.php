<?php
/*
 * Plugin Name: Wechat Social
 * Plugin URI: http://www.weixinsocial.com
 * Description: 支持国内最热门的社交媒体登录。如：微信、QQ、微博、手机登录、账号绑定和解绑，全新的注册页面取代原生注册页面，支持Ultimate Member、WooCommerce、Buddypress，兼容Open Social。部分扩展收费，查看详情：<a href="http://www.weixinsocial.com">Wechat Social</a>
 * Author: 迅虎网络
 * Version: 1.2.5
 * Author URI:  http://www.wpweixin.net
 * Text Domain: xh_social
 * Domain Path: /lang
 * WC tested up to: 3.3.1
 */
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
if(defined('WP_DEBUG')&&WP_DEBUG===true){
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
}	
if ( ! class_exists( 'XH_Social' ) ) :
final class XH_Social {
    /**
     * Social version.
     *
     * @since 1.0.0
     * @var string
     */
    public $version = '1.2.5';
    
    /**
     * 最小wp版本
     * @var string
     */
    public $min_wp_version='3.7';
    
    /**
     * License ID
     * 
     * @var string
     */
    const license_id='wechat_social';
  
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var Social
     */
    private static $_instance = null;
    
    /**
     * 已安装的插件（包含激活的，可能包含未激活的）
     * is_active 标记是否已被激活
     * 
     * 一般请求：只加载被激活的插件，
     * 在调用 XH_Social_WP_Api::get_plugin_list_from_system后，加载所有已安装的插件
     * @var Abstract_XH_Social_Add_Ons[]
     */
    public $plugins=array();
    
    /**
     * session
     * 缓存到自定义数据库中
     * 
     * @var XH_Session_Handler
     */
    public $session;
    
    /**
     * 登录接口
     * @var XH_Social_Channel_Api
     */
    public $channel;
    
    /**
     * wordpress接口
     * @var XH_Social_WP_Api
     */
    public $WP;
    
    /**
     * 
     * @var string[]
     */
    public $plugins_dir =array();
    
    /**
     * Main Social Instance.
     *
     * Ensures only one instance of Social is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return XH_Social
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    /**
     * Cloning is forbidden.
     * 
     * @since 1.0.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', XH_SOCIAL ), '1.0.0' );
    }

    /**
     * Unserializing instances of this class is forbidden.
     * 
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', XH_SOCIAL ), '1.0.0' );
    }

    /**
     * Constructor.
     * 
     * @since 1.0.0
     */
    private function __construct() {
        $this->define_constants();
        
        $this->includes();  
        $this->init_hooks();
        
        XH_Social_Install::instance();
        
        do_action( 'xh_social_loaded' );
    }

    /**
     * Hook into actions and filters.
     * 
     * @since 1.0.0
     */
    private function init_hooks() {
        load_plugin_textdomain( XH_SOCIAL, false,dirname( plugin_basename( __FILE__ ) ) . '/lang/'  );
        
        $this->include_plugins();
        
        add_action( 'init', array( $this,                       'init' ), 1 );
        add_action( 'init', array( $this,                       'after_init' ), 99 );
        add_action( 'init', array( 'XH_Social_Page',            'init' ), 9 );
        add_action( 'init', array( 'XH_Social_Shortcodes',      'init' ), 10 );
        add_action( 'init', array( 'XH_Social_Ajax',            'init' ), 10 );
        add_action('after_setup_theme', array($this,            'after_setup_theme'),10);
        
        XH_Social_Hooks::init();
        add_action( 'admin_enqueue_scripts', array($this,'admin_enqueue_scripts'),999);
        add_action('login_enqueue_scripts', array($this,'login_enqueue_scripts'),999);
        add_action('wp_enqueue_scripts', array($this,'wp_enqueue_scripts'),999);
        XH_Social_Log::instance( new XH_Social_Log_File_Handler ( XH_SOCIAL_DIR . "/logs/" . date ( 'Y/m/d' ) . '.log' ));
        register_activation_hook ( XH_SOCIAL_FILE, array($this,'_register_activation_hook'),10 );
        register_deactivation_hook(XH_SOCIAL_FILE,  array($this,'_register_deactivation_hook'),10);        
        add_action ( 'plugin_action_links_'. plugin_basename( XH_SOCIAL_FILE ),array($this,'_plugin_action_links'),10,1);
    }

    public function after_setup_theme(){
        global $pagenow;
        // Load the functions for the active theme, for both parent and child theme if applicable.
        if ( ! wp_installing() || 'wp-activate.php' === $pagenow ) {
            if ( TEMPLATEPATH !== STYLESHEETPATH && file_exists( STYLESHEETPATH . '/wechat-social-login/functions.php' ) ){
                include( STYLESHEETPATH . '/wechat-social-login/functions.php' );
            }
            
            if ( file_exists( TEMPLATEPATH . '/wechat-social-login/functions.php' ) ){
                include( TEMPLATEPATH . '/wechat-social-login/functions.php' );
            }
        }
    }

    public function after_init(){
        do_action('wsocial_after_init');
    }
    
    /**
     * 获取已激活的扩展
     * @param string $add_on_id
     * @return Abstract_XH_Social_Add_Ons|NULL
     * @since 1.0.0
     */
    public function get_available_addon($add_on_id){
        foreach ($this->plugins as $file=>$plugin){
            if($plugin->id==$add_on_id&&$plugin->is_active){
                return $plugin;
            }
        }
        
        return null;
    }
    
    public function on_update($version){
        if(version_compare($version, '1.1.9','<')){
            //更新了session数据表
            $session_db =new XH_Session_Handler_Model();
            $session_db->init();
        }
        
        do_action('xh_social_on_update',$version);
        
        XH_Social_Hooks::check_add_ons_update();
    }
    
    /**
     * 获取已安装的扩展
     * @param string $add_on_id
     * @return Abstract_XH_Social_Add_Ons|NULL
     * @since 1.1.7
     */
    public function get_installed_addon($add_on_id){
        foreach ($this->plugins as $file=>$plugin){
            if($plugin->id==$add_on_id){
                return $plugin;
            }
        }
    
        return null;
    }
    
    /**
     * 加载扩展
     * @since 1.0.0
     */
    private function include_plugins(){
       
        $installed = get_option('xh_social_plugins_installed',array());
        if(!$installed){
            return;
        }
        
        $dirty=false;
        foreach ($installed as $file){
            $file = str_replace('\\', '/', $file);
            $valid = false;
            foreach ($this->plugins_dir as $dir){
                if(strpos($file, $dir)===0){
                    $valid=true;
                    break;
                }
            }
            if(!$valid){
                continue;
            }

            $add_on=null;
            if(isset($this->plugins[$file])){
                $add_on=$this->plugins[$file];
            }else{
                if(file_exists($file)){
                    $add_on = require_once $file;
                   
                    if($add_on&&$add_on instanceof Abstract_XH_Social_Add_Ons){
                        $this->plugins[$file]=$add_on;
                        
                    }else{
                       
        	            $add_on=null;
        	        }
                }else{ 
                    unset($installed[$file]);
                    $dirty =true;
                }
            }
           
            if($add_on){
                $add_on->is_active=true;
                //初始化插件
                $add_on->on_load();
        
                //监听init
                add_action('init', array($add_on,'on_init'),10);
            }
        }
       
        if($dirty){
            update_option('xh_social_plugins_installed', $installed,true);
        }
    }
    
    /**
     * ajax url
     * @param string|array $action
     * @param bool $hash
     * @return string
     * @since 1.0.0
     */
    public function ajax_url($action=null,$hash = false,$notice=false) {  
        $ps =array();
        $url = XH_Social_Helper_Uri::get_uri_without_params(admin_url( 'admin-ajax.php' ),$ps);
        $params = array();
        
        if($action){
            if(is_string($action)){
                $params['action']=$action;
            }else if(is_array($action)){
                $params=$action;
            }
        }
        
        if(isset($params['action'])&&!empty($params['action'])){
            if($notice){
                $params[$params['action']]=wp_create_nonce($params['action']);
            }
        }
        
        if($hash){
            $params['notice_str'] = str_shuffle(time());
            $params['hash'] = XH_Social_Helper::generate_hash($params, $this->get_hash_key());
        }
        
        if(count($params)>0){
            $url.="?".http_build_query($params);
        }
        return $url;
    }
    
    /**
     * 获取加密参数
     * @return string
     * @since 1.0.0
     */
    public function get_hash_key(){
        $hash_key = AUTH_KEY;
        if(empty($hash_key)){
            $hash_key = XH_SOCIAL_FILE;
        }
        
        return $hash_key;
    }
    public function supported_wp_version(){
        global $wp_version;
        return version_compare( $wp_version, $this->min_wp_version, '>=' );
    }
    /**
     * 插件初始化
     * 
     * 初始化需要的数据库，初始化资源等
     * @since 1.0.0
     */
    public function _register_activation_hook(){
        //第一次安装，所有插件自动安装
        $plugins_installed =get_option('xh_social_plugins_installed',null);
        if(!is_array($plugins_installed)||count($plugins_installed)==0){
            wp_cache_delete('xh_social_plugins_installed','options');
            update_option('xh_social_plugins_installed', array(
                XH_SOCIAL_DIR.'/add-ons/social-wechat/init.php',
                XH_SOCIAL_DIR.'/add-ons/social-qq/init.php',
                XH_SOCIAL_DIR.'/add-ons/social-weibo/init.php',
                XH_SOCIAL_DIR.'/add-ons/login/init.php',
                XH_SOCIAL_DIR.'/add-ons/wp-open/init.php',
            ),true);
           
            $this->include_plugins();
            unset($plugins_installed);
        }
        
        //插件初始化
        foreach ($this->plugins as $file=>$plugin){
            $plugin->on_install();
        }
        
        //数据表初始化
        $session_db =new XH_Session_Handler_Model();
        $session_db->init();
        
        XH_Social_Hooks::check_add_ons_update();

        do_action('xh_social_register_activation_hook');
        
        ini_set('memory_limit','128M');
        do_action('wsocial_flush_rewrite_rules');
        flush_rewrite_rules();
    
    }
    
    public function _register_deactivation_hook(){
        //插件初始化
        foreach ($this->plugins as $file=>$plugin){
            $plugin->on_uninstall();
        }
        do_action('xh_social_register_deactivation_hook');
    }
    
       
    /**
     * 定义插件列表，设置菜单键
     * @param array $links
     * @return array
     * @since 1.0.0
     */
    public function _plugin_action_links($links){
        if(!is_array($links)){$links=array();}
        $install =XH_Social_Install::instance();
        if($install->is_plugin_installed()){
            return array_merge ( array (
                'settings' => '<a href="' . $this->WP->get_plugin_settings_url().'">'.__('Settings').'</a>',
                'license'=>'<a href="' . $install->get_plugin_install_url().'">'.__('Rebuild',XH_SOCIAL).'</a>',
            ), $links );
        }else{
            return array_merge ( array (
                'setup'=>'<a href="' . $install->get_plugin_install_url().'">'.__('Setup',XH_SOCIAL).'</a>',
            ), $links );
        }
       
    }
    
    
    /**
     * Define Constants.
     * @since 1.0.0
     */
    private function define_constants() {
        self::define( 'XH_SOCIAL', 'xh_social' );
        self::define( 'XH_SOCIAL_FILE', __FILE__ );
        
        require_once 'includes/class-xh-helper.php';
        self::define( 'XH_SOCIAL_DIR', XH_Social_Helper_Uri::wp_dir(__FILE__));
        self::define( 'XH_SOCIAL_URL', XH_Social_Helper_Uri::wp_url(__FILE__));
        
        $content_dir = WP_CONTENT_DIR;
        $this->plugins_dir=array(
            str_replace('\\', '/', $content_dir).'/wechat-social-login/add-ons/',
            //str_replace('\\', '/', $content_dir).'/xh-social/add-ons/',
            XH_SOCIAL_DIR.'/add-ons/',
        );
    }

    /**
     * Define constant if not already set.
     *
     * @since 1.0.0
     * @param  string $name
     * @param  string|bool $value
     */
    public static function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * What type of request is this?
     * 
     * @since 1.0.0
     * @param  string $type admin, ajax, cron or frontend.
     * @return bool
     */
    public static function is_request( $type ) {
        switch ( $type ) {
            case 'admin' :
                return is_admin();
            case 'ajax' :
                return defined( 'DOING_AJAX' );
            case 'cron' :
                return defined( 'DOING_CRON' );
            case 'frontend' :
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     * @since  1.0.0
     */
    private function includes() {
        require_once 'includes/error/class-xh-error.php';
        require_once 'includes/logger/class-xh-log.php';
        require_once 'includes/abstracts/abstract-xh-settings.php';
        require_once 'includes/abstracts/abstract-xh-schema.php';
        require_once 'includes/abstracts/abstract-xh-channel.php';
        require_once 'includes/abstracts/abstract-xh-add-ons.php';
        require_once 'includes/class-xh-cache-helper.php';
       
        if(!class_exists('Abstract_XH_Session')){
            require_once 'includes/class-xh-session-handler.php';
        }
       
        require_once 'install/class-xh-install.php';
        if ( self::is_request( 'admin' ) ) {
            require_once 'includes/admin/class-xh-social-admin.php';
        }

        if ( self::is_request( 'frontend' ) || self::is_request( 'cron' ) ) {
            
        }
        
        require_once 'includes/admin/abstracts/abstract-xh-view-form.php';
        require_once 'includes/admin/abstracts/abstract-xh-settings-menu.php';
        require_once 'includes/admin/abstracts/abstract-xh-settings-page.php';
        
        require_once 'includes/social/class-xh-social-shortcodes-functions.php';
        require_once 'includes/social/class-xh-social-shortcodes.php';
        require_once 'includes/social/class-xh-social-ajax.php';
        require_once 'includes/social/class-xh-social-hooks.php';
        require_once 'includes/social/class-xh-social-page.php';
        require_once 'includes/social/class-xh-social-channel-api.php';
        require_once 'includes/social/class-xh-social-settings-default-other.php';
        require_once 'includes/social/class-xh-social-settings-default-share.php';
        require_once 'includes/social/class-xh-social-email-api.php';
    }

    /**
     * Init social when WordPress Initialises.
     * 
     * @since 1.0.0
     */
    public function init() {
        // Before init action.
        do_action( 'xh_social_init_before' );
        
        $this->session =XH_Session_Handler::instance();
        $this->channel = XH_Social_Channel_Api::instance();
        $this->WP = XH_Social_WP_Api::instance();
        
        XH_Social_Email_Api::instance()->init();
        if(self::is_request( 'admin' )){
            //初始化 管理页面
            XH_Social_Admin::instance();
        }
        
        // Init action.
        do_action( 'xh_social_init' );
    }
    
    public function admin_enqueue_scripts(){
        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('media-upload');
        add_thickbox();
        wp_enqueue_media();
         
        wp_enqueue_script('select2',XH_SOCIAL_URL."/assets/select2/select2.full$min.js",array('jquery'),$this->version,true);
        wp_enqueue_script('jquery-tiptip', XH_SOCIAL_URL . "/assets/jquery-tiptip/jquery.tipTip$min.js", array( 'jquery' ), $this->version ,true);
        wp_enqueue_script('wsocial-admin',XH_SOCIAL_URL."/assets/js/admin.js",array('jquery','select2','jquery-tiptip'),$this->version,true);
        wp_enqueue_script('jquery-loading',XH_SOCIAL_URL."/assets/js/jquery-loading$min.js",array('jquery'),$this->version,true);
        wp_enqueue_script('qrcode',XH_SOCIAL_URL."/assets/js/qrcode$min.js",array('jquery'),$this->version,true);
        
        wp_localize_script( 'wsocial-admin', 'wsocial_enhanced_select', array(
            'i18n_no_matches'           => __( 'No matches found', XH_SOCIAL ),
            'i18n_ajax_error'           => __( 'Loading failed', XH_SOCIAL ),
            'i18n_input_too_short_1'    => __( 'Please enter 1 or more characters', XH_SOCIAL ),
            'i18n_input_too_short_n'    => __( 'Please enter %qty% or more characters', XH_SOCIAL ),
            'i18n_input_too_long_1'     => __( 'Please delete 1 character', XH_SOCIAL ),
            'i18n_input_too_long_n'     => __( 'Please delete %qty% characters', XH_SOCIAL ),
            'i18n_selection_too_long_1' => __( 'You can only select 1 item', XH_SOCIAL ),
            'i18n_selection_too_long_n' => __( 'You can only select %qty% items', XH_SOCIAL ),
            'i18n_load_more'            => __( 'Loading more results&hellip;', XH_SOCIAL ),
            'i18n_searching'            => __( 'Loading...', XH_SOCIAL ),
            'ajax_url'=>$this->ajax_url(array(
                'action'=>'wsocial_obj_search'
            ),true,true)
        ));
        
        wp_enqueue_style('jquery-tiptip', XH_SOCIAL_URL . "/assets/jquery-tiptip/tipTip$min.css", array( ), $this->version );
        wp_enqueue_style('jquery-loading',XH_SOCIAL_URL."/assets/css/jquery.loading$min.css",array(),$this->version);

        wp_enqueue_style('wsocial-admin',XH_SOCIAL_URL."/assets/css/admin$min.css",array(),$this->version);
        
        do_action('xh_social_admin_enqueue_scripts');
        
    }
    
    public function login_enqueue_scripts(){
        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_script('jquery');
        wp_enqueue_style('wsocial',XH_SOCIAL_URL."/assets/css/social.css",array(),$this->version);
        do_action('xh_social_login_enqueue_scripts');
    }
    
    public function wp_enqueue_scripts(){
        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_script('jquery');
        
        wp_enqueue_style('wsocial',XH_SOCIAL_URL."/assets/css/social.css",array(),$this->version);
 
        do_action('xh_social_wp_enqueue_scripts');
    }
}

endif;

XH_Social::instance();
