function getParams() {
    let url = window.document.location.href.toString();
    let u = url.split("?");
    if (typeof (u[1]) === "string") {
        u = u[1].split("&");
        let get = {};
        for (let i in u) {
            let j = u[i].split("=");
            get[j[0]] = j[1];
        }
        delete get.page;
        return get;
    } else {
        return {};
    }
}

function ObjectToStr(obj) {
    let result = '';
    for (let k in obj) {
        let v = obj[k];
        if (result === '') {
            result = '?' + k + '=' + v;
        } else {
            result += '&' + k + '=' + v;
        }
    }
    return result;
}

function showWarning(msg) {
    if (undefined === layer) {
        document.write('<script src="/static/lib/layer/layer.js"></script>');
    }
    layer.msg(msg, {icon: 2});
}

function show422Error(data) {
    showWarning(trans422(message));
}

function trans422(data) {
    var message = '';
    for (var key in data.responseJSON) {
        for (var subKey in data.responseJSON[key]) {
            message += data.responseJSON[key][subKey];
            message += "\n";
        }
    }
    return message;
}

function showError(data) {
    if (null !== data.getResponseHeader('Location')) {
        window.location.href = data.getResponseHeader('Location');
    }
    if (422 == data.status) {
        show422Error(data);
    } else if (400 == data.status) {
        showWarning(data.responseJSON.msg);
    } else if (404 == data.status) {
        showWarning('访问的资源不存在，请刷新重试');
    } else if (401 == data.status) {
        showWarning('尚未登录', function () {
            window.location.href = '/login';
        });
    } else if (500 == data.status) {
        showWarning('服务器开小差了，请重试');
    } else {
        showWarning('未知错误');
    }
}