var IndexPage = function (urls) {
    this.urls = urls;

    this.gopusher = new GoPusher(this.urls, {
        "onmessage": function (evt) { //收到消息后处理
            var messages = evt.data.split('\n');
            for (var i = 0; i < messages.length; i++) {
                var msg = JSON.parse(messages[i]);
                console.log("onmessage: ", msg);
                switch (msg.contentType) {
                    case "text": //文本消息 目前唯一支持类型
                        switch (msg.type) {
                            case "person":
                                if (msg.from == uid) {
                                    var fromUser = "<span style='color: red;'>你</span>"
                                } else {
                                    var fromUser = userLists[msg.from];
                                }

                                if (msg.to == uid) {
                                    var toUser = "<span style='color: red;'>你</span>"
                                } else {
                                    var toUser = userLists[msg.to];
                                }

                                var text = fromUser + " 对 " + toUser + " 说:" + msg.content;
                                var item = document.createElement("div");
                                item.innerHTML = text;
                                this.appendLog('mine', item);
                                break;
                            case "group":
                                if (msg.from == uid) {
                                    var text = "<span style='color: red;'>你</span> 说:" + msg.content;
                                } else {
                                    var fromUser = userLists[msg.from];
                                    var text = fromUser + " 说:" + msg.content;
                                }
                                var item = document.createElement("div");
                                item.innerHTML = text;
                                this.appendLog('room', item);
                                break;
                        }
                        break;
                }
            }
        }.bind(this)
    });
};

//实例方法
IndexPage.prototype = {
    "register" : function (name) {
        //注册用户
        $.post(this.urls.register, {
            "name": name
        }, function (data) {
            if (data.code != 0) {
                alert(data.error);
                console.log("register" + data.error)
                return ;
            }

            //注册成功后获取 token
            this.getConnectInfoAndLogin();
        }.bind(this), 'json');
    },
    "getConnectInfoAndLogin": function () {
        $.get(this.urls.getConnectInfo, function (data) {
            if (data.code != 0) {
                alert(data.error)
                console.log("getConnectInfoAndLogin" + data.error)
                return ;
            }

            //连接
            this.gopusher.connect(data.data.wsHost, this.afterConnect)
        }.bind(this), 'json');
    },
    "afterConnect": function () {
        $.get(getSelfUidUrl, {}, function (data) {
            console.log(data);
            if (data.code == 0) {
                uid = data.data;
            } else {
                alert('获取自身uid失败:' + data.error);
            }
        }, 'json');

        //发送上线通知 因为没有账号系统没有存储账号信息
        $.post(onlinePushUrl, {}, function (data) {
            console.log(data);
            if (data.code == 0) {
                refreshRoomUsers();
            } else {
                alert('上线通知发送失败' + data.error);
            }
        }, 'json');

        //定时拉取在线用户信息
        setInterval(function () {
            refreshRoomUsers()
        }, 10000);

        document.getElementById("login").style.display = "none";
        document.getElementById("chat").style.display = "block";
    },
    "send": function (msg) {
        msg = msg.trim();
        if (! msg) {
            return false;
        }

        var pattern = /^@([^@\s\n\r]{1,20})/g;
        var match = pattern.exec(msg);
        if (match != null) {
            var toUid = match[1]; //获取的值

            if (msg.length == toUid.length + 1) {
                console.log("消息内容为空");
                return false;
            }
            msg = msg.slice(toUid.length + 1).trim();

            if (msg.length > 32) {
                alert("消息长度要小于32个字符");
                return false;
            }

            if (toUid == uid) {
                alert("不能自己给自己发消息");
                return false;
            }
            console.log("sendToUser " + toUid + " " + msg);
            this.gopusher.sendToUser(toUid, msg);
            document.getElementById("msg").value = "@" + toUid + " ";//清空消息
        } else {
            if (msg.length > 32) {
                alert("消息长度要小于32个字符");
                return false;
            }
            var group = 1;
            console.log("sendToGroup " + msg);
            this.gopusher.sendToGroup(group, msg);
            document.getElementById("msg").value = "";//清空消息
        }
    },
    "appendLog": function (ele, item) {
        var log = document.getElementById(ele);
        var doScroll = log.scrollTop > log.scrollHeight - log.clientHeight - 1;
        log.appendChild(item);
        if (doScroll) {
            log.scrollTop = log.scrollHeight - log.clientHeight;
        }
    }
};

function refreshRoomUsers() {
    $.get(onlineUsersUrl, {
        'group': 1
    }, function (data) {
        if (data.code == 0) { //刷新用户列表
            userLists = data.data;
            var userListEle = document.getElementById("userlist");
            userListEle.innerHTML = "";
            for (var uid in userLists) {
                // var li = document.createElement("li");
                // li.setAttribute("class", "padding-left-30");
                // var div = document.createElement("div");
                // var nickname = document.createElement("span");
                // nickname.setAttribute("class", "name clearfix");
                // nickname.innerText = userLists[uid] + " [<a href=':;' data-uid=" + uid + ">点击私聊</a>]"; //name
                // div.appendChild(nickname);
                // li.appendChild(div);
                // userListEle.appendChild(li);
                var html = "<li class='padding-left-30'><div><span class='name clearfix'>";
                html += userLists[uid] + " [<a style='color:#98f0d4;' href='javascript:void(0);' data-uid=" + uid + ">点击私聊</a>]";

                html += "</span></div></li>";
                $("#userlist").append(html);
            }
        } else {
            alert('拉取在线用户列表失败:' + data.error);
        }
    }, 'json');
}
