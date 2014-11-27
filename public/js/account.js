
 $(function() {

            // Binding a click event
            // From jQuery v.1.7.0 use .on() instead of .bind()
            $('#add-account').bind('click', function(e) {

                // Prevents the default action to be triggered. 
                e.preventDefault();
                $("#account_add").show();
                $("#account_edit").hide();
                // Triggering bPopup when click event is fired
                $('#popup').bPopup({
                    easing: 'easeOutBack', //uses jQuery easing plugin
                    speed: 1500,
                    transition: 'slideDown'
                });

            });

              $('.edit-account').bind('click', function(e) {
                e.preventDefault();
                $("#account_add").hide();
                $("#account_edit").show();
                var index   = $(this).attr('data-index');
                var subname = $(this).attr('data-name');
                var status  = $(this).attr('data-check');
                    $('#popup').bPopup({
                        contentContainer:'#account_details',
                        loadUrl: 'ajax-call/ajaxAction/subAccount/id/'+index+'/name/'+subname+'/status/'+status 
                     });
             });

  });


    function enable(Idvalue) {
       var confirmMsg = confirm("Are you sure want to enable this account?");
        if(confirmMsg) {
            window.location.href='<?php echo $this->sitePath; ?>default/accounts/index/actid/'+Idvalue;
        }
    }
    function disable(Idvalue) {
       var confirmMsg = confirm("Are you sure want to disable this account?");
        if(confirmMsg) {
            window.location.href='<?php echo $this->sitePath; ?>default/accounts/index/delid/'+Idvalue;
        }
    }
    function redirect() {
        window.location.href='<?php echo $this->sitePath."default/accounts/add"; ?>';
    }