function mysignOut() {
    localStorage.clear();
    fetch("/mapi/sign/out").then(response => response.json()).then(data=>{
        window.location.href =`${window.location.origin}/login`
    });
}
function noticeBell() {
    var bell = document.getElementById("bell_notice");
    var badge = document.getElementById("zy-notification_badge");

    if (!bell || !badge) {
        return false;
    }

    fetch("/mapi/api/notice")
        .then(response => response.json())
        .then(data => {
            if (data && data.status == 0) {
                const noticeCount = data.data.bell_notice;

                if (noticeCount <= 0) {
                    // 没有通知时，隐藏徽章，恢复图标颜色
                    badge.classList.remove("show", "pulse");
                    badge.textContent = "";
                    bell.style.color = "";
                } else {
                    // 有通知时，显示徽章和数量
                    badge.textContent = noticeCount > 99 ? "99+" : noticeCount.toString();
                    badge.classList.add("show");

                    // 如果数量大于5，添加脉冲动画效果
                    if (noticeCount > 5) {
                        badge.classList.add("pulse");
                    } else {
                        badge.classList.remove("pulse");
                    }

                    // 图标颜色变为红色表示有通知
                    bell.style.color = "#ff4757";
                }
            }
        })
        .catch(error => {
            console.error("Error:", error);
        });
}