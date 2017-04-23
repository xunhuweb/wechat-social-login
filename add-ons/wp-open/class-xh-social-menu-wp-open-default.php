<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

/**
 * 菜单：其他
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Menu_WP_Open_Default extends Abstract_XH_Social_Settings_Menu{
    /**
     * Instance
     * @since  1.0.0
     */
    private static $_instance;
    
    /**
     * Instance
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }
    
    /**
     * 菜单初始化
     *
     * @since  1.0.0
     */
    private function __construct(){
        $this->id='menu_wp_open_default';
        $this->title=__('代理登录',XH_SOCIAL);
    }
    
    /* (non-PHPdoc)
     * @see Abstract_XH_Social_Settings_Menu::menus()
     */
    public function menus(){
        return apply_filters("xh_social_admin_menu_{$this->id}", array(
            XH_Social_Settings_WP_Open_Default::instance()
        ));
    }
}


?>