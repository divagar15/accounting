$(document).ready(function(){

      $('.customer-refresh').poshytip({
        content: 'Click to refresh the customer list',
        showOn: 'none',
        alignTo: 'target',
        alignX: 'inner-left',
        offsetX: 0,
        offsetY: 5
      });
      $('.vendor-refresh').poshytip({
        content: 'Click to refresh the vendor list',
        showOn: 'none',
        alignTo: 'target',
        alignX: 'inner-left',
        offsetX: 0,
        offsetY: 5
      });
      $('.payaccount-refresh').poshytip({
        content: 'Click to refresh the payment account list',
        showOn: 'none',
        alignTo: 'target',
        alignX: 'inner-left',
        offsetX: 0,
        offsetY: 5
      });
      $('.incomeaccount-refresh').poshytip({
        content: 'Click to refresh the income account list',
        showOn: 'none',
        alignTo: 'target',
        alignX: 'inner-left',
        offsetX: 0,
        offsetY: 5
      });
      $('.expenseaccount-refresh').poshytip({
        content: 'Click to refresh the expense account list',
        showOn: 'none',
        alignTo: 'target',
        alignX: 'inner-left',
        offsetX: 0,
        offsetY: 5
      });
     $('.product-refresh').poshytip({
        content: 'Click to refresh the product list',
        showOn: 'none',
        alignTo: 'target',
        alignX: 'inner-left',
        offsetX: 0,
        offsetY: 5
      });
      $('.account-refresh').poshytip({
        content: 'Click to refresh the account list',
        showOn: 'none',
        alignTo: 'target',
        alignX: 'inner-left',
        offsetX: 0,
        offsetY: 5
      });
      $('#add-customer').click(function() { $('.customer-refresh').poshytip('show'); });
      $('.customer-refresh').click(function() { $('.customer-refresh').poshytip('hide'); });
      $('#add-vendor').click(function() { $('.vendor-refresh').poshytip('show'); });
      $('.vendor-refresh').click(function() { $('.vendor-refresh').poshytip('hide'); });
      $('#add-payaccount').click(function() { $('.payaccount-refresh').poshytip('show'); });
      $('.payaccount-refresh').click(function() { $('.payaccount-refresh').poshytip('hide'); });
      $('#add-incomeaccount').click(function() { $('.incomeaccount-refresh').poshytip('show'); });
      $('.incomeaccount-refresh').click(function() { $('.incomeaccount-refresh').poshytip('hide'); });
      $('.add-product').on("click",function() { $('.product-refresh').poshytip('show'); });
      $('.product-refresh').click(function() { $('.product-refresh').poshytip('hide'); });
      $('.add-account').on("click",function() { $('.account-refresh').poshytip('show'); });
      $('.account-refresh').click(function() { $('.account-refresh').poshytip('hide'); });
      $('.add-expenseaccount').on("click",function() { $('.expenseaccount-refresh').poshytip('show'); });
      $('.expenseaccount-refresh').click(function() { $('.expenseaccount-refresh').poshytip('hide'); });

});

function expenseAccountRefresh() {
  $('.expenseaccount-refresh').poshytip('show')
}

function productRefresh() {
  $('.product-refresh').poshytip('show');
}

function accountRefresh() {
  $('.account-refresh').poshytip('show');
}