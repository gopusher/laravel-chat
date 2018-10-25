# Gopusher Chat
这是一个聊天应用，演示地址: [https://chat.yadou.net](https://chat.yadou.net)， 支持单聊和群聊，目前demo中默认只加入群组1，具体的实现逻辑可以看源码实现。

所有的业务逻辑（逻辑层和路由层）采用Laravel实现，长连接采用[Gopusher Comet](https://github.com/Gopusher/comet)(接入层)实现。除了Laravel外，还引入以下依赖：

* [phpctx/ctx](https://github.com/phpctx/ctx) 一个Service服务模块化组织框架
* [predis/predis](https://github.com/nrk/predis) Redis库

## 安装

1. 首先需要安装 [Gopusher Comet](https://github.com/Gopusher/comet) 并进行配置

2. 下载 Chat，并安装

   ```
   git clone https://github.com/Gopusher/laravel-chat.git
   配置 .env
   composer install
   ```

## 其它
* 其实以前采用了自己的[一个框架](https://github.com/tree6bee/)实现了一个版本(已废弃)，但是觉得还是 Laravel 使用的人更多，也更容易方便大家参考，所以就采用了Laravel实现了一版，他们都依赖了`Gopusher Comet`来维护与客户端建立的长连接，
* [Gopusher Comet ](https://github.com/Gopusher/comet) 是一个 ***开源*** 的 ***支持分布式部署*** 的 ***通用*** 长连接接入层服务，接管客户端连接，支持集群，提供了API供开发者调用。
* 除了能用于聊天应用，还能用于如网页消息推送，游戏等其他长连接场景下的应用，开发文档见 [https://github.com/Gopusher/comet/wiki](https://github.com/Gopusher/comet/wiki)
* 因为很多时候业务逻辑都很具体，每个产品的需求都会不同，所以这个源码没有实现很漂亮的客户端，只是为了展示怎么实现Comet来实现一个分布式聊天，里边有分布式的comet路由保持相关的逻辑，不过路由层和业务逻辑层没有分太细。
