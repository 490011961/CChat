# CChat
CChat是一个基于swoole扩展和swoole框架的在线聊天应用。

## 1.启动消息服务器
`/path/to/php msg_server.php`

## 2.启动代理服务器
`/path/to/php proxy_server.php`

## 3.架构设计

>1. `消息服务器`：负责收发当前服务器的用户信息、接收代理服务器的信息，广播消息给该服务器的所有用户。
>2. `代理服务器`：接收和分发消息服务器的信息。
>3. `配置服务器`：存储配置信息（暂时还没把数据落地）。

## 4.前端交互
统一使用json格式
1. 登录消息【主动】
```json
{
	"cmd":"login",
	"uid":"用户的id",
	"name":"用户的名字",
	"rid":"房间的id"
}
```

2. 发送消息【主动】
```json
{
	"cmd":"message",
	"type":"消息类型：text/audio/pic",
	"msg":"要发送的内容",
	"rid":"房间的id",
	"tid":"私聊的用户id，【可选】" 
}
```

3. 接收消息【被动】
```json
{
	"cmd":"message",
	"uid":"用户的id",
	"name":"用户的名字",
	"type":"消息类型：text/audio/pic",
	"rid":"房间的id",
	"tid":"私聊的用户id，【可选】" ,
	"msg":"接收到的消息"
}
```

4. 其他用户登录消息【被动】
```json
{
	"cmd":"newUser",
	"uid":"用户的id",
	"name":"用户的名字",
	"rid":"房间的id"
}
```

5. 错误信息【被动】
```json
{
	"cmd":"error",
	"errCode":"错误码",
	"errMsg":"错误信息"
}
```

## 5.TODO
1. 解决代理服务器，实现高可用
2. 消息服务器负载均衡
3. 数据落地
