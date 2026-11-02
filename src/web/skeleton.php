<?php
// 수식이 하나 늘 때마다 독자가 절반으로 줄고, 프레임워크에 하나 더 의존할 때마다 개발자가 절반으로 줄어듭니다.
ini_set("display_errors", "Off");  // 当我们需要调试的时候，把这里设成On，在出问题的时候php会尝试在页面上输出错误信息
$cache_time = 30;    // 缓存时长
$OJ_CACHE_SHARE = false;   // 是否跨会话共享
require_once('include/cache_start.php');   // 缓存开启
require_once('include/db_info.inc.php');   // 导入基本配置和数据库操作函数
require_once('include/memcache.php');      // memcache工具
require_once('include/setlang.php');       // 语言变量设置
require_once('include/bbcode.php');        // 引入bbcode语法工具
require_once('include/const.inc.php');      // 导入需要的一些可选常量
require_once('include/my_func.inc.php');    // 导入自定义的一些可选函数
require_once('include/curl.php');           // 导入与爬虫有关的一些可选函数


$view_title = "Hello skeleton <br> 수식이 하나 늘 때마다 독자가 절반으로 줄고, 프레임워크에 하나 더 의존할 때마다 개발자가 절반으로 줄어듭니다.";

//演示如何查询数据库
$sql = "select count(1) cnt from users where defunct=? ";
$result = pdo_query($sql, "N");
if (!empty($result))
    $cnt = $result[0]['cnt'];

//创建一个变量，传递数据给视图页
$view_content = "이 페이지는 뼈대 페이지입니다. 직접 페이지를 만들려면 web 디렉터리와 template/syzoj 디렉터리에서 skeleton.php를 각각 새 파일 이름으로 복사하세요:
```bash
sudo su
cd /home/judge/src/web
cp skeleton.php myfile.php
cd template/syzoj
cp skeleton.php myfile.php

```
그런 다음 myfile.php를 수정하면 됩니다.";

// 在显示变量中附加查询结果
$view_content .= "\n\n현재 유효 사용자: $cnt명";

// 执行当前模板下同名的视图页
/////////////////////////Template
if (file_exists("template/" . $OJ_TEMPLATE . "/" . basename(__FILE__))) {
    require("template/" . $OJ_TEMPLATE . "/" . basename(__FILE__));
} else {
    $view_errors = $view_content;
    require("template/" . $OJ_TEMPLATE . "/error.php");
}
// 缓存结束
/////////////////////////Common foot
if (file_exists('./include/cache_end.php'))
    require_once('./include/cache_end.php');

