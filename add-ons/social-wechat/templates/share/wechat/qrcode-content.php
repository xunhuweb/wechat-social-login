<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! $guessurl = site_url() ){
    $guessurl = wp_guess_url();
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8">
    <title><?php echo __('Wechat Share',XH_SOCIAL)?></title>
  	<script src="<?php echo $guessurl.'/wp-includes/js/jquery/jquery.js'; ?>"></script>
    <script src="<?php echo XH_SOCIAL_URL.'/assets/js/qrcode.js'?>"></script>
</head>
<body>
<div id="qrcode" style="padding: 10px;vertical-align: middle;line-height: 100%;margin:0 auto;" align="center"></div>
<div style="display:table;width:100%;text-align:center;"><h5><?php echo __('打开微信“扫一扫”，扫描上面的二维码，打开网页后再点击微信右上角的菜单、即可分享到微信',XH_SOCIAL)?></h5></div>
    <script>
    <?php 
    $url = isset($_GET['url'])?esc_url_raw(urldecode($_GET['url'])):'';
    if(empty($url)){
        $url=home_url('/');
    }
    ?>
    (function($){
    	var qrcode = new QRCode(document.getElementById("qrcode"), {
            width : 282,
            height : 282
        });
        qrcode.makeCode("<?php print $url?>");
    })(jQuery);
    </script>
</body>
</html>