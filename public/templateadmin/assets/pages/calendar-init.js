/*
 Template Name: Annex - Bootstrap 4 Admin Dashboard
 Author: Mannatthemes
 Website: www.mannatthemes.com
File: Calendar init js
 */

!(function ($) {
    "use strict";

    var CalendarPage = function () {};

    (CalendarPage.prototype.init = function () {
        //checking if plugin is available
        if ($.isFunction($.fn.fullCalendar)) {
            /* initialize the external events */
            $("#external-events .fc-event").each(function () {
                // create an Event Object (http://arshaw.com/fullcalendar/docs/event_data/Event_Object/)
                // it doesn't need to have a start or end
                var eventObject = {
                    title: $.trim($(this).text()), // use the element's text as the event title
                };

                // store the Event Object in the DOM element so we can get to it later
                $(this).data("eventObject", eventObject);

                // make the event draggable using jQuery UI
                $(this).draggable({
                    zIndex: 999,
                    revert: true, // will cause the event to go back to its
                    revertDuration: 0, //  original position after the drag
                });
            });

            /* initialize the calendar */

            var date = new Date();
            var d = date.getDate();
            var m = date.getMonth();
            var y = date.getFullYear();

            $("#calendar").fullCalendar({
                header: {
                    left: "prev,next today",
                    center: "title",
                    right: "month",
                },

                events: [
                    {
                        title: "Long Event",
                        start: new Date(y, m, d - 5),
                        end: new Date(y, m, d - 2),
                    },
                    {
                        id: 999,
                        title: "Repeating Event",
                        start: new Date(y, m, d - 3, 16, 0),
                        allDay: false,
                    },
                    {
                        id: 999,
                        title: "Repeating Event",
                        start: new Date(y, m, d + 4, 16, 0),
                        allDay: false,
                    },
                    {
                        title: "Meeting",
                        start: new Date(y, m, d, 10, 30),
                        allDay: false,
                    },
                    {
                        title: "Lunch",
                        start: new Date(y, m, d, 12, 0),
                        end: new Date(y, m, d, 14, 0),
                        allDay: false,
                    },
                    {
                        title: "Birthday Party",
                        start: new Date(y, m, d + 1, 19, 0),
                        end: new Date(y, m, d + 1, 22, 30),
                        allDay: false,
                    },
                    {
                        title: "Click for Google",
                        start: new Date(y, m, 28),
                        end: new Date(y, m, 29),
                        url: "http://google.com/",
                    },
                ],
            });

            /*Add new event*/
            // Form to add new event
        } else {
            alert("Calendar plugin is not installed");
        }
    }),
        //init
        ($.CalendarPage = new CalendarPage()),
        ($.CalendarPage.Constructor = CalendarPage);
})(window.jQuery),
    //initializing
    (function ($) {
        "use strict";
        $.CalendarPage.init();
    })(window.jQuery);
