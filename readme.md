Route
======

Route is a simple PHP Router, just less than 100 lines of code. 

### usage ###

use namespace

```
use cmhc\amber\modules\Route;
```

Normal match

```
use cmhc\amber\modules\Route;

Route::get("/",function(){
	echo 'hello';
});
```
open your browser, type `http://localhost/Hcrail/` in the address bar, and you will see "hello" in the browser.


Regular match

```
Route::get("/([0-9]*)\.html",function($args){
	echo $args[1];
});
```

Route can support regular match, regular expressions need be surrounded by parentheses,then the callback function parameters is the matching result.

type `http://localhost/Hcrail/6666.html` in the browser address bar and you will seee "6666" in browser window.

### config ###

apache

We need to create a file called .htaccess and go into the following sections

```
RewriteEngine On
RewriteBase /

RewriteCond %{REQUEST_FILENAME}% !-f
RewriteCond %{REQUEST_FILENAME}% !-d

RewriteRule ^.*$ index.php [L]
```

nginx

We need modify the file named nginx.conf, as follows

```
location / {
	try_files $uri $uri/ /index.php?/$uri;
}
```

config
======

manage your config 

### usage ###

first create config file, store some config information

```
use cmhc\amber\modules\Config;
Config::set("foo","bar");
```

then include this config file to your project, use Config::get to get config information

```
use cmhc\amber\modules\Config;
Config::get("foo");
```