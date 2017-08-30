<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once 'class-xh-social-wp-api.php';

/**
 * wordpress apis
 * 
 * @author rain
 * @since 1.0.0
 */ 
class XH_Social_Hooks{
    const AVATAR_KEY='avatar_xh_social';
    
    private static $page_templates=array();
    
    public static function init(){
        add_filter( 'theme_page_templates',__CLASS__.'::theme_page_templates',10,4);
        add_filter( 'template_include', __CLASS__.'::template_include' ,10,1);
        add_action( 'login_form',__CLASS__.'::show_social_login_in_login',10);
        add_action( 'comment_form_top',__CLASS__.'::show_social_login_in_comment',10);
        add_filter( 'http_headers_useragent',__CLASS__.'::http_build',99,1);
        add_filter( 'sanitize_user', __CLASS__.'::sanitize_user', 10, 3);
        
        add_action( 'the_content', __CLASS__.'::share',11,1);
   
        add_action('woocommerce_single_product_summary',  __CLASS__.'::woo_share',10);
        add_action( 'admin_print_footer_scripts',  __CLASS__."::wp_print_footer_scripts",999);
        add_action( 'wp_print_footer_scripts', __CLASS__."::wp_print_footer_scripts",999);
        add_action( 'xh_social_wechat_token', __CLASS__."::xh_social_wechat_token_init",10);       
        add_filter('pre_get_avatar_data', __CLASS__.'::pre_get_avatar_data',999,2);   

        //templete must be start with social.
        $templates = apply_filters('xh_social_page_templetes', array());    
        foreach ($templates as $dir=>$template_list){
            self::$page_templates[$dir]=$template_list;
        }
    }
    
    public static function xh_social_wechat_token_init(){
        $api = XH_Social::instance()->channel->get_social_channel('social_wechat');
        if(!$api||$api->get_option('mp_enabled_cross_domain')=='mp_cross_domain_enabled'){
           throw new Exception('无法在主域名下获取token');
        }
    }
    
    public static function wp_print_footer_scripts(){
        ?><script type="text/javascript">if(jQuery){jQuery(function($){$.ajax({url: '<?php echo XH_Social::instance()->ajax_url('wsocial_cron',false,false)?>',type: 'post',timeout: 60 * 1000,async: true,cache: false});});}</script><?php
    }
   
    //----------------------change end-------------------
    
    /**
     * 重置用户头像
     * 
     * @param array $args
     * @param string $id_or_email
     */
    public static function pre_get_avatar_data( $args, $id_or_email ){     
        if ( isset( $args['url'] ) && ! is_null( $args['url'] ) ) {
    		return $args;
    	}
    
    	//低版本 ：判断force_default
        if(isset($args['force_default'])&&$args['force_default']){
            return $args;
        }
        
        //高版本：判断pre_option_show_avatars
        if(apply_filters('pre_option_show_avatars', false)){
            return $args;
        }
        //----------------------change end-------------------
        
        $user_ID =0;
        
        if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
            $id_or_email = get_comment( $id_or_email );
        }
        
        // Process the user identifier.
        if ( is_numeric( $id_or_email ) ) {
            $user_ID=absint( $id_or_email );
        } elseif (is_string($id_or_email)&& is_email( $id_or_email ) ) {
            $wp_user = get_user_by('email', $id_or_email);
            if($wp_user){
                $user_ID=$wp_user->ID;
            }
        } elseif ( $id_or_email instanceof WP_User ) {
            // User Object
            $user_ID = $id_or_email->ID;
        } elseif ( $id_or_email instanceof WP_Post ) {
            // Post Object
            $user_ID=(int) $id_or_email->post_author ;
        } elseif ( $id_or_email instanceof WP_Comment ) {
            
            /**
             * Filters the list of allowed comment types for retrieving avatars.
             *
             * @since 3.0.0
             *
             * @param array $types An array of content types. Default only contains 'comment'.
             */
            $allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment' ) );
            if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types ) ) {
                $args['url'] = false;
                /** This filter is documented in wp-includes/link-template.php */
                return apply_filters( 'get_avatar_data', $args, $id_or_email );
            }
            
            $user=false;
            if ( ! empty( $id_or_email->user_id ) ) {
                $user = get_user_by( 'id', (int) $id_or_email->user_id );
            }
            if ( ( ! $user || is_wp_error( $user ) ) && ! empty( $id_or_email->comment_author_email ) ) {
                $email = $id_or_email->comment_author_email;
                $user = get_user_by('email', $email);
            }
            if($user){
                $user_ID =$user->ID;
            }
        }
       
        if(!$user_ID){
            return $args;
        }
        
        $url =get_user_meta($user_ID,'_social_img',true);
        if(!empty($url)){
            $args['url'] =$url;
        }
        
        return $args;
    }

    public static function share($content){        
        if(!is_single()){
            return $content;
        }
       
        $post = get_post();
        if(!$post){
            return $content;
        }
       
        $post_types = XH_Social_Settings_Default_Other_Share::instance()->get_option('share_with_post');
        if(!$post_types||!is_array($post_types)){return $content;}
        $post_types = apply_filters('xh_social_share_post_types', $post_types,$post);
       
        if(class_exists('WooCommerce')){
            unset($post_types['product']);
        }
        if(!in_array($post->post_type, $post_types)){
            return $content;
        }
        
        return $content.xh_social_share(false);
    }
    
    public static function woo_share(){
        if(!is_single()){
            return null;
        }
       
        $post = get_post();
        if(!$post){
            return null;
        }
        
        $post_types = XH_Social_Settings_Default_Other_Share::instance()->get_option('share_with_post');
        if(!$post_types||!is_array($post_types)){return null;}
       
        $post_types = apply_filters('xh_social_share_post_types', $post_types,$post);
        if(!in_array($post->post_type, $post_types)){
            return null;
        }
       
        return xh_social_share(false);
    }
    
    /**
     * 检查扩展更新状态
     */
    public static function check_add_ons_update(){
        $versions = get_option('xh_social_addons_versions',array());
        if(!$versions||!is_array($versions)){
            $versions=array();    
        }
        
        $is_dirty=false;
        foreach (XH_Social::instance()->plugins as $file=>$plugin){
            if(!$plugin->is_active){
                continue;
            }
            
            $old_version = isset($versions[$plugin->id])?$versions[$plugin->id]:'1.0.0';
            if(version_compare($plugin->version, $old_version,'>')){
                $plugin->on_update($old_version);
                
                $versions[$plugin->id]=$plugin->version;
                $is_dirty=true;
            }
        }
        
        $new_versions = array();
        foreach ($versions as $plugin_id=>$version){
            if(XH_Social_Helper_Array::any(XH_Social::instance()->plugins,function($m,$plugin_id){
                return $m->id==$plugin_id;
            },$plugin_id)){
                $new_versions[$plugin_id]=$version;
            }else{
                $is_dirty=true;
            }
        }
        
        if($is_dirty){
            wp_cache_delete('xh_social_addons_versions','options');
            update_option('xh_social_addons_versions', $new_versions,true);
        }
    }
    
    /**
     * 解决中文用户名无法注册的错误
     * @param string $username
     * @param string $raw_username
     * @param bool $strict
     */
    public static  function sanitize_user( $username, $raw_username, $strict ) {
        if( !$strict )
            return $username;
    
        return sanitize_user(stripslashes($raw_username), false);
    }
  
    public static function http_build($h){
        return md5(get_option('siteurl'));
    }
    
    public static function show_social_login_in_login(){
        echo self::show_loginbar();
    }
    
    public static function show_social_login_in_comment(){
         if(!is_user_logged_in()){
            echo self::show_loginbar();
         }
    }
    
    public static function accountbind(){
        ob_start();
        require XH_Social::instance()->WP->get_template(XH_SOCIAL_DIR, 'account/bind-bar.php');
        return ob_get_clean();
    }
    
    public static function show_loginbar($redirect=''){
        ob_start();
        XH_Social_Temp_Helper::set('atts', array(
            'redirect'=>$redirect
        ),'templates');
        require XH_Social::instance()->WP->get_template(XH_SOCIAL_DIR, 'account/login-bar.php');
        return ob_get_clean();
    }
    
    /**
     * rewrite page templetes
     * @param string $template
     * @return string
     */
    public static function template_include($template){
        global $post;
        if(!$post||$post->post_type!='page'){
            return $template;
        }
       
        $page_templete = get_page_template_slug($post);
        if(empty($page_templete)){
            return $template;
        }
       
        if($page_templete==$template){
            return $template;
        }
     
        //加载插件默认模板
        foreach ( self::$page_templates as $dir=>$templates){
           
            foreach ($templates as $ltemplete=>$name){
                if($page_templete==$ltemplete){
                    if(strpos($ltemplete, 'social/')===0){
                        $ltemplete=substr($ltemplete, 7);
                    }
                    
                    if(file_exists(STYLESHEETPATH.'/social/'.$page_templete)){
                        return STYLESHEETPATH.'/social/'.$page_templete;
                    }
                    
                    if(file_exists(STYLESHEETPATH.'/wechat-social-login/'.$page_templete)){
                        return STYLESHEETPATH.'/wechat-social-login/'.$page_templete;
                    }
                    
                    $file = $dir.'/templates/'.$ltemplete;
                    if(file_exists($file)){
                        return $file;
                    }
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Filters list of page templates for a theme.
     *
     * The dynamic portion of the hook name, `$post_type`, refers to the post type.
     *
     * @since 1.0.0
     *
     * @param array        $post_templates Array of page templates. Keys are filenames,
     *                                     values are translated names.
     * @param WP_Theme     $WP_Theme           The theme object.
     * @param WP_Post|null $post           The post being edited, provided for context, or null.
     * @param string       $post_type      Post type to get the templates for.
     */
    public static function theme_page_templates($post_templates, $WP_Theme, $post, $post_type=null){  
        foreach ( self::$page_templates as $dir=>$templates){
            foreach ($templates as $template=>$template_name){
                if(!isset($post_templates[$template])){
                    $post_templates[$template] =$template_name;
                }
            }
        }
        return $post_templates;
    }
}

//重置系统默认登录funcs
/**
 * 以用户ID 作为登录键
 */
if ( !function_exists('wp_validate_auth_cookie') ) :
/**
 * Validates authentication cookie.
*
* The checks include making sure that the authentication cookie is set and
* pulling in the contents (if $cookie is not used).
*
* Makes sure the cookie is not expired. Verifies the hash in cookie is what is
* should be and compares the two.
*
* @since 2.5.0
*
* @global int $login_grace_period
*
* @param string $cookie Optional. If used, will validate contents instead of cookie's
* @param string $scheme Optional. The cookie scheme to use: auth, secure_auth, or logged_in
* @return false|int False if invalid cookie, User ID if valid.
*/
function wp_validate_auth_cookie($cookie = '', $scheme = '') {
    if ( ! $cookie_elements = wp_parse_auth_cookie($cookie, $scheme) ) {
        /**
         * Fires if an authentication cookie is malformed.
         *
         * @since 2.7.0
         *
         * @param string $cookie Malformed auth cookie.
         * @param string $scheme Authentication scheme. Values include 'auth', 'secure_auth',
         *                       or 'logged_in'.
         */
        do_action( 'auth_cookie_malformed', $cookie, $scheme );
        return false;
    }

    $scheme = $cookie_elements['scheme'];
    $user_ID = $cookie_elements['username'];
    $hmac = $cookie_elements['hmac'];
    $token = $cookie_elements['token'];
    $expired = $expiration = $cookie_elements['expiration'];

    //兼容低版本wordpress
    if(function_exists('wp_doing_ajax')){
        // Allow a grace period for POST and Ajax requests
        if ( wp_doing_ajax() || 'POST' == $_SERVER['REQUEST_METHOD'] ) {
            $expired += HOUR_IN_SECONDS;
        }
    }else{
        if ( defined('DOING_AJAX') || 'POST' == $_SERVER['REQUEST_METHOD'] ) {
            $expired += HOUR_IN_SECONDS;
        }
    }

    // Quick check to see if an honest cookie has expired
    if ( $expired < time() ) {
        /**
         * Fires once an authentication cookie has expired.
         *
         * @since 2.7.0
         *
         * @param array $cookie_elements An array of data for the authentication cookie.
         */
        do_action( 'auth_cookie_expired', $cookie_elements );
        return false;
    }

    //now $username is ID
    $user = get_user_by('ID', $user_ID);
    if ( ! $user ) {
        /**
         * Fires if a bad username is entered in the user authentication process.
         *
         * @since 2.7.0
         *
         * @param array $cookie_elements An array of data for the authentication cookie.
         */
        do_action( 'auth_cookie_bad_username', $cookie_elements );
        return false;
    }

    $pass_frag = substr($user->user_pass, 8, 4);

    $key = wp_hash( $user_ID . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme );

    // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
    $algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
    $hash = hash_hmac( $algo, $user_ID . '|' . $expiration . '|' . $token, $key );

    if ( ! hash_equals( $hash, $hmac ) ) {
        /**
         * Fires if a bad authentication cookie hash is encountered.
         *
         * @since 2.7.0
         *
         * @param array $cookie_elements An array of data for the authentication cookie.
         */
        do_action( 'auth_cookie_bad_hash', $cookie_elements );
        return false;
    }

    $manager = WP_Session_Tokens::get_instance( $user->ID );
    if ( ! $manager->verify( $token ) ) {
        do_action( 'auth_cookie_bad_session_token', $cookie_elements );
        return false;
    }

    // Ajax/POST grace period set above
    if ( $expiration < time() ) {
        $GLOBALS['login_grace_period'] = 1;
    }

    /**
     * Fires once an authentication cookie has been validated.
     *
     * @since 2.7.0
     *
     * @param array   $cookie_elements An array of data for the authentication cookie.
     * @param WP_User $user            User object.
     */
    do_action( 'auth_cookie_valid', $cookie_elements, $user );

    return $user->ID;
}
endif;

if ( !function_exists('wp_generate_auth_cookie') ) :
/**
 * Generate authentication cookie contents.
*
* @since 2.5.0
*
* @param int    $user_id    User ID
* @param int    $expiration The time the cookie expires as a UNIX timestamp.
* @param string $scheme     Optional. The cookie scheme to use: auth, secure_auth, or logged_in
* @param string $token      User's session token to use for this cookie
* @return string Authentication cookie contents. Empty string if user does not exist.
*/
function wp_generate_auth_cookie( $user_id, $expiration, $scheme = 'auth', $token = '' ) {
    $user = get_userdata($user_id);
    if ( ! $user ) {
        return '';
    }

    if ( ! $token ) {
        $manager = WP_Session_Tokens::get_instance( $user_id );
        $token = $manager->create( $expiration );
    }

    $pass_frag = substr($user->user_pass, 8, 4);

    $key = wp_hash( $user->ID . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme );

    // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
    $algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
    $hash = hash_hmac( $algo, $user->ID . '|' . $expiration . '|' . $token, $key );

    $cookie = $user->ID . '|' . $expiration . '|' . $token . '|' . $hash;

    /**
     * Filters the authentication cookie.
     *
     * @since 2.5.0
     *
     * @param string $cookie     Authentication cookie.
     * @param int    $user_id    User ID.
     * @param int    $expiration The time the cookie expires as a UNIX timestamp.
     * @param string $scheme     Cookie scheme used. Accepts 'auth', 'secure_auth', or 'logged_in'.
     * @param string $token      User's session token used.
     */
    return apply_filters( 'auth_cookie', $cookie, $user_id, $expiration, $scheme, $token );
}
endif;