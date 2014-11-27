$(document).ready(function(){
       $('#frmlogin').validate({
                rules: {
                    username   : {required: true, email: true},
                    password   : {required: true,	minlength: 5},
				},
                messages: {
                    username   : "Please enter your email id",
				    password   : {
                        required: "Please enter a password",
                        minlength: "Your password must be at least 5 characters long"
                    },
               },
               submitHandler: function(form) {
		            form.submit();
               }
     });
	 	 
});