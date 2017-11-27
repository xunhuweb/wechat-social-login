<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly
/**
 * @author ranj
 * @since 1.0.0
 */
class XH_Social_Add_On_Buddypress extends Abstract_XH_Social_Add_Ons{   
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var XH_Social_Add_On_Buddypress
     */
    private static $_instance = null;
    
    /**
     * 当前插件目录
     * @var string
     * @since 1.0.0
     */
    private $dir;
    
    /**
     * Main Social Instance.
     *
     * @since 1.0.0
     * @static
     * @return XH_Social_Add_On_Buddypress
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    protected function __construct(){
        $this->id='add_ons_buddypress';
        $this->title=__('Buddypress',XH_SOCIAL);
        $this->description=__('Buddypress 扩展，社交登录头像同步到Buddypress',XH_SOCIAL);
        $this->version='1.0.0';
        $this->min_core_version = '1.0.0';
        $this->author=__('xunhuweb',XH_SOCIAL);
        $this->author_uri='https://www.wpweixin.net';
    }
    
    public function on_load(){
        add_filter('bp_core_fetch_avatar_no_grav', '__return_true');
        add_filter('bp_core_default_avatar_user', array($this,'bp_avatar_url'), 10, 2);
        add_filter('bp_get_displayed_user_mentionname', array($this,'bp_get_displayed_user_mentionname'),10,1);
    }
    
    public function bp_get_displayed_user_mentionname($name){
       
        $user = get_userdata(bp_displayed_user_id());
        if($user){return $user->display_name;}
        return $name;
    }
    
    /**
     * 重置头像
     * @param string $default_url
     * @param array $params
     */
    public function bp_avatar_url($default_url, $params) {
        if(!isset($params['item_id'])) return $default_url;
        $url = get_user_meta ( $params['item_id'], '_social_img', true );
        if(empty($url)){
           return $default_url; 
        }
        return $url;
    }
}

return XH_Social_Add_On_Buddypress::instance();
?>