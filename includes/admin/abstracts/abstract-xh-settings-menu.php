<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
	
/**
 * Menu
 *
 * @since 1.0.0
 * @author ranj
 */
abstract class Abstract_XH_Social_Settings_Menu extends Abstract_XH_Social_Settings{  
    /**
     * 获取菜单链接
     * @param Abstract_XH_Social_Settings_Page $page
     * @return string
     */
    public function get_menu_url($page){
        return admin_url("admin.php?page={$page->get_page_id()}&section={$this->id}");
    }
    public function get_submenu_url($page,$sub_id){
        return admin_url("admin.php?page={$page->get_page_id()}&section={$this->id}&sub=$sub_id");
    }
    /**
     * 一级菜单
     *
     * @return array
     * @since 1.0.0
     */
    public function menus(){
        return apply_filters("xh_social_admin_menu_{$this->id}", array());
    }
    
    /**
     * 输出页面
     * 
     * @since 1.0.0
     */
    public function render($page){
        $view = new XH_Social_Menu_View($page,$this);
        $view->render();
    }
}


/**
 *
 * 设置页面 html模板
 *
 * @author ranj
 * @since 1.0.0
 */
class XH_Social_Menu_View extends XH_Social_View_Form{
    /**
     * 一级菜单
     * @var Abstract_XH_Social_Settings_Menu
     * @since 1.0.0
     */
    private $menu;
    
    /**
     * 二级菜单
     *
     * @var Abstract_XH_Social_Settings
     * @since 1.0.0
     */
    private $submenu;
    /**
     * 初始化
     * 
     * @param Abstract_XH_Social_Settings_Page $page
     * @param Abstract_XH_Social_Settings_Menu $menu
     */
    public function __construct($page,$menu){
        parent::__construct($page);
        
        $this->menu = $menu;
    }
     
    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::menus()
     */
    public function menus(){
        $menus = $this->page->menus();
        $show_menus = array();
        foreach ($menus as $menu){
            if(!$menu||!$menu instanceof Abstract_XH_Social_Settings_Menu){
                continue;
            }
        
            $show_menus[]=array(
                'name'=>$menu->title,
                'url'=>$menu->get_menu_url($this->page),
                'selected'=>$this->menu->id===$menu->id
            );
        }
        
        return $show_menus;
    }
    
    /**
     * 获取当前菜单
     * @return Abstract_XH_Social_Settings
     * @since 1.0.0
     */
    private function get_current_submenu(){
        if(is_null($this->submenu)){
            $menus =  $this->menu->menus();
            $current= isset($_GET['sub'])?XH_Social_Helper_String::sanitize_key_ignorecase(trim($_GET['sub'])):'';
   
            $index=0;
            foreach ($menus as $menu){
                if(!$menu||!$menu instanceof Abstract_XH_Social_Settings){
                    continue;
                }
    
                if($index++===0){
                    $this->submenu=$menu;
                }
                
                $select =strcasecmp($current,$menu->id)===0;
                if($select){
                    $this->submenu=$menu;
                    break;
                }
            }
        }
    
        return $this->submenu;
    }
    
    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::sub_menus()
     */
    public function sub_menus() {
        $current_submenu= $this->get_current_submenu();
       
        if(!$current_submenu){
            return array();
        }
        
        $submenus = $this->menu->menus();;
        $show_menus = array();
       
        foreach ($submenus as $menu){
            if(!$menu||!$menu instanceof Abstract_XH_Social_Settings){
                continue;
            }

            $show_menus[]=array(
                'name'=>$menu->title,
                'url'=>$this->menu->get_submenu_url($this->page, $menu->id),
                'selected'=>$current_submenu->id===$menu->id
            );
        }
        
        if(count($show_menus)<=1){
            return array();
        }
        return $show_menus;
    }

    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::process_admin_options()
     */
    public function process_admin_options(){
        $current_submenu= $this->get_current_submenu();
        if(!$current_submenu){
            return;
        }

        $current_submenu->process_admin_options();
    }

    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::before_content()
     */
    public function before_content() {
        $current_submenu= $this->get_current_submenu();
        if(!$current_submenu){
            return;
        }
         
        $current_submenu->admin_form_start();
    }
    
    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::content()
     */
    public function content() {
        $current_submenu= $this->get_current_submenu();
        if(!$current_submenu){
            return;
        }
         
        $current_submenu->admin_options();
    }
    
    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::after_content()
     */
    public function after_content() {
        $current_submenu= $this->get_current_submenu();
        if(!$current_submenu){
            return;
        }
         
        $current_submenu->admin_form_end();
    }
    
}