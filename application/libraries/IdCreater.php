<?php
/**
 * Id生成器
 * 本地生成snowflake标准Id。
 * 毫秒级时间41位+机器ID 10位+毫秒内序列12位。
 * 格式如：
 * 0 - 0000000000 0000000000 0000000000 0000000000 0 - 00000 00000 - 000000000000
 * 第一位为未使用，接下来的41位为毫秒级时间(41位的长度可以使用69年)，如果使用无符号数，可以使用138年
 * 然后是10位machineId(10位的长度最多支持部署1024个节点） ，
 * 最后12位是毫秒内的计数（12位的计数顺序号支持每个节点每毫秒产生4096个ID序号）
 * 
 * @author Mr.Nobody
 */
class IdCreater
{
    
    /**
     * id生成器相关配置
     * 
     * @var array
     */
    protected $_config = array();
    
    /**
     * 获取自增id时候最大失败尝试次数
     * 
     * @var int
     */
    protected $_maxAttemptsOfAutoInc = 3;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $ci = get_instance();
        $ci->config->load('idcreater');
        $this->_config = config_item('idcreater');
    }
    
    /**
     * 生成
     */
    public function generate()
    {
        
        $micTimeStamp = $this->_getMicroTimeStamp();
    
        $machineId = $this->_getMachineId();
        if (FALSE === $machineId) {
            return FALSE;
        }
    
        $autoIncId = $this->_getAutoIncId();
        if (FALSE === $autoIncId) {
            return FALSE;
        }
    
        $serialId = $micTimeStamp << 10  | ($machineId % 1024);
        $serialId = $serialId << 12 | $autoIncId;
    
        return $serialId;
    }
    
    /**
     * 取毫秒时间戳
     */
    protected function _getMicroTimeStamp()
    {
        $micTimeStamp = microtime(TRUE) - mktime(0, 0, 0, 1, 1, 2017);
        return $micTimeStamp * 1000;
    }
    
    /**
     * 取机器id
     */
    protected function _getMachineId()
    {
        return $this->_config['machine_id'];
    }

    /**
     * 取自增id
     * 
     * 若出现冲突，会递归调用 $_maxAttemptsOfAutoInc 次
     * 
     * @param number $attempts
     * @return boolean|int
     */
    protected function _getAutoIncId($attempts = 1)
    {
        $autoincStateFile = $this->_config['auto_inc_id_file_path'];
        $nextValue = 0;
        $fp = fopen($autoincStateFile, "c+");
        if (! $fp) {
            write_fatal('Can not create auto inc file.', 'idcreater');
            return FALSE;
        }

        if (flock($fp, LOCK_EX)) {
            $nextValue = fread($fp, 32);
            fseek($fp, 0, SEEK_SET);
            if (empty($nextValue)) {
                $nextValue = 1;
                fwrite($fp, $nextValue);
            } else {
                $nextValue = (intval($nextValue) + 1) % 4096;
                fwrite($fp, $nextValue);
            }
        } elseif ($attempts < $this->_maxAttemptsOfAutoInc) {
            fclose($fp);
            write_notice("Auto inc conflict @{$attempts}.", 'idcreater');
            usleep(2);
            return $this->_getAutoIncId($attempts + 1);
        } else {
            fclose($fp);
            write_fatal('Get auto inc id failed at max attempts.', 'idcreater');
            return FALSE;
        }

        fclose($fp);

        return $nextValue;
    }
    
    
}

/* End of file stdreturn.php */
/* Location: ./application/libaries/stdreturn.php */