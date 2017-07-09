Route
======

Route 是一个简单的PHP路由，代码不到100行

Route 支持普通匹配和正则匹配，如下

普通匹配，匹配首页
```
use cmhc\amber\modules\Route;

Route::get("/",function(){
    echo 'hello';
});
```

正则匹配，匹配类似 http://localhost/6666.html 的地址
```
Route::get("/([0-9]*)\.html",function($args){
    echo $args[1];
});
```

### 服务器配置 ###

apache
在根目录建立 .htaccess 文件 
```
RewriteEngine On
RewriteBase /

RewriteCond %{REQUEST_FILENAME}% !-f
RewriteCond %{REQUEST_FILENAME}% !-d

RewriteRule ^.*$ index.php [L]
```

nginx

```
location / {
    try_files $uri $uri/ /index.php?/$uri;
}