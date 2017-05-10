<?php

define('PHPER_SPACE_TASK_QEUE_MARK', 'mark');
define('PHPER_SPACE_TASK_QEUE_PATH', __DIR__ . '/');

require_once PHPER_SPACE_TASK_QEUE_PATH . 'conf/conf.php';

// 支持匹配规则：a_b 或  a/b 或 a\b
function phper_space_queue_autoload($class){
    
    if (function_exists('__autoload')) {
        //    Register any existing autoloader function with SPL, so we don't get any clashes
        spl_autoload_register('__autoload');
    }
    $file = preg_replace('{\\\\|_(?!.*\\\\)}', DIRECTORY_SEPARATOR, ltrim($class, '\\')).'.php';
    
    $file = PHPER_SPACE_TASK_QEUE_PATH . strtolower($file);
    
    if (is_file($file)) {
        require $file;
    }

}
spl_autoload_register('phper_space_queue_autoload');





