<?php
namespace amber\modules;
use amber\modules\Instance;

class Http extends Instance
{

	protected $optmap;//curl 设置选项的数组映射关系

	protected $curlopt;//最终的设置数组

	protected $curl;//curl句柄

	protected $curlQueue;//curl多线程列队

	/**
	* 获取结果的网页编码
	* @var string
	*/
	protected $charset;

	/**
	* 结果状态吗
	* @var [type]
	*/
	protected $code;

	/**
	* 构造函数
	* 作用是
	* 1.初始化useragent，referer，cookie头部信息
	* 2.初始化curl
	*/
	public function __construct()
	{
		$this->optmap = array(
		  'header'        => CURLOPT_HEADER,
		  'returntransfer'=> CURLOPT_RETURNTRANSFER,
		  'useragent'     => CURLOPT_USERAGENT,
		  'referer'       => CURLOPT_REFERER,
		  'cookie'        => CURLOPT_COOKIE,
		  'url'           => CURLOPT_URL,
		  'proxy'         => CURLOPT_PROXY,
		  'port'          => CURLOPT_PROXYPORT,
		);

		$this->default_opt = array(
		  'useragent'=>'Mozilla/5.0 (Windows NT 6.1; rv:45.0) Gecko/20100101 Firefox/45.0',//浏览器useragent信息
		  'header'=>true,
		  'returntransfer'=>true
		);

		$this->init();
	}

	public function __destruct()
	{
		curl_close($this->curl);
	}

	/**
	*执行简单的http请求，将请求的结果返回
	*@param string $url 任意网址
	*@return string 网站内容
	*/
	public function get($url)
	{
		curl_setopt($this->curl, CURLOPT_URL, $url);
		return $this->run();
	}

	/**
	*post请求
	*@param string $url url内容 
	*@param array $post 需要发送post请求的数组
	*/
	public function post($url,$post)
	{
		curl_setopt($this->curl,CURLOPT_URL,$url);
		curl_setopt($this->curl,CURLOPT_POST,1);
		curl_setopt($this->curl,CURLOPT_POSTFIELDS,$post);    
		return $this->run();

	}

	/**
	* 获取响应码
	*/
	public function getCode()
	{
		return $this->code;
	}

	/**
	* 获取网页编码
	*/
	public function getCharset()
	{
		return $this->charset;
	}

	/**
	* 开启ssl
	* 获取https协议的站点必须要开启此选项
	*/
	public function enableSSL()
	{
		curl_setopt($this->curl,CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($this->curl,CURLOPT_SSL_VERIFYPEER, false);
	}

	/**
	* 设置UA
	* @param $ua string 标准的ua字符串
	*/
	public function setUA($UA)
	{
		curl_setopt($this->curl,CURLOPT_USERAGENT, $UA);
	}



	/**
	* 批量设置curl参数，参数并非curl的参数，而是去掉CURLOPT之后的参数，程序会将参数映射到CURLOPT上面
	* @param array $option 有效的参数为一个 '选项=>值'的数组。 可选的选项有
	* header=>0|1
	* returntransfer=>0|1
	* useragent=>浏览器UA
	* referer=>来源地址
	* cookie=>cookie内容
	* url=>请求的地址
	* proxy=>代理ip
	* port=>代理端口
	*/
	public function set($options)
	{
		foreach($options as $opt=>$value){
		  $this->curlopt[$this->optmap[$opt]] = $value;//设置参数
		}
		curl_setopt_array($this->curl,array_filter($this->curlopt));
	}

	/**
	* 设置
	*/
	public function setopt($key,$val)
	{
		curl_setopt($this->curl,$key,$val);
	}


	/**
	* 单独设置cookie，将cookie加载到设置项中。
	* @param string $cookie cookie内容
	* @return none
	*/
	public function setCookie($cookie)
	{
		curl_setopt($this->curl,CURLOPT_COOKIE,$cookie);
	}

	/**
	*单独设置代理
	* @param string $proxy 比如172.0.0.1:80,端口可加可不加
	*/
	public function setProxy($proxy)
	{
		if (strpos($proxy,':')) {
		  $proxy_array = explode(':',$proxy);
		  $ip = $proxy_array[0];
		  $port = $proxy_array[1];
		} else {
		  $ip = $proxy;
		}

		curl_setopt($this->curl,CURLOPT_PROXY,$ip);

		if (isset($port)) {
			curl_setopt($this->curl,CURLOPT_PROXYPORT,$port);
		}
	}

	/**
	* 单独设置来路
	* @param string $referer 参数为来路的地址
	* @return none
	*/
	public function setReferer($referer)
	{
		curl_setopt($this->curl, CURLOPT_REFERER, $referer); //构造来路
	}

	/**
	* 单独设置超时时间
	* @param int $timeout 参数为时间限制，单位为秒，最小值为1
	* @return none
	*/
	public function setTimeout($timeout)
	{
		curl_setopt($this->curl,CURLOPT_TIMEOUT, $timeout);
	}

	/**
	* 设置是否显示头部信息
	* @param $header boolean
	*/
	public function setHeader($header)
	{
		curl_setopt($this->curl, CURLOPT_HEADER, $header);
	}


	/**
	* 多线程请求方法
	* @param array $url_array 一个url数组
	* @return array 请求状态结果
	*/
	public function multiGet($url_array , $content = false)
	{
		$this->multi_init();//调用多线程初始化，对$curlQueue复制

		foreach($url_array as $url){
		  $curl = curl_init();
		  curl_setopt_array(
		      $curl,
		      array(
		        CURLOPT_URL=>$url,
		        CURLOPT_RETURNTRANSFER=>1,
		        CURLOPT_HEADER=>1,
		        CURLOPT_NOSIGNAL=>true,
		        CURLINFO_HEADER_OUT=>true
		      )
		   );
		  curl_multi_add_handle($this->curlQueue,$curl);//添加执行列队
		}
		$responses = array();
		do{
		    while( ( $code = curl_multi_exec($this->curlQueue,$active ) ) == CURLM_CALL_MULTI_PERFORM );
		    if ($code != CURLM_OK) { break; }
		    //找出哪一个是已经完成的请求
		    while ($done = curl_multi_info_read($this->curlQueue)) {
		    
		        // 获取请求的结果
		        $info = curl_getinfo($done['handle'],CURLINFO_HEADER_OUT);            
		        $error = curl_error($done['handle']);
		        if($content){
		          $html = curl_multi_getcontent($done['handle']);
		          $responses[] = compact('info', 'error','html');
		        }else{
		          $responses[] = compact('info', 'error');
		        }
		        
		        //移除已经完成的列队
		        curl_multi_remove_handle($this->curlQueue, $done['handle']);
		        curl_close($done['handle']);
		        
		    }
		    // 锁定输出的内容
		    if ($active > 0) {
		        curl_multi_select($this->curlQueue, 0.5);
		    }
		    
		    
		}while ($active);

		curl_multi_close($this->curlQueue);
		return $responses;    
	}


	/**
	* 单线程初始化，执行curl_init，将句柄赋值给$this->curl
	* @param none
	* @return none
	*/
	protected function init()
	{
		$this->curl = curl_init();
		$this->curlopt = array();
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_MAXREDIRS, 10);    
		$this->set($this->default_opt);//设置初始信息
	}

	/**
	* 多线程初始化函数
	* 对多线程处理事务进行初始化工作，并将句柄赋值给$this->curlQueue。仅仅在执行多url请求的时候才会执行此方法
	* @param none
	* @return none
	*/
	protected function multi_init()
	{
		$this->curlQueue = curl_multi_init();
	}


	/**
	* 执行最后的curl步骤，发送以及接收数据，作为结果返回
	* @param none
	* @return 请求得到的结果
	*/

	protected function run()
	{
		$result = curl_exec($this->curl);
		if ($result === false) {
		  return false;
		}
		$this->code = curl_getinfo($this->curl,CURLINFO_HTTP_CODE);
		$this->contentType = curl_getinfo($this->curl,CURLINFO_CONTENT_TYPE);
		$this->readContentCharset($result);
		return $result;
	}



	/**
	* 读取网页编码
	*/
	protected function readContentCharset($content)
	{
		if (!empty($this->contentType) && strpos($this->contentType, 'charset') !== false) {
		    $charset = substr($this->contentType, strpos($this->contentType, '=') + 1);
			return $this->charset = strtolower($charset);
		}

		//第一种模式
		$pattern1 = "|<meta[^>]*charset=[\"']([0-9a-zA-Z\-]*)[\"']|is";
		preg_match($pattern1, $content, $matches);
		if (!empty($matches[1])) {
		    return $this->charset = $matches[1];
		}

		//第二种模式
		$pattern2 = "|<meta[^>]*charset=([0-9a-zA-Z\-]*)['\"]|is";
		preg_match($pattern2, $content, $matches);
		if (!empty($matches[1])) {
		    return $this->charset = $matches[1];
		}

		//默认为utf8编码
		return $this->charset = 'utf-8';

	}
}