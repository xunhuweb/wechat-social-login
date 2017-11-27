<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class XH_Social_Page{
    private static $page_templates=array();
    
    public static function init(){
        add_filter( 'theme_page_templates',__CLASS__.'::theme_page_templates',10,4);
        //4.7.0之前的不兼容
        add_filter( 'page_template_hierarchy', __CLASS__.'::page_template_hierarchy' ,10,1);
        
        add_filter( 'template_include', __CLASS__.'::template_include' ,10,1);
        
        //template must be start with shop.
        $templates = apply_filters('xh_social_page_templetes', array());
        
        foreach ($templates as $dir=>$template_list){
            self::$page_templates[$dir]=$template_list;
        }
    }
    

    private static $_templates = array();
    public static function page_template_hierarchy($templates){  
        if(!is_page()){
            return $templates;
        }
        
        self::$_templates=$templates;
        
        return $templates;
    }
    
    public static function template_include($template){
        //兼容4.7.0版本之前，没有page_template_hierarchy 这个钩子函数
        if(count(self::$_templates)==0&&!did_action('page_template_hierarchy')){
           self::$_templates[] = get_page_template_slug();
        }
       
        if(!is_page()||count(self::$_templates)==0){
            return $template;
        }
        
        $default_template = self::$_templates[0];
        if(strpos($default_template, 'social/')===0){
            $default_template = substr($default_template, 7);
        }
        
        if(file_exists(STYLESHEETPATH.'/social/'.$default_template)){
            return STYLESHEETPATH.'/social/'.$default_template;
        }
        
        if(file_exists(STYLESHEETPATH.'/wechat-social-login/'.$default_template)){
            return STYLESHEETPATH.'/wechat-social-login/'.$default_template;
        }
        
        foreach ( self::$page_templates as $dir=>$templates){
            foreach ($templates as $ltemplate=>$name){
                if(strpos($ltemplate, 'social/')===0){
                    $ltemplate = substr($ltemplate, 7);
                }
                
                if($default_template==$ltemplate){
                    return $dir.'/templates/'.$ltemplate;
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
       $template_now =  get_page_template_slug($post);
       if(strpos($template_now, 'social/')===0){
           update_post_meta($post->ID, '_wp_page_template', substr($template_now, 7));
       }
       
        foreach ( self::$page_templates as $dir=>$templates){
            foreach ($templates as $template=>$template_name){
                if(strpos($template, 'social/')===0){
                    $template = substr($template, 7);
                }
                
                if(!isset($post_templates[$template])){
                    $post_templates[$template] =$template_name;
                }
            }
        }
        return $post_templates;
    }
}