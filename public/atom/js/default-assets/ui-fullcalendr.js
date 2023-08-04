(function ($) {
    'use strict';

    var today = new Date();
    var y = today.getFullYear();
    var m = today.getMonth();
    var d = today.getDate();

    
    // Default view
    $('#fullcalendar-default-view').fullCalendar({
        themeSystem: 'bootstrap4',
        bootstrapFontAwesome: {
            close: ' ion ion-md-close',
            prev: ' fa fa-angle-left scaleX--1-rtl',
            next: ' fa fa-angle-right scaleX--1-rtl',
            prevYear: ' fa fa-angle-left scaleX--1-rtl',
            nextYear: ' fa fa-angle-right scaleX--1-rtl'
        },

        header: {
            left: 'title',
            center: 'month,agendaDay',
            right: 'prev,next today'
        },
        timeZone: 'PRC',

        contentHeight:550,
        dayMaxEventRows: 1, //最大事件行数
        moreLinkContent(jsEvent) {
            console.log(jsEvent)
            return jsEvent.shortText;
        },
        defaultDate: today,
        navLinks: true,
        selectable: true,
        selectHelper: true,
        weekNumbers: false,
        nowIndicator: true,
        firstDay: 1,
        businessHours: {
            dow: [1, 2, 3, 4, 5],
            start: '9:00',
            end: '18:00',
        },
        editable: false,
        eventLimit: true,
        events: "/mapi/schedule/calendarclient"
    });

})(jQuery);