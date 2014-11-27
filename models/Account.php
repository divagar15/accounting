<?php
class Account extends Zend_Db_Table 
{
	protected $getVal;

	public function init() {

		if(Zend_Session::namespaceIsset('sess_remote_database')) {
			$remoteSession = new Zend_Session_Namespace('sess_remote_database');
			$this->remoteDb = new Zend_Db_Adapter_Pdo_Mysql(array(
							    'host'     =>  $remoteSession->hostName,
							    'username' =>  $remoteSession->userName,
						        'password' =>  $remoteSession->password,
								'dbname'   =>  $remoteSession->dataBase
								)); 
			$authAdapter = new Zend_Auth_Adapter_DbTable($this->remoteDb);
			//Zend_Session::destroy();
			//echo $remoteSession->userName; die();
		}
	}

	public function userAuth($getPost) {
	//	echo 'SELECT * FROM login_credentials WHERE username = "'.$getPost['username'].'" AND password = "'.md5($getPost['password']).'" AND account_status = 1 ';
		$where = 't1.username = "'.$getPost['username'].'" AND t1.password = "'.md5($getPost['password']).'" AND t1.account_status = 1 AND t2.delete_status=1';
		$select  = $this->_db->select()
				 ->from(array('t1' => 'login_credentials'))
				 ->joinLeft(array('t2' => 'company_details'),'t1.fkcompany_id = t2.id',array('t2.id as cid',
				 		't2.company_name','t2.country','t2.financial_year_start_date','t2.financial_year_end_date','t2.currency','t2.status'))
				 ->joinLeft(array('t3' => 'company_database'),'t1.fkcompany_id = t3.fkcompany_id',array('t3.id as did',
				 		't3.server_address','t3.username as server_user','t3.password as server_pass','t3.database_name'))
				 ->where($where);
	    $sql = $this->_db->fetchAll($select);
		return $sql;	
	}

	public function secondDb() {
	//	echo 'SELECT * FROM '.$db2.'.company_details';

	     /* $dbType   = 'Pdo_Mysql'; 
	      $dbParams =  array('host'     =>  'localhost',
					      	  'username' =>  'root',
					          'password' =>  '',
					          'dbname'   =>  'accounting',
						      'profiler' => false
						     );
			$db = Zend_Db::factory($dbType, $dbParams);
			Zend_Db_Table::setDefaultAdapter($db);
			$this->registry->database = $db; */

		$sql = $this->remoteDb->fetchAll('SELECT * FROM customers');
		return $sql;
	}

	public function primaryDb() {
			$sql = $this->_db->fetchAll('SELECT * FROM company_details');
			return $sql;
	}


	/**
	* Purpose : Register Company Details 
	* @param   array $postVal contain form post value
	* @return  last insert id when success
	*/
	
	public function insertCompany($postVal) {
		$start_year = trim($postVal['start_year']);
		$getData    =   array('company_name'   				 => trim($postVal['company']),
						 	  'company_uen'    				 => trim($postVal['cuen']),
						 	  'company_gst'    				 => trim($postVal['gst']),
						 	  'telephone'    	   			 => trim($postVal['phone']),
						 	  'block_no'    	   			 => trim($postVal['block_no']),
						 	  'street_name'    	   			 => trim($postVal['street_name']),
						 	  'level'    	   				 => trim($postVal['level']),
						 	  'unit_no'    	   				 => trim($postVal['unit_no']),
						 	  'city'    	   				 => trim($postVal['city']),
						 	  'zip_code'    	   			 => trim($postVal['zip_code']),
						 	  'region'    	   				 => trim($postVal['region']),
						 	  'country'    	   				 => trim($postVal['country']),
						 	  'financial_year_start_date'    => trim($postVal['start_date']),
						 	  'financial_year_end_date'    	 => trim($postVal['end_date']),
						 	  'financial_start_year'    	 => trim($start_year));
		if($this->_db->insert('company_details',$getData)) {
			return  $this->_db->lastInsertId();	
		} else {
			return false;	
		}
	}

	/**
	* Purpose : Check login username already exists or not
	* @param   array $postVal contain form username
	* @return  last insert id when success
	*/

	public function checkLogin($userName,$id='') {
		if(isset($id) && !empty($id)) {
			$sql = $this->_db->fetchOne('SELECT * FROM login_credentials WHERE username="'.$userName.'" AND id='.$id.'');
			if($sql) {
				return false;
			} else {
				$sql = $this->_db->fetchOne('SELECT * FROM login_credentials WHERE username="'.$userName.'" AND account_status=1');
				return $sql;
			}
		} else {
		 	$sql = $this->_db->fetchOne('SELECT * FROM login_credentials WHERE username="'.$userName.'" AND account_status=1');
		 	return $sql;
		}
	}

	/**
	* Purpose : Insert login details for the company
	* @param   array $postVal contain form post value and company last inserted id
	* @return  last insert id when success
	*/
	
	public function insertLogin($postVal,$resultId) {
		$getData    =   array('username'   		 => trim($postVal['username']),
						 	  'password'    	 => trim(md5($postVal['password'])),
						 	  'fkcompany_id'     => $resultId,
						 	  'account_type'     => trim($postVal['account_type']));
		if($this->_db->insert('login_credentials',$getData)) {
			return  $this->_db->lastInsertId();	
		} else {
			return false;	
		}
	}

	/**
	* Purpose : Create a database for the company
	* @param   contains dbname, sql file array and company primary row id
	* @return  last insert id when success
	*/
	
	public function createDatabase($database_name,$sql_contents,$resultId) {
		//echo "opki"; die();
		//echo '<pre>'; print_r($sql_contents); echo '</pre>'; die();
		$status = 0;
		$sql = $this->_db->query("CREATE DATABASE ".$database_name."");
		if($sql) {
			$this->dynamicDb = new Zend_Db_Adapter_Pdo_Mysql(array(
							    'host'     =>  "accountingdb.c1ewvqstfjes.ap-southeast-1.rds.amazonaws.com:3306",
							    'username' =>  "pinnone",
						        'password' =>  "Accounting2014",
								'dbname'   =>  $database_name
								)); 
			$authAdapter = new Zend_Auth_Adapter_DbTable($this->dynamicDb);
			foreach($sql_contents as $queries){
				$result = $this->dynamicDb->query($queries);
				if(!$result) {
					$status = 1;
				}
			  }
			  if($status==0) {


					$getData    =   array('server_address'   => "default",
									 	  'database_name'    => $database_name,
									 	  'fkcompany_id'     => $resultId
									 	  );
					// echo '<pre>'; print_r($getData); echo '</pre>'; die();
					if($this->_db->insert('company_database',$getData)) {
/*					$getInvoiceData  =  array('template'    	 => '2',
									 	  'company_logo'    	 => '',
									 	  'display_logo'    	 => '2',
									 	  'invoice_prefix'    	 => 'INV',
									 	  'default_credit_term'  => '2',
									 	  'default_tax_code'     => '0',
									 	  'default_currency'     => 'SGD',
									 	  'default_product_title'=> '1');
			  	    $this->dynamicDb->insert('invoice_credit_note_customization',$getInvoiceData);*/
						return  $this->_db->lastInsertId();	
					} else {
						return false;	
					}			  		
			  } else {
			  	return false;
			  }
		} else {
			return false;
		}
	}

	/**
	* Purpose : Drop a database for the company
	* @param   contains dbname, and company primary row id
	* @return  delete database
	*/
	public function dropDatabase($id='') {
		if(isset($id) && !empty($id)) {
			$getData    =   array('delete_status'   => 2,
			             		  'date_modified' 	=> new Zend_Db_Expr('NOW()'));
				if($this->_db->update('company_details',$getData,'id = '.$id.'')) {
					$getData    =   array('account_status'   => 2,
			             		  	      'date_modified'  => new Zend_Db_Expr('NOW()'));
					$this->_db->update('login_credentials',$getData,'fkcompany_id = '.$id.'');
					return  true;	
				} else {
					return  false;
				}
		} else {
			$remoteSession = new Zend_Session_Namespace('sess_remote_database');
			//echo $database_name = $remoteSession->dataBase; die();
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
			/*$sql = $this->_db->query("DROP DATABASE ".$database_name."");
			if($sql) {*/
				$getData    =   array('delete_status'   => 2,
			             		  	 'date_modified' 	=> new Zend_Db_Expr('NOW()'));
				if($this->_db->update('company_details',$getData,'id = '.$cid.'')) {
					$getData    =   array('account_status'   => 2,
			             		  	      'date_modified'  => new Zend_Db_Expr('NOW()'));
					$this->_db->update('login_credentials',$getData,'fkcompany_id = '.$cid.'');
					return  true;	
				} else {
					return  false;
				}
		//}
		}
	}

	/**
	* Purpose : Create a default COA for the company
	* @param   contains company primary row id
	* @return  last insert id when success
	*/
	
/*	public function CreateDefaultCoa($resultId) {
		$account = array(
						  array('fkcompany_id'=>$resultId,'account_type'=>2,'level1'=>1,'level2'=>0,'account_name'=>'Unrealised Foreign Exchange Gain / (Loss)','currency'=>null,'pay_status'=>0,'edit_status'=>2,'delete_status'=>1,'date_created'=>'2014-05-15 16:09:53','date_modified'=>'0000-00-00 00:00:00'),
						  array('fkcompany_id'=>$resultId,'account_type'=>4,'level1'=>0,'level2'=>0,'account_name'=>'Foreign Exchange Gain/(Loss)','currency'=>null,'pay_status'=>0,'edit_status'=>2,'delete_status'=>1,'date_created'=>'2014-05-15 16:09:53','date_modified'=>'0000-00-00 00:00:00'),
						  array('fkcompany_id'=>$resultId,'account_type'=>1,'level1'=>1,'level2'=>4,'account_name'=>'Trade Receivables','currency'=>null,'pay_status'=>0,'edit_status'=>2,'delete_status'=>1,'date_created'=>'2014-05-15 16:09:53','date_modified'=>'0000-00-00 00:00:00'),
						  array('fkcompany_id'=>$resultId,'account_type'=>1,'level1'=>1,'level2'=>5,'account_name'=>'Other Receivables','currency'=>null,'pay_status'=>0,'edit_status'=>2,'delete_status'=>1,'date_created'=>'2014-05-15 16:09:53','date_modified'=>'0000-00-00 00:00:00'),
						  array('fkcompany_id'=>$resultId,'account_type'=>2,'level1'=>1,'level2'=>3,'account_name'=>'Trade Payable','currency'=>null,'pay_status'=>0,'edit_status'=>2,'delete_status'=>1,'date_created'=>'2014-05-15 16:09:53','date_modified'=>'0000-00-00 00:00:00'),
						  array('fkcompany_id'=>$resultId,'account_type'=>2,'level1'=>1,'level2'=>8,'account_name'=>'Other Creditors','currency'=>null,'pay_status'=>0,'edit_status'=>2,'delete_status'=>1,'date_created'=>'2014-05-15 16:09:53','date_modified'=>'0000-00-00 00:00:00'),
						  array('fkcompany_id'=>$resultId,'account_type'=>3,'level1'=>0,'level2'=>0,'account_name'=>'Discounts','currency'=>null,'pay_status'=>0,'edit_status'=>2,'delete_status'=>1,'date_created'=>'2014-05-15 16:09:53','date_modified'=>'0000-00-00 00:00:00'),
						  array('fkcompany_id'=>$resultId,'account_type'=>4,'level1'=>0,'level2'=>0,'account_name'=>'Discounts','currency'=>null,'pay_status'=>0,'edit_status'=>2,'delete_status'=>1,'date_created'=>'2014-05-15 16:09:53','date_modified'=>'0000-00-00 00:00:00'),
						  array('fkcompany_id'=>$resultId,'account_type'=>5,'level1'=>4,'level2'=>1,'account_name'=>'Retained Earnings','currency'=>null,'pay_status'=>0,'edit_status'=>2,'delete_status'=>1,'date_created'=>'2014-05-15 16:09:53','date_modified'=>'0000-00-00 00:00:00'),
						  array('fkcompany_id'=>$resultId,'account_type'=>5,'level1'=>4,'level2'=>1,'account_name'=>'Current Year Earnings','currency'=>null,'pay_status'=>0,'edit_status'=>2,'delete_status'=>1,'date_created'=>'2014-05-15 16:09:53','date_modified'=>'0000-00-00 00:00:00')
						);
		foreach ($account as $acc) {
			$this->_db->insert('account',$acc);
		}
	}*/

	/**
	* Purpose  get all the company details and their database details
	* @param   none
	* @return  all company details with their database
	*/	

	public function getCompanies() {
		$where = 't1.company_type != 0 AND t1.delete_status=1';
		$select  = $this->_db->select()
				 ->from(array('t1' => 'company_details'),array('t1.id as cid',
				 		't1.company_name','t1.country','t1.financial_year_start_date','t1.financial_year_end_date'))
				 ->joinLeft(array('t2' => 'company_database'),'t1.id = t2.fkcompany_id',array('t2.id as did',
				 		't2.server_address','t2.username as server_user','t2.password as server_pass','t2.database_name'))
				 ->where($where);
	    $sql = $this->_db->fetchAll($select);
		return $sql;	
	}

	/**
	* Purpose  get all login details or particular login details
	* @param   none
	* @return  all login details (or) particular details for the companies
	*/	

	public function getLoginDetails($id='') {
		if(isset($id) && !empty($id)) {
			$sql = $this->_db->fetchAll('SELECT * FROM login_credentials WHERE id='.$id.'');
		} else {
			$sql = $this->_db->fetchAll('SELECT * FROM login_credentials WHERE account_type!=0');
		}
		return $sql;	
	}


    /**
	* Purpose  get particular company details
	* @param   company primary id
	* @return  company details
	*/	

	public function getCompany($id) {
		$sql = $this->_db->fetchAll('SELECT * FROM company_details WHERE id='.$id.' AND delete_status=1');
		return $sql;	
	}

	/**
	* Purpose : Update Particular Company Details 
	* @param   array $postVal contain form post value
	* @return  last update id when success
	*/
	
	public function updateCompany($postVal,$id) {
		$getData    =   array('company_name'   				 => trim($postVal['company']),
						 	  'company_uen'    				 => trim($postVal['cuen']),
						 	  'company_gst'    				 => trim($postVal['gst']),
						 	  'telephone'    	   			 => trim($postVal['phone']),
						 	  'block_no'    	   			 => trim($postVal['block_no']),
						 	  'street_name'    	   			 => trim($postVal['street_name']),
						 	  'level'    	   				 => trim($postVal['level']),
						 	  'unit_no'    	   				 => trim($postVal['unit_no']),
						 	  'city'    	   				 => trim($postVal['city']),
						 	  'zip_code'    	   			 => trim($postVal['zip_code']),
						 	  'region'    	   				 => trim($postVal['region']),
						 	  'country'    	   				 => trim($postVal['country']),
						 	  'financial_year_start_date'    => trim($postVal['start_date']),
						 	  'financial_year_end_date'    	 => trim($postVal['end_date']),
		             		  'date_modified' 				 => new Zend_Db_Expr('NOW()'));
		if($this->_db->update('company_details',$getData,'id = '.$id.'')) {
			return  true;	
		} else {
			return false;	
		}	
	}


	/**
	* Purpose : Update login details for the company
	* @param   array $postVal contain form post value and user primary id
	* @return  last update id when success
	*/
	
	public function updateLogin($postVal,$resultId) {
		$getData    =   array('username'   		 => trim($postVal['username']),
						 	  'account_type'     => trim($postVal['account_type']),
		             		  'date_modified' 	 => new Zend_Db_Expr('NOW()'));
		if($this->_db->update('login_credentials',$getData,'id = '.$resultId.'')) {
			if(isset($postVal['change_password']) && !empty($postVal['change_password']) && isset($postVal['password']) && !empty($postVal['password']) && isset($postVal['cpassword']) && !empty($postVal['cpassword'])) {
				$getData    =   array('password'   		 => trim(md5($postVal['password'])),
		             		  'date_modified' 	 => new Zend_Db_Expr('NOW()'));
				if($this->_db->update('login_credentials',$getData,'id = '.$resultId.'')) {
					return  true;	
				} else {
					return false;	
				}	
			} else {
				return  true;
			}	
		} else {
			return false;	
		}		
	}

    /**
	* Purpose  delete particular user 
	* @param   user primary id
	* @return  return true on success
	*/	

	 public function deleteUser($delid) {  
	 	$sql = $this->_db->delete('login_credentials', 'id = '.$delid.'');
		if($sql) {
			return true;
		} else {
			return false;
		}
	}	

	/**
	* Purpose  get all announcements details or particular announcements details
	* @param   none
	* @return  all announcements details (or) particular announcements details
	*/	

	public function getAnnouncements($id='') {
		if(isset($id) && !empty($id)) {
			$sql = $this->_db->fetchAll('SELECT t1.*,t2.company_name  FROM announcements as t1 LEFT JOIN company_details as t2 ON (t1.fkcompany_id=t2.id) WHERE t1.id='.$id.'');
		} else {
			$sql = $this->_db->fetchAll('SELECT t1.*,t2.company_name FROM announcements as t1 LEFT JOIN company_details as t2 ON (t1.fkcompany_id=t2.id)');
		}
		return $sql;	
	}

	/**
	* Purpose : Insert announcement details for all the company (or) selected companies
	* @param   array $postVal contain form post value
	* @return  last insert id when success
	*/
	
	public function sendAnnouncement($postVal) {
		$getData    =   array('fkcompany_id' => $postVal['company'],
							  'users'        => $postVal['users'],
						 	  'subject'      => stripslashes($postVal['subject']),
						 	  'message'      => stripslashes($postVal['message']));
		//print_r($getData); die();
		if($this->_db->insert('announcements',$getData)) {
			return  $this->_db->lastInsertId();	
		} else {
			return false;	
		} 
	}


	/**
	* Purpose : Update announcement details for all the company (or) selected companies
	* @param   array $postVal contain form post value
	* @return  last updated id when success
	*/
	
	public function updateAnnouncement($postVal,$resultId) {
		if(isset($postVal['all']) && !empty($postVal['all'])) {
			$companies = 'all';
		} else {
			$companies = implode(",", $postVal['company']);
		}
		//echo '<pre>'; print_r($postVal); echo '</pre>'; 
		$getData    =   array('companies'   => $companies,
						 	  'subject'     => stripslashes($postVal['subject']),
						 	  'message'     => stripslashes($postVal['message']),
		             		  'date_modified' 				 => new Zend_Db_Expr('NOW()'));
		if($this->_db->update('announcements',$getData,'id = '.$resultId.'')) {
			return  true;	
		} else {
			return false;	
		}	
	}

    /**
	* Purpose  delete particular announcement 
	* @param   announcement primary id
	* @return  return true on success
	*/	

	 public function deleteAnnouncement($delid) {  
	 	$sql = $this->_db->delete('announcements', 'id = '.$delid.'');
		if($sql) {
			return true;
		} else {
			return false;
		}
	}	


	/**
	* Purpose  get all the system accounts
	* @param   none
	* @return  all system account details
	*/	

	public function getSystemAccount() {
		$sql = $this->_db->fetchAll('SELECT * FROM system_accounts');
		return $sql;
	}

	/**
	* Purpose  get all the sub accounts under particular user account
	* @param   user session id
	* @return  all sub account details under particular user account
	*/	

	public function getSubAccount($sessId) {
		$sql = $this->_db->fetchAll('SELECT * FROM sub_accounts WHERE fkaccount_id='.$sessId.'');
		return $sql;
	}

	/**
	* Purpose  disable particular sub account, set delete status as 2 so account will be locked 
	* @param   sub account id
	* @return  updated disabled status id of particular sub account
	*/	

	 public function disableAccount($delid) {  
		$getData    =   array('delete_status'   => 2,
		             		  'date_modified' 	=> new Zend_Db_Expr('NOW()'));
		if($this->_db->update('sub_accounts',$getData,'id = '.$delid.'')) {
			return  true;	
		} else {
			return false;	
		}
	}	

	/**
	* Purpose  enable particular sub account, set delete status as 1 so account will be locked 
	* @param   sub account id
	* @return  updated enabled status id of particular sub account
	*/	

	 public function enableAccount($actid) {  
		$getData    =   array('delete_status'   => 1,
		             		  'date_modified' 	=> new Zend_Db_Expr('NOW()'));
		if($this->_db->update('sub_accounts',$getData,'id = '.$actid.'')) {
			return  true;	
		} else {
			return false;	
		}
	}


	/**
	* Purpose : Create new sub account Details 
	* @param   array $postVal contain form post value,$aid contain session account value
	* @return  last insert id when success
	*/
	
	public function insertAccount($aid,$postVal) {
		$getData    =   array('fkaccount_id'   => $aid,
						 	  'fksys_id' 	   => trim($postVal['accountType']),
						 	  'name'           => stripslashes($postVal['name']),
						 	  'payment_status' => $postVal['payment_account']);
		if($this->_db->insert('sub_accounts',$getData)) {
			return  $this->_db->lastInsertId();	
		} else {
			return false;	
		}
	}


    /**
	* Purpose : Edit sub account Details 
	* @param   array $postVal contain form post value
	* @return  update id when success
	*/
	
	public function editAccount($postVal) {
		$id = $postVal['id'];
		$getData    =   array('name'           => stripslashes($postVal['name']),
							  'payment_status' => $postVal['payment_account'],
		             		  'date_modified'  => new Zend_Db_Expr('NOW()'));
		if($this->_db->update('sub_accounts',$getData,'id = '.$id.'')) {
			return  true;	
		} else {
			return false;	
		}	
	}


	/**
	* Purpose : Reset Password the particular login of the company
	* @param   array $postVal contain form post value and user primary id
	* @return  last update id when success
	*/
	
	public function resetPassword($postVal,$resultId) {
		$getData    =   array('password'   		 => trim(md5($postVal['password'])),
		             		  'date_modified' 	 => new Zend_Db_Expr('NOW()'));
		if($this->_db->update('login_credentials',$getData,'id = '.$resultId.'')) {
			return  true;	
		} else {
			return false;	
		}		
	}

	/**
	* Purpose  get all login details for particular companies
	* @param   none
	* @return  all login details for the particular companies
	*/	

	public function getCompanyUserDetails($id) {
		$sql = $this->_db->fetchAll('SELECT * FROM login_credentials WHERE account_type!=0 AND account_type!=1 AND fkcompany_id='.$id.'');
		return $sql;	
	}

	public function getCompanyDatabase($id) {
		$sql = $this->_db->fetchAll('SELECT * FROM company_database WHERE fkcompany_id='.$id.'');
		return $sql;	
	}

	public function getDefaultTheme() {
		$logSession = new Zend_Session_Namespace('sess_login');
		if(isset($logSession->proxy_type) && !empty($logSession->proxy_type)) {
			$type = $logSession->proxy_type;
		} else {
			$type = $logSession->type;
		}
		//echo $id; die();
		if($type!=0) {
		    $sql = $this->remoteDb->fetchOne('SELECT id FROM theme_setting WHERE default_theme=1');
			return $sql;
		} else {
			$sql = $this->_db->fetchOne('SELECT id FROM themes WHERE default_theme=1');
			return $sql;
		}
	}


	public function updateLogo($filename='') {
		$logSession = new Zend_Session_Namespace('sess_login');
		if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
			$cid = $logSession->proxy_cid;
		} else {
			$cid = $logSession->cid;
		}
		if(isset($filename) && !empty($filename)) {
			$getData    =   array('company_logo'    => $filename,
			             		  'date_modified' 	=> new Zend_Db_Expr('NOW()'));
		} else {
			$getData    =   array('company_logo'    => '',
			             		  'date_modified' 	=> new Zend_Db_Expr('NOW()'));
		}
		if($this->_db->update('company_details',$getData,'id = '.$cid.'')) {
			return  true;	
		} else {
			return false;	
		}		
	}

	public function getCompanyLogo() {
		$logSession = new Zend_Session_Namespace('sess_login');
		if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
			$cid = $logSession->proxy_cid;
		} else {
			$cid = $logSession->cid;
		}
		$sql = $this->_db->fetchOne('SELECT company_logo FROM company_details WHERE id='.$cid.'');
		return $sql;	
	}


		public function checkFinance($start,$end) {

			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}

			$getCompanies = $this->_db->fetchAll('SELECT * FROM company_details WHERE id='.$cid.' AND delete_status=1');
			foreach ($getCompanies as $company) {
				$start_years = $company['financial_start_year'];
				$startDate = $company['financial_year_start_date'];
				$endDate   = $company['financial_year_end_date'];
			}

			if($start_years!=NULL && $start_years!='0000-00-00') {

					$explodess = explode('-', $start);
					$startYear = $start_years;
					$start_year = $start_years.'-'.$explodess[1].'-'.$explodess[2];
					$today     = date('Y');



					for($i=$startYear;$i<=$today;$i++) {

						$explodes = explode('-', $start_year);

						$year = $explodes[0]+1;

						if(($year)%4==0) {
							$end_year = date(strtotime($start_year . "+365 day"));
						} else {
							$end_year = date(strtotime($start_year . "+364 day"));
						}
						$fincance_end = date('Y-m-d',$end_year);



				/*echo $start_year."----";

				echo $start_year.'<br/>';*/

				$sqls = $this->remoteDb->fetchOne('SELECT id FROM financial_year WHERE financial_start="'.$start_year.'" AND financial_end="'.$fincance_end.'"');
				if(!$sqls) {
					$getData    =   array('financial_start'  => $start_year,
									  'financial_end'    => $fincance_end);
					$insertData = $this->remoteDb->insert('financial_year',$getData);
				}

				$start_year = $year.'-'.$explodes[1].'-'.$explodes[2];
			}

			//die();

		}

			$sql = $this->remoteDb->fetchOne('SELECT id FROM financial_year WHERE financial_start="'.$start.'" AND financial_end="'.$end.'"');
			if(!$sql) {
				$getData    =   array('financial_start'  => $start,
									  'financial_end'    => $end);
				$insertData = $this->remoteDb->insert('financial_year',$getData);
				return  $this->remoteDb->lastInsertId();	
			} else {
				return $sql;
			}
		}

}
?>