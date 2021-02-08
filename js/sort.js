require(['jquery', 'javascript/jquery.tablesorter.min.js'], function($) {
    $('#smartquest_listresponse').tablesorter();
    $(document).ready( function () {
        $('td.c1').attr('colspan',3);
    });
})
