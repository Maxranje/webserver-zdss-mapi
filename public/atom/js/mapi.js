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
    $(document).on("click","#logout",function(){
        $.post("/mapi/sign/out",function(data){window.location.reload();});
    });
})(jQuery);