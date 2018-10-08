<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    <title>聊天室</title>
    <link rel="stylesheet" type="text/css" href="{{ $staticHost }}/index.css" />
</head>
<body>
<div id="login">
    <div>
        <h1>聊天室</h1>
    </div>
    <div>
        <input class="input" id="name" type="text" placeholder="用户名"/>
    </div>
    <div>
        <button class="button" id="loginButton">登录</button>
    </div>
</div>
<div id="chat" class="clearfix">
    <div class="left">
        <div>
            <ul>
                <li>
                    <div>
                        <span id="welcome"></span>
                    </div>
                </li>
                <li>
                    <div class="tag">
                        <span>在线列表</span>
                        <span>▼</span>
                    </div>
                </li>
                <li>
                    <div>
                        <ul id="userlist">
                        </ul>
                    </div>
                </li>
            </ul>

        </div>
    </div>
    <div class="right clearfix">
        <div id="room" class="log">
            <div class="log-title">
                聊天室:
            </div>
        </div>
        <div id="mine" class="log">
            <div class="log-title">
                个人聊天:
            </div>
        </div>
        <div class="form">
            <input id="msg" type="text" class="msg" placeholder="请输入聊天内容!"/>
            <button id="send" class="send">发送消息</button>
        </div>
    </div>
</div>
</body>
<script src="https://cdn.staticfile.org/jquery/1.7.2/jquery.min.js"></script>
<script src="{{ $staticHost }}/gopusher.js"></script>
<script src="{{ $staticHost }}/index.js"></script>
<script>
    var userLists;
    var name = "{{ $name }}";
    var uid = "{{ $uid }}";

    window.onload = function () {
        var apiHost = "{{ $apiHost }}";
        var urls = {
            "register": apiHost + '/im/index/register',
            "getConnectInfo": apiHost + '/im/index/connectInfo',
            "sendToGroup": apiHost + '/im/index/sendToGroup',
            "sendToUser": apiHost + '/im/index/sendToUser'
        };
        onlinePushUrl = apiHost + '/im/index/pushOnline'; //上线url，没时间调试写全局了
        onlineUsersUrl = apiHost + '/im/index/getGroupOnlineUsers'; //获取在线列表，没时间调试写全局了
        getSelfUidUrl = apiHost + '/im/index/getSelfUid'; //获取自身user id，没时间调试写全局了，真实环境直接uid，因为没有账号系统所以。。

        var indexPage = new IndexPage(urls);
        if (name.length > 0) {
            indexPage.getConnectInfoAndLogin();
            $("#welcome").html("你好：" + name);
        } else {
            document.getElementById("loginButton").onclick = function () {
                name = document.getElementById("name").value;
                indexPage.register(name);
                $("#welcome").html("你好：" + name);
            };
        }

        document.getElementById("send").onclick = function(event) {
            var msg = document.getElementById("msg").value;
            indexPage.send(msg);
        };

        document.getElementById("name").onkeyup = function(event) {
            if (event.keyCode == "13") {
                $("#loginButton").trigger("click");
            }
        };

        document.getElementById("msg").onkeyup = function(event) {
            if (event.keyCode == "13") {
                $("#send").trigger("click");
            }
        };

        $("#userlist a").live('click', function(e) {
            var uid = $(this).attr('data-uid');
            document.getElementById("msg").value = "@" + uid + " ";
            $("#msg").focus();
        });
    };
</script>
</html>
