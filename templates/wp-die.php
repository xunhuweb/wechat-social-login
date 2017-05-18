<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$attdata = XH_Social_Temp_Helper::get('atts','templete');
$err = $attdata['err'];
$include_header_footer = $attdata['include_header_footer'];

if($err){
    if($err instanceof Exception){
        $err = "errcode:{$err->getCode()},errmsg:{$err->getMessage()}";
    }
    if($err instanceof XH_Social_Error){
        $err = "errcode:{$err->errcode},errmsg:{$err->errmsg}";
    }
    if($err instanceof WP_Error){
        $err = "errcode:{$err->get_error_code()},errmsg:{$err->get_error_message()}";
    }
    if(is_object($err)){
        $err = print_r($err,true);
    }
}

if(empty($err)){
    $err = XH_Social_Error::err_code(500)->errmsg;
}
if($include_header_footer){
    ?>
     <!DOCTYPE html>
            <html>
                <head>
                	<title>抱歉，出错了!</title>
                    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0">
                   
                </head>
                <body>
<?php  } ?>
       <link rel="stylesheet" type="text/css" href="<?php echo XH_SOCIAL_URL.'/assets/css/weui.css'?>">
       <div class="weui_msg">
       <div class="weui_icon_area"><i class="weui_icon_warn weui_icon_msg"></i></div>
       <div class="weui_text_area">
       <h4 class="weui_msg_title">抱歉，出错了!</h4>
       <p class="weui_msg_desc"><?php echo $err;?></p>
       </div>
       </div>
<?php 
if($include_header_footer){
    ?>
        </body>
    </html>
    <?php 
}