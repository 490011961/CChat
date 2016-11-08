<?php
namespace JWebIM;
use Swoole\Client\WebSocket;

/**
 * 代理服务
 *
 * @author zengqingyun
 * @package JWebIM
 * @namespace JWebIM
 * @version 1.0.0
 * @copyright 2007-2016 聚橙网
 */
class ProxyServerManager
{

    /**
     * ProxyServer
     *
     * @var ProxyServer
     */
    private static $_self;

    /**
     * 与消息服务器的连接
     *
     * @var array
     */
    protected $msgSvrConns;

    /**
     * 消息服务器列表
     *
     * @var array
     */
    protected $msgServers;

    /**
     */
    static public function getInstance()
    {
        if (static::$_self == null) {
            static::$_self = new static();
        }
        return static::$_self;
    }

    /**
     */
    public function run($msgServers = [])
    {
        $this->initMsgServers($msgServers);
        $this->connect2MsgServers();
        $this->setProxy2MsgServers();
        $this->dispatch();
    }

    /**
     * 初始化消息服务器
     */
    protected function initMsgServers($msgServers)
    {
        if (empty($msgServers)) {
            $msgServers[] = [
                'host' => '127.0.0.1',
                'port' => '9503'
            ];
        }
        foreach ($msgServers as $svr) {
            if (empty($svr['host']) || empty($svr['port'])) {
                throw new \Exception('消息服务器配置错误');
            }
            $this->msgServers[] = [
                'host' => trim($svr['host']),
                'port' => trim($svr['port'])
            ];
        }
    }

    /**
     * 连接到消息服务器
     */
    protected function connect2MsgServers()
    {
        foreach ($this->msgServers as $server) {
            $conn = new WebSocket($server['host'], $server['port']);
            if (! $conn->connect()) {
                echo '无法连接到服务器：' . $server['host'] . '(' . $server['port'] . ')' . PHP_EOL;
            } else {
                echo '已成功连接到服务器：' . $server['host'] . '(' . $server['port'] . ')' . PHP_EOL;
                $this->msgSvrConns[$server['host']] = $conn;
            }
        }
        
        if (empty($this->msgSvrConns)) {
            echo '[EXIT]无法连接到任何一台消息服务器：' . PHP_EOL;
            exit();
        }
        
        return true;
    }

    /**
     * 登录到消息服务器
     */
    protected function setProxy2MsgServers()
    {
        $arr[Cmd::KEY] = Cmd::PROXY_SET;
        foreach ($this->msgSvrConns as $conn) {
            /* @var $conn WebSocket */
            $helloRes = $conn->sendJson($arr);
            echo 'proxy_set:' . $helloRes . PHP_EOL;
        }
        return true;
    }

    /**
     * 消息调度
     */
    protected function dispatch()
    {
        while (1) {
            foreach ($this->msgSvrConns as $conn1) {
                /* @var $conn1 WebSocket */
                try {
                    $revData = $conn1->recv();
                    $data = json_decode($revData, true);
                    if (! $data) {
                        continue;
                    }
                    
                    echo 'json:' . (string) $revData . PHP_EOL;
                    /* 有数据则通知其他服务器 */
                    if (isset($data[Cmd::KEY]) && $data[Cmd::KEY] == Cmd::LOGIN) {
                        $data[Cmd::KEY] = Cmd::PROXY_LOGIN;
                    } else {
                        $data[Cmd::KEY] = Cmd::PROXY_MSG;
                    }
                    
                    foreach ($this->msgSvrConns as $host => $conn2) {
                        if ($conn1 == $conn2) {
                            continue;
                        }
                        /* @var $conn2 WebSocket */
                        echo 'Send msg to ' . $host . PHP_EOL;
                        $conn2->sendJson($data);
                    }
                    usleep(10);
                } catch (\Exception $e) {
                    echo $e->getTraceAsString();
                }
            }
        }
    }
}

