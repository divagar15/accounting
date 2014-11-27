<?php 
require_once "Account/Uploader.php";
class Transaction_ExpenseController extends Zend_Controller_Action {
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
			$sort   = $this->_getParam('sort');
			if(isset($sort) && !empty($sort) && ($sort==1 || $sort==2)) {
				$sort   = $this->_getParam('sort');
			} else {
				$sort = '';
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

			$this->view->sort = $sort;
			$this->view->filepath    =  $this->uploadPath.$cid."/receipts/";
			$this->view->nextId 	 =  $this->transaction->getNextExpenseTransaction();
			if($this->_request->isPost()) {
				$postArray  				= $this->getRequest()->getPost();
				if(isset($postArray['add_payment']) && !empty($postArray['add_payment'])) {
					$postArray['discount'] = 0;
					$postArray['ref_id']   = $postArray['expense_id'];
					$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
					if(isset($postArray['payment_discount']) && $postArray['payment_discount']==1 && isset($postArray['discount_payment_amount'])) {
						$postArray['discount'] = $postArray['discount_payment_amount'];
					}
					$addPayment = $this->transaction->addPayment($postArray,2);
					$auditId      = $this->transaction->addPaymentAudit($postArray,2);
					$accountEntry = $this->transaction->accountEntry($postArray['ref_id'],2);
					$auditLog	  = $this->settings->insertAuditLog(1,11,'Expense',$auditId);
					if($addPayment) {
						$sessSuccess = new Zend_Session_Namespace('add_payment_success');
						$sessSuccess->status = 1;
					} else {
						$sessSuccess = new Zend_Session_Namespace('add_payment_success');
						$sessSuccess->status = 2;
					}
					$this->_redirect('transaction/expense/');
				} else {
					$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
					$postArray['due_date'] = date("Y-m-d",strtotime(trim($postArray['due_date'])));

					/*$adapter    =  new Zend_File_Transfer_Adapter_Http();
					$fileInfo 	=  $adapter->getFileInfo('file'); 
					if(isset($fileInfo['file']['name']) && ($fileInfo['file']['name'] != '')) {
						$adapter->addValidator('Count', false, array('min' =>1, 'max' => 2))
						        ->addValidator('Size',false,array('max'=>2024000),'file')
								->addValidator('Extension',false,'pdf,jpg,doc,docx,png','file');
						$adapter->setDestination("..".$this->view->filepath,'file');
						$fileInfo 	         	  =   $adapter->getFileInfo('file');
						$fileArray		  		  =   explode('.',$fileInfo['file']['name']);
						$postArray['extension']   =   $fileArray['1'];
						$renameFile 		  	  =   trim($this->view->nextId."_".rand(10,10000)."_".$this->view->nextId.".".$fileArray['1']);
						$postArray['receipt_id']  =   $renameFile;
						$adapter->addFilter('Rename',"..".$this->view->filepath.$renameFile);
							if ($adapter->isValid('file') && $adapter->receive('file')) {
								$postArray['receipt_id'] =   $renameFile;
							} else {
								$postArray['receipt_id'] =   '';
							}
					} else {
						$postArray['receipt_id'] =   '';
					}*/
					//$payment_account = explode("_", $postArray['payment_account']);
					//$postArray['pay_account'] = $payment_account[0];
					if(isset($postArray['approve_expense']) && !empty($postArray['approve_expense'])) {
						//$postArray['approval_for'] = $logSession->id;
						$expenseTransaction = $this->transaction->insertExpenseTransaction($postArray,$cid,1);
						$auditId = $this->transaction->insertExpenseAuditTransaction($postArray,$expenseTransaction,1);
						$accountEntry = $this->transaction->accountEntry($expenseTransaction,2);
						$auditLog	  = $this->settings->insertAuditLog(1,2,'Expense',$auditId);
						$auditLog	  = $this->settings->insertAuditLog(6,2,'Expense',$expenseTransaction);
						$fixedexpense  =  $this->transaction->getFixedExpenseTransaction($expenseTransaction);
						if($fixedexpense) {
							$sessFixed = new Zend_Session_Namespace('fixed_asset');
							$sessFixed->id = $expenseTransaction;
						}
					} else if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
						$expenseTransaction = $this->transaction->insertExpenseTransaction($postArray,$cid,2);
						$auditId = $this->transaction->insertExpenseAuditTransaction($postArray,$expenseTransaction,2);
						$sendNotify		   = $this->sendMail($postArray['approval_for']);
						$auditLog	  = $this->settings->insertAuditLog(1,2,'Expense',$auditId);
					}
					if($expenseTransaction) {
						$sessSuccess = new Zend_Session_Namespace('insert_success_expense');
						$sessSuccess->status = 1;
						$this->_redirect('transaction/expense/');
					} else {
						$this->view->error = 'Expense Transaction cannot be added. Kindly try again later';
					}
				}
				//echo '<pre>'; print_r($postArray); echo '</pre>'; die();
			}
			if(Zend_Session::namespaceIsset('insert_success_expense')) {
				$this->view->success = 'Expense Transaction Added successfully';
				Zend_Session::namespaceUnset('insert_success_expense');
			}
			if(Zend_Session::namespaceIsset('delete_success_expense_transaction')) {
				$this->view->success = 'Transaction deleted successfully';
				Zend_Session::namespaceUnset('delete_success_expense_transaction');
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
			if(Zend_Session::namespaceIsset('verify_success_expense_transaction')) {
				$this->view->success = 'Transaction verified successfully';
				Zend_Session::namespaceUnset('verify_success_expense_transaction');
			}
			if(Zend_Session::namespaceIsset('unverify_success_expense_transaction')) {
				$this->view->success = 'Transaction unverified successfully';
				Zend_Session::namespaceUnset('unverify_success_expense_transaction');
			}

			if(Zend_Session::namespaceIsset('sess_draft_expense_insert')) {
				$this->view->success = 'Expense Transaction saved as draft';
				Zend_Session::namespaceUnset('sess_draft_expense_insert');
			}
			$delid = base64_decode($this->_getParam('delid'));
			if(isset($delid) && !empty($delid)) {
				$location1   = $this->_getParam('location'); 

				$financeSet1 = $this->_getParam('financial_year'); 

				$deleteStatus = $this->transaction->deleteExpenseTransaction($delid,2);
				$auditLog	  = $this->settings->insertAuditLog(3,2,'Expense',$delid);
				if($deleteStatus) {
					$sessSuccess = new Zend_Session_Namespace('delete_success_expense_transaction');
					$sessSuccess->status = 1;
				}
					$this->_redirect('transaction/expense?location='.$location1.'&financial_year='.$financeSet1);
			}
			$verifyid  = base64_decode($this->_getParam('verifyid'));
			$status    = $this->_getParam('status');
			if(isset($verifyid) && !empty($verifyid) && isset($status) && !empty($status)) {
				$location2   = $this->_getParam('location'); 

				$financeSet2 = $this->_getParam('financial_year'); 
				
				$changeStatus = $this->transaction->changeExpenseTransactionStatus($verifyid,$status);
				if($changeStatus) {
					if($status==1) {
						$accountEntry = $this->transaction->accountEntry($verifyid,2);
						$auditLog	  = $this->settings->insertAuditLog(6,2,'Expense',$verifyid);
						$fixedexpense  =  $this->transaction->getFixedExpenseTransaction($verifyid);
						if($fixedexpense) {
							$sessFixed = new Zend_Session_Namespace('fixed_asset');
							$sessFixed->id = $verifyid;
						}
						$sessSuccess = new Zend_Session_Namespace('verify_success_expense_transaction');
						$sessSuccess->status = 1;
					} else if($status==2) {
						$accountEntryExpired = $this->transaction->accountEntryExpired($verifyid,2);
						$auditLog	  = $this->settings->insertAuditLog(7,2,'Expense',$verifyid);
						$sessSuccess = new Zend_Session_Namespace('unverify_success_expense_transaction');
						$sessSuccess->status = 2;
					}
				}
					$this->_redirect('transaction/expense?location='.$location2.'&financial_year='.$financeSet2);
			}
			$maximum = array();
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray','payMethod','purchaseTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->creditTerm     =  $getAccountArray['creditTermArray'];
			$this->view->payMethod      =  $getAccountArray['payMethod'];
			//$this->view->purchase       =  $getAccountArray['purchaseTaxCodes'];
			$purchase 					= array();
			$this->view->cashAccount	=  $this->transaction->getCashAccount();
			$this->view->payAccount		=  $this->transaction->getPaymentEXpenseAccount();
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->vendor 		=  $this->transaction->getVendorDetails();
			$this->view->location       =  $this->settings->getLocation();
			$this->view->locations      =  $this->settings->getLocations();
			$this->view->finance        =  $this->settings->getFinanceYears();
			//$this->view->receipts 		=  $this->business->getReceipts('',2);
			$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();
			$this->iras 	    		=  $this->transaction->getIrasTax(1);
			foreach ($this->iras as $iras) {
				$purchase[$iras['id']]['name']	    = $iras['name'];
				$purchase[$iras['id']]['percentage']  = $iras['percentage'];
				$purchase[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->purchase 		= $purchase;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(1);
			$this->view->creditSet 		=  2;
			$this->view->maxExpense  	=  $this->transaction->getMaxExpenseTransaction();
			$this->view->result 		=  $this->transaction->getExpenseTransaction($id='',$sort,$location,$financeSet);
			$this->view->payments 		=  $this->transaction->getPaymentDetails('',2);
			$this->view->fixedExpense   =  $this->transaction->getFixedExpenseTransaction();
			$this->view->fixedAsset     =  $this->transaction->getFixedTransaction();
			foreach ($this->view->maxExpense as $max) {
				if(!array_key_exists($max['fkexpense_id'], $maximum)) {
					$maximum[$max['fkexpense_id']]['product_description'] = $max['product_description'];
					$maximum[$max['fkexpense_id']]['account_name'] 		  = $max['account_name'];
				}
			}
			$this->view->maximumExpense = $maximum;
			//echo '<pre>'; print_r($maximum); echo '</pre>';
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
			$this->view->filepath    	=  $this->uploadPath.$cid."/receipts/";
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray','payMethod','purchaseTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->creditTerm     =  $getAccountArray['creditTermArray'];
			$this->view->payMethod      =  $getAccountArray['payMethod'];
			$purchase 					= array();
			//$this->view->purchase       =  $getAccountArray['purchaseTaxCodes'];
			$this->view->approveUser	=  $this->settings->getApproveUsers();
			$this->view->payAccount		=  $this->transaction->getPaymentAccount();
			$this->view->vendor 		=  $this->transaction->getVendorDetails();
			$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();
			$this->view->location       =  $this->settings->getLocation();
		//	$this->view->receipts 		=  $this->business->getReceipts('',2);
			$this->iras 	    		=  $this->transaction->getIrasTax(1);
			foreach ($this->iras as $iras) {
				$purchase[$iras['id']]['name']	    = $iras['name'];
				$purchase[$iras['id']]['percentage']  = $iras['percentage'];
				$purchase[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->purchase 		= $purchase;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(1);
			if($this->_request->isPost()) {
				$postArray  				= $this->getRequest()->getPost();
				$postArray['discount'] = 0;
				$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				if(isset($postArray['payment_discount']) && $postArray['payment_discount']==1 && isset($postArray['discount_amount'])) {
					$postArray['discount'] = $postArray['discount_amount'];
				}
				$updatePayment = $this->transaction->updatePayment($postArray,2);
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
			if(Zend_Session::namespaceIsset('delete_success_expense_payment')) {
				$this->view->success = 'Expense Payment Deleted successfully';
				Zend_Session::namespaceUnset('delete_success_expense_payment');
			}
			$id = base64_decode($this->_getParam('id'));
			$this->view->exp_id = $id;
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/expense');
			} else {
				$this->view->expense  =  $this->transaction->getExpenseTransaction($id);
				if(!$this->view->expense) {
					$this->_redirect('transaction/expense');
				} else {
					$this->view->expenseList    =  $this->transaction->getExpenseTransactionList($id);
					$this->view->expensePayment =  $this->transaction->getPaymentDetails($id,2);
					if(!$this->view->expense) {
					$this->_redirect('transaction/expense');
					} 
				}
				//echo '<pre>'; print_r($this->view->expensePayment); echo '</pre>';
			}
			$delid = base64_decode($this->_getParam('delid'));
			if(isset($delid) && !empty($delid)) {
				$deleteStatus = $this->transaction->deletePayment($delid);
				if($deleteStatus) {
					$sessSuccess = new Zend_Session_Namespace('delete_success_expense_payment');
					$sessSuccess->status = 1;
				}
					$this->_redirect('transaction/expense/view/id/'.$this->_getParam('id'));
			}			
		}
	}


	public function editAction() {
		if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			if(Zend_Session::namespaceIsset('update_success_expense')) {
				$this->view->success = 'Expense Transaction Updated successfully';
				Zend_Session::namespaceUnset('update_success_expense');
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
			if(Zend_Session::namespaceIsset('delete_success_expense_payment')) {
				$this->view->success = 'Expense Payment Deleted successfully';
				Zend_Session::namespaceUnset('delete_success_expense_payment');
			}
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
			$this->view->filepath    =  $this->uploadPath.$cid."/receipts/";
			$id = base64_decode($this->_getParam('id'));
			$this->view->exp_id = $id;
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/expense');
			} else {
				$this->view->expense  =  $this->transaction->getExpenseTransaction($id);
				if(!$this->view->expense) {
					$this->_redirect('transaction/expense');
				} else {
					$this->view->expenseList  =  $this->transaction->getExpenseTransactionList($id);
					$this->view->expensePayment =  $this->transaction->getPaymentDetails($id,2);
					if(!$this->view->expense) {
					$this->_redirect('transaction/expense');
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
					$updatePayment = $this->transaction->updatePayment($postArray,2);
					$auditId      = $this->transaction->addPaymentAudit($postArray,2);
					$accountEntry  = $this->transaction->accountEntry($id,2);
					$auditLog	  = $this->settings->insertAuditLog(2,11,'Expense',$auditId);
					if($updatePayment) {
						$sessSuccess = new Zend_Session_Namespace('update_payment_success');
						$sessSuccess->status = 1;
						$this->_redirect('transaction/expense/edit/id/'.$this->_getParam('id'));
					} else {
						$sessSuccess = new Zend_Session_Namespace('update_payment_success');
						$sessSuccess->status = 2;
						$this->_redirect('transaction/expense/edit/id/'.$this->_getParam('id'));
					}
				} else {
				$postArray['date']	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				$postArray['due_date'] = date("Y-m-d",strtotime(trim($postArray['due_date'])));


				/*$adapter    =  new Zend_File_Transfer_Adapter_Http();
				$fileInfo 	=  $adapter->getFileInfo('file'); 
				if(isset($fileInfo['file']['name']) && ($fileInfo['file']['name'] != '')) {
					$adapter->addValidator('Count', false, array('min' =>1, 'max' => 2))
					        ->addValidator('Size',false,array('max'=>2024000),'file')
							->addValidator('Extension',false,'pdf,jpg,doc,docx,png','file');
					$adapter->setDestination("..".$this->view->filepath,'file');
					$fileInfo 	         	  =   $adapter->getFileInfo('file');
					$fileArray		  		  =   explode('.',$fileInfo['file']['name']);
					$postArray['extension']   =   $fileArray['1'];
					$renameFile 		  	  =   trim($id."_".rand(10,10000)."_".$id.".".$fileArray['1']);
					$postArray['receipt_id']  =   $renameFile;
					$adapter->addFilter('Rename',"..".$this->view->filepath.$renameFile);
						if ($adapter->isValid('file') && $adapter->receive('file')) {
							//unlink($this->view->fileuploadpath.$postArray['attachment']);
							$postArray['receipt_id'] =   $renameFile;
						} else {
							$postArray['receipt_id'] =  $postArray['attachment'];
						}
				} else {
					$postArray['receipt_id'] =  $postArray['attachment'];
				}*/
				//$payment_account = explode("_", $postArray['payment_account']);
				//$postArray['pay_account'] = $payment_account[0];

				if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
					$expenseTransaction = $this->transaction->updateExpenseTransaction($postArray,$id,2);
					$auditId = $this->transaction->insertExpenseAuditTransaction($postArray,$id,2);
					$sendNotify		   = $this->sendMail($postArray['approval_for']);
					$auditLog	  = $this->settings->insertAuditLog(2,2,'Expense',$auditId);
				} else if(isset($postArray['approve_expense']) && !empty($postArray['approve_expense'])) {
					//$postArray['approval_for'] = $logSession->id;
					$expenseTransaction = $this->transaction->updateExpenseTransaction($postArray,$id,1);
					$auditId = $this->transaction->insertExpenseAuditTransaction($postArray,$id,1);
					$accountEntry = $this->transaction->accountEntry($id,2);
					$auditLog	  = $this->settings->insertAuditLog(2,2,'Expense',$auditId);
					$auditLog	  = $this->settings->insertAuditLog(6,2,'Expense',$id);
					$fixedexpense  =  $this->transaction->getFixedExpenseTransaction($id);
						if($fixedexpense) {
							$sessFixed = new Zend_Session_Namespace('fixed_asset');
							$sessFixed->id = $id;
						}
				}
				if($expenseTransaction) {
					$sessSuccess = new Zend_Session_Namespace('update_success_expense');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/expense/edit/id/'.$this->_getParam('id'));
				} else {
						$this->view->error = 'Expense Transaction cannot be updated. Kindly try again later';
				}
			}
				
			}

			$delid = base64_decode($this->_getParam('delid'));
			$payid = base64_decode($this->_getParam('payid'));
			if(isset($delid) && !empty($delid)) {
				$deleteStatus = $this->transaction->deletePayment($delid,$id,2,$payid);
				$auditLog	  = $this->settings->insertAuditLog(3,11,'Expense',$id);
				if($deleteStatus) {
					$sessSuccess = new Zend_Session_Namespace('delete_success_expense_payment');
					$sessSuccess->status = 1;
				}
					$this->_redirect('transaction/expense/edit/id/'.$this->_getParam('id'));
			}
			//echo '<pre>'; print_r($this->view->expense); echo '</pre>'; 
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray','payMethod','purchaseTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->creditTerm     =  $getAccountArray['creditTermArray'];
			$this->view->payMethod      =  $getAccountArray['payMethod'];
			$purchase 					= array();
			//$this->view->purchase       =  $getAccountArray['purchaseTaxCodes'];
			//$this->view->receipts 		=  $this->business->getReceipts('',2);
			$this->view->cashAccount	=  $this->transaction->getCashAccount();
			$this->view->location       =  $this->settings->getLocation();
			$this->view->payAccount		=  $this->transaction->getPaymentExpenseAccount();
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->vendor 		=  $this->transaction->getVendorDetails();
			$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();
			$this->iras 	    		=  $this->transaction->getIrasTax(1);
			foreach ($this->iras as $iras) {
				$purchase[$iras['id']]['name']	    = $iras['name'];
				$purchase[$iras['id']]['percentage']  = $iras['percentage'];
				$purchase[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->purchase 		= $purchase;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(1);
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
			$this->view->filepath    =  $this->uploadPath.$cid."/receipts/";
			$this->view->nextId 	 =  $this->transaction->getNextExpenseTransaction();
			$id = base64_decode($this->_getParam('id'));
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/expense');
			} else {
				$this->view->expense  =  $this->transaction->getExpenseTransaction($id);
				if(!$this->view->expense) {
					$this->_redirect('transaction/expense');
				} else {
					$this->view->expenseList  =  $this->transaction->getExpenseTransactionList($id);
					if(!$this->view->expense) {
					$this->_redirect('transaction/expense');
					} 
				}
			}
			if($this->_request->isPost()) {
				$postArray  		   = $this->getRequest()->getPost();
				$postArray['date']	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				$postArray['due_date'] = date("Y-m-d",strtotime(trim($postArray['due_date'])));


				/*$adapter    =  new Zend_File_Transfer_Adapter_Http();
				$fileInfo 	=  $adapter->getFileInfo('file'); 
				if(isset($fileInfo['file']['name']) && ($fileInfo['file']['name'] != '')) {
					$adapter->addValidator('Count', false, array('min' =>1, 'max' => 2))
					        ->addValidator('Size',false,array('max'=>2024000),'file')
							->addValidator('Extension',false,'pdf,jpg,doc,docx,png','file');
					$adapter->setDestination("..".$this->view->filepath,'file');
					$fileInfo 	         	  =   $adapter->getFileInfo('file');
					$fileArray		  		  =   explode('.',$fileInfo['file']['name']);
					$postArray['extension']   =   $fileArray['1'];
					$renameFile 		  	  =   trim($this->view->nextId."_".rand(10,10000)."_".$this->view->nextId.".".$fileArray['1']);
					$postArray['receipt_id']  =   $renameFile;
					$adapter->addFilter('Rename',"..".$this->view->filepath.$renameFile);
						if ($adapter->isValid('file') && $adapter->receive('file')) {
							$postArray['receipt_id'] =   $renameFile;
						} else {
							$postArray['receipt_id'] =  $postArray['attachment'];
						}
				} else {
					$postArray['receipt_id'] =  $postArray['attachment'];
				}*/
				//$payment_account = explode("_", $postArray['payment_account']);
				//$postArray['pay_account'] = $payment_account[0];
				if(isset($postArray['approve_expense']) && !empty($postArray['approve_expense'])) {
						//$postArray['approval_for'] = $logSession->id;
						$expenseTransaction = $this->transaction->insertExpenseTransaction($postArray,$cid,1);
						$auditId = $this->transaction->insertExpenseAuditTransaction($postArray,$expenseTransaction,1);
						$accountEntry = $this->transaction->accountEntry($expenseTransaction,2);
						$auditLog	  = $this->settings->insertAuditLog(1,2,'Expense',$auditId);
						$auditLog	  = $this->settings->insertAuditLog(6,2,'Expense',$expenseTransaction);
						$fixedexpense  =  $this->transaction->getFixedExpenseTransaction($expenseTransaction);
						if($fixedexpense) {
							$sessFixed = new Zend_Session_Namespace('fixed_asset');
							$sessFixed->id = $expenseTransaction;
						}
					} else if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
						$expenseTransaction = $this->transaction->insertExpenseTransaction($postArray,$cid,2);
						$auditId = $this->transaction->insertExpenseAuditTransaction($postArray,$expenseTransaction,2);
						$sendNotify		   = $this->sendMail($postArray['approval_for']);
						$auditLog	  = $this->settings->insertAuditLog(1,2,'Expense',$auditId);
					}
				if($expenseTransaction) {
					$sessSuccess = new Zend_Session_Namespace('insert_success_expense');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/expense');
				} else {
						$this->view->error = 'Expense Transaction cannot be added. Kindly try again later';
				}
				
			}
			//echo '<pre>'; print_r($this->view->expense); echo '</pre>'; 
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray','payMethod','purchaseTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->creditTerm     =  $getAccountArray['creditTermArray'];
			$this->view->payMethod      =  $getAccountArray['payMethod'];
			$purchase 					= array();
			//$this->view->purchase       =  $getAccountArray['purchaseTaxCodes'];
			//$this->view->receipts 		=  $this->business->getReceipts('',2);
			$this->view->cashAccount	=  $this->transaction->getCashAccount();
			$this->view->payAccount		=  $this->transaction->getPaymentExpenseAccount();
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->vendor 		=  $this->transaction->getVendorDetails();
			$this->view->expenseAccount	=  $this->transaction->getExpenseAccount();
			$this->view->location       =  $this->settings->getLocation();
			$this->iras 	    		=  $this->transaction->getIrasTax(1);
			foreach ($this->iras as $iras) {
				$purchase[$iras['id']]['name']	    = $iras['name'];
				$purchase[$iras['id']]['percentage']  = $iras['percentage'];
				$purchase[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->purchase 		= $purchase;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(1);
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
				$bodyContent = 'Dear User, <br/> Expense Transaction has been created by user '.$user.' and is awaiting for your approval. <a href='.$this->view->sitePath."default/notification/transactions".'>Click here </a> to approve the transaction.';
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

	public function ajaxUploadAction() {

		$this->_helper->getHelper('layout')->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		$logSession = new Zend_Session_Namespace('sess_login');
		if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
			$cid = $logSession->proxy_cid;
		} else {
			$cid = $logSession->cid;
		}
		$this->view->filepath    =  $this->uploadPath.$cid."/receipts/";
		$action = $this->_getParam('operation');

		if($action=='add') {
				$this->view->nextId 	 =  $this->transaction->getNextExpenseTransaction();

				$uploader = new FileUpload('uploadfile');   
				$uploader->allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'doc');
				$uploader->sizeLimit = 10485760;
				$extension = $uploader->getExtension();
				$newfilename  = $this->view->nextId."_".rand(10,10000)."_expense.".$extension;
				$uploader->newFileName = $newfilename;
				$result = $uploader->handleUpload($this->view->filepath);

				if (!$result) {

				  echo json_encode(array(

				          'status' => "failure",

				          'file' => $uploader->getErrorMsg()

				       ));    

				} else {

				    echo json_encode(array ( 'data' => array(

				            'status' => "success",

				            'file' => $uploader->getFileName()

				         )));

				}
		}  else if($action=='edit') {

				$fileid = $this->_getParam('id');
				$uploader = new FileUpload('uploadfile');   
				$uploader->allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'doc');
				$uploader->sizeLimit = 10485760;
				$extension = $uploader->getExtension();
				$newfilename  = $fileid."_".rand(10,10000)."_expense.".$extension;
				$uploader->newFileName = $newfilename;
				$result = $uploader->handleUpload($this->view->filepath);

				if (!$result) {

				  echo json_encode(array(

				          'status' => "failure",

				          'file' => $uploader->getErrorMsg()

				       ));    

				} else {

				    echo json_encode(array ( 'data' => array(

				            'status' => "success",

				            'file' => $uploader->getFileName()

				         )));

				}
		} 
	}

	public function ajaxRemoveAction() {

		$this->_helper->getHelper('layout')->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$logSession = new Zend_Session_Namespace('sess_login');
		if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
			$cid = $logSession->proxy_cid;
		} else {
			$cid = $logSession->cid;
		}
		$this->view->filepath    =  $this->uploadPath.$cid."/receipts/";
		if($this->_request->isXmlHttpRequest()) {
			if ($this->_request->isPost()) {
				$ajaxVal = $this->getRequest()->getPost();
				if($ajaxVal['action']=='fileRemove') {
					$unlinkFile = unlink($this->view->filepath.$ajaxVal['id']);
					if($unlinkFile) {
						echo "success";
					} else {
						echo "failure";
					}
				}
			}
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
				if($ajaxVal['action']=='vendorRefresh') {
					$this->vendor 		=  $this->transaction->getVendorDetails();
					if($this->vendor) {
						//	echo '<select class="select2 form-control" name="vendor" id="vendor"  onchange="return getReceipt(this.value);">';
							echo '<option value="">Select</option>';
						foreach ($this->vendor as $vendor) {
							$coa = $vendor['coa_link'].",".$vendor['other_coa_link'];
							if($ajaxVal['id']==$vendor['id'])
                                $vendorSelect = 'selected';
                            else
                                $vendorSelect = '';
							echo '<option value='.$vendor['id'].' '.$vendorSelect.' data-coa='.$coa.'>'.$vendor['vendor_name'].'</option>';
						}
					//	echo '</select>';
					}
				} else if($ajaxVal['action']=='payaccountRefresh') {
					$this->payAccount		=  $this->transaction->getPaymentAccount();
					if($this->payAccount) {
							echo '<select class="form-control" name="payment_account" id="payment_account" onchange="triggerPayment();">';
							echo '<option value="">Select</option>';
						foreach ($this->payAccount as $pay) {
							$pays = $pay['id']."_".$pay['account_id']."_".$pay['account_type'];
							if($ajaxVal['id']==$pays)
                                $paySelect = 'selected';
                            else
                                $paySelect = '';
                           //echo '<option value='.$pay['id'].' '.$paySelect.'>'.$pay['account_name'].'</option>';
							echo '<option value='.$pay['id']."_".$pay['account_id']."_".$pay['account_type'].' '.$paySelect.'>'.$pay['account_name'].'</option>';
						}
						echo '</select>';
					}
				} else if($ajaxVal['action']=='expenseRefresh') {
					$this->expenseAccount	=  $this->transaction->getExpenseAccount();
					if($this->expenseAccount) {
						$jsonEncode = json_encode($this->expenseAccount);
						echo $jsonEncode;
						/*echo '<select class="form-control" name="expense_type_'.$ajaxVal['expense'].'" id="expense_type_'.$ajaxVal['expense'].'" required>';
						echo '<option value="">Select</option>';
						foreach ($this->expenseAccount as $expense) {
							if($ajaxVal['id']==$expense['id'])
                                $expenseSelect = 'selected';
                            else
                                $expenseSelect = '';
							echo '<option value='.$expense['id'].' '.$expenseSelect.'>'.$expense['account_name'].'</option>';
						}
						echo '</select>';*/
					}
				} else if($ajaxVal['action']=='getPayAccount') {
					$this->cashAccount	=  $this->transaction->getCashAccount();
					$this->payAccount	=  $this->transaction->getPaymentExpenseAccount($ajaxVal['coa']);
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
							if($pay['level2']==3 && $opt1!=1) {
								echo '<optgroup label="Trade Payables">';
								$opt1=1;
							}
							if($pay['level2']==8 && $opt2!=1) {
								echo '<optgroup label="Other Creditors">';
								$opt2=1;
							}
							echo '<option value='.$pay['id']."_".$pay['level2']."_".$pay['account_type'].'>'.ucfirst($pay['account_name']).'</option>';
						}
						//echo '</select>';
					}
				} else if($ajaxVal['action']=='getPayAccount_update') {
					$this->cashAccount	=  $this->transaction->getCashAccount();
					$this->payAccount	=  $this->transaction->getPaymentExpenseAccount($ajaxVal['coa']);
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
							if($pay['level2']==3 && $opt1!=1) {
								echo '<optgroup label="Trade Payables">';
								$opt1=1;
							}
							if($pay['level2']==8 && $opt2!=1) {
								echo '<optgroup label="Other Creditors">';
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
				} else if($ajaxVal['action']=='check_receipt') {
					$checkReceipt = $this->transaction->checkExpenseReceipt($ajaxVal['receipt_no']);
					if($checkReceipt) {
						echo "2";
					} else {
						echo "1";
					}
				}  else if($ajaxVal['action']=='check_receipt_update') {
					$checkReceipt = $this->transaction->checkExpenseReceipt($ajaxVal['receipt_no'],$ajaxVal['id']);
					if($checkReceipt) {
						echo "2";
					} else {
						echo "1";
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
				if($ajaxVal['action']=='save_draft_expense') {
					$ajaxVal['date'] 	 = date("Y-m-d",strtotime(trim($ajaxVal['date'])));
					$ajaxVal['due_date'] = date("Y-m-d",strtotime(trim($ajaxVal['due_date'])));
					if(isset($ajaxVal['vendor']) && !empty($ajaxVal['vendor'])) {
						$ajaxVal['vendor'] = trim($ajaxVal['vendor']);
					} else {
						$ajaxVal['vendor'] = NULL;
					}
					/*if(isset($ajaxVal['payment_account']) && !empty($ajaxVal['payment_account'])) {
						$payment_account = explode("_", $ajaxVal['payment_account']);
					    $ajaxVal['pay_account'] = $payment_account[0];
					} else {
						$ajaxVal['pay_account'] = NULL;
					}*/
					if(isset($ajaxVal['receipt_id']) && !empty($ajaxVal['receipt_id'])) {
						$ajaxVal['receipt_id'] = trim($ajaxVal['receipt_id']);
					} else {
						$ajaxVal['receipt_id'] = NULL;
					}
					$expenseTransaction = $this->transaction->insertExpenseTransaction($ajaxVal,$cid,3);
					$auditId = $this->transaction->insertExpenseAuditTransaction($ajaxVal,$expenseTransaction,3);
					$auditLog	  = $this->settings->insertAuditLog(8,2,'Expense',$auditId);
					if($expenseTransaction) {
						$sessDraft = new Zend_Session_Namespace('sess_draft_expense_insert');
						$sessDraft->status = 1;
						echo "success";
					} else {
						echo "Failure";
					}
				} else if($ajaxVal['action']=='update_draft_expense') {
					$ajaxVal['date'] 	 = date("Y-m-d",strtotime(trim($ajaxVal['date'])));
					$ajaxVal['due_date'] = date("Y-m-d",strtotime(trim($ajaxVal['due_date'])));
					if(isset($ajaxVal['vendor']) && !empty($ajaxVal['vendor'])) {
						$ajaxVal['vendor'] = trim($ajaxVal['vendor']);
					} else {
						$ajaxVal['vendor'] = NULL;
					}
					/*if(isset($ajaxVal['payment_account']) && !empty($ajaxVal['payment_account'])) {
						$payment_account = explode("_", $ajaxVal['payment_account']);
					    $ajaxVal['pay_account'] = $payment_account[0];
					} else {
						$ajaxVal['pay_account'] = NULL;
					}*/
					if(isset($ajaxVal['receipt_id']) && !empty($ajaxVal['receipt_id'])) {
						$ajaxVal['receipt_id'] = trim($ajaxVal['receipt_id']);
					} else {
						$ajaxVal['receipt_id'] = NULL;
					}
					$expenseTransaction = $this->transaction->updateExpenseTransaction($ajaxVal,$ajaxVal['expense_id'],3);
					$auditId = $this->transaction->insertExpenseAuditTransaction($ajaxVal,$ajaxVal['expense_id'],3);
					$auditLog	  = $this->settings->insertAuditLog(8,2,'Expense',$auditId);
					if($expenseTransaction) {
						$sessDraft = new Zend_Session_Namespace('sess_draft_expense_insert');
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