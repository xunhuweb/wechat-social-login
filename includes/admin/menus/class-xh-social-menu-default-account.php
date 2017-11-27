<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

/**
 * 账户设置
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Menu_Default_Account extends Abstract_XH_Social_Settings_Menu{
    /**
     * @since  1.0.0
     */
    private static $_instance;
    
    /**
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }
    
    /**
     * @since  1.0.0
     */
    private function __construct(){
        $this->id='menu_default_account';
        $this->title=__('Page Settings',XH_SOCIAL);
    }
    
}


?>