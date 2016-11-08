<?php
namespace JWebIM;

/**
 * 消息服务
 *
 * @author zengqingyun
 * @package JWebIM
 * @namespace JWebIM
 * @version 1.0.0
 * @copyright 2007-2016 iam2C
 */
class MsgServer extends \swoole_websocket_server
{
	/**
     * 发送限制秒数
     * 
     * @var int
     */
    const SEND_INTERVAL_LIMIT = 2;

    /**
     * 所有连接的用户（二维数组，一维key为用户连接的fd）
     * <pre>
     * [
     * 'fd1' => [
     * 'uid' = > 'xxx1',
     * 'name' => 'name1',
     * 'rids' => ['xx1', 'xx2', ...]
     * ],
     * 'fd2' => [
     * 'uid' = > 'xxx2',
     * 'name' => 'name2',
     * 'rids' => ['xx1', 'xx2', ...]
     * ],
     * ...
     * ]
     * </pre>
     *
     * @var array
     */
    protected $users;

    /**
     * 房间用户（二维数组，一维key为房间ID）
     * <pre>
     * [
     * 'rid1' => [
     * 'uid1' => 'fd1',
     * 'uid2' => 'fd2',
     * ...
     * ],
     * 'rid2' => [
     * 'uid1' => 'fd1',
     * 'uid3' => 'fd3',
     * ...
     * ],
     * ...
     * ]
     * </pre>
     *
     * @var array
     */
    protected $roomUsers;

    /**
     * 代理的连接fd
     *
     * @var int
     */
    protected $proxyFd;

    /**
     * 上一次发送消息的时间
     *
     * @var array
     */
    protected $lastSentTime = [];

    /**
     * 单条消息不得超过1K
     *
     * @var int
     */
    const MESSAGE_MAX_LEN = 1024;

    /**
     * 消息类型
     *
     * @var array
     */
    const MSG_TYPES = [
        'text',
        'audio',
        'pic'
    ];

    public function __construct($host = '0.0.0.0', $port = '9503', $flag = SWOOLE_BASE)
    {
        parent::__construct($host, $port, $flag);
    }

    /**
     * 登录
     *
     * @param int $fd            
     * @param string $msg            
     * @return bool
     */
    protected function cmd_login($fd, $msg)
    {
        try {
            $resMsg = $this->filterLoginMsg($fd, $msg);
            
            // 把会话存起来，后面可以抽离出来封装到业务层
            if (! isset($this->users[$fd])) {
                $this->users[$fd]['uid'] = $resMsg['uid'];
                $this->users[$fd]['name'] = $resMsg['name'];
            }
            // 保存房间信息
            $this->users[$fd]['rids'][] = $resMsg['rid'];
            $this->roomUsers[$resMsg['rid']][$resMsg['uid']] = $fd;
            
            // 发送消息给自己
            $this->sendJson($fd, $resMsg);
            // 发送给代理
			if($this->proxyFd) {
				$this->sendJson($this->proxyFd, $resMsg);
			}
            // 广播给其它在线用户
            $resMsg[Cmd::KEY] = Cmd::NEW_USER;
            $this->broadcastJson($fd, $resMsg, $resMsg['rid']);
            return true;
        } catch (\Exception $e) {
            $this->sendErrorMessage($fd, Err::EX_INVALID_ARGS, $e->getMessage());
            return false;
        }
    }

	/**
	 * 调试命令
	 * @param int $fd            
     * @param array $msgArr  
	 */
    protected function cmd_debug($fd, $msgArr)
    {
        $msgArr[Cmd::KEY] = 'debug';
        $msgArr['data1'] = $this->users;
        $msgArr['data2'] = $this->roomUsers;
        $msgArr['data3'] = $this->proxyFd;
        $this->sendJson($fd, $msgArr);
    }

    /**
     * 过滤登录信息
     *
     * @param int $fd            
     * @param array $msgArr            
     * @return array
     * @throws \Exception
     */
    private function filterLoginMsg($fd, $msgArr)
    {
        if (empty($msgArr['name'])) {
            throw new \Exception('缺少姓名参数');
        }
        if (strlen($msgArr['name']) < 2 || strlen($msgArr['name']) > 16) {
            throw new \Exception('姓名不合法');
        }
        
        /* 房间id */
        if (! isset($msgArr['rid'])) {
            throw new \Exception('缺少聊天室参数');
        }
        if (! filter_var($msgArr['rid'], FILTER_VALIDATE_INT) || $msgArr['rid'] <= 0) {
            throw new \Exception('聊天室参数不正确');
        }
        
        /* 用户id */
        $uid = 0;
        if (! empty($msgArr['uid'])) {
            if (! filter_var($msgArr['uid'], FILTER_VALIDATE_INT) || $msgArr['uid'] <= 0) {
                return false;
            }
            $uid = $msgArr['uid'];
        } else {
            $uid = rand(1000000, 9999999);
        }
        
        $resMsg = [
            Cmd::KEY => Cmd::LOGIN,
            'name' => strip_tags($msgArr['name']),
            'uid' => (string) $uid,
            'rid' => $msgArr['rid']
        ];
        return $resMsg;
    }

    /**
     * 代理连接
     *
     * @param int $fd            
     * @param string $msg            
     */
    protected function cmd_proxySet($fd, $msg)
    {
        // 把代理的会话存起来
        $this->proxyFd = $fd;
        $this->push($fd, 'Success：fd=' . $fd);
    }

    /**
     * 代理转发的登录
     *
     * @param int $fd            
     * @param string $msg            
     * @return bool
     */
    protected function cmd_proxyLogin($fd, $msg)
    {
        try {
            $resMsg = $this->filterLoginMsg($fd, $msg);
            // 广播给其它在线用户
            $resMsg[Cmd::KEY] = Cmd::NEW_USER;
            // 将上线消息发送给所有人
            $this->broadcastJson2($resMsg, $resMsg['rid']);
            return true;
        } catch (\Exception $e) {
            $this->sendErrorMessage($fd, Err::EX_INVALID_ARGS, $e->getMessage());
            return false;
        }
    }

    /**
     * 代理转发的消息
     *
     * @param int $fd            
     * @param string $msgArr            
     */
    protected function cmd_proxyMsg($fd, $msgArr)
    {
        $msgArr[Cmd::KEY] = Cmd::FROM_MSG;
        
        if (empty($msgArr['tid'])) {
            $this->broadcastJson2($msgArr, $msgArr['rid']); // 表示群发
        } else {
            $this->sendJson($this->roomUsers[$msgArr['rid']][$resMsg['tid']], $msgArr); // 表示私聊
        }
    }

    /**
     * 发送信息请求
     *
     * @param int $fd            
     * @param string $msg            
     */
    protected function cmd_message($fd, $msg)
    {
        try {
            $resMsg = $this->filterMessage($fd, $msg);
            
            // 发送到代理服务器
			if($this->proxyFd) {
				$this->sendJson($this->proxyFd, $resMsg);
			}
            
            if (empty($msg['tid'])) {
                $this->broadcastJson($fd, $resMsg, $resMsg['rid']); // 表示群发
            } else {
                if (isset($this->roomUsers[$resMsg['rid']][$resMsg['tid']])) {
                    $this->sendJson($this->roomUsers[$resMsg['rid']][$resMsg['tid']], $resMsg); // 表示私聊
                }
            }
            return true;
        } catch (\Exception $e) {
            $this->sendErrorMessage($fd, Err::EX_INVALID_ARGS, $e->getMessage());
            return false;
        }
    }

    /**
     * 过滤消息
     *
     * @param int $fd            
     * @param array $msgArr            
     * @return array
     * @throws \Exception
     */
    private function filterMessage($fd, $msgArr)
    {
        /* 消息类型 */
        if (! isset($msgArr['type']) || ! in_array($msgArr['type'], self::MSG_TYPES, true)) {
            throw new \Exception('不支持的消息类型');
        }
        
        /* 消息内容 */
        if (! isset($msgArr['msg'])) {
            throw new \Exception('缺少消息内容');
        }
        if (strlen($msgArr['msg']) > self::MESSAGE_MAX_LEN) {
            throw new \Exception('消息内容过长');
        }
        
        /* 私聊用户id */
        if (! empty($msgArr['tid'])) {
            if (! filter_var($msgArr['tid'], FILTER_VALIDATE_INT) || $msgArr['tid'] <= 0) {
                throw new \Exception('私信用户不正确');
            }
        }
        
        /* 房间id */
        if (! isset($msgArr['rid'])) {
            throw new \Exception('缺少聊天室参数');
        }
        if (! filter_var($msgArr['rid'], FILTER_VALIDATE_INT) || $msgArr['rid'] <= 0) {
            throw new \Exception('聊天室参数不正确');
        }
        
        $now = time();
        // 上一次发送的时间超过了允许的值，每N秒可以发送一次
        if (isset($this->lastSentTime[$fd]) &&
             $this->lastSentTime[$fd] > $now - self::SEND_INTERVAL_LIMIT) {
            throw new \Exception('您发送消息的频率过快，请稍后再试');
        }
        // 记录本次消息发送的时间
        $this->lastSentTime[$fd] = $now;
        
        /* 用户id */
        if (empty($this->users[$fd]['uid']) || empty($this->users[$fd]['name'])) {
            throw new \Exception('您已丢失登录信息');
        }
        
        $resMsg = [
            Cmd::KEY => Cmd::FROM_MSG,
            'type' => $msgArr['type'],
            'uid' => $this->users[$fd]['uid'],
            'name' => $this->users[$fd]['name'],
            'msg' => $msgArr['msg'],
            'rid' => (string) $msgArr['rid']
        ];
        
        // 如果有私聊则加上
        if (isset($msgArr['tid'])) {
            $resMsg['tid'] = (string) $msgArr['tid'];
        }
        
        return $resMsg;
    }

    /**
     * 接收到消息时
     */
    public function onMessage($wsServer, $frame)
    {
        $msg = json_decode($frame->data, true);
        $fd = $frame->fd;

        if (! $msg || empty($msg[Cmd::KEY])) {
            $this->sendErrorMessage($fd, 101, "无效的命令[1]");
            return;
        }
        
        $func = 'cmd_' . $msg[Cmd::KEY];
        if (method_exists($this, $func)) {
            $this->$func($fd, $msg);
        } else {
            $this->sendErrorMessage($fd, 102, "无效的命令[2]");
            return;
        }
    }

    /**
     * 关闭连接
     * 
     * @param \swoole_websocket_server $ser            
     * @param int $fd            
     */
    public function onClose($wsServer, $fd)
    {
        /* 先清理roomUsers，再清理user */
        if (isset($this->users[$fd])) {
            foreach ($this->users[$fd]['rids'] as $rid) {
                unset($this->roomUsers[$rid][$this->users[$fd]['uid']]);
            }
        }
        unset($this->users[$fd]);
    }

    /**
     * 发送错误信息
     *
     * @param int $fd            
     * @param int $code            
     * @param string $msg            
     */
    protected function sendErrorMessage($fd, $code, $msg)
    {
        $this->sendJson($fd, 
            [
                Cmd::KEY => Cmd::ERR,
                'errCode' => $code,
                'errMsg' => $msg
            ]);
    }

    /**
     * 发送JSON数据
     *
     * @param int $fd            
     * @param array $msgArray            
     */
    protected function sendJson($fd, $msgArray, $roomId = 0)
    {
        $msg = json_encode($msgArray);
        if ($this->push($fd, $msg) === false) {
            $this->close($fd);
        }
    }

    /**
     * 广播JSON数据给房间的所有人（除了自己）
     *
     * @param int $curFd
     *            当前用户的fd
     * @param array $msgArray
     *            消息体数组
     * @param string $roomId
     *            房间id
     */
    protected function broadcastJson($curFd, $msgArray, $roomId = 0)
    {
        $msg = json_encode($msgArray, JSON_UNESCAPED_UNICODE);
        $this->broadcast($msg, $curFd, $roomId);
    }

    /**
     * 广播JSON数据给房间的所有人
     *
     * @param array $msgArray
     *            消息体数组
     * @param string $roomId
     *            房间id
     */
    protected function broadcastJson2($msgArray, $roomId = 0)
    {
        $msg = json_encode($msgArray, JSON_UNESCAPED_UNICODE);
        $this->broadcast($msg, null, $roomId);
    }

    /**
     * 广播消息
     *
     * @param string $msg
     *            要广播的消息
     * @param string $curFd
     *            当前fd，为null时广播所有用户（包括自己）
     * @param string $roomId
     *            房间id
     */
    protected function broadcast($msg, $curFd = null, $roomId = 0)
    {
        if (! isset($this->roomUsers[$roomId])) {
            return false;
        }
        
        if ($curFd === null) {
            foreach ($this->roomUsers[$roomId] as $fd) {
                $this->push($fd, $msg);
            }
        } else {
            foreach ($this->roomUsers[$roomId] as $fd) {
                if ($curFd != $fd) {
                    $this->push($fd, $msg);
                }
            }
        }
    }
}