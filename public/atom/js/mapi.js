(function ($) {
    $(document).on("click","#login",function(){
        $.post("/mapi/sign/in",$('.login-form').serialize(),function(data){
            if (data.status != 0) {
                $('#tips').css('display', 'block').show();
                $('#tips-content').html(data.msg);
            } else {
                window.location.reload();
            }
        });
    });
    $(document).on("click","#reset",function(){
        $('#tips').css('display', 'none').hide();
        $('#tips1').css('display', 'none').hide();
        $.post("/mapi/sign/reset",$('.login-form').serialize(),function(data){
            if (data.status != 0) {
                $('#tips').css('display', 'block').show();
                $('#tips-content').html(data.msg);
            } else {
                $('#tips1').css('display', 'block').show();
                $('#tips-content1').html("修改成功, 去登录");
            }
        });
    });
    $(document).on("click","#logout",function(){
        $.post("/mapi/sign/out",function(data){window.location.reload();});
    });

    var tmpUserName = localStorage.getItem('username');
    if (tmpUserName && tmpUserName != "" && tmpUserName != "undefined") {
        console.log($("#username").val() );
        
        $("#username").attr("value",localStorage.getItem('username'));
        $("#remember").attr("checked", true);
    }

    $("#remember").on("change", function(){
        var un = $("#username").val();
        if (this.checked) {
            if (un != "" && un != "undefined")  {
                localStorage.setItem('username',$("#username").val())
            }
        } else {
            localStorage.removeItem('username');
        }
    });
    
})(jQuery);