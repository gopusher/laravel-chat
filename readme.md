# Gopusher Chat

这是一个聊天应用，界面如: [https://chat.yadou.net](https://chat.yadou.net)

所有的业务逻辑采用Laravel实现，长连接采用[Gopusher Comet](https://github.com/Gopusher/comet)接入层实现。除了Laravel外，还引入以下依赖：

* [phpctx/ctx](https://github.com/phpctx/ctx) 一个Service服务模块化组织框架
* [predis/predis](https://github.com/nrk/predis) Redis库

## 安装

1. 首先需要安装 [Gopusher Comet](https://github.com/Gopusher/comet) 并进行配置

2. 下载 Chat，并安装

   ```
   git clone https://github.com/Gopusher/laravel-chat.git
   配置 .env
   ```

## 其它

[Gopusher Comet ](https://github.com/Gopusher/comet)除了能用于聊天应用，还能用于如游戏等其他长连接场景下的应用，开发文档见 [https://github.com/Gopusher/comet/wiki](https://github.com/Gopusher/comet/wiki)