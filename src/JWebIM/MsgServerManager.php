<?php
namespace JWebIM;

/**
 * 消息服务
 *
 * @author zengqingyun
 * @package JWebIM
 * @namespace JWebIM
 * @version 1.0.0
 * @copyright 2007-2016 聚橙网
 */
class MsgServerManager
{

    /**
     * 自身
     *
     * @var Server2
     */
    private static $_self;

    /**
     * websocket服务
     *
     * @var MsgServer
     */
    private $wsServer;

    /**
     * 配置信息
     *
     * @var array
     */
    private $cfg;

    /**
     * 构造方法
     *
     * @param array $cfg            
     * @throws \Exception
     */
    public function __construct($cfg = [])
    {
        if (empty($cfg)) {
            $this->setWsServer(new MsgServer());
        } else {
            if (empty($cfg['host']) || empty($cfg['post'])) {
                throw new \InvalidArgumentException(Err::msg(Err::EX_INVALID_ARGS), 
                    Err::EX_INVALID_ARGS);
            }
            $this->setWsServer(new MsgServer($cfg['host'], $cfg['port'], SWOOLE_BASE));
        }
    }

    /**
     *
     * @return the $wsServer
     */
    public function getWsServer()
    {
        return $this->wsServer;
    }

    /**
     *
     * @param WebSocketServe $wsServer            
     */
    public function setWsServer(MsgServer $wsServer)
    {
        $this->wsServer = $wsServer;
        $this->bindListeners();
    }

    /**
     * 监听
     */
    public function bindListeners()
    {
        $this->wsServer->on('message', [$this->wsServer, 'onMessage']);
        $this->wsServer->on('close', [$this->wsServer, 'onClose']);
    }

    /**
     * 开启服务
     *
     * @param array $cfg            
     * @throws \Exception
     */
    static public function start($cfg = [])
    {
        if (static::$_self == null) {
            static::$_self = new static($cfg);
			/*
			static::$_self->getWsServer()->set(array(
				'worker_num' => 2,
			));*/
			
            static::$_self->getWsServer()->start();
        }
    }
}

