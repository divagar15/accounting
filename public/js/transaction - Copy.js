$(function() {
        $('#income').click(function () {
          $('#add_expense').hide();
          $('#viewCustomer').hide();
          $('#splitCustomer').hide();
          $(".view_more").show();
          $('#add_income').slideToggle(1000);
            return false;
          });

        $('#expense').click(function () {
          $('#add_income').hide();
          $('#viewVendor').hide();
          $('#splitVendor').hide();
          $(".view_more").show();
          $('#add_expense').slideToggle(1000);
            return false;
          });

    $(".edit-transaction").click(function() {
                var index   = $(this).attr('data-index');
                var type    = $(this).attr('data-type');
                    $('#popup').bPopup({
                        contentContainer:'#edit_transaction',
                        loadUrl: 'transactions/ajax-display/ajaxAction/edit-transaction/id/'+index+'/type/'+type
                     });
    });
    $(document).on('click', '.edit_transaction', function() {
               var index   = $(this).attr('data-index');
                var type    = $(this).attr('data-type');
                    $('#popup').bPopup({
                        contentContainer:'#edit_transaction',
                        loadUrl: 'transactions/ajax-display/ajaxAction/edit-transaction/id/'+index+'/type/'+type
                     });
    });

    $(".create-payment").click(function() {
                var index   = $(this).attr('data-index');
                var type    = $(this).attr('data-type');
                var trans   = $(this).attr('data-trans');
                var amount  = $(this).attr('data-amount');
                    $('#popup').bPopup({
                        contentContainer:'#edit_transaction',
                        loadUrl: 'transactions/ajax-display/ajaxAction/create-payment/id/'+index+'/type/'+type+'/amount/'+amount+'/trans/'+trans
                     });
    });

            var counter = 0;

          $("#addrow").on("click", function () {

              counter = $('#myTable tr').length - 2;

              var newRow = $("<tr>");
              var cols = "";

              cols += '<td><input type="text" name="name' + counter + '"  class="form-control"/></td>';
              cols += '<td><input type="text" name="price' + counter + '"  class="form-control"/></td>';

              cols += '<td><span class="icon-cancel-circle-2 ibtnDel"></span></td>';
              newRow.append(cols);
             // if (counter == 4) $('#addrow').attr('disabled', true).prop('value', "You've reached the limit");
              
              $("table.order-list").append(newRow);
              $("#split_count").val(counter);
              counter++;
          });

          $("table.order-list").on("change", 'input[name^="price"]', function (event) {
              calculateRow($(this).closest("tr"));
              calculateGrandTotal();                
          });


          $("table.order-list").on("click", ".ibtnDel", function (event) {
              $(this).closest("tr").remove();
              calculateGrandTotal();
              counter = counter-1;
              var splitval = $("#split_count").val();
              --splitval;
              $("#split_count").val(splitval);
            
              $('#addrow').attr('disabled', false).prop('value', "Add Row");
          });



          $("#addrows").on("click", function () {

              counter = $('#myTables tr').length - 2;

              var newRow = $("<tr>");
              var cols = "";

              cols += '<td><input type="text" name="name' + counter + '"  class="form-control" /></td>';
              cols += '<td><input type="text" name="price' + counter + '"  class="form-control" /></td>';

              cols += '<td><span class="icon-cancel-circle-2 ibtnDels"></span></td>';
              newRow.append(cols);
             // if (counter == 4) $('#addrow').attr('disabled', true).prop('value', "You've reached the limit");
              
              $("table.order-lists").append(newRow);
              $("#split_counts").val(counter);
              counter++;
          });

          $("table.order-lists").on("change", 'input[name^="price"]', function (event) {
              calculateRows($(this).closest("tr"));
              calculateGrandTotals();                
          });


          $("table.order-lists").on("click", ".ibtnDels", function (event) {
              $(this).closest("tr").remove();
              calculateGrandTotals();
              counter = counter-1;
              var splitval = $("#split_counts").val();
              --splitval;
              $("#split_counts").val(splitval);
            
              $('#addrows').attr('disabled', false).prop('value', "Add Row");
          });
});

    function viewMore(value) {
      if(value=='customer') {
        $("#viewCustomer").slideToggle(1000);
      } else if(value=='vendor') {
         $("#viewVendor").slideToggle(1000);
      }
    }

    function viewSplit(value) {
      if(value=='customer') {
         $("#splitCustomer").fadeIn(1000);
         $("#viewCustomer").hide();
         $(".view_more").hide();
         $("#split").val('split');
         $("#split_count").val('2');
      } else if(value=='vendor') {
         $("#splitVendor").fadeIn(1000);
         $("#viewVendor").hide();
         $(".view_more").hide();
         $("#splits").val('split');
         $("#split_counts").val('2');
      }
    }

    function cancelSplit(value) {
      if(value=='customer') {
        $("#splitCustomer").fadeOut(1000);
        $(".view_more").show();
        $("#split").val('');
        $("#split_count").val('');

      } else if(value=='vendor') {
         $("#splitVendor").fadeOut(1000);
         $(".view_more").show();
         $("#splits").val('');
         $("#split_counts").val('');
      }
    }



function calculateRow(row) {
    var price = +row.find('input[name^="price"]').val();

}

function calculateGrandTotal() {
    var grandTotal = 0;
    var total = $('#amount').val();
    $("table.order-list").find('input[name^="price"]').each(function () {
        grandTotal += +$(this).val();
    });
    var calcTotal = total-grandTotal;
    $("#grandtotal").text(calcTotal.toFixed(2));
    $("#split_amount").val(grandTotal);
}


function calculateRows(row) {
    var price = +row.find('input[name^="price"]').val();

}

function calculateGrandTotals() {
    var grandTotal = 0;
    var total = $('#amounts').val();
    $("table.order-lists").find('input[name^="price"]').each(function () {
        grandTotal += +$(this).val();
    });
    var calcTotal = total-grandTotal;
    $("#grandtotals").text(calcTotal.toFixed(2));
    $("#split_amounts").val(grandTotal);
}

function verify(Idvalue) {
        window.location.href='transactions/index/verifyid/'+Idvalue;
    }
function unverify(Idvalue) {
        window.location.href='transactions/index/unverifyid/'+Idvalue;
    }

    function deleteTransaction(idValue,transType) {
      var confirmMsg = confirm("Are you sure want to delete this transaction");
      if(confirmMsg) {
          window.location.href='transactions/index/delid/'+idValue+'/type/'+transType;
      }
    }