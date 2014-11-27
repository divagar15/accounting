<?php
class Zend_View_Helper_SessionCheck 
{
	public function checkLogin() {
		if(Zend_Session::namespaceIsset('sess_login'))	{
			echo '<pre>';  print_r(Zend_Session::namespaceGet('sess_login')); echo '</pre>';
		} else {
			echo 'ttt';	
		}
	}
}
?>