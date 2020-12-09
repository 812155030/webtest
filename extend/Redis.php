<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/3/1
 * Time: 6:13 PM
 */


namespace addons\unishop\extend;

use think\Env;
use think\Exception;

/**
 * Class Redis
 * @package addons\unishop\extend
 *
 */
class Redis
{
    public $handler = null;
    private $options = [];

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = [])
    {
        self::available();

        $this->options = array_merge([
            'host'       => Env::get('redis.host', '127.0.0.1'),
            'port'       => Env::get('redis.port', 6379),
            'password'   => Env::get('redis.password', ''),
            'select'     => Env::get('redis.select', 2), // 默认使用2数据库索引， 因为fastadmin请缓存会清掉1的
            'timeout'    => Env::get('redis.timeout', 0),
            'expire'     => Env::get('redis.expire', 0),
            'persistent' => Env::get('redis.persistent', false),
            'prefix'     => Env::get('redis.prefix', ''),
        ], $options);

        try {
            $this->handler = new \Redis;
            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
            } else {
                $this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
            }

            if ('' != $this->options['password']) {
                $this->handler->auth($this->options['password']);
            }

            if (0 != $this->options['select']) {
                $this->handler->select($this->options['select']);
            }

        } catch (\RedisException $e) {
            throw new Exception($e->getMessage().': redis');
        }
    }

    public static function available()
    {
        if (!extension_loaded('redis')) {
            throw new Exception('not support: redis');
        }
    }

}
