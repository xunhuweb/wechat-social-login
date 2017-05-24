<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

/**
 * 菜单：登录设置
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Page_Default extends Abstract_XH_Social_Settings_Page{    
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
        $this->id='page_default';
        $this->title=__('Settings',XH_SOCIAL);
    }
    
    /* (non-PHPdoc)
     * @see Abstract_XH_Social_Settings_Menu::menus()
     */
    public function menus(){
        $submenus =array(
            XH_Social_Menu_Default_Other::instance(),
            XH_Social_Menu_Default_Channel::instance(),
            XH_Social_Menu_Default_Account::instance(),
            XH_Social_Menu_Default_Ext::instance()
        );
        
        return apply_filters("xh_social_admin_page_{$this->id}", XH_Social_Helper_Array::where($submenus, function($m){
            $menus= $m->menus();
            return count($menus)>0;
        }));
    }
}?>