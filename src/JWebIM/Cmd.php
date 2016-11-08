<?php
namespace JWebIM;

/**
 * 消息命令
 *
 * @author zengqingyun
 * @package JWebIM
 * @namespace JWebIM
 * @version 1.0.0
 * @copyright 2007-2016 iam2C
 */
class Cmd
{

    /**
     * 命令的key
     *
     * @var string
     */
    const KEY = 'cmd';

    /**
     * 错误命令
     *
     * @var string
     */
    const ERR = 'error';

    /**
     * 登录命令
     *
     * @var string
     */
    const LOGIN = 'login';

    /**
     * 发送消息命令
     *
     * @var string
     */
    const FROM_MSG = 'message';

    /**
     * 新用户消息
     *
     * @var string
     */
    const NEW_USER = 'newUser';

    /**
     * 历史消息
     *
     * @var string
     */
    const GET_HISTORY = 'getHistory';

    /**
     * 增加到历史消息
     *
     * @var string
     */
    const ADD_HISTORY = 'addHistory';

    /**
     * 在线用户
     *
     * @var string
     */
    const GET_ONLINE = 'getOnline';

    /**
     * 代理连接
     *
     * @var string
     */
    const PROXY_SET = 'proxySet';

    /**
     * 代理登录
     *
     * @var string
     */
    const PROXY_LOGIN = 'proxyLogin';

    /**
     * 代理发送的消息
     *
     * @var string
     */
    const PROXY_MSG = 'proxyMsg';
}