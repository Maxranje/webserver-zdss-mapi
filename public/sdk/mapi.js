function mysignOut() {
    $.post("/mapi/sign/out", function(data){
        alert(data);
    });
    return false;
}

let nobell = '<div class="flex justify-between"><div></div><div><a style="margin-right:20px;text-decoration:none; cursor:pointer;"title="邮箱"href="http://mail.zdss.cn"><i class="fa fa-envelope-o"></i></a><a style="margin-right:10px;text-decoration:none; cursor:pointer;"title="退出"href="/mapi/sign/out"><i class="fa fa-sign-out"></i></a></div></div>';
let bell = '<div class="flex justify-between"><div></div><div><a style="margin-right:20px;text-decoration:none; cursor:pointer;"title=""href="/mapi/dashboard/page#/review"><i class="fa fa-bell" id="bell_notice"></i></a><a style="margin-right:20px;text-decoration:none; cursor:pointer;"title="邮箱"href="http://mail.zdss.cn"><i class="fa fa-envelope-o"></i></a><a style="margin-right:10px;text-decoration:none; cursor:pointer;"title="退出"href="/mapi/sign/out"><i class="fa fa-sign-out"></i></a></div></div>';

function noticeBell() {
    var bell =  document.getElementById("bell_notice"); 
    if (!bell) {
        return false;
    }
    fetch("/mapi/api/notice?isbell=1").then(response => response.json()).then(data=>{
        if (data && data.status == 0 ){
            if (data.data.bell_notice <= 0) {
                bell.innerHTML = "";
                bell.style.color = "";
            } else {
                bell.innerHTML = "有" + data.data.bell_notice + "条未处理工单";
                bell.style.color = "red";
            }
        }
    }).catch(error=>{console.error('Error:', error);});
}