<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * Social Admin
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Settings_Default_Other_Default extends Abstract_XH_Social_Settings{
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

    private function __construct(){
        $this->id='settings_default_other_default';
        $this->title=__('Basic Settings',XH_SOCIAL);

        $this->init_form_fields();
    }

    public function init_form_fields(){
        $this->form_fields =array(
            'logo'=>array(
                'title'=>__('Website Logo',XH_SOCIAL),
                'type'=>'image',
                'default'=>XH_SOCIAL_URL.'/assets/image/wordpress-logo.png'
            ),
            'bingbg'=>array(
                'title'=>__('调用Bing背景作为登录页背景',XH_SOCIAL),
                'type'=>'checkbox'
            )
        );
    }
}
?>