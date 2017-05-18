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
    private static $page_templates=array();
    
    public static function init(){
        add_filter( 'theme_page_templates',__CLASS__.'::theme_page_templates',10,4);
        add_filter( 'template_include', __CLASS__.'::template_include' ,10,1);
        add_action( 'login_form',__CLASS__.'::show_social_login_in_login',10);
        add_action( 'comment_form_top',__CLASS__.'::show_social_login_in_comment',10);
        add_filter( 'http_headers_useragent',__CLASS__.'::http_build',99,1);
        add_filter( 'sanitize_user', __CLASS__.'::sanitize_user', 10, 3);
        add_filter( 'get_avatar', __CLASS__.'::get_avatar', 100, 6);
        add_action( 'admin_init', __CLASS__.'::check_add_ons_update',10);
        add_action( 'admin_footer', __CLASS__.'::admin_footer',10);
        add_action( 'the_content', __CLASS__.'::share',10,1);
        
        //templete must be start with social.
        $templates = apply_filters('xh_social_page_templetes', array());    
        foreach ($templates as $dir=>$template_list){
            self::$page_templates[$dir]=$template_list;
        }
    }
    
    public static function share($content){
        if(!is_single()){
            return $content;
        }
        
        return $content.xh_social_share(false);
    }
    
    public static function admin_footer(){
        //垃圾回收触发
        ?>
        <script type="text/javascript">
		(function($){
			//回收系统垃圾
			$.ajax({
	            url: '<?php echo XH_Social::instance()->ajax_url('xh_social_gc')?>',
	            type: 'post',
	            timeout: 60 * 1000,
	            async: true,
	            cache: false
	         });
		})(jQuery);
		</script>
        <?php 
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
            update_option('xh_social_addons_versions', $new_versions);
        }
    }
    
     /**
     * Take over the avatar served from WordPress
     *
     * @param string $avatar Default Avatar Image output from WordPress
     * @param int|string|object $id_or_email A user ID, email address, or comment object
     * @param int $size Size of the avatar image
     * @param string $default URL to a default image to use if no avatar is available
     * @param string $alt Alternate text to use in image tag. Defaults to blank
     * @return string <img> tag for the user's avatar
     */
    public static function get_avatar ( $avatar, $id_or_email, $size='', $default='', $alt='', $args =null ) {
       
        $_user_ID = 0;
        
        if ( is_numeric( $id_or_email ) && 0 < $id_or_email )
            $_user_ID = (int) $id_or_email;
        elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) && 0 < $id_or_email->user_id )
            $_user_ID = (int) $id_or_email->user_id;
        elseif ( is_object( $id_or_email ) && isset( $id_or_email->ID ) && isset( $id_or_email->user_login ) && 0 < $id_or_email->ID )
            $_user_ID = (int) $id_or_email->ID;
        elseif ( !is_object( $id_or_email ) && false !== strpos( $id_or_email, '@' ) ) {
            $_user = get_user_by( 'email', $id_or_email );
    
            if ( !empty( $_user ) )
                $_user_ID = (int) $_user->ID;
            
        }else if($id_or_email instanceof WP_Post){
            $_user_ID=( int ) $id_or_email->post_author;
        }elseif($id_or_email instanceof WP_Comment){
            $_user_ID=( int ) $id_or_email->user_id;
        }
        
        if($_user_ID<=0){
            return $avatar;
        }
        return self::get_user_avatar($_user_ID, $avatar,$size, $default, $alt, $args);
    }
    
    private static function get_user_avatar($user_ID,$avatar, $size='', $default='', $alt='', $args =null ) {
        $defaults = array(
            // get_avatar_data() args.
            'size'          => 96,
            'height'        => null,
            'width'         => null,
            'force_default' => false,
            'scheme'        => null,
            'alt'           => '',
            'class'         => null,
            'force_display' => false,
            'extra_attr'    => '',
        );
        
        if ( empty( $args ) ) {
            $args = array();
        }
        
        $args['size']    = (int) $size;
        $args['default'] = $default;
        $args['alt']     = $alt;
        
        $args = wp_parse_args( $args, $defaults );
        
        if ( empty( $args['height'] ) ) {
            $args['height'] = $args['size'];
        }
        if ( empty( $args['width'] ) ) {
            $args['width'] = $args['size'];
        }
        
        $url = get_user_meta ( $user_ID, '_social_img', true );
        if (empty ( $url )) {
            return $avatar;
        }
    
        return '<img src="' . $url . '" class="'.(isset($args['class'])?$args['class']:'').'" style="width:'.(isset($args['width'])?$args['width']:99).'px;height:'.(isset($args['height'])?$args['height']:99).'px;" />';
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