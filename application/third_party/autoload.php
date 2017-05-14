<?php

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





