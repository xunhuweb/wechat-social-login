<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

class XH_Social_Wechat_Error{
    /**
     * @var XH_Social_Wechat_Token
     */
    private $wechat_token;
    public function __construct($appid=null,$appsecret=null){
        if($appid&&$appid instanceof XH_Social_Wechat_Token){
            $this ->wechat_token =$appid;
        }else{
            $this ->wechat_token = new XH_Social_Wechat_Token($appid,$appsecret);
        }
    }
    
    public function validate($response,&$retry=1){
        $obj = json_decode($response,true);
        if(!$obj){
            return $response;
        }
        
        if(isset($obj['errcode'])){
            switch ($obj['errcode']){
                case 0:
                    return $obj;
                    //获取access_token时AppSecret错误，或者access_token无效。请开发者认真比对AppSecret的正确性，或查看是否正在为恰当的公众号调用接口
                case 40002:
                    //不合法的AppID，请开发者检查AppID的正确性，避免异常字符，注意大小写
                case 40125:
                    //合法的appsecret
                case 40013:
                     //ip白名单
                case 40164:
                    //不合法的OpenID，请开发者确认OpenID（该用户）是否已关注公众号，或是否是其他公众号的OpenID
                case 40003: 
                case 40132:
                case 41001:
                    throw new Exception($response,500);
                    //access_token超时，请检查access_token的有效期，请参考基础支持-获取access_token中，对access_token的详细机制说明
                case 42001:
                    //不合法的access_token，请开发者认真比对access_token的有效性（如是否过期），或查看是否正在为恰当的公众号调用接口
                case 40014:
                    //缺少access_token参数
                //获取 access_token 时 AppSecret 错误，或者 access_token 无效。请开发者认真比对 AppSecret 的正确性
                case 40001:
                    //不合法的凭证类型
                    if($retry>0){
                       $this ->wechat_token->access_token($retry,true);
                    }
                    throw new Exception($response);
                default:
                    throw new Exception($response);
            }
        }
        
        return $obj;
    } 
}
?>