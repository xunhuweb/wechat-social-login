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
    private static $page_templetes=array();
    
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
        //templete must be start with social.
        $templetes = apply_filters('xh_social_page_templetes', array());    
        foreach ($templetes as $dir=>$templete_list){
            self::$page_templetes[$dir]=$templete_list;
        }
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
        $channels = XH_Social::instance()->channel->get_social_channels(array('login'));
        global $current_user;
        if(!is_user_logged_in()){
            return '';
        }
        if(count($channels)==0){
            return '';
        }
        ob_start();
            ?>
            <div class="xh-regbox" style="width: 100%;">
              <h4 class="xh-title" style="margin-bottom:40px"><?php echo __('Account Binding/Unbundling',XH_SOCIAL)?></h4> 
              <div class="xh-form ">
              <?php if($channels){
        			    foreach ($channels as $channel){
        			        ?>
                            <div class="xh-form-group xh-mT20  xh-social clearfix">
                                 <div class="xh-left"><span class="xh-text"><img src="<?php echo $channel->icon?>" style="width:25px;vertical-align:middle;"/> <?php echo $channel->title?></span></div>
                                 <div class="xh-right"><?php echo $channel->bindinfo($current_user->ID)?></div>
                            </div>
                            <hr/>
                  <?php }
              }?>  
                 
              </div>
        
            </div>
           <?php 
           
           return ob_get_clean();
    }
    
    public static function show_loginbar($redirect=''){
        $channels =XH_Social::instance()->channel->get_social_channels(array('login'));    
        ob_start();
        ?>
	    <div class="xh_social_box" style="clear:both;">
    	   <?php 
	        foreach ($channels as $channel){
    	        ?>
    	        <a href="<?php echo XH_Social::instance()->channel->get_authorization_redirect_uri($channel->id,$redirect);?>" rel="noflow" style="background:url(<?php echo $channel->icon?>) no-repeat transparent;" class="xh_social_login_bar" title="<?php echo $channel->title;?>"></a>
    	        <?php 
    	    }?>
	    </div><?php 
	    
	    unset($channels);
	    
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
        foreach ( self::$page_templetes as $dir=>$templetes){
           
            foreach ($templetes as $ltemplete=>$name){
                if($page_templete==$ltemplete){
                    if(file_exists(STYLESHEETPATH.'/'.$page_templete)){
                        return STYLESHEETPATH.'/'.$page_templete;
                    }
                    
                    $file = $dir.'/templates/'.(strpos($ltemplete, 'social/')===0?substr($ltemplete, 7):$ltemplete);
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
        foreach ( self::$page_templetes as $dir=>$templetes){
            foreach ($templetes as $templete=>$templete_name){
                if(!isset($post_templates[$templete])){
                    $post_templates[$templete] =$templete_name;
                }
            }
        }
        return $post_templates;
    }
}