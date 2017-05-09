<?php 
/**
 * 登录注册
 * 
 * @author ranj
 * @since 1.0.0
 */
class XH_Social_Add_On_WP_Open extends Abstract_XH_Social_Add_Ons{   
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var XH_Social_Add_On_WP_Open
     */
    private static $_instance = null;

    /**
     * Main Social Instance.
     *
     * @since 1.0.0
     * @static
     * @return XH_Social_Add_On_WP_Open
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private function __construct(){
        $this->id='add_ons_wp_open';
        $this->title=__('代理登录',XH_SOCIAL);
        $this->description=__('因微信、QQ、微博登录申请要求高，个人用户很难申请到，因此我们提供代理登录服务。',XH_SOCIAL);
        $this->version='1.0.0';
        $this->min_core_version = '1.0.0';
        $this->author=__('xunhuweb',XH_SOCIAL);
        $this->author_uri='https://www.wpweixin.net';
        $this->setting_uri = admin_url('admin.php?page=social_page_wp_open&section=menu_wp_open_default&sub=settings_wp_open_default');
  
        $this->init_form_fields();
        $this->enabled ='yes'== $this->get_option('enabled');
    }

    public function on_init(){
        require_once 'class-xh-social-settings-wp-open-default.php';
        require_once 'class-xh-social-menu-wp-open-default.php';
        require_once 'class-xh-social-page-wp-open.php';
        
        add_filter('xh_social_admin_pages', array($this,'add_pages'),10,1);
    }      
    
    public function add_pages($pages){
        $pages[]=XH_Social_Page_WP_Open::instance();
        return $pages;
    }
    
    
}

return XH_Social_Add_On_WP_Open::instance();
?>