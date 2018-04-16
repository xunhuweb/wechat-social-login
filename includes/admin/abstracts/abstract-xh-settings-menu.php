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
    public function get_submenu(){
        $current= isset($_GET['sub'])?XH_Social_Helper_String::sanitize_key_ignorecase(trim($_GET['sub'])):'';
         
        $index=0;
        $menu =null;
        $menus = $this->menus();
        ksort($menus);
        reset($menus);
        foreach ($menus as $item){
            if($index++===0){
                $menu=$item;
            }
    
            $select =strcasecmp($current,$item->id)===0;
            if($select){
                $menu=$item;
                break;
            }
        }
    
        return $menu;
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
        $this->submenu = $menu->get_submenu();
    }
     
    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::menus()
     */
    public function menus(){
        $menus = $this->page->menus();
        $show_menus = array();
        ksort($menus);
        reset($menus);
        foreach ($menus as $menu){
            if(!$menu||!$menu instanceof Abstract_XH_Social_Settings_Menu){
                continue;
            }
        
            $show_menus[]=array(
                'name'=>isset($menu->menu_title)&&!empty($menu->menu_title)?$menu->menu_title: $menu->title,
                'url'=>$menu->get_menu_url($this->page),
                'selected'=>$this->menu->id===$menu->id
            );
        }
        
        return $show_menus;
    }
    
    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::sub_menus()
     */
    public function sub_menus() {
        $submenus = $this->menu->menus();
        ksort($submenus);
        reset($submenus);
        $show_menus = array();
        if(!$this->submenu){
            return $show_menus;
        }
       
        foreach ($submenus as $menu){
            $show_menus[]=array(
                'name'=>isset($menu->menu_title)&&!empty($menu->menu_title)?$menu->menu_title: $menu->title,
                'url'=>$this->menu->get_submenu_url($this->page, $menu->id),
                'selected'=>$this->submenu->id===$menu->id
            );
        }
       
        return $show_menus;
    }

    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::process_admin_options()
     */
    public function process_admin_options(){
        if(!$this->submenu){
            return;
        }
        $this->submenu->process_admin_options();
    }

    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::before_content()
     */
    public function before_content() {
        if(!$this->submenu){
            return;
        }
        $this->submenu->admin_form_start();
    }
    
    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::content()
     */
    public function content() {
        if(!$this->submenu){
            return;
        }
        $this->submenu->admin_options();
    }
    
    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::after_content()
     */
    public function after_content() {
        if(!$this->submenu){
            return;
        }
        $this->submenu->admin_form_end();
    }
    
}