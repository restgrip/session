<?php
namespace Restgrip\Session;

use Phalcon\Config;
use Phalcon\Session\Adapter\Files;
use Phalcon\Session\Adapter\Libmemcached;
use Phalcon\Session\Adapter\Memcache;
use Phalcon\Session\Adapter\Redis;
use Restgrip\Module\ModuleAbstract;

/**
 * @package   Restgrip\Session
 * @author    Sarjono Mukti Aji <me@simukti.net>
 */
class Module extends ModuleAbstract
{
    /**
     * Default configs with key 'session'
     *
     * @var array
     */
    protected $defaultConfigs = [
        'adapter' => 'redis',
        'options' => [
            'redis'        => [
                'uniqueId'   => 'restgrip',
                'host'       => '127.0.0.1',
                'port'       => 6379,
                'auth'       => '',
                'persistent' => false,
                'lifetime'   => 3600,
                'prefix'     => 'restgrip_',
                'index'      => 1,
            ],
            'files'        => [
                'uniqueId' => 'restgrip',
            ],
            'memcache'     => [
                'uniqueId'   => 'restgrip',
                'host'       => '127.0.0.1',
                'port'       => 11211,
                'persistent' => true,
                'lifetime'   => 3600,
                'prefix'     => 'restgrip_',
            ],
            'libmemcached' => [
                'servers'  => [
                    [
                        'host'   => '127.0.0.1',
                        'port'   => 11211,
                        'weight' => 1,
                    ],
                ],
                'client'   => [
                    // \Memcached::OPT_HASH       => \Memcached::HASH_MD5,
                    // \Memcached::OPT_PREFIX_KEY => 'restgrip.',
                ],
                'lifetime' => 3600,
                'prefix'   => 'restgrip_',
            ],
        ],
    ];
    
    protected function http()
    {
        $app        = $this->app;
        $sessConfig = new Config($this->defaultConfigs);
        $configs    = $this->getDI()->getShared('configs');
        if ($configs->get('session') instanceof Config) {
            $sessConfig->merge($configs->session);
        }
        $configs->offsetSet('session', $sessConfig);
        
        $this->getDI()->setShared(
            'session',
            function () use ($app, $configs) {
                $config   = $configs->session;
                $adapter  = $config->adapter;
                $instance = null;
                
                switch ($adapter) {
                    case 'redis':
                        $instance = new Redis($config->options->{$adapter}->toArray());
                        break;
                    case 'files':
                        $instance = new Files($config->options->{$adapter}->toArray());
                        break;
                    case 'memcache':
                        $instance = new Memcache($config->options->{$adapter}->toArray());
                        break;
                    case 'libmemcached':
                        $instance = new Libmemcached($config->options->{$adapter}->toArray());
                        break;
                }
                
                if (!$instance) {
                    throw new \InvalidArgumentException(sprintf("Invalid session adapter '%s'", $adapter));
                }
                
                if (!$instance->isStarted()) {
                    $instance->start();
                }
                
                return $instance;
            }
        );
    }
}