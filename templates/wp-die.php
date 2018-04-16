<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$attdata = XH_Social_Temp_Helper::clear('atts','templete');
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
		<style type="text/css">
            @font-face {
              font-weight: normal;
              font-style: normal;
              font-family: "weui";
              src: url('data:application/octet-stream;base64,AAEAAAALAIAAAwAwR1NVQrD+s+0AAAE4AAAAQk9TLzJAKEx1AAABfAAAAFZjbWFw64JcfgAAAhQAAAI0Z2x5ZvCBJt8AAARsAAAHLGhlYWQIuM5WAAAA4AAAADZoaGVhCC0D+AAAALwAAAAkaG10eDqYAAAAAAHUAAAAQGxvY2EO3AzsAAAESAAAACJtYXhwAR4APgAAARgAAAAgbmFtZeNcHtgAAAuYAAAB5nBvc3RP98ExAAANgAAAANYAAQAAA+gAAABaA+gAAP//A+kAAQAAAAAAAAAAAAAAAAAAABAAAQAAAAEAAKZXmK1fDzz1AAsD6AAAAADS2MTEAAAAANLYxMQAAAAAA+kD6QAAAAgAAgAAAAAAAAABAAAAEAAyAAQAAAAAAAIAAAAKAAoAAAD/AAAAAAAAAAEAAAAKAB4ALAABREZMVAAIAAQAAAAAAAAAAQAAAAFsaWdhAAgAAAABAAAAAQAEAAQAAAABAAgAAQAGAAAAAQAAAAAAAQOqAZAABQAIAnoCvAAAAIwCegK8AAAB4AAxAQIAAAIABQMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAUGZFZABA6gHqDwPoAAAAWgPpAAAAAAABAAAAAAAAAAAAAAPoAAAD6AAAA+gAAAPoAAAD6AAAA+gAAAPoAAAD6AAAA+gAAAPoAAAD6AAAA+gAAAPoAAAD6AAAA+gAAAAAAAUAAAADAAAALAAAAAQAAAFwAAEAAAAAAGoAAwABAAAALAADAAoAAAFwAAQAPgAAAAQABAABAADqD///AADqAf//AAAAAQAEAAAAAQACAAMABAAFAAYABwAIAAkACgALAAwADQAOAA8AAAEGAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAwAAAAAAMQAAAAAAAAADwAA6gEAAOoBAAAAAQAA6gIAAOoCAAAAAgAA6gMAAOoDAAAAAwAA6gQAAOoEAAAABAAA6gUAAOoFAAAABQAA6gYAAOoGAAAABgAA6gcAAOoHAAAABwAA6ggAAOoIAAAACAAA6gkAAOoJAAAACQAA6goAAOoKAAAACgAA6gsAAOoLAAAACwAA6gwAAOoMAAAADAAA6g0AAOoNAAAADQAA6g4AAOoOAAAADgAA6g8AAOoPAAAADwAAAAAALgBmAKIA3gEaAV4BtgHkAgoCRgKIAtIDFANOA5YAAAACAAAAAAOvA60ACwAXAAABDgEHHgEXPgE3LgEDLgEnPgE3HgEXDgEB9bz5BQX5vLv5BQX5u6zjBQXjrKvjBQXjA60F+by7+gQE+ru8+fy0BOSrq+QEBOSrq+QAAAIAAAAAA7MDswALACEAAAEOAQceARc+ATcuAQMHBiIvASY2OwERNDY7ATIWFREzMhYB7rn7BQX7ucL+BQX+JHYPJg92DgwYXQsHJggKXRgMA7MF/sK5+wUF+7nC/v31mhISmhIaARcICwsI/ukaAAADAAAAAAOtA6sACwAZACIAAAEOAQceARc+ATcuAQMUBisBIiY1ETY3MxYXJy4BNDYyFhQGAfC49gUF9ri++gUF+poKBxwHCgEILAgBHxMZGSYZGQOrBfq+uPYFBfa4vvr9dQcKCgcBGggBAQg5ARklGRklGQAAAAACAAAAAAOSA8IADQAfAAABDgEHERYEFzYkNxEuARMBBi8BJj8BNh8BFjclNh8BFgH0gchUCQEDkZEBAwlUyHr+vwQDlAMCFQMDegMEAScEAxMDA8IePRz+w9TwJCTw1AE9HD3+3f7DAgOZBAMcBANdAgL2AwMTBAADAAAAAAOCA7AADQAZACIAAAEOAQcRHgEXPgE3ES4BBzMWFQcGByMmLwE0EyImNDYyFhQGAfV7wVEJ+YuL+QlRwZIuCQoBBCIEAQogDhISHBISA7AdOxr+z8vnIyPnywExGjv3AQjYBAEBBNgI/rETHBISHBMAAAACAAAAAAO9A70AFwAjAAABLgE/AT4BHwEWMjclNhYXJxYUBwEGJiclJgAnBgAHFgAXNgABIAUCBQMFEAdiBxIGARMHEQYCBgb+0AYQBgIcBf79x77/AAUFAQC+xwEDAccGEQcEBwIFTAQF5QYBBgIGEAb+1QYBBqzHAQMFBf79x77/AAUFAQAABAAAAAADrwOtAAsAFwAtADEAAAEOAQceARc+ATcuAQMuASc+ATceARcOARMFDgEvASYGDwEGFh8BFjI3AT4BJiIXFjEXAfW8+QUF+by7+QUF+bus4wUF46yr4wUF4yv+9gcRBmAGDwUDBQEGfQUQBgElBQELDxQBAQOtBfm8u/oEBPq7vPn8tATkq6vkBATkq6vkAiLdBQEFSQUCBgQHEQaABgUBIQUPCwQBAQAAAAABAAAAAAO7AzoAFwAAEy4BPwE+AR8BFjY3ATYWFycWFAcBBiInPQoGBwUIGQzLDSALAh0MHgsNCgr9uQscCwGzCyEOCw0HCZMJAQoBvgkCCg0LHQv9sQsKAAAAAAIAAAAAA7gDuAALABEAAAEGAgceARc2JDcmABMhETMRMwHuvP0FBf28xQEABQX/ADr+2i35A7gF/wDFvP0FBf28xQEA/d4BTv7fAAAEAAAAAAOvA60AAwAPABsAIQAAARYxFwMOAQceARc+ATcuAQMuASc+ATceARcOAQMjFTM1IwLlAQHyvPkFBfm8u/kFBfm7rOMFBeOsq+MFBePZJP3ZAoMBAQEsBfm8u/oEBPq7vPn8tATkq6vkBATkq6vkAi39JAADAAAAAAPDA8MACwAbACQAAAEGAAcWABc2ADcmAAczMhYVAw4BKwEiJicDNDYTIiY0NjIWFAYB7sD+/AUFAQTAyQEHBQX++d42CAoOAQUEKgQFAQ4KIxMaGiYaGgPDBf75ycD+/AUFAQTAyQEH5woI/tMEBgYEASwIC/4oGicZGScaAAAEAAAAAAPAA8AACAASAB4AKgAAAT4BNCYiBhQWFyMVMxEjFTM1IwMGAAcWBBc+ATcmAgMuASc+ATceARcOAQH0GCEhMCEhUY85Ock6K83++AQEAQjNuf8FBf/Hq+MEBOOrq+MEBOMCoAEgMSAgMSA6Hf7EHBwCsQT++M25/wUF/7nNAQj8pwTjq6vjBATjq6vjAAAAAwAAAAADpwOnAAsAFwAjAAABBycHFwcXNxc3JzcDDgEHHgEXPgE3LgEDLgEnPgE3HgEXDgECjpqaHJqaHJqaHJqatrn1BQX1ubn1BQX1uajfBATfqKjfBATfAqqamhyamhyamhyamgEZBfW5ufUFBfW5ufX8xwTfqKjfBATfqKjfAAAAAwAAAAAD6QPpABEAHQAeAAABDgEjLgEnPgE3HgEXFAYHAQcBPgE3LgEnDgEHHgEXAo41gEmq4gQE4qqq4gQvKwEjOf3giLUDA7WIiLUDBLSIASMrLwTiqqriBATiqkmANP7dOQEZA7WIiLUDA7WIiLUDAAACAAAAAAPoA+gACwAnAAABBgAHFgAXNgA3JgADFg4BIi8BBwYuATQ/AScmPgEyHwE3Nh4BFA8BAfTU/uUFBQEb1NQBGwUF/uUDCgEUGwqiqAobEwqoogoBFBsKoqgKGxMKqAPoBf7l1NT+5QUFARvU1AEb/WgKGxMKqKIKARQbCqKoChsTCqiiCgEUGwqiAAAAABAAxgABAAAAAAABAAQAAAABAAAAAAACAAcABAABAAAAAAADAAQACwABAAAAAAAEAAQADwABAAAAAAAFAAsAEwABAAAAAAAGAAQAHgABAAAAAAAKACsAIgABAAAAAAALABMATQADAAEECQABAAgAYAADAAEECQACAA4AaAADAAEECQADAAgAdgADAAEECQAEAAgAfgADAAEECQAFABYAhgADAAEECQAGAAgAnAADAAEECQAKAFYApAADAAEECQALACYA+ndldWlSZWd1bGFyd2V1aXdldWlWZXJzaW9uIDEuMHdldWlHZW5lcmF0ZWQgYnkgc3ZnMnR0ZiBmcm9tIEZvbnRlbGxvIHByb2plY3QuaHR0cDovL2ZvbnRlbGxvLmNvbQB3AGUAdQBpAFIAZQBnAHUAbABhAHIAdwBlAHUAaQB3AGUAdQBpAFYAZQByAHMAaQBvAG4AIAAxAC4AMAB3AGUAdQBpAEcAZQBuAGUAcgBhAHQAZQBkACAAYgB5ACAAcwB2AGcAMgB0AHQAZgAgAGYAcgBvAG0AIABGAG8AbgB0AGUAbABsAG8AIABwAHIAbwBqAGUAYwB0AC4AaAB0AHQAcAA6AC8ALwBmAG8AbgB0AGUAbABsAG8ALgBjAG8AbQAAAAIAAAAAAAAACgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAECAQMBBAEFAQYBBwEIAQkBCgELAQwBDQEOAQ8BEAERAAZjaXJjbGUIZG93bmxvYWQEaW5mbwxzYWZlX3N1Y2Nlc3MJc2FmZV93YXJuB3N1Y2Nlc3MOc3VjY2Vzc19jaXJjbGURc3VjY2Vzc19ub19jaXJjbGUHd2FpdGluZw53YWl0aW5nX2NpcmNsZQR3YXJuC2luZm9fY2lyY2xlBmNhbmNlbAZzZWFyY2gFY2xvc2UAAAAA') format('truetype');
            }
            [class^="weui_icon_"]:before,
            [class*=" weui_icon_"]:before {
              font-family: "weui";
              font-style: normal;
              font-weight: normal;
              speak: none;
              display: inline-block;
              vertical-align: middle;
              text-decoration: inherit;
              width: 1em;
              margin-right: .2em;
              text-align: center;
              /* opacity: .8; */
              /* For safety - reset parent styles, that can break glyph codes*/
              font-variant: normal;
              text-transform: none;
              /* fix buttons height, for twitter bootstrap */
              line-height: 1em;
              /* Animation center compensation - margins should be symmetric */
              /* remove if not needed */
              margin-left: .2em;
              /* you can be more comfortable with increased icons size */
              /* font-size: 120%; */
              /* Uncomment for 3D effect */
              /* text-shadow: 1px 1px 1px rgba(127, 127, 127, 0.3); */
            }
            .weui_icon_circle:before {
              content: "\EA01";
            }
            /* '顮€' */
            .weui_icon_download:before {
              content: "\EA02";
            }
            /* '顮€' */
            .weui_icon_info:before {
              content: "\EA03";
            }
            /* '顮€' */
            .weui_icon_safe_success:before {
              content: "\EA04";
            }
            /* '顮€' */
            .weui_icon_safe_warn:before {
              content: "\EA05";
            }
            /* '顮€' */
            .weui_icon_success:before {
              content: "\EA06";
            }
            /* '顮€' */
            .weui_icon_success_circle:before {
              content: "\EA07";
            }
            /* '顮€' */
            .weui_icon_success_no_circle:before {
              content: "\EA08";
            }
            /* '顮€' */
            .weui_icon_waiting:before {
              content: "\EA09";
            }
            /* '顮€' */
            .weui_icon_waiting_circle:before {
              content: "\EA0A";
            }
            /* '顮€' */
            .weui_icon_warn:before {
              content: "\EA0B";
            }
            /* '顮€' */
            .weui_icon_info_circle:before {
              content: "\EA0C";
            }
            /* '顮€' */
            .weui_icon_cancel:before {
              content: "\EA0D";
            }
            /* '顮€' */
            .weui_icon_search:before {
              content: "\EA0E";
            }
            /* '顮€' */
            .weui_icon_clear:before {
              content: "\EA0F";
            }
            /* '顮€' */
            [class^="weui_icon_"]:before,
            [class*=" weui_icon_"]:before {
              margin: 0;
            }
            .weui_icon_success:before {
              font-size: 23px;
              color: #09BB07;
            }
            .weui_icon_waiting:before {
              font-size: 23px;
              color: #10AEFF;
            }
            .weui_icon_warn:before {
              font-size: 23px;
              color: #F43530;
            }
            .weui_icon_info:before {
              font-size: 23px;
              color: #10AEFF;
            }
            .weui_icon_success_circle:before {
              font-size: 23px;
              color: #09BB07;
            }
            .weui_icon_success_no_circle:before {
              font-size: 23px;
              color: #09BB07;
            }
            .weui_icon_waiting_circle:before {
              font-size: 23px;
              color: #10AEFF;
            }
            .weui_icon_circle:before {
              font-size: 23px;
              color: #C9C9C9;
            }
            .weui_icon_download:before {
              font-size: 23px;
              color: #09BB07;
            }
            .weui_icon_info_circle:before {
              font-size: 23px;
              color: #09BB07;
            }
            .weui_icon_safe_success:before {
              color: #09BB07;
            }
            .weui_icon_safe_warn:before {
              color: #FFBE00;
            }
            .weui_icon_cancel:before {
              color: #F43530;
              font-size: 22px;
            }
            .weui_icon_search:before {
              color: #B2B2B2;
              font-size: 14px;
            }
            .weui_icon_clear:before {
              color: #B2B2B2;
              font-size: 14px;
            }
            .weui_icon_msg:before {
              font-size: 104px;
            }
            .weui_icon_warn.weui_icon_msg:before {
              color: #F76260;
            }
            .weui_icon_safe:before {
              font-size: 104px;
            }    
            .weui_msg {
              padding-top: 36px;
              text-align: center;
            }
            .weui_msg .weui_icon_area {
              margin-bottom: 30px;
            }
            .weui_msg .weui_text_area {
              margin-bottom: 25px;
              padding: 0 20px;
            }
            .weui_msg .weui_msg_title {
              margin-bottom: 5px;
              font-weight: 400;
              font-size: 20px;
            }
            .weui_msg .weui_msg_desc {
              font-size: 14px;
              color: #888;
            }
            .weui_msg .weui_opr_area {
              margin-bottom: 25px;
            }
            .weui_msg .weui_extra_area {
              margin-bottom: 15px;
              font-size: 14px;
              color: #888;
            }
            .weui_msg .weui_extra_area a {
              color: #61749B;
            }
        </style>
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