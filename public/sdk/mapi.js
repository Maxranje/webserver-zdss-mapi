function mysignOut() {
    $.post("/mapi/sign/out", function(data){
        alert(data);
    });
    return false;
}