$(function() {
    // make code pretty
    window.prettyPrint && prettyPrint()
});


/*====LEFT BAR TOGGLE====*/
$(function() {
    $(".left-toggle").click(function() {
        $('.main-wrapper').toggleClass('merge-right');
    });

    $('.main-container').click(function() {
        if ($('.main-wrapper').hasClass('merge-right'))
        {
            $('.main-wrapper').removeClass('merge-right');
        }

    });
});

/*====ACTION BAR TOOLTIP====*/
$(function() {
    $('.action-bar a').tooltip({
        placement: 'top'

    });
});


/*================================
 SCROLL TOP
 =================================*/
$(function() {
    $(".scroll-top").hide();
    $(window).scroll(function() {
        if ($(this).scrollTop() > 100) {
            $('.scroll-top').fadeIn();
        } else {
            $('.scroll-top').fadeOut();
        }
    });

    $('.scroll-top a').click(function() {
        $('body,html').animate({
            scrollTop: 0
        }, 500);
        return false;
    });
});
/*==NICE SCROLL==*/
$(function() {
    $('.recent-users-scroll').niceScroll({
        cursorcolor: "#4074b4",
        cursorwidth: "5px"
    });
    $('.support-ticket-scroll').niceScroll({
        cursorcolor: "#4074b4",
        cursorwidth: "5px"
    });

    $('.right-shortcut-bar-items').niceScroll({
        cursorcolor: "#aaa",
        cursorwidth: "5px"
    });



    /*
     $('#recnet-post-scroll').slimScroll({
     color: '#111',
     height: '335px',
     railVisible: true,
     railColor: '#ccc',
     railOpacity: 0.9
     });
     $('.right-shortcut-bar-items').slimScroll({
     color: '#111',
     height: '100%',
     railVisible: true,
     railColor: '#ccc',
     railOpacity: 0.9
     });*/

});

/*Collapsible*/
$(function() {


    $('.widget-collapse').click(function(e)
    {
        var widgetElem = $(this).children('i');
        $(this).parents('.widget-head')
                .next('.widget-container')
                .slideToggle('slow');

        if ($(widgetElem).hasClass('icon-arrow-down')) {
            $(widgetElem).removeClass('icon-arrow-down');
            $(widgetElem).addClass('icon-arrow-up');


        }

        else
        {
            $(widgetElem).removeClass('icon-arrow-up');
            $(widgetElem).addClass('icon-arrow-down');

        }


        e.preventDefault();

    });


    $('.widget-remove').click(function(e) {
        $(this).parents('.widget-module').remove();
        e.preventDefault();
    });

});

/* Data tables */
 $(function() {

     $('.data-tbl-boxy').dataTable({
            "sPaginationType": "bootstrap",
            "iDisplayLength": 10,
            "oLanguage": {
                 "sLengthMenu": "<span class='lengthLabel pull-left'>Entries per page:</span><span class='lenghtMenu pull-left'> _MENU_</span>",
             },
            "sDom": '<"widget-head clearfix"fl>,<"widget-container"<"widget-block"<"widget-content"t>,,<"table-bottom clearfix"<"tbl-pagination pull-left"p><"tbl-data-info pull-right"i>>'
     });

    $('#pending_transaction').dataTable({
            "sPaginationType": "bootstrap",
            "iDisplayLength": 10,
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': ['nosort']
            }],
            "oLanguage": {
                 "sLengthMenu": "<span class='lengthLabel pull-left'>Entries per page:</span><span class='lenghtMenu pull-left'> _MENU_</span>",
             },
            "sDom": '<"widget-head clearfix"fl>,<"widget-container"<"widget-block"<"widget-content"t>,,<"table-bottom clearfix"<"tbl-pagination pull-left"p><"tbl-data-info pull-right"i>>'
     });

     $('#income-table').dataTable({
            "sPaginationType": "bootstrap",
            "aLengthMenu": [
                [10, 25, 60, 120, 240, 360],
                [50, 100, 200, 300, 400, 500]
            ], 
            "iDisplayLength": 50,
            "aaSorting": [[0, 'desc']],
            "aoColumnDefs" : [ 
               { "iDataSort": 1, "aTargets": [ 0 ] },
               { 'bSortable': false, 'aTargets': [ 8 ] }
            ], 
            "oLanguage": {
                 "sLengthMenu": "<span class='lengthLabel pull-left'>Entries per page:</span><span class='lenghtMenu pull-left'> _MENU_</span>",
             },
            "sDom": '<"widget-head clearfix"fl>,<"widget-container"<"widget-block"<"widget-content"t>,,<"table-bottom clearfix"<"tbl-pagination pull-left"p><"tbl-data-info pull-right"i>>'
     });

    $('#expense-table').dataTable({
            "sPaginationType": "bootstrap",
            "aLengthMenu": [
                [10, 25, 60, 120, 240, 360],
                [50, 100, 200, 300, 400, 500]
            ], 
            "iDisplayLength": 50,
            "aaSorting": [[0, 'desc']],
            "aoColumnDefs" : [ 
               { "iDataSort": 1, "aTargets": [ 0 ] },
               { 'bSortable': false, 'aTargets': [ 8 ] } 
            ], 
            "oLanguage": {
                 "sLengthMenu": "<span class='lengthLabel pull-left'>Entries per page:</span><span class='lenghtMenu pull-left'> _MENU_</span>",
             },
            "sDom": '<"widget-head clearfix"fl>,<"widget-container"<"widget-block"<"widget-content"t>,,<"table-bottom clearfix"<"tbl-pagination pull-left"p><"tbl-data-info pull-right"i>>'
     });


    $('#invoice-table').dataTable({
            "sPaginationType": "bootstrap",
            "aLengthMenu": [
                [10, 25, 60, 120, 240, 360],
                [50, 100, 200, 300, 400, 500]
            ], 
            "iDisplayLength": 50,
            "aaSorting": [[0, 'desc']],
            "aoColumnDefs" : [ 
              { "iDataSort": 1, "aTargets": [ 0 ] },
               { 'bSortable': false, 'aTargets': [ 7 ] }
            ], 
            "oLanguage": {
                 "sLengthMenu": "<span class='lengthLabel pull-left'>Entries per page:</span><span class='lenghtMenu pull-left'> _MENU_</span>",
             },
            "sDom": '<"widget-head clearfix"fl>,<"widget-container"<"widget-block"<"widget-content"t>,,<"table-bottom clearfix"<"tbl-pagination pull-left"p><"tbl-data-info pull-right"i>>'
     });


    $('#credit-table').dataTable({
            "sPaginationType": "bootstrap",
            "aLengthMenu": [
                [10, 25, 60, 120, 240, 360],
                [50, 100, 200, 300, 400, 500]
            ], 
            "iDisplayLength": 50,
            "aaSorting": [[0, 'desc']],
            "aoColumnDefs" : [ 
               { "iDataSort": 1, "aTargets": [ 0 ] },
               { 'bSortable': false, 'aTargets': [ 8 ] }
            ], 
            "oLanguage": {
                 "sLengthMenu": "<span class='lengthLabel pull-left'>Entries per page:</span><span class='lenghtMenu pull-left'> _MENU_</span>",
             },
            "sDom": '<"widget-head clearfix"fl>,<"widget-container"<"widget-block"<"widget-content"t>,,<"table-bottom clearfix"<"tbl-pagination pull-left"p><"tbl-data-info pull-right"i>>'
     });


    $('#journal-table').dataTable({
            "sPaginationType": "bootstrap",
            "aLengthMenu": [
                [10, 25, 60, 120, 240, 360],
                [50, 100, 200, 300, 400, 500]
            ], 
            "iDisplayLength": 50,
            "aaSorting": [[0, 'desc']],
            "aoColumnDefs" : [ 
               { "iDataSort": 1, "aTargets": [ 0 ] },
               { 'bSortable': false, 'aTargets': [ 5 ] } 
            ], 
            "oLanguage": {
                 "sLengthMenu": "<span class='lengthLabel pull-left'>Entries per page:</span><span class='lenghtMenu pull-left'> _MENU_</span>",
             },
            "sDom": '<"widget-head clearfix"fl>,<"widget-container"<"widget-block"<"widget-content"t>,,<"table-bottom clearfix"<"tbl-pagination pull-left"p><"tbl-data-info pull-right"i>>'
     });



     
    $('#example').dataTable( { 
        "sPaginationType": "bootstrap",
        "iDisplayLength": 10, 
         "aoColumns": [
            { "sType": "date-uk" },
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,            
        ], 
            "oLanguage": {
                 "sLengthMenu": "<span class='lengthLabel pull-left'>Entries per page:</span><span class='lenghtMenu pull-left'> _MENU_</span>",
             },
            "sDom": '<"widget-head clearfix"fl>,<"widget-container"<"widget-block"<"widget-content"t>,,<"table-bottom clearfix"<"tbl-pagination pull-left"p><"tbl-data-info pull-right"i>>'

    }); 
/*

 var stripHtml = (function() {
    var tmpDiv = document.createElement("DIV");
    return function(html) {
        tmpDiv.innerHTML = html;
        return $.trim(tmpDiv.textContent || tmpDiv.innerText);
    };
})();

var mRenderFactory = function (colIndex) {
    return function (data, type, full) {
        var cache = MRenderCache.getCache(full);

        if (type === "filter" || type === "sort" || type === "type") {
            return cache.getOrElse(colIndex, data, stripHtml)
        }
        return data;
    };
};

var MRenderCache = function () {
    this.full = [];
}
MRenderCache.getCache = function (full) {
    var cache = full[full.length - 1];
    if (cache == null || !cache.MRenderCache) {
        cache = new MRenderCache();
        full.push(cache);
    }
    return cache;
}
MRenderCache.prototype.MRenderCache = true;
MRenderCache.prototype.getOrElse = function (colIndex, rawData, convert) {
    var result = this.full[colIndex];
    if (result === undefined) {
        result = convert(rawData);
        this.full[colIndex] = result;
    }
    return result;
}


$('#example').dataTable( {
    "aoColumns": [
        { "mRender": mRenderFactory(0) },
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
    ]
} );
*/

     
});


/* Drop down select box */

$(function() {
 $(".select2").select2();
});

$(document).on(function() {
    $(".select2").select2();
});
/*checkbox */
$(function() {
        $('.icheck-box,.icheck-radio').iCheck({
            checkboxClass: 'icheckbox_minimal-red',
            radioClass: 'iradio_minimal-red',
            increaseArea: '20%' // optional
        });           
 });

$(function() {
    $('.date-pick').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
    });
    $('.date-pick-finance').datepicker({
        format: 'dd-M',
        autoclose: true,
    });
    $('.date-pick-company-financial').datepicker({
        format: 'yyyy',
        autoclose: true,
    });

    new UISearch( document.getElementById( 'sb-search' ) );
});

$(document).on('focus', '#date', function(e) {
    $('.date-pick').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
    });
});
$(function() {
$(".filetree").treeview({
});
});

function loading_show(){
     $('#popup').html("<img src='../images/ajax-loader/loader.gif'/>").fadeIn('fast');
}
function loading_hide(){
        $('#popup').fadeOut('fast');
}   

function companyAnnouncement() {
    if($('#all').is(':checked')) {
            $('#company').attr('disabled','disable');
            $('#users').attr('disabled','disable');
        } else {
            $('#company').removeAttr('disabled');
            $('#users').removeAttr('disabled');
        }
}


/*     $(function() {
        $("#from_date").datepicker({dateFormat:'dd-mm-yyyy'});
        $("#to_date").datepicker({dateFormat:'dd-mm-yyyy'});
        $("#from_date").datepicker().bind("change",function(){
            var minValue = $(this).val();
            minValue = $.datepicker.parseDate("dd-mm-yyyy", minValue);
            minValue.setDate(minValue.getDate());
            $("#to_date").datepicker( "option", "minDate", minValue );
        })
     });*/