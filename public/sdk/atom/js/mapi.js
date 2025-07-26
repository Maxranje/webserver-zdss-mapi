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

    $("#selectService").on("change", function(){
        var selectedOption = $(this).val();  
        if (selectedOption) {
            window.location.href=selectedOption;
        }
    });


    var abraodplan_checkbox ;
    $('.zy_checkbox_abroadplan').change(function() {
        var isChecked = $(this).is(':checked'); // 检查复选框是否被选中
        if (!isChecked) {
            return ;
        }
        abraodplan_checkbox = $(this)
        $("#exampleModal").modal("show");
    });

    $('#sureClick').on("click",function() {
        var isChecked = abraodplan_checkbox.is(":checked");
        if (!isChecked) {
            return ;
        }
        var checkId = abraodplan_checkbox.data('id'); // 获取复选框的 data-id 属性
        var token = abraodplan_checkbox.data('token'); // 获取复选框的 data-id 属性
        var data = {
            check_id : checkId,
            token: token
        };
        // 发送 AJAX 请求到服务端
        $.ajax({
            url: '/mapi/api/apconfirm',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(response) {
                if (response && response.status == 0) {
                    abraodplan_checkbox.attr("disabled", true);
                    $("#exampleModal").modal("hide");
                    abraodplan_checkbox = null
                } else{
                    abraodplan_checkbox.attr("checked", false);
                    $("#exampleModal").modal("hide");
                    abraodplan_checkbox = null
                    alert("系统异常重试");
                }
   
            },
            error: function(xhr, status, error) {
                abraodplan_checkbox.attr("checked", false);
                $("#exampleModal").modal("hide");
                abraodplan_checkbox = null
                alert("系统异常重试");
            }
        });
    });

    $('#dissClick').on("click",function() {
        var isChecked = abraodplan_checkbox.is(":checked");
        if (!isChecked) {
            return ;
        }
        abraodplan_checkbox.attr("checked", false);
        $("#exampleModal").modal("hide");
        abraodplan_checkbox = null
    });
    
})(jQuery);