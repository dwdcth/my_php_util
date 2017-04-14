oss_accessid = ''
oss_accessoss_key = ''
oss_host = ''
oss_policyBase64 = ''
oss_signature = ''
oss_callbackbody = ''
oss_filename = ''
oss_key = ''
oss_expire = 0
g_object_name = ''
g_object_name_type = 'local_name'
OSS_FILE_NAME_TYPE_LOCAL = "local_name"
OSS_FILE_NAME_TYPE_RANDOM = "random_name"

oss_now = oss_timestamp = Date.parse(new Date()) / 1000;

//向服务端请求policy
function send_request() {
    
}
//生成签名
function get_oss_signature() {
    //可以判断当前oss_expire是否超过了当前时间,如果超过了当前时间,就重新取一下.3s 做为缓冲
    oss_now = oss_timestamp = Date.parse(new Date()) / 1000;
    if (oss_expire < oss_now + 3) {
        body = send_request()
        var obj = eval("(" + body + ")");
        oss_host = obj['host']
        oss_policyBase64 = obj['policy']
        oss_accessid = obj['accessid']
        oss_signature = obj['signature']
        oss_expire = parseInt(obj['expire'])
        oss_callbackbody = obj['callback']
        oss_key = obj['dir']

    }

}

//随机字符串
function random_string(len) {
    len = len || 32;
    var chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';
    var maxPos = chars.length;
    var str = '';
    for (i = 0; i < len; i++) {
        str += chars.charAt(Math.floor(Math.random() * maxPos));
    }
    return str;
}
//获取文件名后缀
function get_suffix(filename) {
    pos = filename.lastIndexOf('.')
    suffix = ''
    if (pos != -1) {
        suffix = filename.substring(pos)
    }
    return suffix;
}
//根据文件名类型 临时文件还是原始文件名，返回文件名
function calculate_object_name(filename) {
    if (g_object_name_type == 'local_name') {
        g_object_name = oss_key + "${filename}"
    }
    else if (g_object_name_type == 'random_name') {
        suffix = get_suffix(filename)
        g_object_name = oss_key + random_string(10) + suffix
    }
    return ''
}

//把policy填充到Formdata里
function set_upload_param(file) {

    get_oss_signature()
    if (file) {

        calculate_object_name(file.name);
        var res = {
            'key': g_object_name,
            'policy': oss_policyBase64,
            'OSSAccessKeyId': oss_accessid,
            'success_action_status': '200', //让服务端返回200,不然，默认会返回204
//                'callback': oss_callbackbody,
            'signature': oss_signature,
        };

        var form_data = new FormData();
        for ( var key in res ) {
            form_data.append(key, res[key]);
        }
        form_data.append("file",file);

        return res;
    }

    return false;

}

//上传到阿里云 callBack 是用来在上传成功后通知服务端
function OssUpload( file,fileNameType,callBack) {
    g_object_name_type = fileNameType;
    var form_data = set_upload_param(file.name);
    
    if(!form_data){
        alert("form_data error")
        return
    }
    var fileFullName = oss_host+form_data.get("key");
   
    $.ajax({
        url: oss_host,
        data: form_data,
        processData: false,
        cache: false,
        async: false,
        type:'POST',
        contentType: false,//这个就是了
        success: function (data, textStatus, request) {
           
            //textStatus === "success" 表示成功
            if(typeof callBack === "function") {
                callBack(fileFullName,form_data.get("policy"),textStatus);
            }

        },
        error : function(responseStr) {
          
            if(typeof callBack === "function") {
                callBack(fileFullName,form_data.get("policy"),responseStr.responseText);
            }
        }
    });
}
