<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly
/**
 * @author ranj
 * @since 1.0.0
 */
class XH_Social_Add_On_Open_Social extends Abstract_XH_Social_Add_Ons{   
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var XH_Social_Add_On_Open_Social
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
     * @return XH_Social_Add_On_Open_Social
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    protected function __construct(){
        $this->id='add_ons_open_social';
        $this->title=__('Compatible OS',XH_SOCIAL);
        $this->description=__('兼容open social 老用户登录',XH_SOCIAL);
        $this->version='1.0.0';
        $this->min_core_version = '1.0.0';
        $this->author=__('xunhuweb',XH_SOCIAL);
        $this->author_uri='https://www.wpweixin.net';
    }
    
    public function on_load(){
       add_filter('xh_social_process_login_new_user', array($this,'process_login_new_user'),10,3);
    }
    
    /**
     * 
     * @param WP_User $wp_user
     * @param Abstract_XH_Social_Settings_Channel $channel
     * @param int $ext_user_id
     */
    public function process_login_new_user($wp_user,$channel,$ext_user_id){
         $opens=array();
         $ext_user = $channel->get_ext_user_info($ext_user_id);
         if(!$ext_user){
             return $wp_user;
         }
         
         switch ($channel->id){
             case 'social_qq':
                 $opens['qq']=$ext_user['uid'];
                 break;
             case 'social_weibo':
                 $opens['sina']=$ext_user['uid'];
                 break;
             case 'social_wechat':
                 $opens['wechat_unionid']=$ext_user['uid'];
                 $opens['wechat']=$ext_user['op_openid'];
                 $opens['wechat_mp']=$ext_user['mp_openid'];
                 
                 break;
         }
         
         global $wpdb;
         foreach ($opens as $open_type=>$uid){
             $query = $wpdb -> get_var($wpdb -> prepare(
                 "SELECT user_id
                 FROM {$wpdb->usermeta} um
                 WHERE um.meta_key='%s'
                        AND um.meta_value='%s'
                 limit 1;", 'open_type_'.$open_type, $uid));
             
             if(!$query){//single era
                 $query = $wpdb -> get_var($wpdb -> prepare(
                     "SELECT um1.user_id 
                     FROM {$wpdb->usermeta} um1 
                     INNER JOIN {$wpdb->usermeta} um2 ON um1.user_id = um2.user_id 
                     WHERE um1.meta_key='open_type' 
                           AND um1.meta_value='%s' 
                           AND um2.meta_key='open_id' 
                            AND um2.meta_value='%s'
                     limit 1;", $open_type, $uid));
             } 
             
             if($query){
                 $wp_user = get_userdata($query->user_id);
                 if($wp_user){
                     return $wp_user;
                 }
             }
         }
        return $wp_user;
    }
}

return XH_Social_Add_On_Open_Social::instance();
?>