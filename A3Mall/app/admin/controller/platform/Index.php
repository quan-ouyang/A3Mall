<?php
// +----------------------------------------------------------------------
// | A3Mall
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.a3-mall.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: xzncit <158373108@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller\platform;

use mall\basic\Order;
use mall\utils\Date;
use mall\utils\Tool;
use think\App;
use app\admin\controller\Auth;
use think\facade\Db;
use think\facade\View;
use think\facade\Request;
use mall\response\Response;

class Index extends Auth {

    public function index(){
        // 会员订单总数和订单总购物额
        $order = Db::name("order")
            ->field("COUNT(*) AS order_num,order_amount")
            ->where("user_id > 0 AND status=5")->find();

        // 注册后还未购买过商品的会员
        $users_goods_count = Db::name("users")
            ->field("COUNT(DISTINCT o.user_id) as count")
            ->alias("u")
            ->join("order o","o.user_id=u.id","LEFT")
            ->where("o.user_id > 0 AND o.status=5")
            ->find();

        // 订单排行
        $fields = "SUM(o.order_amount) as total,COUNT(o.id) as count,u.username";
        $order_hot = Db::name("order")
            ->field($fields)
            ->alias("o")
            ->join("users u","o.user_id=u.id","LEFT")
            ->where("o.user_id > 0 AND o.status=5")
            ->group("o.user_id")->order("total DESC")
            ->limit(10)->select();
        $i=1;
        foreach($order_hot as $k=>$v){
            $order_hot[$k]['p'] = $i++;
        }

        return View::fetch("",[
            "order_total"=>Db::name("order")->count(),
            "goods_total"=>Db::name("goods")->count(),
            "users_total"=>Db::name("users")->count(),
            "users_comment_total"=>Db::name("users_comment")->count(),
            "e"=>empty($order["order_amount"]) ? "0.00" : number_format($order["order_amount"],2),
            "f"=>empty($order["order_amount"]) ? "0.00" : number_format($order["order_amount"]/$users_goods_count["count"],2),
            "g"=>$order_hot
        ]);
    }

    public function info(){
        $version = Db::query("SELECT VERSION() as version");
        return View::fetch("info",[
            "version"=>$version[0]['version'],
            "think_ver"=>App::VERSION,
            "app_version"=>'1.0',
            "ip"=>$_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT']
        ]);
    }

    public function cache(){
        if(Request::isAjax()){
            $type = Request::get("type","","trim,strip_tags");
            $path = (new App())->getRuntimePath();
            if(empty($type)){
                return ["code"=>0,"msg"=>"","data"=>[
                    [
                        "type"=>"cache","info"=>"数据缓存",
                        'size'=>Tool::convert(
                            Tool::getDirSize($path . "admin/cache") +
                            Tool::getDirSize($path . "home/cache")
                        )
                    ],
                    [
                        "type"=>"log","info"=>"日志数据",
                        'size'=>Tool::convert(
                            Tool::getDirSize($path . "admin/log") +
                            Tool::getDirSize($path . "admin/log")
                        )
                    ],
                    [
                        "type"=>"temp","info"=>"模板缓存",
                        'size'=>Tool::convert(
                            Tool::getDirSize($path . "admin/temp") +
                            Tool::getDirSize($path . "home/temp")
                        )
                    ]
                ]];
            }

            if(!in_array($type, ["cache","log","temp"])){
                return Response::returnArray("非法操作！",0);
            }

            switch($type){
                case "cache":
                    Tool::deleteFile($path . "admin/cache");
                    Tool::deleteFile($path . "home/cache");
                    break;
                case "log":
                    Tool::deleteFile($path . "admin/log");
                    Tool::deleteFile($path . "admin/log");
                    break;
                case "temp":
                    Tool::deleteFile($path . "admin/temp");
                    Tool::deleteFile($path . "home/temp");
                    break;
            }

            return Response::returnArray("清理缓存成功！",1);
        }

        return View::fetch();
    }


}