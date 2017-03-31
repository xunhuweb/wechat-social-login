<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

require_once 'abstract-xh-view.php';

/**
 * Custom setting page
 *
 * @since       1.0.0
 * @author      ranj
 */
abstract class XH_Social_View_Form extends XH_Social_Abstract_View{
    /**
     * 页面
     * @var Abstract_XH_Social_Settings_Page
     * @since 1.0.0
     */
    protected $page;
    
    /**
     * 
     * @param Abstract_XH_Social_Settings_Page $page
     */
    protected function __construct($page){
        $this->page = $page;
    }
    
    const notice ='xh_social_notice';
 
    /* (non-PHPdoc)
     * @see XH_Social_SHOP_View::before_content()
     */
	public function before_content(){	
	    
	}
	
	/**
	 * 进行表单数据存储处理
	 *
	 * @since 1.0.0
	 */
	public function process_admin_options(){
	    //save datas ...
	}
	
	
	/* (non-PHPdoc)
	 * @see XH_Social_SHOP_View::after_content()
	 */
	public  function after_content(){
		
	}
}