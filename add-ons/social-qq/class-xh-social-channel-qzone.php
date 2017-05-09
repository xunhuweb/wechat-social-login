<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * 微信接口
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Channel_Qzone extends Abstract_XH_Social_Settings_Channel{    
    /**
     * Instance
     * @var XH_Social_Channel_QQ
     * @since  1.0.0
     */
    private static $_instance;

    /**
     * @return XH_Social_Channel_QQ
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }
    
    /**
     * 初始化接口ID，标题等信息
     * 
     * @since 1.0.0
     */
    protected function __construct(){
        $this->id='social_qzone';
        
        $this->icon =XH_SOCIAL_URL.'/assets/image/qzone.png';
        $this->title =__('Qzone', XH_SOCIAL);
    
        $this->supports=array('share');
        $this->enabled =true;
    }
  
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_share_link()
     */
    public function get_share_link(){
        $site_name = urlencode(get_option('blogname'));
        return array(
            'link'=>"http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url={url}&title={title}&desc=&summary={summary}&site={$site_name}&pics={img}",
            'width'=>640,
            'height'=>440
        );
    }
    
}
