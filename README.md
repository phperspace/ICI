# 一、介绍    

## ICI是什么？

    ICI是一个基于CI扩展出来的框架;      

## 做这个框架的愿景是什么？     

    对可预想到的共性问题提供优雅的解决方案，为框架增加实用的工具，减少项目前期框架性工作。

## 为什么要基于CI开发？

    基于CI开发，并不是因为CI有多好（相反CI其实挺烂）。
	基于CI开发，是因为我对CI的源码很熟，而且其它框架也好不到哪里去，它们各有各的烂。     
    所谓“君子性非已也，善假于物也”，框架再烂，至少很稳定，而且做了大量基础工作，我没必要重复造轮子。   
    我要做的是把已有的工具类包装成更好用的工具类，实在丑陋的代码，就重写覆盖之。  

# 二、ICI使用手册      

## ICI完全支持CI的操作。  

    CI的介绍和使用，请参见官网
    http://codeigniter.org.cn   

## ICI相对于CI做了哪些升级？    

### 1 日志      
1）进一步细化了日志级别，有8个日志级别，如下	

	'FATAL' => 1,
	'ERROR' => 1,
	'WARNING' => 2,
	'NOTICE' => 4,
	'TRACE' => 8,
	'DEBUG' => 16,
	'INFO' => 32,
	'ALL' => 64

可以在config.php里，配置最小记录级别，例如

	$config['log_threshold'] = 8;

2）写磁盘buffer，有效减少刷磁盘次数

	达到4096（页大小）才刷一次磁盘，最后log_finish再做一次强刷。

3）自动添加log_request, log_finish

	log_request是利用钩子（hook），在请求一开始记录requet数据；
	log_finish是通过扩展regist_shutdown_hanler，在php脚本执行退出前，记录的本次请求的各项数据统计。

4）规范日志格式
一条典型的log_finish日志，如下：

	[NOTICE][2017-04-20 01:32:29:906105][log_id=1492623149897549001414][line=/ICI/application/core/MY_common.php +7 function=::log_finish][uri=/module1/welcome][mark=request_out][proc_time=0.009400][time_total=0.009400][time_load_base=0.004800][time_ac_exe=0.004300 (s)][memory_use=1.750000][memory_peak=1.750000 (MB)]

5）封装了log_helper

	可以方便的记录各级别日志，如write_notice、write_warning、write_fatal


### 2 参数检查	

框架添加了参数检查的钩子，将参数验证可配置化。把琐碎的令人讨厌的“参数检查”从业务代码里剥离出来。
如果请求的参数不符合配置的规则，框架会自动返回一个“参数错误”。对应的核心代码为，hooks/Param.php

	使用时，用户只需要在config/param目录下，添加每个controller的参数检查配置。
	比如，对于uri是/module1/welcome/index的请求，可以添加config/param/module1/welcome.php，代码如下：

	<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	/**
	* welcome接口规则
	* @author Mr.Nobody
	*
	*/
	$config['param']['index']['method'] = HTTP_REQUEST_METHOD_GET;
	$config['param']['index']['rules'] = array(
		array(
			'field' => 'hello',
			'rules' => 'trim|required|max_length[32]'
		),
		array(
			'field' => 'arg1',
			'rules' => 'trim|integer|max_length[32]'
		),
	);

### 3 标准返回
封装了标准返回类 libraries/StdReturn.php  
1）response的数据格式为json格式（Content-Type:application/json）  
2）接口返回三元组(status, msg, data)，如果不符合要求，请自行修改。  
3）支持jsonp，前端需要在GET里传入jsonpCallback参数。  
该类已经做了autoload，可以直接使用，如下：    

	$this->stdreturn->ok($data);
	$this->stdreturn->failed('4001', $error);

至于错误码&错误提示，需要在语言包中配置。如：language/zh_cn/myerror_lang.php     

	<?php

	$lang['myerror'] = array(
		'500' => '服务器内部错误',
		'404' => '所访问内容不存在',
		
		// 5开头，服务端错误
		'5000' => '服务器内部错误',//未知错误
		'5001' => '服务器内部错误',//db 异常
		'5002' => '服务器内部错误',//redis 异常
		'5003' => '服务器内部错误',//php 异常
		
		// 4开头，客户端错误
		'4001' => '参数错误',
	);


	/* End of file myerror_lang.php */
	/* Location: ./wc_content/language/zh_cn/myerror_lang.php */

### 4 IdCreater类 
一个基于snowflake标准的Id生成器类。  

	毫秒级时间41位+机器ID 10位+毫秒内序列12位。
	格式如：
	0 - 0000000000 0000000000 0000000000 0000000000 0 - 00000 00000 - 000000000000
	第一位为未使用，接下来的41位为毫秒级时间(41位的长度可以使用69年)，如果使用无符号数，可以使用138年
	然后是10位machineId(10位的长度最多支持部署1024个节点） ，
	最后12位是毫秒内的计数（12位的计数顺序号支持每个节点每毫秒产生4096个ID序号）


### 5 在third_party中使用自动加载    
因为CI本身没有使用命名空间和类自动加载。所以我在third_party内引入带命名空间的类库之前，增加了CI类、CI覆盖类的过滤。
	
	// 支持匹配规则：a_b 或  a/b 或 a\b
	function third_party_autoload($class){
	    
	    if (function_exists('__autoload')) {
	        //    Register any existing autoloader function with SPL, so we don't get any clashes
	        spl_autoload_register('__autoload');
	    }
	    $file = preg_replace('{\\\\|_(?!.*\\\\)}', DIRECTORY_SEPARATOR, ltrim($class, '\\')).'.php';
	
	    // 过滤CI类、CI覆盖类
	    if (strpos($file, 'CI') === 0 || strpos($file, 'MY') === 0) {
	        return ;
	    }
	
	}
	spl_autoload_register('third_party_autoload');
	
	// 在此后require或include需要引入的第三方类库的autoload文件。
	
	// 例如，引入一个基于redis的延时队列
	require 'redis_queue/autoload.php';
		

### 6 常驻进程类 
封装了cli模式启动的常驻进程类。该类具有如下几个特点  
1）可以配置最大执行时间  
2）支持平滑启动  

### 7 Curl类 
支持 get、post、mutiGet；

### 8 ApiProxy类 
该类是处理api调用的一个标准类，可以通过继承该类，实现快速接入外部api。    
1）api配置文件，config/development/api.php

	/*
	| -------------------------------------------------------------------
	| API REQUEST SETTINGS
	| -------------------------------------------------------------------
	*/
	// host
	$config['xxx_api']['host'] = 'http://{HOST}/{URI}';
	// uri
	$config['xxx_api']['uri'] = array(
		'xxx_ooo' => 'xxx/ooo/v1',
	);
	// 需要做log的api
	$config['xxx_api']['request_log'] = array(
		'xxx_ooo',
	);

2)继承ApiProxy类后，子类一版需要覆盖签名方法、添加通用参数的方法。

### 9 CRedis类 
该类是基于phpredis扩展的一个Library。主要功能有：  
1）将redis连接可配置化  

	/*
	| -------------------------------------------------------------------
	| REDIS CONNECTIVITY SETTINGS
	| -------------------------------------------------------------------
	*/
	
	$config['redis']['default'] = array(
		0 => array(
			'host' => '127.0.0.1',
			'port' => '6379',
			'weight' => '1',
			'lasting' => 1,
			'connectTime' => 1,
			'db' => 0,
			'password' => '',
		),
		1 => array(
			'host' => '127.0.0.1',
			'port' => '6379',
			'weight' => '1',
			'lasting' => 1,
			'connectTime' => 1,
			'db' => 0,
			'password' => '',
		)
	);

	$config['redis']['host1'] = array(
		0 => array(
			'host' => '127.0.0.1',
			'port' => '6379',
			'weight' => '1',
			'lasting' => 1,
			'connectTime' => 1,
			'db' => 0,
			'password' => '',
		),
		1 => array(
			'host' => '127.0.0.1',
			'port' => '6379',
			'weight' => '1',
			'lasting' => 1,
			'connectTime' => 1,
			'db' => 0,
			'password' => '',
		)
	);


2）多个redis实例可切换  

	$this->load->library('CRedis', '', 'CRedis');
	$this->CRedis->set('hello', 'hello world1!');
	$this->CRedis->switchHost('host1');
	$this->CRedis->set('hello', 'hello world2!');
	$this->stdreturn->ok($this->CRedis->get('hello'));

3）支持主从实例自动切换  

	默认取第0个实例，若失败，则获取第1个实例。




