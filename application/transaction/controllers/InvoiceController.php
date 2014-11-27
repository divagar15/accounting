<?php 
require_once "MPDF/mpdf.php";
class Transaction_InvoiceController extends Zend_Controller_Action {
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
		$this->account 	   = new Account();
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
			if($this->_request->isPost()) {
				$postArray  				= $this->getRequest()->getPost();
				$postArray['discount'] = 0;
				$postArray['ref_id']   = $postArray['invoice_id'];
				$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				if(isset($postArray['payment_discount']) && $postArray['payment_discount']==1 && isset($postArray['discount_payment_amount'])) {
					$postArray['discount'] = $postArray['discount_payment_amount'];
				}
				$addPayment = $this->transaction->addPayment($postArray,3);
				$auditId      = $this->transaction->addPaymentAudit($postArray,3);
				$accountEntry = $this->transaction->accountEntry($postArray['ref_id'],3);
				$auditLog	  = $this->settings->insertAuditLog(1,11,'Invoice',$auditId);
				if($addPayment) {
					$sessSuccess = new Zend_Session_Namespace('add_payment_success');
					$sessSuccess->status = 1;
				} else {
					$sessSuccess = new Zend_Session_Namespace('add_payment_success');
					$sessSuccess->status = 2;
				}
				$this->_redirect('transaction/invoice/');
			}
			if(Zend_Session::namespaceIsset('insert_success_invoice')) {
				$this->view->success = 'Invoice Added successfully';
				Zend_Session::namespaceUnset('insert_success_invoice');
			}
			if(Zend_Session::namespaceIsset('delete_success_invoice_transaction')) {
				$this->view->success = 'Invoice deleted successfully';
				Zend_Session::namespaceUnset('delete_success_invoice_transaction');
			}
			if(Zend_Session::namespaceIsset('mark_success_invoice_transaction')) {
				$this->view->success = 'Invoice marked successfully';
				Zend_Session::namespaceUnset('mark_success_invoice_transaction');
			}
			if(Zend_Session::namespaceIsset('add_payment_success')) {
				$sessCheck = new Zend_Session_Namespace('add_payment_success');
				if($sessCheck->status==1) {
					$this->view->success = 'Payment successfully added';
					Zend_Session::namespaceUnset('add_payment_success');
				} else if($sessCheck->status==2) {
					$this->view->error = 'Payment cannot be added. Kindly try again later';
					Zend_Session::namespaceUnset('add_payment_success');
				}
			}
			if(Zend_Session::namespaceIsset('verify_success_invoice_transaction')) {
				$this->view->success = 'Invoice verified successfully';
				Zend_Session::namespaceUnset('verify_success_invoice_transaction');
			}
			if(Zend_Session::namespaceIsset('unverify_success_invoice_transaction')) {
				$this->view->success = 'Invoice unverified successfully';
				Zend_Session::namespaceUnset('unverify_success_invoice_transaction');
			}
			if(Zend_Session::namespaceIsset('sess_draft_invoice_insert')) {
				$this->view->success = 'Invoice saved as draft';
				Zend_Session::namespaceUnset('sess_draft_invoice_insert');
			}
			$sentid = base64_decode($this->_getParam('sentid'));
			if(isset($sentid) && !empty($sentid)) {
				$markStatus = $this->transaction->markInvoiceTransaction($sentid,1);
				if($markStatus) {
					$sessSuccess = new Zend_Session_Namespace('mark_success_invoice_transaction');
					$sessSuccess->status = 1;
				}
					$this->_redirect('transaction/invoice');
			}
			$delid = base64_decode($this->_getParam('delid'));
			if(isset($delid) && !empty($delid)) {
				$location1   = $this->_getParam('location'); 

				$financeSet1 = $this->_getParam('financial_year'); 

				$deleteStatus = $this->transaction->deleteInvoiceTransaction($delid,2);
				$auditLog	  = $this->settings->insertAuditLog(3,3,'Invoice',$delid);
				if($deleteStatus) {
					$sessSuccess = new Zend_Session_Namespace('delete_success_invoice_transaction');
					$sessSuccess->status = 1;
				}
					$this->_redirect('transaction/invoice?location='.$location1.'&financial_year='.$financeSet1);
			}
			$verifyid  = base64_decode($this->_getParam('verifyid'));
			$status    = $this->_getParam('status');
			if(isset($verifyid) && !empty($verifyid) && isset($status) && !empty($status)) {
				$location2   = $this->_getParam('location'); 

				$financeSet2 = $this->_getParam('financial_year'); 
				
				$changeStatus = $this->transaction->changeInvoiceTransactionStatus($verifyid,$status);
				if($changeStatus) {
					if($status==1) {
						$accountEntry = $this->transaction->accountEntry($verifyid,3);
						$auditLog	  = $this->settings->insertAuditLog(6,3,'Invoice',$verifyid);
						$sessSuccess = new Zend_Session_Namespace('verify_success_invoice_transaction');
						$sessSuccess->status = 1;
					} else if($status==2) {
						$accountEntryExpired = $this->transaction->accountEntryExpired($verifyid,3);
						$auditLog	  = $this->settings->insertAuditLog(7,3,'Invoice',$verifyid);
						$sessSuccess = new Zend_Session_Namespace('unverify_success_invoice_transaction');
						$sessSuccess->status = 2;
					}
				}
					$this->_redirect('transaction/invoice?location='.$location2.'&financial_year='.$financeSet2);
			}
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray','payMethod'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->creditTerm     =  $getAccountArray['creditTermArray'];
			$this->view->payMethod      =  $getAccountArray['payMethod'];
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->cashAccount	=  $this->transaction->getCashAccount();
			$this->view->payAccount		=  $this->transaction->getPaymentIncomeAccount();
			$this->view->location       =  $this->settings->getLocation();
			$this->view->locations      =  $this->settings->getLocations();
			$this->view->finance        =  $this->settings->getFinanceYears();
			$this->view->vendor 		=  $this->transaction->getVendorDetails();
			//$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();
			/*$this->view->taxCode    	=  $this->transaction->getTax();*/
			$this->view->invoice 			   =  $this->transaction->getInvoiceTransaction($id='',$sort,$location,$financeSet);
			$this->view->invoiceExpense 	   =  $this->transaction->getInvoiceExpenseTransaction($id='',$sort,$location,$financeSet);
			$this->view->invoiceCredit  	   =  $this->transaction->getInvoiceCredit();
			$this->view->invoiceCreditExpense  =  $this->transaction->getInvoiceExpenseCredit();
			$this->view->payments 		=  $this->transaction->getPaymentDetails('',3);
			//echo '<pre>'; print_r($this->view->invoice); echo '</pre>';
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
			$this->currency_id = $this->_getParam('currency');
			if(isset($this->currency_id) && !empty($this->currency_id)) {
			    $this->view->currency_id = $this->_getParam('currency');
			}
			$this->customer_id = $this->_getParam('customer');
			if(isset($this->customer_id) && !empty($this->customer_id)) {
			    $this->view->customer_id = $this->_getParam('customer');
			    $this->view->shippings   = $this->transaction->getCustomerShippingDetails($this->view->customer_id);
			} 
			$this->date = $this->_getParam('date');
			$this->due  = $this->_getParam('due');
			if(isset($this->date) && !empty($this->date) && isset($this->due) && !empty($this->due)) {
				$this->view->date = $this->_getParam('date');
				$this->view->due  = $this->_getParam('due');
			}
			//$this->view->customer_id = base64_decode($this->_getParam('cid'));
			if($this->_request->isPost()) {
				$postArray  				= $this->getRequest()->getPost();
				$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				$postArray['due_date'] = date("Y-m-d",strtotime(trim($postArray['due_date'])));
				//echo '<pre>'; print_r($postArray); echo '</pre>'; die();
				if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
					$invoiceTransaction = $this->transaction->insertInvoiceTransaction($postArray,$cid,2);
					$auditId = $this->transaction->insertInvoiceAuditTransaction($postArray,$invoiceTransaction,2);
					$sendNotify		   = $this->sendMail($postArray['approval_for']);
					$auditLog	  = $this->settings->insertAuditLog(1,3,'Invoice',$auditId);
				} else if(isset($postArray['approve_invoice']) && !empty($postArray['approve_invoice'])) {
					//$postArray['approval_for'] = $logSession->id;
					$invoiceTransaction = $this->transaction->insertInvoiceTransaction($postArray,$cid,1);
					$auditId = $this->transaction->insertInvoiceAuditTransaction($postArray,$invoiceTransaction,1);
					$accountEntry = $this->transaction->accountEntry($invoiceTransaction,3);
					$auditLog	  = $this->settings->insertAuditLog(1,3,'Invoice',$auditId);
					$auditLog	  = $this->settings->insertAuditLog(6,3,'Invoice',$invoiceTransaction);
				} else if(isset($postArray['save_sent']) && !empty($postArray['save_sent'])) {
					$invoiceTransaction = $this->transaction->insertInvoiceTransaction($postArray,$cid,3);
				}
				if($invoiceTransaction) {
					$sessSuccess = new Zend_Session_Namespace('insert_success_invoice');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/invoice/');
				} else {
						$this->view->error = 'Invoice cannot be added. Kindly try again later';
				}
				//echo '<pre>'; print_r($postArray); echo '</pre>'; die();
			}

			if(Zend_Session::namespaceIsset('insert_success_invoice')) {
				$this->view->success = 'Invoice Added successfully';
				Zend_Session::namespaceUnset('insert_success_invoice');
			}
			
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray','payMethod','supplyTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->creditTerm     =  $getAccountArray['creditTermArray'];
			$this->view->payMethod      =  $getAccountArray['payMethod'];
			$supply 					= array();
			$purchase 					= array();
			//$this->view->supply         =  $getAccountArray['supplyTaxCodes'];
			$this->view->invoiceNo    	=  $this->transaction->generateInvoiceNo();
			$this->view->invoiceCustom	=  $this->settings->getInvoiceCustomization();
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->cashAccount	=  $this->transaction->getCashAccount();
			$this->view->payAccount		=  $this->transaction->getPaymentIncomeAccount();
			$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();
			$this->view->location       =  $this->settings->getLocation();
			$this->view->customer 		=  $this->transaction->getCustomerDetails();
			$this->view->shipping 		=  $this->transaction->getShippingDetails();
			if(isset($this->view->currency_id) && !empty($this->view->currency_id)) {
				$this->view->product 		=  $this->transaction->getCurrencyProductDetails($this->view->currency_id);
				$this->view->ajaxCurrency   =  $this->view->currency_id;
			} else if(isset($this->invoiceCustom[0]['default_currency']) && !empty($this->invoiceCustom[0]['default_currency'])) {
				$this->view->product 		=  $this->transaction->getCurrencyProductDetails($this->invoiceCustom[0]['default_currency']);
				$this->view->ajaxCurrency   =  $this->invoiceCustom[0]['default_currency'];
			} else {
				$this->view->product 		=  $this->transaction->getCurrencyProductDetails('SGD');
				$this->view->ajaxCurrency   =  'SGD';
			}
		//	$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();
			$this->iras 	    		=  $this->transaction->getIrasTax(2);
			foreach ($this->iras as $iras) {
				$supply[$iras['id']]['name']	    = $iras['name'];
				$supply[$iras['id']]['percentage']  = $iras['percentage'];
				$supply[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->supply 		=  $supply;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(2);

			$this->iras2	    		=  $this->transaction->getIrasTax(1);
			foreach ($this->iras2 as $iras2) {
				$purchase[$iras2['id']]['name']	      = $iras2['name'];
				$purchase[$iras2['id']]['percentage']  = $iras2['percentage'];
				$purchase[$iras2['id']]['description'] = $iras2['description'];
			}
			$this->view->purchase 		=  $purchase;
			$this->view->taxCode2    	=  $this->transaction->getSalesTax(1);
		//	$this->view->product 	    =  $this->settings->getProducts();
			$this->view->creditSet 		=  1;
			/*echo '<pre>'; print_r($this->view->purchase); echo '</pre>';
			echo '<pre>'; print_r($this->view->taxCode2); echo '</pre>';*/
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
			if($this->_request->isPost()) {
				$postArray  				= $this->getRequest()->getPost();
				$postArray['discount'] = 0;
				$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				if(isset($postArray['payment_discount']) && $postArray['payment_discount']==1 && isset($postArray['discount_amount'])) {
					$postArray['discount'] = $postArray['discount_amount'];
				}
				$updatePayment = $this->transaction->updatePayment($postArray,3);
				if($updatePayment) {
					$sessSuccess = new Zend_Session_Namespace('update_payment_success');
					$sessSuccess->status = 1;
				} else {
					$sessSuccess = new Zend_Session_Namespace('update_payment_success');
					$sessSuccess->status = 2;
				}
			}
			if(Zend_Session::namespaceIsset('update_payment_success')) {
				$sessCheck = new Zend_Session_Namespace('update_payment_success');
				if($sessCheck->status==1) {
					$this->view->success = 'Payment successfully updated';
					Zend_Session::namespaceUnset('update_payment_success');
				} else if($sessCheck->status==2) {
					$this->view->error = 'Payment cannot be updated. Kindly try again later';
					Zend_Session::namespaceUnset('update_payment_success');
				}
			}
			if(Zend_Session::namespaceIsset('delete_success_invoice_payment')) {
				$this->view->success = 'Invoice Payment Deleted successfully';
				Zend_Session::namespaceUnset('delete_success_invoice_payment');
			}
			$id = base64_decode($this->_getParam('id'));
			$this->view->decode_id = $this->_getParam('id');
			$this->view->inv_id = $id;
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/invoice');
			} else {
				$invoice_view = $this->transaction->getInvoiceTransaction($id);
				$this->view->invoice  =  $invoice_view;
				if(!$this->view->invoice) {
					$this->_redirect('transaction/invoice');
				} else {
					$invoiceProductList_view = $this->transaction->getInvoiceProductList($id);
					$invoicePayment_view = $this->transaction->getPaymentDetails($id,3);
					$invoiceCredit_view = $this->transaction->getInvoiceCredit($id);
					$getInvoiceExpenseCredit_view = $this->transaction->getInvoiceExpenseCredit($id);
					$shipping_view = $this->transaction->getParticularShippingDetails($this->view->invoice[0]['fkshipping_address']);
					$this->view->invoiceProductList  =  $invoiceProductList_view;
					$this->view->invoicePayment      =  $invoicePayment_view;
					$this->view->invoiceCredit  	 =  $invoiceCredit_view;
					$this->view->getInvoiceExpenseCredit  	 =  $getInvoiceExpenseCredit_view;
					$this->view->shipping 			 =  $shipping_view; 
					$compDetail         = $this->account->getCompany($cid);
					foreach ($compDetail as $comp) {
						$this->view->compGst =  $comp['company_gst']; 
					}
					if(!$this->view->invoiceProductList) {
						$this->_redirect('transaction/invoice');
					} 
				}
			}	
			$delid = base64_decode($this->_getParam('delid'));
			if(isset($delid) && !empty($delid)) {
				$deleteStatus = $this->transaction->deletePayment($delid);
				if($deleteStatus) {
					$sessSuccess = new Zend_Session_Namespace('delete_success_invoice_payment');
					$sessSuccess->status = 1;
				}
					$this->_redirect('transaction/invoice/view/id/'.$this->_getParam('id'));
			}
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray','payMethod','supplyTaxCodes','country'));
			$country_view = $getAccountArray['country'];
			$currencies_view = $getAccountArray['currencies'];
			$creditTerm_view = $getAccountArray['creditTermArray'];
			$payMethod_view = $getAccountArray['payMethod'];
			$this->view->country     	=  $country_view;
			$this->view->currencies     =  $currencies_view;
			$this->view->creditTerm     =  $creditTerm_view;
			$this->view->payMethod      =  $payMethod_view;
			//$this->view->supply         =  $getAccountArray['supplyTaxCodes'];
			$supply 					= array();
			$purchase 					= array();
			$filepath_view = $this->uploadPath.$cid;
			$company_view = $this->account->getCompany($cid);
			$approveUser_view = $this->settings->getApproveUsers();
			$cashAccount_view = $this->transaction->getCashAccount();
			$payAccount_view = $this->transaction->getPaymentIncomeAccount();
			$product_view = $this->transaction->getProductDetails();
			$location_view = $this->settings->getLocation();
			$this->view->filepath    	=  $filepath_view;
			$this->view->company		=  $company_view;
			$this->view->approveUser	=  $approveUser_view;
			$this->view->cashAccount	=  $cashAccount_view;
			$this->view->payAccount		=  $payAccount_view;
			/*$this->view->customer 		=  $this->transaction->getCustomerDetails();*/
			$this->view->product 		=  $product_view;
			$this->view->location       =  $location_view;
		//	$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();
			$this->iras 	    		=  $this->transaction->getIrasTax(2);
			foreach ($this->iras as $iras) {
				$supply[$iras['id']]['name']	    = $iras['name'];
				$supply[$iras['id']]['percentage']  = $iras['percentage'];
				$supply[$iras['id']]['description'] = $iras['description'];
			}
			$supply_view = $supply;
			$this->view->supply 		= $supply_view;
			$taxCode_view = $this->transaction->getSalesTax(2);
			$this->view->taxCode    	=  $taxCode_view;


			$this->iras2	    		=  $this->transaction->getIrasTax(1);
			foreach ($this->iras2 as $iras2) {
				$purchase[$iras2['id']]['name']	      = $iras2['name'];
				$purchase[$iras2['id']]['percentage']  = $iras2['percentage'];
				$purchase[$iras2['id']]['description'] = $iras2['description'];
			}
			$purchase_view              = $purchase;
			$taxCode2_view              = $this->transaction->getSalesTax(1);
			$expenseAccount_view        = $this->transaction->getExpenseAccount();
			$product_view               = $this->settings->getProducts();
			$invoiceCustom_view         = $this->settings->getInvoiceCustomization();
			$creditSet_view             = '1';			
			$this->view->purchase 		=  $purchase;
			$this->view->taxCode2    	=  $taxCode2_view;
			$this->view->expenseAccount	=  $expenseAccount_view;
			$this->view->product 	    =  $product_view;
			$this->view->invoiceCustom	=  $invoiceCustom_view;
			$this->view->creditSet 		=  $creditSet_view;
			if($this->_getParam('print') == 'pdf') { 
			    $productTitle = 'Product';
				if(isset($invoiceCustom_view[0]['default_product_title']) && $invoiceCustom_view[0]['default_product_title']==1) {
					 $productTitle = 'Product';
				} else if(isset($invoiceCustom_view['default_product_title']) && $invoiceCustom_view[0]['default_product_title']==2) {
					 $productTitle = 'Service';
				} else if(isset($invoiceCustom_view[0]['default_product_title']) && $invoiceCustom_view[0]['default_product_title']==3) {
					 $productTitle = 'Product / Service';
				}
				$html .='';

				$html = '';
				$html .= '<html><body>
							<div class="main-wrapper"><div class="main-container"><div class="container">
							<div id="container" class="container">
							<div id="invoice_template">
							<div class="row">';							
							
							if($invoiceCustom_view[0]['template']!=4) {	
							$html .='<div class="row" style="margin-top:-50px;">

									<div>';

									if(!empty($invoiceCustom_view[0]['company_logo'])) {
										$html .='<img src="'.$this->sitePath.$filepath_view."/".$invoiceCustom_view[0]['company_logo'].'" alt="'.$company_view[0]['company_name'].' Logo" title="'.$company_view[0]['company_name'].' Logo">';
									   }

									$html .='</div>
									
									<div class="col-md-3" style="min-height:150px; width:100%;margin-left:450px;margin-top:-100px;"> 
									  <div style="">';
									   
									$html .='</div>
											<div style="float:right;"><h4>From</h4>
										    <address>
											<strong>'.$company_view[0]['company_name'].'.</strong><br>
											'.$company_view[0]['block_no']." ". $company_view[0]['street_name']." ". $company_view[0]['city'].'<br>
											 '.$country_view[$company_view[0]['country']].' - '.$company_view[0]['zip_code'].'<br>
											<abbr title="Phone">P:</abbr> '.$company_view[0]['telephone'].' </address>
									   </div>
									</div>
									 <div class="col-md-3" style="min-height:150px;">
									 <div class="col-md-3">									 										
										<h4>Invoice To</h4>
										<address>
											<strong>'.$invoice_view[0]['customer_name'].'.</strong><br>';
										 
											  if($invoice_view[0]['fkshipping_address']==0) {
											
											$html .=$invoice_view[0]['address1'].'<br>
											        '.$country_view[$invoice_view[0]['country']]."-".$invoice_view[0]['postcode'].'<br>
													<abbr title="Phone">P:</abbr> '.$invoice_view[0]['office_number'].'';
											
											  } else {
											$html .= $shipping_view[0]['shipping_address1'].'<br>
											         '.$country_view[$shipping_view[0]['shipping_country']]."-".$shipping_view[0]['shipping_postcode'].'<br>
													 <abbr title="Phone">P:</abbr> '.$invoice_view[0]['office_number'].'';											
											  }
											
										$html  .='</address>
												</div>
										<div class="col-md-4" style="float:right !important;margin-left:440px; margin-top:-130px;">
											 <h4>INVOICE</h4> 
											<ul class="invoice-info">
												<li><label>Invoice ID</label> '.$invoice_view[0]['invoice_no'].'</li>
												<li><label>Issue Date</label> '. date("d-m-Y",strtotime($invoice_view[0]['date'])).'</li>
												<li><label>Due Date</label> '.date("d-m-Y",strtotime($invoice_view[0]['due_date'])).'('; 
												if(isset($creditTerm_view) && !empty($creditTerm_view)) {
														  foreach ($creditTerm_view as $key => $credit) {
															if($key==$invoice_view[0]['credit_term']) {
															  if($key==1) {
																  $html .= $credit;
															  } else if($key!=1) {
																  $html .= $credit." Days";
															  }
															}
														  }
													  }
											$html .=')</li>
												<li><label>Currency </label>';
												  if(isset($currencies_view) && !empty($currencies_view)) {
													foreach ($currencies_view as $key => $currency) {
													  if($key==$invoice_view[0]['transaction_currency']) {
														  $html .= $currency." - ".$key;
														}
													}
												  }
										$html .='</li>
											</ul>
										</div>
									   </div>
									</div>
									<div class="row">
									</div>';	


								
								  } else if($invoiceCustom_view[0]['template']==21) {

								$html .='<div class="row">
									<div style="text-align:center;"><h4><strong>Test</strong></h4></div>
									<div class="col-md-3"> <div>';
									   if(empty($invoiceCustom_view[0]['company_logo'])) {
									   }
									$html .='</div><div style="float:right; padding-bottom:50px;"><h4>From</h4>
										<address>
											<strong>'.$company_view[0]['company_name'].'.</strong><br>
											'.$company_view[0]['block_no']." ". $company_view[0]['street_name']." ". $company_view[0]['city'].'<br>
											 '.$country_view[$company_view[0]['country']].'<br>
											<abbr title="Phone">P:</abbr> '.$company_view[0]['telephone'].' </address>
									</div></div>

									 <div class="col-md-3">
									 <br/><br/>
										<h4>Invoice To</h4>
										<address>
											<strong>'.$invoice_view[0]['customer_name'].'.</strong><br>';
										 
											  if($invoice_view[0]['fkshipping_address']==0) {
											
											$html .=$invoice_view[0]['address1'].", ".$invoice_view[0]['city'].'<br>
											        '.$invoice_view[0]['state'].", ".$country_view[$invoice_view[0]['country']]."-".$invoice_view[0]['postcode'].'<br>
													<abbr title="Phone">P:</abbr> '.$invoice_view[0]['office_number'].'';
											
											  } else {
											$html .= $shipping_view[0]['shipping_address1'].", ".$shipping_view[0]['shipping_city'].'<br>
											         '.$shipping_view[0]['shipping_state'].", ".$country_view[$shipping_view[0]['shipping_country']]."-".$shipping_view[0]['shipping_postcode'].'<br>
													 <abbr title="Phone">P:</abbr> '.$invoice_view[0]['office_number'].'';											
											  }
											
										$html  .='</address>
												</div>
										<div class="col-md-4">
											 <h4>INVOICE</h4> <br/>
											<ul class="invoice-info">
												<li><label>Invoice ID</label> '.$invoice_view[0]['invoice_no'].'</li>
												<li><label>Issue Date</label> '. date("d-m-Y",strtotime($invoice_view[0]['date'])).'</li>
												<li><label>Due Date</label> '.date("d-m-Y",strtotime($invoice_view[0]['due_date'])).'('; 
												if(isset($creditTerm_view) && !empty($creditTerm_view)) {
														  foreach ($creditTerm_view as $key => $credit) {
															if($key==$invoice_view[0]['credit_term']) {
															  if($key==1) {
																  $html .= $credit;
															  } else if($key!=1) {
																  $html .= $credit." Days";
															  }
															}
														  }
													  }
											$html .=')</li>
												<li><label>Currency</label>';
												  if(isset($currencies_view) && !empty($currencies_view)) {
													foreach ($currencies_view as $key => $currency) {
													  if($key==$invoice_view[0]['transaction_currency']) {
														  $html .= $currency." - ".$key;
														}
													}
												  }
										$html .='</li>
											</ul>
										</div>
									</div>
									<div class="row">
									</div>';									
									  } else if($invoiceCustom_view[0]['template']==31) {
									  
								    $html .='<div class="row">
										<div class="col-md-2">';
										if(!empty($invoiceCustom_view[0]['company_logo'])) {
											$html .='<img src="'.$this->sitePath.$filepath_view."/".$invoiceCustom_view[0]['company_logo'].'" alt="'.$company_view[0]['company_name'].' Logo" title="'.$company_view[0]['company_name'].' Logo">';
										}
									$html .='</div>
										<div class="col-md-5">											
										</div>
										<div class="col-md-3">
											<h4>INVOICE</h4>
											<h4>From</h4>
											<address>
												<strong'.$company_view[0]['company_name'].'. </strong><br>
												'.$company_view[0]['block_no']." ". $company_view[0]['street_name']." ". $company_view[0]['city'].'<br>
												'.$country_view[$company_view[0]['country']].'<br>
												<abbr title="Phone">P:</abbr> '.$company_view[0]['telephone'].' </address>
										</div>
									</div>
									<div class="row">
										<div class="col-md-7">
											<ul class="invoice-info">
												<li><label>Invoice ID</label> '.$invoice_view[0]['invoice_no'].'</li>
												<li><label>Issue Date</label> '.date("d-m-Y",strtotime($invoice_view[0]['date'])).'</li>
												<li><label>Due Date</label> '.date("d-m-Y",strtotime($invoice_view[0]['due_date'])).' (';												
														  if(isset($this->creditTerm) && !empty($this->creditTerm)) {
															  foreach ($this->creditTerm as $key => $credit) {
																if($key==$this->invoice[0]['credit_term']) {
																  if($key==1) {
																	  $html .= $credit;
																  } else if($key!=1) {
																	 $html .= $credit." Days";
																  }
																}
															  }
														  }
												$html .=')</li>
												<li><label>Currency</label>';
														  if(isset($currencies_view) && !empty($currencies_view)) {
															foreach ($currencies_view as $key => $currency) {
															  if($key==$invoice_view[0]['transaction_currency']) {
																  $html .= $currency." - ".$key;
																}
															}
														  }
												$html .='</li>
											</ul>
										</div>
										<div class="col-md-3">
											<h4>Invoice To</h4>
											<address>
												<strong>'.$invoice_view[0]['customer_name'].'.</strong><br>';
												
												  if($invoice_view[0]['fkshipping_address']==0) {
												
												$html .=$invoice_view[0]['address1'].", ".$invoice_view[0]['city'].'<br>
												        '.$invoice_view[0]['state'].", ".$country_view[$invoice_view[0]['country']]."-".$invoice_view[0]['postcode'].'<br>
													<abbr title="Phone">P:</abbr> '.$invoice_view[0]['office_number'].'';
												
												  } else {
												
												$html .=$shipping_view[0]['shipping_address1'].", ".$shipping_view[0]['shipping_city'].'<br>
												    '.$shipping_view[0]['shipping_state'].", ".$country_view[$shipping_view[0]['shipping_country']]."-".$shipping_view[0]['shipping_postcode'].'<br>
													<abbr title="Phone">P:</abbr> '.$invoice_view[0]['office_number'].'';
												  }
												
										$html .='</address>
										</div>
									</div>';
									
				                      }  else if($invoiceCustom_view[0]['template']==4) { 

				                   $html .='<div class="row">
				                        <div class="img-logo" style="text-align:left; font-size:30px;">';
				                          if(!empty($invoiceCustom_view[0]['company_logo'])) {
											$html .='<img src="'.$this->sitePath.$filepath_view."/".$invoiceCustom_view[0]['company_logo'].'" alt="'.$company_view[0]['company_name'].' Logo" title="'.$company_view[0]['company_name'].' Logo">';
											}
				                         $html .='&nbsp;&nbsp;&nbsp;&nbsp;<span style=" font-size:24px; font-weight:bold;">'.$company_view[0]['company_name'];
				                         $html .='</span></div>
				                           <p class="pdf-reg" style="float:right;  margin-left:490px; "><strong>GST Reg:No.: </strong>		
				                           '.$this->view->compGst.'</p><br/><br/>
				                        <div style="width:100%;">    
				                            <div class="col-md-3" style="float:left; width:100%;">                           
				                                
				                                <address>
				                                    <strong> Finance Department </strong><br>';
				                               $html .=$company_view[0]['block_no']." ". $company_view[0]['street_name']." ". $company_view[0]['city']."<br>";
				                               $html .=$country_view[$company_view[0]['country']].' - '.$company_view[0]['zip_code'].'<br>';
				                               $html .='<abbr title="Phone">Tel :</abbr> '.$company_view[0]['telephone'].' </address>
				                            </div>
				                            <div class="col-md-3s" style=" margin-left:8px;"> <h4><strong>Tax Invoice</strong></h4></div>
				                          <div class="col-md-3" style="float:left;width:90%;">   
				                          
				                             <div class="col-md-3s" style="text-align:left; float:left;">
				                             <br/>                          
				                                <address>
				                                    <strong>'.$invoice_view[0]['customer_name'].'. </strong><br>';
				                                     
				                                      if($this->invoice[0]['fkshipping_address']==0) {
				                                   
				                                     $html .=$invoice_view[0]['address1'].'<br>';
				                                      $html .=$country_view[$invoice_view[0]['country']].'-'.$invoice_view[0]['postcode'].'<br>
				                                    <abbr title="Phone">Tel :</abbr> '.$invoice_view[0]['office_number'];
				                                    
				                                      } else {
				                                   
				                                    $html .=$shipping_view[0]['shipping_address1'].'<br>';		
				                                    $html .=$country_view[$shipping_view[0]['shipping_country']].'-'.$shipping_view[0]['shipping_postcode'].'<br>
				                                    <abbr title="Phone">Tel :</abbr> '.$invoice_view[0]['office_number'];
				                                    
				                                      }
				                                    
				                                 $html .='</address>
				                            </div>

				                            <div class="col-md-3s" style="float:right !important; margin-left:490px; margin-top:-90px;">                                 
				                                <ul class="invoice-info">
				                                    <li><label>Invoice ID</label> '.$invoice_view[0]['invoice_no'].'</li>
				                                    <li><label>Invoice Date</label> '.date("d-m-Y",strtotime($invoice_view[0]['date'])).'</li>
				                                    <li><label>Due Date</label> '.date("d-m-Y",strtotime($invoice_view[0]['due_date'])).' 
				                                   </li>
				                                    
				                                </ul>
				                            </div>
				                          </div>
				                          </div>
				                    </div>
				                    <div class="row">                       
				                       
				                    </div>';

				                    }
				                    


					if($invoiceCustom_view[0]['template'] !=4 ) {			
							
					$html .='<div class="row">
							<div class="col-md-9">
						    <table class="table table-striped table-well block-head responsive">
							<thead>
								<tr style="border:none">
									<th class="invoice-id"> 
										'. $productTitle.' ID
									</th>
									<th class="invoice-type">
										'.$productTitle.' Description
									</th>
									<th class="invoice-qty">
										Quantity
									</th>
									<th class="invoice-unit">
										Unit Price
									</th>
									<th class="invoice-unit">
										Discount
									</th>
									<th class="invoice-type">
										Tax Code
									</th>
									<th class="invoice-unit" style="text-align:center;">
										GST
									</th>
									<th class="invoice-amount" style="text-align:center;">
										Amount
									</th>
								</tr>
								<tr>
                                      <td colspan="8" style=" border-top:1px solid #000;"></td>
                                    </tr>
							</thead>
							<tbody>';								 
								$j=1;
								$total_gst = 0.00;
								$sub_total = 0.00;
								foreach ($invoiceProductList_view  as $invoiceProduct) {
								  if($invoiceProduct['row_type']==1) {
									$net_amount = $invoiceProduct['quantity'] * $invoiceProduct['unit_price'] - $invoiceProduct['discount_amount'];
									$total_gst += $invoiceProduct['gst_amount'];
									$sub_total += $net_amount;
								         
								$html .= '<tr>
									<td class="invoice-type" style="border:none">
										'.$invoiceProduct['product_id'].'
									</td>
									<td class="invoice-type" style="border:none">
										';
										   foreach ($product_view as $product) {
											 if($invoiceProduct['product_description']==$product['id']) {
												 $html .= ucfirst($product['name']);
											  }
											}
										 
									$html .='</td>
									<td class="invoice-qty" style="border:none; text-align:right;">
										'.$invoiceProduct['quantity'].'
									</td>
									<td class="invoice-unit" style="border:none;text-align:right;">
										'. number_format($invoiceProduct['unit_price'],2,'.',','). '
									</td>
									 <td class="invoice-unit" style="border:none;text-align:right;">
										'. number_format($invoiceProduct['discount_amount'],2,'.',',').'
									</td>
									<td class="invoice-type" style="border:none">';
										
													if(isset($taxCode_view) && !empty($taxCode_view) && $invoiceProduct['fktax_id']!=0) { 
														foreach ($taxCode_view as $tax) {
															if($invoiceProduct['fktax_id']==$tax['id']) {
															  foreach ($supply_view as $key => $supply) {
																  if($tax['tax_code']==$key) {
																	$html .=$supply['name']." - ".$tax['tax_percentage']." %";
																  }
															  }
															}
														}
													} else {
													 $html .= "NA - Not Applicable";
													}
												
												
								$html .='</td>
									<td class="invoice-unit" style="border:none;text-align:right;">
										'. number_format($invoiceProduct['gst_amount'],2,'.',',').'
									</td>
									<td class="invoice-amount" style="border:none;text-align:right;">
										<span id="net_amount_'.$j.'">'. number_format($net_amount,2,'.',',').' </span>
									</td>
								</tr>';
								
									$j++;
									  }
								  }
								$html .='<tr>
										 <th colspan="8" style="border:none">&nbsp;&nbsp;&nbsp;</th>
									   </tr>
									  <tr>
										 <th colspan="8" style="border:none">Less Expenses:</th>
									   </tr>';

											$i=1;
											$esub_gst   = 0.00;
											$esub_total = 0.00;
											foreach ($invoiceProductList_view  as $invoiceProduct) {
											  if($invoiceProduct['row_type']==2) {
												$enet_amount = $invoiceProduct['quantity'] * $invoiceProduct['unit_price'];
												$esub_gst   += $invoiceProduct['gst_amount'];
												$esub_total += $enet_amount;
									          
									$html .='<tr>
										<td class="invoice-type"  style="border:none">
											'.$invoiceProduct['product_id'].'';
											   
									$html .='</td>
											<td class="invoice-type"  style="border:none">
												'.$invoiceProduct['product_description'].'											
											</td>
											 <td class="invoice-type" style="text-align:right;">
												'.$invoiceProduct['quantity'].'										 
											</td>
											<td class="invoice-unit" style="text-align:right;">
												'.number_format($invoiceProduct['unit_price'],2,'.',',').'
											</td>
											<td class="invoice-unit" style="border:none">
												
											</td>
											<td class="invoice-type" style="border:none">';											
													if(isset($taxCode2_view) && !empty($taxCode2_view) && $invoiceProduct['fktax_id']!=0) {
														foreach ($taxCode2_view as $tax) {
															if($invoiceProduct['fktax_id']==$tax['id']) {
															  foreach ($purchase_view as $key => $purchase) {
																  if($tax['tax_code']==$key) {
																	$html .= $purchase['name']." - ".$tax['tax_percentage']." %";
																  }
															  }
															}
														}
													} else {
													 $html .= "NA - Not Applicable";
													}
													
								   $html .='</td>
											<td class="invoice-unit" style="border:none;text-align:right;">
												'. number_format($invoiceProduct['gst_amount'],2,'.',',').'
											</td>
											<td class="invoice-amount" style="border:none;text-align:right;">
												<span id="enet_amount_'.$i.'">'.number_format($enet_amount,2,'.',',').'</span>
											</td>
										</tr>';									
										$i++;
										  }
									  }										
                                      $sub_total  = $sub_total-$esub_total;
                                      $total_gst  = $total_gst-$esub_gst;                                   

                                    $html .='<tr class="invoice-cal">
											<td colspan="7" style="text-align:right;">
												<span><strong>Sub Total</strong></span><br />
												<span><strong>Total GST</strong></span><br />
												<span><strong>Grand Total</strong></span><br />
												<span><strong>Exchange Rate</strong></span><br />
												<span><strong>Grand Total SGD</strong></span><br />
											</td>
											<td style="text-align:right;">
												<span style="text-align:right;"><strong id="sub_total">'.number_format($sub_total,2,'.',',').'</strong></span>
												<span><strong id="total_gst">'. number_format($total_gst,2,'.',',').'</strong></span>
												<span><strong id="grand_total">';
												 $over_all = $sub_total+$total_gst; 
													  $html .= number_format($over_all,2,'.',',');
												 $html .='</strong></span>
												 <span><strong>'.$invoice_view[0]['exchange_rate'].'</strong></span><br/>
												 <span><strong id="grand_total_sgd">';
												
													  $calculate_all = $invoice_view[0]['exchange_rate']*$over_all;
													  $html .= number_format($calculate_all,2,'.',','); 
													  if($invoice_view[0]['transaction_currency']!='SGD') {
														$totAmoumt = $calculate_all;
													  } else {
														$totAmoumt = $over_all;
													  }
												  
												 $html .='</strong></span>
											</td>
										</tr>';
								
							$html .= '</tbody>
									</table>						
									</div>
									</div>';
							$html .='<div class="row">
										 <p>';
				             	 if($invoiceCustom_view[0]['payment_methods']) {
				                          $html .= $invoiceCustom_view[0]['payment_methods'];
				                       }
				              $html .='</p>
									 </div>';
									
							
									$html .='</div></div></body></html>';

								} else {

									$html .='<div class="row">
											<div class="col-md-9">
										    <table class="" style="width:100%;" cellspacing="3" cellpadding="3">
											<thead>
												<tr style="width:60%; border:1px solid #000;">													
													<th class="invoice-type">
														Description
													</th>
													</th>
													<th class="invoice-type" style="width:20%;">
														Amount
													</th>
													<th class="invoice-type" style="width:20%;">
														Tax Code
													</th>
												</tr>
											</thead>
											<tbody>';								 
												$j=1;
												$total_gst = 0.00;
												$sub_total = 0.00;
												foreach ($invoiceProductList_view  as $invoiceProduct) {
												  if($invoiceProduct['row_type']==1) {
													$net_amount = $invoiceProduct['quantity'] * $invoiceProduct['unit_price'] - $invoiceProduct['discount_amount'];
													$total_gst += $invoiceProduct['gst_amount'];
													$sub_total += $net_amount;
												         
												$html .= '<tr style=" border-left:1px solid #000; border-right:1px solid #000;">
													
													<td class="invoice-type" style="border:none; width:60%;">
														';
														   foreach ($product_view as $product) {
															 if($invoiceProduct['product_description']==$product['id']) {
																 $html .= ucfirst($product['name']);
															  }
															}
														 
													$html .='</td>	
													         <td class="invoice-amount" style="border:none; width:20%;">
																<span id="net_amount_'.$j.'">'. number_format($net_amount,2,'.',',').' </span>
															</td>												
													       <td class="invoice-type" style="border:none; text-align:center; width:20%;">';
														
																	if(isset($taxCode_view) && !empty($taxCode_view) && $invoiceProduct['fktax_id']!=0) { 
																		foreach ($taxCode_view as $tax) {
																			if($invoiceProduct['fktax_id']==$tax['id']) {
																			  foreach ($supply_view as $key => $supply) {
																				  if($tax['tax_code']==$key) {
																					$html .=$supply['name']." - ".$tax['tax_percentage']." %";
																				  }
																			  }
																			}
																		}
																	} else {
																	 $html .= "NA - Not Applicable";
																	}
																
																
												$html .='</td>												
													
												</tr>';
												
													$j++;
													  }
												  }
												$html .='';

															$i=1;
															$esub_gst   = 0.00;
															$esub_total = 0.00;
															foreach ($invoiceProductList_view  as $invoiceProduct) {
															  if($invoiceProduct['row_type']==2) {
																$enet_amount = $invoiceProduct['quantity'] * $invoiceProduct['unit_price'];
																$esub_gst   += $invoiceProduct['gst_amount'];
																$esub_total += $enet_amount;
													          
													$html .='<tr style=" border-left:1px solid #000; border-right:1px solid #000;">';
															   
															 
													$html .='
															<td class="invoice-type"  style="border:none; width:60%;">
																<strong>Less : </strong>'.$invoiceProduct['product_description'].'											
															</td>
															
															<td class="invoice-amount" style="border:none; width:20%;">
																<span id="enet_amount_'.$i.'">('.number_format($enet_amount,2,'.',',').')</span>
															</td>
															<td class="invoice-type" style="border:none; text-align:center; width:20%;">';											
																	if(isset($taxCode2_view) && !empty($taxCode2_view) && $invoiceProduct['fktax_id']!=0) {
																		foreach ($taxCode2_view as $tax) {
																			if($invoiceProduct['fktax_id']==$tax['id']) {
																			  foreach ($purchase_view as $key => $purchase) {
																				  if($tax['tax_code']==$key) {
																					$html .= $purchase['name']." - ".$tax['tax_percentage']." %";
																				  }
																			  }
																			}
																		}
																	} else {
																	 $html .= "NA - Not Applicable";
																	}
																	
												   $html .='</td>
															
														</tr>';									
														$i++;
														  }
													  }										
				                                      $sub_total  = $sub_total-$esub_total;
				                                      $total_gst  = $total_gst-$esub_gst;                                   

				                                    $html .='<tr class="invoice-cal" style="border-top:1px solid #000;">
															<td style="float:right; text-align:right; width:60%;border-top:1px solid #000;">';
				                                           
				                                            
				                                           
														$html .='
																<span><strong>Total GST</strong></span><br />
																<span><strong>Total Inc GST</strong></span><br />
																
															</td>
															<td style="float:right; text-align:right; width:20%;border-top:1px solid #000;">
																<span><strong id="total_gsts">'. number_format($total_gst,2,'.',',').'</strong></span><br/>
																<span><strong id="grand_totals">';
																 $over_all = $sub_total+$total_gst; 
																	  //$html .= number_format($over_all,2,'.',',');
																 $html .='</strong></span>
																 <span><strong id="grand_total_sgds">';
																
																	  $calculate_all = $invoice_view[0]['exchange_rate']*$over_all;
																	  
																	  if($invoice_view[0]['transaction_currency']!='SGD') {
																		$totAmoumt = $calculate_all;
																	  } else {
																		$totAmoumt = $over_all;
																	  }

																	  $html .= number_format($totAmoumt,2,'.',','); 
																  
																 $html .='</strong></span><br/><span><strong>';	

																 	$paid_amount = 0.00;          
                                             if(isset($invoicePayment_view) && !empty($invoicePayment_view)) {                                
                                            foreach ($invoicePayment_view  as $payment) {
                                                $paid_amount += $payment['payment_amount']+$payment['discount_amount'];
                                              }
                                            }

					                                                 $total_credit = 0.00;               
					                                                 if(isset($invoiceCredit_view) && !empty($invoiceCredit_view)) {                           
					                                                foreach ($invoiceCredit_view  as $invCre) {
					                                                   $total_amount   = 0.00;
					                                                   $total_amount   = $invCre['amount'] + $invCre['tax_amount'];

					                                                   if(isset($getInvoiceExpenseCredit_view) && !empty($getInvoiceExpenseCredit_view)) {
					                                                      foreach ($getInvoiceExpenseCredit_view  as $invCreExp) {
					                                                        if($invCre['id']==$invCreExp['id']) {
					                                                          $totExpAmount  = 0.00;
					                                                          $totExpAmount  = $invCreExp['amount'] + $invCreExp['tax_amount'];
					                                                          $total_amount -= $totExpAmount;
					                                                        }
					                                                      }
					                                                   }
					                                                 }
					                                                   if($invCre['transaction_currency']!='SGD') {
					                                                      $converted_amount = $invCre['exchange_rate']*$total_amount;
					                                                   } else {
					                                                      $converted_amount = $total_amount;
					                                                   }
					                                                   $total_credit  += $converted_amount;
					                                                 }
  
													$html .='</td><td style=" width:20%;border-top:1px solid #000;"></td>
														</tr>';
												
											$html .= '</tbody>
													</table>						
													</div>
													</div>';
													
											$html .='</div></div><div style="margin-bottom:0px; bottom:0px; position:absolute;">';
												   
							                        if($invoiceCustom_view[0]['payment_methods']) {
							                          $html .= $invoiceCustom_view[0]['payment_methods'];
							                       } 

							                       $html .= '<br/> <strong>Payment Terms : </strong>';

							                       if(isset($creditTerm_view) && !empty($creditTerm_view)) {
				                                              foreach ($creditTerm_view as $key => $credit) {
				                                                if($key==$invoice_view[0]['credit_term']) {
				                                                  if($key==1) {
				                                                      $html .= $credit;
				                                                  } else if($key!=1) {
				                                                      $html .= $credit." Days";
				                                                  }
				                                                }
				                                              }
				                                           } 
							                $html .='</div></div> <br/>  
							                    </div></div></div></body></html>';

								}		
				error_reporting(0);
				$mpdf=new mPDF('c','A4','','',32,25,27,25,16,13); 
				$mpdf->SetTitle('Web Accounting Software');		
				$mpdf->SetDisplayMode('fullpage');
				$mpdf->list_indent_first_level = 0;
				$stylesheet1 = file_get_contents('http://accounting.pinnone.com/public/css/bootstrap.css');
				$stylesheet4 = file_get_contents('http://accounting.pinnone.com/public/css/responsive-tables.css');
				$stylesheet5 = file_get_contents('http://accounting.pinnone.com/public/css/styles.css');	
				//$stylesheet6 = file_get_contents('http://192.168.1.8/accounting_phase2/public/css/style.css');	
				$stylesheet7 = file_get_contents('http://accounting.pinnone.com/public/css/bootstrap-reset.css');
				$mpdf->WriteHTML($stylesheet1,1); 
				$mpdf->WriteHTML($stylesheet4,1);
				$mpdf->WriteHTML($stylesheet5,1);		
				//$mpdf->WriteHTML($stylesheet6,1);	
				$mpdf->WriteHTML($stylesheet7,1);
				$mpdf->WriteHTML(utf8_encode($html),2);
				//echo "string"; die();
				$mpdf->Output('invoice.pdf','I');
				exit;						
				
			}
			//print_r($this->view->shipping);
		}
	}
	

	public function editAction() {
		if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			if(Zend_Session::namespaceIsset('update_success_invoice')) {
				$this->view->success = 'Invoice Updated successfully';
				Zend_Session::namespaceUnset('update_success_invoice');
			}
			if(Zend_Session::namespaceIsset('update_payment_success')) {
				$sessCheck = new Zend_Session_Namespace('update_payment_success');
				if($sessCheck->status==1) {
					$this->view->success = 'Payment successfully updated';
					Zend_Session::namespaceUnset('update_payment_success');
				} else if($sessCheck->status==2) {
					$this->view->error = 'Payment cannot be updated. Kindly try again later';
					Zend_Session::namespaceUnset('update_payment_success');
				}
			}
			if(Zend_Session::namespaceIsset('delete_success_invoice_payment')) {
				$this->view->success = 'Invoice Payment Deleted successfully';
				Zend_Session::namespaceUnset('delete_success_invoice_payment');
			}
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
			$id = base64_decode($this->_getParam('id'));
			$this->view->inv_id = $id;
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/invoice');
			} else {
				$this->view->invoice  =  $this->transaction->getInvoiceTransaction($id);
				if(!$this->view->invoice) {
					$this->_redirect('transaction/invoice');
				} else {
					$this->view->invoiceProductList  =  $this->transaction->getInvoiceProductList($id);
					$this->view->invoicePayment =  $this->transaction->getPaymentDetails($id,3);
					//print_r($this->view->invoicePayment);
					$this->view->invoiceCredit  =  $this->transaction->getInvoiceCredit($id);
					$this->view->getInvoiceExpenseCredit  	 =  $this->transaction->getInvoiceExpenseCredit($id);
					if(!$this->view->invoiceProductList) {
						$this->_redirect('transaction/invoice');
					} 
				}
			}	
			if($this->_request->isPost()) {
				$postArray  		   = $this->getRequest()->getPost();
				if(isset($postArray['action']) && $postArray['action']=='add_payment') {
					$postArray['discount'] = 0;
					$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['pay_date'])));
					if(isset($postArray['payment_discount']) && $postArray['payment_discount']==1 && isset($postArray['discount_amount'])) {
						$postArray['discount'] = $postArray['discount_amount'];
					}
					$updatePayment = $this->transaction->updatePayment($postArray,3);
					$auditId      = $this->transaction->addPaymentAudit($postArray,3);
					$accountEntry  = $this->transaction->accountEntry($id,3);
					$auditLog	   = $this->settings->insertAuditLog(2,11,'Invoice',$auditId);
					if($updatePayment) {
						$sessSuccess = new Zend_Session_Namespace('update_payment_success');
						$sessSuccess->status = 1;
						$this->_redirect('transaction/invoice/edit/id/'.$this->_getParam('id'));
					} else {
						$sessSuccess = new Zend_Session_Namespace('update_payment_success');
						$sessSuccess->status = 2;
						$this->_redirect('transaction/invoice/edit/id/'.$this->_getParam('id'));
					}
				} else {
				$postArray['date']	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				$postArray['due_date'] = date("Y-m-d",strtotime(trim($postArray['due_date'])));
				if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
					$invoiceTransaction = $this->transaction->updateInvoiceTransaction($postArray,$id,2);
					$auditId = $this->transaction->insertInvoiceAuditTransaction($postArray,$id,2);
					$sendNotify		   = $this->sendMail($postArray['approval_for']);
					$auditLog	   = $this->settings->insertAuditLog(2,3,'Invoice',$auditId);
				} else if(isset($postArray['approve_invoice']) && !empty($postArray['approve_invoice'])) {
					//$postArray['approval_for'] = $logSession->id;
					$invoiceTransaction = $this->transaction->updateInvoiceTransaction($postArray,$id,1);
					$auditId = $this->transaction->insertInvoiceAuditTransaction($postArray,$id,1);
					$accountEntry  = $this->transaction->accountEntry($id,3);
					$auditLog	   = $this->settings->insertAuditLog(2,3,'Invoice',$auditId);
					$auditLog	   = $this->settings->insertAuditLog(6,3,'Invoice',$id);
				} 
				if($invoiceTransaction) {
					$sessSuccess = new Zend_Session_Namespace('update_success_invoice');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/invoice/edit/id/'.$this->_getParam('id'));
				} else {
					$this->view->error = 'Invoice cannot be updated. Kindly try again later';
				}
			}
			}
			$delid = base64_decode($this->_getParam('delid'));
			$payid = base64_decode($this->_getParam('payid'));
			if(isset($delid) && !empty($delid)) {
				$deleteStatus = $this->transaction->deletePayment($delid,$id,3,$payid);
				$auditLog	   = $this->settings->insertAuditLog(3,11,'Invoice',$id);
				if($deleteStatus) {
					$sessSuccess = new Zend_Session_Namespace('delete_success_invoice_payment');
					$sessSuccess->status = 1;
				}
					$this->_redirect('transaction/invoice/edit/id/'.$this->_getParam('id'));
			}
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray','payMethod','supplyTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->creditTerm     =  $getAccountArray['creditTermArray'];
			$this->view->payMethod      =  $getAccountArray['payMethod'];
			$supply 					= array();
			$purchase 					= array();
			//$this->view->supply         =  $getAccountArray['supplyTaxCodes'];
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->cashAccount	=  $this->transaction->getCashAccount();
			$this->view->payAccount		=  $this->transaction->getPaymentIncomeAccount();
			$this->view->customer 		=  $this->transaction->getCustomerDetails();
			$this->view->shipping 		=  $this->transaction->getShippingDetails();
			$this->view->product 		=  $this->transaction->getProductDetails();
			$this->view->location       =  $this->settings->getLocation();
		//	$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();
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
			$this->view->creditSet 		=  1;
		}
	}

	public function copyAction() {
		if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			if(Zend_Session::namespaceIsset('insert_success_invoice')) {
				$this->view->success = 'Invoice Added successfully';
				Zend_Session::namespaceUnset('insert_success_invoice');
			}
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
			$id = base64_decode($this->_getParam('id'));
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/invoice');
			} else {
				$this->view->invoice  =  $this->transaction->getInvoiceTransaction($id);
				if(!$this->view->invoice) {
					$this->_redirect('transaction/invoice');
				} else {
					$this->view->invoiceProductList  =  $this->transaction->getInvoiceProductList($id);
					if(!$this->view->invoiceProductList) {
						$this->_redirect('transaction/invoice');
					} 
				}
			}	
			if($this->_request->isPost()) {
				$postArray  		   = $this->getRequest()->getPost();
				$postArray['date']	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				$postArray['due_date'] = date("Y-m-d",strtotime(trim($postArray['due_date'])));
				if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
					$invoiceTransaction = $this->transaction->insertInvoiceTransaction($postArray,$cid,2);
					$auditId = $this->transaction->insertInvoiceAuditTransaction($postArray,$invoiceTransaction,2);
					$sendNotify		   = $this->sendMail($postArray['approval_for']);
					$auditLog	   = $this->settings->insertAuditLog(1,3,'Invoice',$auditId);
				} else if(isset($postArray['approve_invoice']) && !empty($postArray['approve_invoice'])) {
					//$postArray['approval_for'] = $logSession->id;
					$invoiceTransaction = $this->transaction->insertInvoiceTransaction($postArray,$cid,1);
					$auditId = $this->transaction->insertInvoiceAuditTransaction($postArray,$invoiceTransaction,1);
					$accountEntry  = $this->transaction->accountEntry($invoiceTransaction,3);
					$auditLog	   = $this->settings->insertAuditLog(1,3,'Invoice',$auditId);
					$auditLog	   = $this->settings->insertAuditLog(6,3,'Invoice',$invoiceTransaction);
				} 
				if($invoiceTransaction) {
					$sessSuccess = new Zend_Session_Namespace('insert_success_invoice');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/invoice');
				} else {
						$this->view->error = 'Invoice cannot be added. Kindly try again later';
				}
			}
			$getAccountArray            =  $this->accountData->getData(array('payMethod','currencies','creditTermArray','supplyTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->creditTerm     =  $getAccountArray['creditTermArray'];
			$this->view->payMethod      =  $getAccountArray['payMethod'];
			$supply 					= array();
			$purchase 					= array();
			//$this->view->supply         =  $getAccountArray['supplyTaxCodes'];
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->invoiceNo    	=  $this->transaction->generateInvoiceNo();
			$this->view->cashAccount	=  $this->transaction->getCashAccount();
			$this->view->payAccount		=  $this->transaction->getPaymentIncomeAccount();
			$this->view->customer 		=  $this->transaction->getCustomerDetails();
			$this->view->shipping 		=  $this->transaction->getShippingDetails();
			$this->view->product 		=  $this->transaction->getProductDetails();
			$this->view->location       =  $this->settings->getLocation();
		//	$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();
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
				$bodyContent = 'Dear User, <br/> Invoice Transaction has been created by user '.$user.' and is awaiting for your approval. <a href='.$this->view->sitePath."default/notification/transactions".'>Click here </a> to approve the transaction.';
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
						//	echo '<select class="select2 form-control" name="customer" id="customer" onchange="return shippingAddress(this.value);">';
							echo '<option value="">Select</option>';
						foreach ($this->customer as $customer) {
							$coa = $customer['coa_link'].",".$customer['other_coa_link'];
							if($ajaxVal['id']==$customer['id'])
                                $customerSelect = 'selected';
                            else
                                $customerSelect = '';
							echo '<option value='.$customer['id'].' '.$customerSelect.' data-coa='.$coa.'>'.$customer['customer_name'].'</option>';
						}
					//	echo '</select>';
					}
				} else if($ajaxVal['action']=='productRefresh') {
					$this->product 		=  $this->transaction->getCurrencyProductDetails($ajaxVal['cur_id']);
					if($this->product) {
						$jsonEncode = json_encode($this->product);
						echo $jsonEncode;
						/*echo '<select class="form-control" name="product_description_'.$ajaxVal['product'].'" id="product_description_'.$ajaxVal['product'].'" required  onchange="return productSelect('.$ajaxVal['product'].',this.value);">';
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
				} else if($ajaxVal['action']=='getPayAccount') {
					$this->cashAccount	=  $this->transaction->getCashAccount();
					$this->payAccount	=  $this->transaction->getPaymentIncomeAccount($ajaxVal['coa']);
					$opt1 = 0;
					$opt2 = 0;
					if($this->cashAccount || $this->payAccount) {
						//echo '<select class="form-control" name="payment_account" id="payment_account" onchange="triggerPayment();">';
						echo '<option value="">Select</option>';
						echo '<optgroup label="Cash and Cash Equivalents">';
						foreach ($this->cashAccount as $cash) {
							$pays = $cash['id']."_".$cash['level2']."_".$cash['account_type'];
							echo '<option value='.$cash['id']."_".$cash['level2']."_".$cash['account_type'].'>'.ucfirst($cash['account_name']).'</option>';
						}
						echo '</optgroup>';
						foreach ($this->payAccount as $pay) {
							$pays = $pay['id']."_".$pay['level2']."_".$pay['account_type'];
							if($pay['level2']==4 && $opt1!=1) {
								echo '<optgroup label="Trade Receivables">';
								$opt1=1;
							}
							if($pay['level2']==5 && $opt2!=1) {
								echo '<optgroup label="Other Receivables">';
								$opt2=1;
							}
							echo '<option value='.$pay['id']."_".$pay['level2']."_".$pay['account_type'].'>'.ucfirst($pay['account_name']).'</option>';
						}
						//echo '</select>';
					}
				} else if($ajaxVal['action']=='getPayAccount_update') {
					$this->cashAccount	=  $this->transaction->getCashAccount();
					$this->payAccount	=  $this->transaction->getPaymentIncomeAccount($ajaxVal['coa']);
					$opt1 = 0;
					$opt2 = 0;
					if($this->cashAccount || $this->payAccount) {
						//echo '<select class="form-control" name="payment_account" id="payment_account" onchange="triggerPayment();">';
						//echo '<option value="">Select</option>';
						echo '<optgroup label="Cash and Cash Equivalents">';
						foreach ($this->cashAccount as $cash) {
							$pays = $cash['id']."_".$cash['level2']."_".$cash['account_type'];
							if($ajaxVal['payId']==$cash['id']) 
								$paySelect = 'selected';
							else
								$paySelect = '';
							echo '<option value='.$cash['id']."_".$cash['level2']."_".$cash['account_type'].' '.$paySelect.'>'.ucfirst($cash['account_name']).'</option>';
						}
						echo '</optgroup>';
						foreach ($this->payAccount as $pay) {
							$pays = $pay['id']."_".$pay['level2']."_".$pay['account_type'];
							if($pay['level2']==4 && $opt1!=1) {
								echo '<optgroup label="Trade Receivables">';
								$opt1=1;
							}
							if($pay['level2']==5 && $opt2!=1) {
								echo '<optgroup label="Other Receivables">';
								$opt2=1;
							}
							if($ajaxVal['payId']==$pay['id']) 
								$paySelect = 'selected';
							else
								$paySelect = '';
							echo '<option value='.$pay['id']."_".$pay['level2']."_".$pay['account_type'].' '.$paySelect.'>'.ucfirst($pay['account_name']).'</option>';
						}
						//echo '</select>';
					}
				} else if($ajaxVal['action']=='shippingDetails') {
					$this->shipping 	=  $this->transaction->getCustomerShippingDetails($ajaxVal['cust_id']);
					if(isset($this->shipping)) {
						//	echo '<select class="select2 form-control" name="customer" id="customer" onchange="return shippingAddress(this.value);">';
							echo '<option value="0">Default Shipping Address</option>';
							$i = 1;
						foreach ($this->shipping as $shipping) {
							echo '<option value='.$shipping['id'].' >Shipping Address '.$i.'</option>';
							$i++;
						}
					//	echo '</select>';
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
				if($ajaxVal['action']=='save_draft_invoice') {
					$ajaxVal['date'] 	 = date("Y-m-d",strtotime(trim($ajaxVal['date'])));
					$ajaxVal['due_date'] = date("Y-m-d",strtotime(trim($ajaxVal['due_date'])));
					if(isset($ajaxVal['customer']) && !empty($ajaxVal['customer'])) {
						$ajaxVal['customer'] = trim($ajaxVal['customer']);
					} else {
						$ajaxVal['customer'] = NULL;
					}
					$invoiceTransaction = $this->transaction->insertInvoiceTransaction($ajaxVal,$cid,3);
					$auditId = $this->transaction->insertInvoiceAuditTransaction($ajaxVal,$invoiceTransaction,3);
					$auditLog	   = $this->settings->insertAuditLog(8,3,'Invoice',$auditId);
					if($invoiceTransaction) {
						$sessDraft = new Zend_Session_Namespace('sess_draft_invoice_insert');
						$sessDraft->status = 1;
						echo "success";
					} else {
						echo "Failure";
					}
				} else if($ajaxVal['action']=='update_draft_invoice') {
					$ajaxVal['date'] 	 = date("Y-m-d",strtotime(trim($ajaxVal['date'])));
					$ajaxVal['due_date'] = date("Y-m-d",strtotime(trim($ajaxVal['due_date'])));
					if(isset($ajaxVal['customer']) && !empty($ajaxVal['customer'])) {
						$ajaxVal['customer'] = trim($ajaxVal['customer']);
					} else {
						$ajaxVal['customer'] = NULL;
					}
					$invoiceTransaction = $this->transaction->updateInvoiceTransaction($ajaxVal,$ajaxVal['invoice_id'],3);
					$auditId = $this->transaction->insertInvoiceAuditTransaction($ajaxVal,$ajaxVal['invoice_id'],3);
					$auditLog	   = $this->settings->insertAuditLog(8,3,'Invoice',$auditId);
					if($invoiceTransaction) {
						$sessDraft = new Zend_Session_Namespace('sess_draft_invoice_insert');
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