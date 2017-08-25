<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * Social Admin
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Settings_Default_Other_Share extends Abstract_XH_Social_Settings{
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
        $this->id='settings_default_other_share';
        $this->title=__('Share Settings',XH_SOCIAL);

        $this->init_form_fields();
    }

    public function init_form_fields(){
        
        $this->form_fields =array(
            'share'=>array(
                'title'=>__('Enabled share channels',XH_SOCIAL),
                'type'=>'multiselect',
                'func'=>true,
                'options'=>function(){
                    $channels = XH_Social::instance()->channel->get_social_channels(array('share'));
                    $options = array();
                    if($channels){
                        foreach ($channels as $channel){
                            $options[$channel->id] = $channel->title;
                        }
                    }
                    
                    return $options;
                },
                'description'=>sprintf('微信分享带logo，请启用<a href="%s" target="_blank">'.__('Wechat Senior Extension',XH_SOCIAL).'</a>',
                    class_exists('XH_Social_Add_On_Social_Wechat_Ext')?admin_url('admin.php?page=social_page_default&section=menu_default_ext&sub=wechat_social_add_ons_social_wechat_ext'): 'https://www.wpweixin.net/product/1135.html'
                )
            ),
            'share_with_post'=>array(
                'title'=>__('Enable share with post types',XH_SOCIAL),
                'type'=>'multiselect',
                'func'=>true,
                'options'=>array($this,'get_post_type_options')
            )
        );
    }
}
?>