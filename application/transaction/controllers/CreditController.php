<?php 
class Transaction_CreditController extends Zend_Controller_Action {
	/**
     * @var result
    */
	protected $result;
	
	/**
    * @var $postArray
    */
	protected $postArray;
	
	public function init() {
		$this->root 	   = Zend_Registry::get('path');
		$this->uploadPath  = Zend_Registry::get('uploadpath');
		$this->receiptPath = Zend_Registry::get('receiptuploadpath');
		$this->account     = new Account();
		$this->business    = new Business();
		$this->transaction = new Transaction();
		$this->settings    = new Settings();
		$this->approval    = new Approval();
		$this->accountData = new Account_Data();
		if(Zend_Session::namespaceIsset('sess_login')) {
			$logSession = new Zend_Session_Namespace('sess_login');	
			if($logSession->type==0 && !isset($logSession->proxy_type)) {
				$this->_redirect('developer');
			} 
		} else {
			$this->_redirect('index');
		}

			$logSession = new Zend_Session_Namespace('sess_login');

			if(isset($logSession->proxy_id) && !empty($logSession->proxy_id)) {
				$id = $logSession->proxy_id;
			} else {
				$id = $logSession->id;
			}

			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}

			$notify = array();
			$countMessage = 0; 
			$countUnseenMessage = 0; 

			$this->view->defaultTheme = $this->account->getDefaultTheme();
			$this->view->companyLogo  = $this->account->getCompanyLogo();
			$this->view->logopath     = $this->uploadPath.$cid."/";
			$this->notifications = $this->approval->getNotificationMessage($cid,$id);
			if(isset($this->notifications) && !empty($this->notifications)) {
				foreach ($this->notifications as $notification) {
					$users 			= explode(",", $notification['users']);
					$seen_users 	= explode(",", $notification['seen_users']);
					if(in_array($id,$users) || $notification['users']=='all') {
						if(in_array($id, $seen_users)) {
							$notify[$notification['id']]['seen'] = 1;
						} else {
							$notify[$notification['id']]['seen'] = 2;
							$countUnseenMessage++;
						}
						$countMessage++; 
						$notify[$notification['id']]['subject'] = $notification['subject'];
						$notify[$notification['id']]['message'] = $notification['message'];
						$notify[$notification['id']]['date']    = $notification['date_created'];
					}
				}
			}

			$this->view->notifyMessage  	   = $countMessage;
			$this->view->notifyUnseenMessage   = $countUnseenMessage;
			$this->view->notifyHeaderMessage   = $notify;		

			$getCompanies = $this->account->getCompany($cid);
				foreach ($getCompanies as $company) {
					$start_year = $company['financial_year_start_date'];
					$end_year   = $company['financial_year_end_date'];
				}
				$current_month = date('m-d');
				$finance_month = date('m-d',strtotime($start_year));
				if($current_month < $finance_month) {
					$cur_date  = date('Y-m-d',strtotime($start_year));
					$strtotime = strtotime($cur_date);
					$last_year = strtotime("-1 year",$strtotime);
					$current_year = date('Y-m-d',$last_year);
				} else {
					$current_year = date('Y-m-d',strtotime($start_year));
				}
				$this->fincance_start = $current_year;
				$end_year = date(strtotime($current_year . "+364 day"));
				$this->fincance_end = date('Y-m-d',$end_year);

				$this->view->fstart = $this->fincance_start;
				$this->view->fend   = $this->fincance_end;

			    $this->view->checkFinance   =   $this->account->checkFinance($this->fincance_start,$this->fincance_end);
 	}

	/**
    * @param $method action
    */
	
	public function __call($method, $args) {
			// If an unmatched 'Action' method was requested, pass on to the
			// default action method:
			if ('Action' == substr($method, -6)) {
				return $this->_redirect('index/error/');
			}
			throw new Zend_Controller_Exception('Invalid method called');
	}
	
	public function indexAction() {
		if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}

			$location   = $this->_getParam('location'); 

			$financeSet = $this->_getParam('financial_year'); 

			$this->view->setlocation = $location;

			if(isset($financeSet) && !empty($financeSet)) {
				$financeSet = $this->_getParam('financial_year'); 
				$this->view->setfinance  = $financeSet;
			} else {
				$financeSet = $this->view->checkFinance; 
				$this->view->setfinance  = $this->view->checkFinance;
			}
			
			$sort   = $this->_getParam('sort');
			if(isset($sort) && !empty($sort) && ($sort==1 || $sort==2)) {
				$sort   = $this->_getParam('sort');
			} else {
				$sort = '';
			}

			$this->view->sort = $sort;
			if(Zend_Session::namespaceIsset('insert_success_credit')) {
				$this->view->success = 'Credit Note Added successfully';
				Zend_Session::namespaceUnset('insert_success_credit');
			}
			if(Zend_Session::namespaceIsset('delete_success_credit_transaction')) {
				$this->view->success = 'Credit Note deleted successfully';
				Zend_Session::namespaceUnset('delete_success_credit_transaction');
			}
			if(Zend_Session::namespaceIsset('mark_success_credit_transaction')) {
				$this->view->success = 'Credit Note marked successfully';
				Zend_Session::namespaceUnset('mark_success_credit_transaction');
			}
			if(Zend_Session::namespaceIsset('verify_success_credit_transaction')) {
				$this->view->success = 'Transaction verified successfully';
				Zend_Session::namespaceUnset('verify_success_credit_transaction');
			}
			if(Zend_Session::namespaceIsset('unverify_success_credit_transaction')) {
				$this->view->success = 'Transaction unverified successfully';
				Zend_Session::namespaceUnset('unverify_success_credit_transaction');
			}
			if(Zend_Session::namespaceIsset('sess_draft_credit_insert')) {
				$this->view->success = 'Credit Note saved as draft';
				Zend_Session::namespaceUnset('sess_draft_credit_insert');
			}
			$sentid = base64_decode($this->_getParam('sentid'));
			if(isset($sentid) && !empty($sentid)) {
				$markStatus = $this->transaction->markCreditTransaction($sentid,1);
				if($markStatus) {
					$sessSuccess = new Zend_Session_Namespace('mark_success_credit_transaction');
					$sessSuccess->status = 1;
				}
					$this->_redirect('transaction/credit');
			}
			$delid = base64_decode($this->_getParam('delid'));
			if(isset($delid) && !empty($delid)) {
				$location1   = $this->_getParam('location'); 

				$financeSet1 = $this->_getParam('financial_year'); 

				$deleteStatus = $this->transaction->deleteCreditTransaction($delid,2);
				$auditLog	  = $this->settings->insertAuditLog(3,4,'Credit Note',$delid);
				if($deleteStatus) {
					$sessSuccess = new Zend_Session_Namespace('delete_success_credit_transaction');
					$sessSuccess->status = 1;
				}
					$this->_redirect('transaction/credit?location='.$location1.'&financial_year='.$financeSet1);
			}
			$verifyid  = base64_decode($this->_getParam('verifyid'));
			$status    = $this->_getParam('status');
			if(isset($verifyid) && !empty($verifyid) && isset($status) && !empty($status)) {
				$location2   = $this->_getParam('location'); 

				$financeSet2 = $this->_getParam('financial_year'); 
				
				$changeStatus = $this->transaction->changeCreditTransactionStatus($verifyid,$status);
				if($changeStatus) {
					if($status==1) {
						$accountEntry = $this->transaction->accountEntry($verifyid,4);
						$auditLog	  = $this->settings->insertAuditLog(6,4,'Credit Note',$verifyid);
						$sessSuccess = new Zend_Session_Namespace('verify_success_credit_transaction');
						$sessSuccess->status = 1;
					} else if($status==2) {
						$accountEntryExpired = $this->transaction->accountEntryExpired($verifyid,4);
						$auditLog	  = $this->settings->insertAuditLog(7,4,'Credit Note',$verifyid);
						$sessSuccess = new Zend_Session_Namespace('unverify_success_credit_transaction');
						$sessSuccess->status = 2;
					}
				}
					$this->_redirect('transaction/credit?location='.$location2.'&financial_year='.$financeSet2);
			}
			$getAccountArray            =  $this->accountData->getData(array('currencies'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->taxCode    	=  $this->transaction->getTax();
			$this->view->location       =  $this->settings->getLocation();
			$this->view->locations      =  $this->settings->getLocations();
			$this->view->finance        =  $this->settings->getFinanceYears();
			$this->view->result 		=  $this->transaction->getCreditTransaction($id='',$sort,$location,$financeSet);
			$this->view->creditExpense  =  $this->transaction->getCreditExpenseTransaction($id='',$sort,$location,$financeSet);
		}
	}

	public function addAction() {
		if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
			if($this->_request->isPost()) {
				$postArray  				= $this->getRequest()->getPost();
				$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
					$creditTransaction = $this->transaction->insertCreditTransaction($postArray,$cid,2);
					$auditId = $this->transaction->insertCreditAuditTransaction($postArray,$creditTransaction,2);
					$sendNotify		   = $this->sendMail($postArray['approval_for']);
					$auditLog	  = $this->settings->insertAuditLog(1,4,'Credit Note',$auditId);
				} else if(isset($postArray['approve_invoice']) && !empty($postArray['approve_credit'])) {
					//$postArray['approval_for'] = $logSession->id;
					$creditTransaction = $this->transaction->insertCreditTransaction($postArray,$cid,1);
					$auditId = $this->transaction->insertCreditAuditTransaction($postArray,$creditTransaction,1);
					$accountEntry = $this->transaction->accountEntry($creditTransaction,4);
					$auditLog	  = $this->settings->insertAuditLog(1,4,'Credit Note',$auditId);
					$auditLog	  = $this->settings->insertAuditLog(6,4,'Credit Note',$creditTransaction);
				} 
				if($creditTransaction) {
					$sessSuccess = new Zend_Session_Namespace('insert_success_credit');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/credit/');
				} else {
						$this->view->error = 'Credit note cannot be added. Kindly try again later';
				}
				//echo '<pre>'; print_r($postArray); echo '</pre>'; die();
			}
			if(Zend_Session::namespaceIsset('insert_success_credit')) {
				$this->view->success = 'Credit Note Added successfully';
				Zend_Session::namespaceUnset('insert_success_credit');
			}
			
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->creditNo    	=  $this->transaction->generateCreditNo();
			$this->view->customer 		=  $this->transaction->getCustomerDetails();
			$this->view->taxCode    	=  $this->transaction->getTax();
			$this->view->product 	    =  $this->settings->getProducts();
			$this->view->invoice 		=  $this->transaction->getInvoiceCreditTransaction();
			$this->view->invoiceCustom	=  $this->settings->getInvoiceCustomization();
			$this->view->creditSet 		=  1;
		}
	}

	public function copyInvoiceAction() {
		if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
			$id = $this->_getParam('id');
			$memo = $this->_getParam('memo');
			if($memo=='' || empty($memo) || $memo=='null') {
				$this->view->memo = '';
			} else {
				$this->view->memo  = base64_decode($this->_getParam('memo'));
			}
			$this->view->inv_id = $this->_getParam('id');
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/credit');
			} else {
				$this->view->invoice  =  $this->transaction->getInvoiceTransaction($id);
				if(!$this->view->invoice) {
					$this->_redirect('transaction/credit');
				} else {
					$this->view->invoiceProductList  =  $this->transaction->getInvoiceProductList($id);
					$this->view->creditProductList   =  $this->transaction->getCreditInvoiceProductList($id);
					if(!$this->view->invoiceProductList) {
						$this->_redirect('transaction/credit');
					} 
				}
			}	
			if($this->_request->isPost()) {
				$postArray  				= $this->getRequest()->getPost();
				$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
					$creditTransaction = $this->transaction->insertCreditTransaction($postArray,$cid,2);
					$auditId = $this->transaction->insertCreditAuditTransaction($postArray,$creditTransaction,2);
					$sendNotify		   = $this->sendMail($postArray['approval_for']);
					$auditLog	  = $this->settings->insertAuditLog(1,4,'Credit Note',$auditId);
				} else if(isset($postArray['approve_credit']) && !empty($postArray['approve_credit'])) {
					//$postArray['approval_for'] = $logSession->id;
					$creditTransaction = $this->transaction->insertCreditTransaction($postArray,$cid,1);
					$auditId = $this->transaction->insertCreditAuditTransaction($postArray,$creditTransaction,1);
					$accountEntry = $this->transaction->accountEntry($creditTransaction,4);
					$auditLog	  = $this->settings->insertAuditLog(1,4,'Credit Note',$auditId);
					$auditLog	  = $this->settings->insertAuditLog(6,4,'Credit Note',$creditTransaction);
				} 
				if($creditTransaction) {
					$sessSuccess = new Zend_Session_Namespace('insert_success_credit');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/credit/');
				} else {
						$this->view->error = 'Credit note cannot be added. Kindly try again later';
				}
				//echo '<pre>'; print_r($postArray); echo '</pre>'; die();
			}
			if(Zend_Session::namespaceIsset('insert_success_credit')) {
				$this->view->success = 'Credit Note Added successfully';
				Zend_Session::namespaceUnset('insert_success_credit');
			}

			$product = array();			
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray','supplyTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$supply 					= array();
			$purchase 					= array();
			//$this->view->supply         =  $getAccountArray['supplyTaxCodes'];
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->creditNo    	=  $this->transaction->generateCreditNo();
			$this->view->customer 		=  $this->transaction->getCustomerDetails();
			$this->iras 	    		=  $this->transaction->getIrasTax(2);
			foreach ($this->iras as $iras) {
				$supply[$iras['id']]['name']	    = $iras['name'];
				$supply[$iras['id']]['percentage']  = $iras['percentage'];
				$supply[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->supply 		= $supply;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(2);


			$this->iras2	    		=  $this->transaction->getIrasTax(1);
			foreach ($this->iras2 as $iras2) {
				$purchase[$iras2['id']]['name']	      = $iras2['name'];
				$purchase[$iras2['id']]['percentage']  = $iras2['percentage'];
				$purchase[$iras2['id']]['description'] = $iras2['description'];
			}
			$this->view->purchase 		=  $purchase;
			$this->view->taxCode2    	=  $this->transaction->getSalesTax(1);
			$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();


			$this->product 	    		=  $this->settings->getProducts();
			foreach ($this->view->invoiceProductList as $invProd) {
				foreach ($this->product as $prod) {
					if($invProd['product_description']==$prod['id']) {
						$product[$prod['id']]['id'] 		= $invProd['product_description'];
						$product[$prod['id']]['product_id'] = $invProd['product_id'];
						$product[$prod['id']]['unit_price'] = $invProd['product_description'];
						$product[$prod['id']]['name'] 		= $invProd['product_description'];
						$product[$prod['id']]['quantity'] 	= $invProd['quantity'];
						$product[$prod['id']]['name'] 		= $prod['name'];
					}
				}
			}
			$this->view->product 		=  $product;
			$this->view->invoices 		=  $this->transaction->getInvoiceCreditTransaction();
			$this->view->invoiceCustom	=  $this->settings->getInvoiceCustomization();
			$this->view->location       =  $this->settings->getLocation();
			$this->view->creditSet 		=  1;
			/*echo '<pre>'; print_r($this->view->creditProductList); echo '</pre>';
			echo '<pre>'; print_r($this->view->invoiceProductList); echo '</pre>';*/
		}
	}

	public function viewAction() {
		if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
			
			$id = base64_decode($this->_getParam('id'));
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/credit');
			} else {
				$this->view->credit  =  $this->transaction->getCreditTransaction($id);
				if(!$this->view->credit) {
					$this->_redirect('transaction/credit');
				} else {
					$this->view->creditProductList  =  $this->transaction->getCreditProductList($id);
					//$this->view->shipping 			=  $this->transaction->getParticularShippingDetails($this->view->credit[0]['fkshipping_address']);
					if(!$this->view->creditProductList) {
						$this->_redirect('transaction/invoice');
					} 
				}
			}	
			
			$getAccountArray            =  $this->accountData->getData(array('currencies','supplyTaxCodes','country'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->country     	=  $getAccountArray['country'];
			$supply 					= array();
			$purchase 					= array();
			//$this->view->supply         =  $getAccountArray['supplyTaxCodes'];
			$this->view->filepath    	=  $this->uploadPath.$cid;
			$this->view->approveUser	=  $this->settings->getApproveUsers();
			$this->view->company		=  $this->account->getCompany($cid);
			$this->iras 	    		=  $this->transaction->getIrasTax(2);
			foreach ($this->iras as $iras) {
				$supply[$iras['id']]['name']	    = $iras['name'];
				$supply[$iras['id']]['percentage']  = $iras['percentage'];
				$supply[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->supply 		= $supply;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(2);


			$this->iras2	    		=  $this->transaction->getIrasTax(1);
			foreach ($this->iras2 as $iras2) {
				$purchase[$iras2['id']]['name']	      = $iras2['name'];
				$purchase[$iras2['id']]['percentage']  = $iras2['percentage'];
				$purchase[$iras2['id']]['description'] = $iras2['description'];
			}
			$this->view->purchase 		=  $purchase;
			$this->view->taxCode2    	=  $this->transaction->getSalesTax(1);
			$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();


			$this->view->product 	    =  $this->settings->getProducts();
			$this->view->invoiceCustom	=  $this->settings->getInvoiceCustomization();
			$this->view->location       =  $this->settings->getLocation();
			$this->view->creditSet 		=  1;
		}
	}


	public function editAction() {
		if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			if(Zend_Session::namespaceIsset('update_success_credit')) {
				$this->view->success = 'Credit Note Updated successfully';
				Zend_Session::namespaceUnset('update_success_credit');
			}
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
			$id = base64_decode($this->_getParam('id'));
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/credit');
			} else {
				$this->view->credit  =  $this->transaction->getCreditTransaction($id);
				if(!$this->view->credit) {
					$this->_redirect('transaction/credit');
				} else {
					$this->view->creditProductList   =  $this->transaction->getCreditProductList($id);
					foreach ($this->view->credit as $cre) {
						$invId = $cre['fkinvoice_id'];
					}
					$this->view->invoiceProductList  =  $this->transaction->getInvoiceProductList($invId);
					$this->view->creditInvoiceProductList   =  $this->transaction->getCreditInvoiceProductList($invId);
					if(!$this->view->creditProductList) {
						$this->_redirect('transaction/credit');
					} 
				}
			}
			if($this->_request->isPost()) {
				$postArray  		   = $this->getRequest()->getPost();
				$postArray['date']	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
					$creditTransaction = $this->transaction->updateCreditTransaction($postArray,$id,2);
					$auditId = $this->transaction->insertCreditAuditTransaction($postArray,$id,2);
					$sendNotify		   = $this->sendMail($postArray['approval_for']);
					$auditLog	  = $this->settings->insertAuditLog(2,4,'Credit Note',$auditId);
				} else if(isset($postArray['approve_credit']) && !empty($postArray['approve_credit'])) {
					//$postArray['approval_for'] = $logSession->id;
					$creditTransaction = $this->transaction->updateCreditTransaction($postArray,$id,1);
					$auditId = $this->transaction->insertCreditAuditTransaction($postArray,$id,1);
					$accountEntry = $this->transaction->accountEntry($id,4);
					$auditLog	  = $this->settings->insertAuditLog(2,4,'Credit Note',$auditId);
					$auditLog	  = $this->settings->insertAuditLog(6,4,'Credit Note',$id);
				} 
				if($creditTransaction) {
					$sessSuccess = new Zend_Session_Namespace('update_success_credit');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/credit/edit/id/'.$this->_getParam('id'));
				} else {
						$this->view->error = 'Credit Note cannot be updated. Kindly try again later';
				}
			}
			$getAccountArray            =  $this->accountData->getData(array('currencies','supplyTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$supply 					= array();
			$purchase 					= array();
			//$this->view->supply         =  $getAccountArray['supplyTaxCodes'];
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->customer 		=  $this->transaction->getCustomerDetails();
			$this->iras 	    		=  $this->transaction->getIrasTax(2);
			foreach ($this->iras as $iras) {
				$supply[$iras['id']]['name']	    = $iras['name'];
				$supply[$iras['id']]['percentage']  = $iras['percentage'];
				$supply[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->supply 		= $supply;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(2);

			$this->iras2	    		=  $this->transaction->getIrasTax(1);
			foreach ($this->iras2 as $iras2) {
				$purchase[$iras2['id']]['name']	      = $iras2['name'];
				$purchase[$iras2['id']]['percentage']  = $iras2['percentage'];
				$purchase[$iras2['id']]['description'] = $iras2['description'];
			}
			$this->view->purchase 		=  $purchase;
			$this->view->taxCode2    	=  $this->transaction->getSalesTax(1);
			$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();


			$this->view->product 	    =  $this->settings->getProducts();
			$this->view->invoiceCustom	=  $this->settings->getInvoiceCustomization();
			$this->view->location       =  $this->settings->getLocation();
			$this->view->creditSet 		=  1;
			/*echo '<pre>'; print_r($this->view->creditInvoiceProductList); echo '</pre>';
			echo '<pre>'; print_r($this->view->invoiceProductList); echo '</pre>';*/
		}
	}

	public function copyAction() {
		if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
			$id = base64_decode($this->_getParam('id'));
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/credit');
			} else {
				$this->view->credit  =  $this->transaction->getCreditTransaction($id);
				if(!$this->view->credit) {
					$this->_redirect('transaction/credit');
				} else {
					$this->view->creditProductList  =  $this->transaction->getCreditProductList($id);
					foreach ($this->view->credit as $cre) {
						$invId = $cre['fkinvoice_id'];
					}
					$this->view->invoiceProductList  =  $this->transaction->getInvoiceProductList($invId);
					$this->view->creditInvoiceProductList   =  $this->transaction->getCreditInvoiceProductList($invId);
					if(!$this->view->creditProductList) {
						$this->_redirect('transaction/credit');
					} 
				}
			}	
			if($this->_request->isPost()) {
				$postArray  		   = $this->getRequest()->getPost();
				$postArray['date']	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
					$creditTransaction = $this->transaction->insertCreditTransaction($postArray,$cid,2);
					$auditId = $this->transaction->insertCreditAuditTransaction($postArray,$creditTransaction,2);
					$sendNotify		   = $this->sendMail($postArray['approval_for']);
					$auditLog	  = $this->settings->insertAuditLog(1,4,'Credit Note',$auditId);
				} else if(isset($postArray['approve_credit']) && !empty($postArray['approve_credit'])) {
					//$postArray['approval_for'] = $logSession->id;
					$creditTransaction = $this->transaction->insertCreditTransaction($postArray,$cid,1);
					$auditId = $this->transaction->insertCreditAuditTransaction($postArray,$creditTransaction,1);
					$accountEntry = $this->transaction->accountEntry($creditTransaction,4);
					$auditLog	  = $this->settings->insertAuditLog(1,4,'Credit Note',$auditId);
					$auditLog	  = $this->settings->insertAuditLog(6,4,'Credit Note',$creditTransaction);
				} 
				if($creditTransaction) {
					$sessSuccess = new Zend_Session_Namespace('insert_success_credit');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/credit');
				} else {
						$this->view->error = 'Credit Note cannot be added. Kindly try again later';
				}
			}
			$getAccountArray            =  $this->accountData->getData(array('currencies','supplyTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$supply 					= array();
			$purchase 					= array();
			//$this->view->supply         =  $getAccountArray['supplyTaxCodes'];
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->creditNo    	=  $this->transaction->generateCreditNo();
			$this->view->customer 		=  $this->transaction->getCustomerDetails();
			$this->iras 	    		=  $this->transaction->getIrasTax(2);
			foreach ($this->iras as $iras) {
				$supply[$iras['id']]['name']	    = $iras['name'];
				$supply[$iras['id']]['percentage']  = $iras['percentage'];
				$supply[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->supply 		= $supply;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(2);

			$this->iras2	    		=  $this->transaction->getIrasTax(1);
			foreach ($this->iras2 as $iras2) {
				$purchase[$iras2['id']]['name']	      = $iras2['name'];
				$purchase[$iras2['id']]['percentage']  = $iras2['percentage'];
				$purchase[$iras2['id']]['description'] = $iras2['description'];
			}
			$this->view->purchase 		=  $purchase;
			$this->view->taxCode2    	=  $this->transaction->getSalesTax(1);
			$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();

			$this->view->product 	    =  $this->settings->getProducts();
			$this->view->invoiceCustom	=  $this->settings->getInvoiceCustomization();
			$this->view->location       =  $this->settings->getLocation();
			$this->view->creditSet 		=  1;
		}
	}

	public function sendMail($userid) {
		if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}

			$id = $logSession->id;
				$userEmail = $this->transaction->getApproveUserEmail($userid);
				$user      = $this->transaction->getApproveUserEmail($id);
				$mail = new Zend_Mail();
				$bodyContent = 'Dear User, <br/> Credit Note has been created by user '.$user.' and is awaiting for your approval. <a href='.$this->view->sitePath."default/notification/transactions".'>Click here </a> to approve the transaction.';
				$subject 	 = 'Notification for Transaction Approval - Immediate';
				$config = array('ssl' => 'tls', 'port' => '587', 'auth' => 'login', 'username' => 'divagar.umm@gmail.com', 'password' => 'UMMdivagar');
				$transport = new Zend_Mail_Transport_Smtp('smtp.gmail.com', $config);
				$mail->addTo($userEmail, '');
				$mail->setFrom('Accounting', 'no-reply');
				$mail->setSubject($subject);
				$mail->setBodyHtml($bodyContent);
				$mail->send(/*$transport*/);
				return true;
		}
	}

	public function ajaxRefreshAction() {
		$this->_helper->getHelper('layout')->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$logSession = new Zend_Session_Namespace('sess_login');
		if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
		if($this->_request->isXmlHttpRequest()) {
			if ($this->_request->isPost()) {
				$ajaxVal = $this->getRequest()->getPost();
				if($ajaxVal['action']=='customerRefresh') {
					$this->customer 	=  $this->transaction->getCustomerDetails();
					if($this->customer) {
							echo '<select class="select2 form-control" name="customer" id="customer" onchange="return customerInvoiceSelect(this.value);">';
							echo '<option value="">Select</option>';
						foreach ($this->customer as $customer) {
							if($ajaxVal['id']==$customer['id'])
                                $customerSelect = 'selected';
                            else
                                $customerSelect = '';
							echo '<option value='.$customer['id'].' '.$customerSelect.'>'.$customer['customer_name'].'</option>';
						}
						echo '</select>';
					}
				} else if($ajaxVal['action']=='productRefresh') {
					$this->product 		=  $this->transaction->getProductDetails();
					if($this->product) {
						$jsonEncode = json_encode($this->product);
						echo $jsonEncode;
						/*echo '<select class="form-control" name="product_description_'.$ajaxVal['product'].'" id="product_description_'.$ajaxVal['product'].'" required onchange="return productSelect('.$ajaxVal['product'].',this.value);">';
						echo '<option value="">Select</option>';
						foreach ($this->product as $product) {
							$prod = $product['id']."_".$product['product_id']."_".$product['price'];
							if($ajaxVal['id']==$prod)
                                $productSelect = 'selected';
                            else
                                $productSelect = '';
							echo '  <option value='.$product['id']."_".$product['product_id']."_".$product['price'].' '.$productSelect.'>'.ucfirst($product['name']).'</option>';
						}
						echo '</select>';*/
					}
				}
			}
		}
	}

	public function ajaxCallAction() {
		$this->_helper->getHelper('layout')->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$logSession = new Zend_Session_Namespace('sess_login');
		if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
		if($this->_request->isXmlHttpRequest()) {
			if ($this->_request->isPost()) {
				$ajaxVal = $this->getRequest()->getPost();
				if($ajaxVal['action']=='save_draft_credit') {
					$ajaxVal['date'] 	 = date("Y-m-d",strtotime(trim($ajaxVal['date'])));
					if(isset($ajaxVal['customer']) && !empty($ajaxVal['customer'])) {
						$ajaxVal['customer'] = trim($ajaxVal['customer']);
					} else {
						$ajaxVal['customer'] = NULL;
					}
					if(isset($ajaxVal['invoice']) && !empty($ajaxVal['invoice'])) {
						$ajaxVal['invoice'] = trim($ajaxVal['invoice']);
					} else {
						$ajaxVal['invoice'] = NULL;
					}
					$creditTransaction = $this->transaction->insertCreditTransaction($ajaxVal,$cid,3);
					$auditId = $this->transaction->insertCreditAuditTransaction($ajaxVal,$creditTransaction,3);
					$auditLog	  = $this->settings->insertAuditLog(8,4,'Credit Note',$auditId);
					if($creditTransaction) {
						$sessDraft = new Zend_Session_Namespace('sess_draft_credit_insert');
						$sessDraft->status = 1;
						echo "success";
					} else {
						echo "Failure";
					}
				} else if($ajaxVal['action']=='update_draft_credit') {
					$ajaxVal['date'] 	 = date("Y-m-d",strtotime(trim($ajaxVal['date'])));
					if(isset($ajaxVal['customer']) && !empty($ajaxVal['customer'])) {
						$ajaxVal['customer'] = trim($ajaxVal['customer']);
					} else {
						$ajaxVal['customer'] = NULL;
					}
					$creditTransaction = $this->transaction->updateCreditTransaction($ajaxVal,$ajaxVal['credit_id'],3);
					$auditId = $this->transaction->insertCreditAuditTransaction($ajaxVal,$ajaxVal['credit_id'],3);
					$auditLog	  = $this->settings->insertAuditLog(8,4,'Credit Note',$auditId);
					if($creditTransaction) {
						$sessDraft = new Zend_Session_Namespace('sess_draft_credit_insert');
						$sessDraft->status = 1;
						echo "success";
					} else {
						echo "Failure";
					}
				} 
			}
		} 
	}

		
}

?>