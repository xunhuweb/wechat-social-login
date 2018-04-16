<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
	
/**
 * Menu
 *
 * @since 1.0.0
 * @author ranj
 */
abstract class Abstract_XH_Social_Settings_Page extends Abstract_XH_Social_Settings{ 
    protected $current_menu;
    
    public function get_page_id(){
        return 'social_'.$this->id;
    }
    
    public function get_page_url(){
        return admin_url("admin.php?page={$this->get_page_id()}");
    }
    
    /**
     * 菜单
     * @return array
     * @since 1.0.0
     */
    public function menus(){
        return apply_filters("xh_social_admin_page_{$this->id}", array());
    }
    
/**
     * 输出页面
     * 
     * @since 1.0.0
     */
    public function render(){
        $current_menu = $this->get_current_menu();
        if(!$current_menu){
           return;
        }
        
        $current_menu->render($this);
    }
    
    /**
     * 
     * @return NULL|Abstract_XH_Social_Settings_Menu
     */
    public function get_current_menu(){
        $menu_id = isset($_GET['section'])?$_GET['section']:null;
        
        $menus =$this->menus();
        ksort($menus);
        reset($menus);
        $index =0;
        $current =null;
        foreach ($menus as $menu){
            if($index++==0){
                $current=$menu;
            }
            
            if($menu->id ==$menu_id){
                $current=$menu;
                break;
            }
        }
        
        return $current;
    }
}