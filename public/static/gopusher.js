//im sdk
var GoPusher = function (urls, evt) {
    this.urls = urls;

    var initConfig = function (evt) {
        this.evt = evt;

        if (! this.evt.onopen) {
            this.evt.onopen = function (e) {
                console.log('##onopen##');
            }
        }

        if (! this.evt.onclose) {
            this.evt.onclose = function (e) {
                console.log('onclose');
            }
        }

        if (! this.evt.onmessage) {
            this.evt.onmessage = function (e) {
                console.log('##onmessage##');
                console.log(e.data);
            }
        }

        if (! this.evt.onerror) {
            this.evt.onerror = function (e) {
                console.log('onerror');
            }
        }
    }.bind(this);

    initConfig(evt);
};

//实例方法
GoPusher.prototype = {
    "connect": function (wsHost, callback) {
        if (window["WebSocket"]) {
            this.conn = new WebSocket(wsHost);

            // 连接成功
            this.conn.onopen =this.evt.onopen;

            // 关闭
            this.conn.onclose = this.evt.onclose;

            // 收到消息
            this.conn.onmessage = this.evt.onmessage;

            // 异常
            this.conn.onerror = this.evt.onerror;
        } else {
            alert('您的浏览器不支持WebSockets.');
        }

        this.waitForConnection(callback, 100);
    },
    'waitForConnection': function (callback, interval) {
        if (this.conn.readyState === 1) {
            callback();
        } else {
            setTimeout(function () {
                this.waitForConnection(callback, interval);
            }.bind(this), interval);
        }
    },
    'sendToGroup': function (group, msg) {
        $.post(this.urls.sendToGroup, {
            'to': group,
            'msg': msg
        }, function (data) {
            console.log(data);
            if (data.code == 0) {
            } else {
                alert('发送失败' + data.error);
            }
        }, 'json');
    },
    'sendToUser': function (uid, msg) {
        $.post(this.urls.sendToUser, {
            'to': uid,
            'msg': msg
        }, function (data) {
            console.log(data);
            if (data.code == 0) {
            } else {
                alert('发送失败' + data.error);
            }
        }, 'json');
    }
};

// //静态方法
// GoPusher.parseResponse = function (content) {
// };
