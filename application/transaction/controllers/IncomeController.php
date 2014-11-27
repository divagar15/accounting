<?php 
require_once "Account/Uploader.php";
class Transaction_IncomeController extends Zend_Controller_Action {
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
		$this->cacheUrl    = Zend_Registry::get('cacheurl');
		$this->account     = new Account();
		$this->business    = new Business();
		$this->settings    = new Settings();
		$this->transaction = new Transaction();
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
			//$test = array('1' => "ddf", '2' =>"afddf" );
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
			
			//echo $logSession->proxy_currency;
			$sort   = $this->_getParam('sort');
			if(isset($sort) && !empty($sort) && ($sort==1 || $sort==2)) {
				$sort   = $this->_getParam('sort');
			} else {
				$sort = '';
			}

			$this->view->sort = $sort;

			$this->view->filepath    =  $this->uploadPath.$cid."/receipts/";
			$this->view->nextId 	 =  $this->transaction->getNextIncomeTransaction();
			if($this->_request->isPost()) {
				$postArray  				= $this->getRequest()->getPost();
				if(isset($postArray['add_payment']) && !empty($postArray['add_payment'])) {
					$postArray['discount'] = 0;
					$postArray['ref_id']   = $postArray['income_id'];
					$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
					if(isset($postArray['payment_discount']) && $postArray['payment_discount']==1 && isset($postArray['discount_payment_amount'])) {
						$postArray['discount'] = $postArray['discount_payment_amount'];
					}
					$addPayment   = $this->transaction->addPayment($postArray,1);
					$auditId      = $this->transaction->addPaymentAudit($postArray,1);
					$accountEntry = $this->transaction->accountEntry($postArray['ref_id'],1);
					$auditLog	  = $this->settings->insertAuditLog(1,11,'Income',$auditId);
					if($addPayment) {
						$sessSuccess = new Zend_Session_Namespace('add_payment_success');
						$sessSuccess->status = 1;
					} else {
						$sessSuccess = new Zend_Session_Namespace('add_payment_success');
						$sessSuccess->status = 2;
					}
					$this->_redirect('transaction/income/');
				} else {

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

					$postArray['tax_id'] = ' ';
					$postArray['tax_percentage'] = ' ';
					$postArray['date'] = date("Y-m-d",strtotime(trim($postArray['date'])));
					$taxes = explode("_",$postArray['tax_code']);
					$postArray['tax_id'] = $taxes[0];
					if(isset($taxes[1]) && !empty($taxes[1])) {
						$postArray['tax_percentage'] = $taxes[1];
					}
					$postArray['amount'] = str_replace(",","",$postArray['amount']);
					//$payment_account = explode("_", $postArray['payment_account']);
					//$postArray['pay_account'] = $payment_account[0];
					//echo '<pre>'; print_r($postArray); echo '</pre>'; die();
					if(isset($postArray['approve_income']) && !empty($postArray['approve_income'])) {
					 	//$postArray['approval_for'] = $logSession->id;
						$incomeTransaction = $this->transaction->insertIncomeTransaction($postArray,$cid,1);
						$auditId           = $this->transaction->insertIncomeAuditTransaction($postArray,$incomeTransaction,1);
						$accountEntry = $this->transaction->accountEntry($incomeTransaction,1);
						$auditLog	  = $this->settings->insertAuditLog(1,1,'Income',$auditId);
						$auditLog	  = $this->settings->insertAuditLog(6,1,'Income',$incomeTransaction);
					}  else if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
						$incomeTransaction = $this->transaction->insertIncomeTransaction($postArray,$cid,2);
						$auditId           = $this->transaction->insertIncomeAuditTransaction($postArray,$incomeTransaction,2);
						$sendNotify		   = $this->sendMail($postArray['approval_for']);
						$auditLog	  = $this->settings->insertAuditLog(1,1,'Income',$auditId);
					}
					if($incomeTransaction) {
						$sessSuccess = new Zend_Session_Namespace('insert_success_income');
						$sessSuccess->status = 1;
						$this->_redirect('transaction/income/');
					} else {
							$this->view->error = 'Income Transaction cannot be added. Kindly try again later';
					}
				}
				//echo '<pre>'; print_r($postArray); echo '</pre>'; die();
			}
			if(Zend_Session::namespaceIsset('insert_success_income')) {
				$this->view->success = 'Income Transaction Added successfully';
				Zend_Session::namespaceUnset('insert_success_income');
			}
			if(Zend_Session::namespaceIsset('delete_success_income_transaction')) {
				$this->view->success = 'Transaction deleted successfully';
				Zend_Session::namespaceUnset('delete_success_income_transaction');
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
			if(Zend_Session::namespaceIsset('verify_success_income_transaction')) {
				$this->view->success = 'Transaction verified successfully';
				Zend_Session::namespaceUnset('verify_success_income_transaction');
			}
			if(Zend_Session::namespaceIsset('unverify_success_income_transaction')) {
				$this->view->success = 'Transaction unverified successfully';
				Zend_Session::namespaceUnset('unverify_success_income_transaction');
			}
			if(Zend_Session::namespaceIsset('sess_draft_income_insert')) {
				$this->view->success = 'Income Transaction saved as draft';
				Zend_Session::namespaceUnset('sess_draft_income_insert');
			}
			$delid = base64_decode($this->_getParam('delid'));
			if(isset($delid) && !empty($delid)) {
				$location1   = $this->_getParam('location'); 

				$financeSet1 = $this->_getParam('financial_year'); 

				$deleteStatus = $this->transaction->deleteIncomeTransaction($delid,2);
				$auditLog	  = $this->settings->insertAuditLog(3,1,'Income',$delid);
				if($deleteStatus) {
					$sessSuccess = new Zend_Session_Namespace('delete_success_income_transaction');
					$sessSuccess->status = 1;
				}
					$this->_redirect('transaction/income?location='.$location1.'&financial_year='.$financeSet1);
			}
			$verifyid  = base64_decode($this->_getParam('verifyid'));
			$status    = $this->_getParam('status');
			if(isset($verifyid) && !empty($verifyid) && isset($status) && !empty($status)) {
				$location2   = $this->_getParam('location'); 

				$financeSet2 = $this->_getParam('financial_year'); 

				$changeStatus = $this->transaction->changeIncomeTransactionStatus($verifyid,$status);
				if($changeStatus) {
					if($status==1) {
						$accountEntry = $this->transaction->accountEntry($verifyid,1);
						$auditLog	  = $this->settings->insertAuditLog(6,1,'Income',$verifyid);
						$sessSuccess = new Zend_Session_Namespace('verify_success_income_transaction');
						$sessSuccess->status = 1;
					} else if($status==2) {
						$accountEntryExpired = $this->transaction->accountEntryExpired($verifyid,1);
						$auditLog	  = $this->settings->insertAuditLog(7,1,'Income',$verifyid);
						$sessSuccess = new Zend_Session_Namespace('unverify_success_income_transaction');
						$sessSuccess->status = 2;
					}
				}
					$this->_redirect('transaction/income?location='.$location2.'&financial_year='.$financeSet2);
			}
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray','payMethod','supplyTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->creditTerm     =  $getAccountArray['creditTermArray'];
			$this->view->payMethod      =  $getAccountArray['payMethod'];
			$supply 					= array();
			//$this->view->supply         =  $getAccountArray['supplyTaxCodes'];
			$this->view->cashAccount	=  $this->transaction->getCashAccount();
			$this->view->payAccount		=  $this->transaction->getPaymentIncomeAccount();
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->customer 		=  $this->transaction->getCustomerDetails();
			$this->view->incomeAccount	=  $this->transaction->getIncomeAccount();
			$this->view->location       =  $this->settings->getLocation();
			$this->view->locations      =  $this->settings->getLocations();
			$this->view->finance        =  $this->settings->getFinanceYears();
			$this->iras 	    		=  $this->transaction->getIrasTax(2);
			foreach ($this->iras as $iras) {
				$supply[$iras['id']]['name']	    = $iras['name'];
				$supply[$iras['id']]['percentage']  = $iras['percentage'];
				$supply[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->supply 		= $supply;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(2);
			$this->view->creditSet 		=  3;
			$this->view->result 		=  $this->transaction->getIncomeTransaction($id='',$sort,$location,$financeSet);
			$this->view->payments 		=  $this->transaction->getPaymentDetails('',1);

			

			//echo '<pre>'; print_r($this->view->finance); echo '</pre>'; 
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
			$this->view->filepath    =  $this->uploadPath.$cid."/receipts/";
			if($this->_request->isPost()) {
				$postArray  				= $this->getRequest()->getPost();
				$postArray['discount'] = 0;
				$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				if(isset($postArray['payment_discount']) && $postArray['payment_discount']==1 && isset($postArray['discount_amount'])) {
					$postArray['discount'] = $postArray['discount_amount'];
				}
				$updatePayment = $this->transaction->updatePayment($postArray,1);
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
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray','payMethod','supplyTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->creditTerm     =  $getAccountArray['creditTermArray'];
			$this->view->payMethod      =  $getAccountArray['payMethod'];
			$supply 					= array();
			//$this->view->supply         =  $getAccountArray['supplyTaxCodes'];
			$this->view->approveUser	=  $this->settings->getApproveUsers();
			$this->view->payAccount		=  $this->transaction->getPaymentAccount();
			$this->view->customer 		=  $this->transaction->getCustomerDetails();
			$this->view->location       =  $this->settings->getLocation();
			$this->view->incomeAccount	=  $this->transaction->getIncomeAccount();
			$this->iras 	    		=  $this->transaction->getIrasTax(2);
			foreach ($this->iras as $iras) {
				$supply[$iras['id']]['name']	    = $iras['name'];
				$supply[$iras['id']]['percentage']  = $iras['percentage'];
				$supply[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->supply 		= $supply;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(2);
			$id = base64_decode($this->_getParam('id'));
			$this->view->inc_id = $id;
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/income');
			} else {
				$this->view->income  =  $this->transaction->getIncomeTransaction($id);
				if(!$this->view->income) {
					$this->_redirect('transaction/income');
				}  else {
					$this->view->incomePayment =  $this->transaction->getPaymentDetails($id,1);
				}
			}
			
		}
	}


	public function editAction() {
		if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			if(Zend_Session::namespaceIsset('update_success_income')) {
				$this->view->success = 'Income Transaction Updated successfully';
				Zend_Session::namespaceUnset('update_success_income');
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
			if(Zend_Session::namespaceIsset('delete_success_income_payment')) {
				$this->view->success = 'Income Payment Deleted successfully';
				Zend_Session::namespaceUnset('delete_success_income_payment');
			}
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
			$this->view->filepath    =  $this->uploadPath.$cid."/receipts/";
			$id = base64_decode($this->_getParam('id'));
			$this->view->inc_id = $id;
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/income');
			} else {
				$this->view->income  =  $this->transaction->getIncomeTransaction($id);
				if(!$this->view->income) {
					$this->_redirect('transaction/income');
				}  else {
					$this->view->incomePayment =  $this->transaction->getPaymentDetails($id,1);
				}
			}
			if($this->_request->isPost()) {
				$postArray  				= $this->getRequest()->getPost();
				if(isset($postArray['action']) && $postArray['action']=='add_payment') {
					$postArray['discount'] = 0;
					$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['pay_date'])));
					if(isset($postArray['payment_discount']) && $postArray['payment_discount']==1 && isset($postArray['discount_amount'])) {
						$postArray['discount'] = $postArray['discount_amount'];
					}
					$updatePayment = $this->transaction->updatePayment($postArray,1);
					$auditId       = $this->transaction->addPaymentAudit($postArray,1);
					$accountEntry  = $this->transaction->accountEntry($id,1);
					$auditLog	  = $this->settings->insertAuditLog(2,11,'Income',$auditId);
					if($updatePayment) {
						$sessSuccess = new Zend_Session_Namespace('update_payment_success');
						$sessSuccess->status = 1;
						$this->_redirect('transaction/income/edit/id/'.$this->_getParam('id'));
					} else {
						$sessSuccess = new Zend_Session_Namespace('update_payment_success');
						$sessSuccess->status = 2;
						$this->_redirect('transaction/income/edit/id/'.$this->_getParam('id'));
					}
				} else {
			/*	$adapter    =  new Zend_File_Transfer_Adapter_Http();
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


				$postArray['tax_id'] = ' ';
				$postArray['tax_percentage'] = ' ';
				$postArray['date'] = date("Y-m-d",strtotime(trim($postArray['date'])));
				$taxes = explode("_",$postArray['tax_code']);
				$postArray['tax_id'] = $taxes[0];
				if(isset($taxes[1]) && !empty($taxes[1])) {
					$postArray['tax_percentage'] = $taxes[1];
				}
				$postArray['amount'] = str_replace(",","",$postArray['amount']);
				//$payment_account = explode("_", $postArray['payment_account']);
				//$postArray['pay_account'] = $payment_account[0];
				//echo '<pre>'; print_r($postArray); echo '</pre>'; die();
				if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
					$incomeTransaction = $this->transaction->updateIncomeTransaction($postArray,$id,2);
					$auditId           = $this->transaction->insertIncomeAuditTransaction($postArray,$id,2);
					$sendNotify		   = $this->sendMail($postArray['approval_for']);
					$auditLog	  = $this->settings->insertAuditLog(2,1,'Income',$auditId);
				} else if(isset($postArray['approve_income']) && !empty($postArray['approve_income'])) {
					//$postArray['approval_for'] = $logSession->id;
					$incomeTransaction = $this->transaction->updateIncomeTransaction($postArray,$id,1);
					$auditId           = $this->transaction->insertIncomeAuditTransaction($postArray,$id,2);
					$accountEntry = $this->transaction->accountEntry($id,1);
					$auditLog	  = $this->settings->insertAuditLog(2,1,'Income',$auditId);
					$auditLog	  = $this->settings->insertAuditLog(6,1,'Income',$id);
				}
				if($incomeTransaction) {
					$sessSuccess = new Zend_Session_Namespace('update_success_income');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/income/edit/id/'.$this->_getParam('id'));
				} else {
						$this->view->error = 'Income Transaction cannot be updated. Kindly try again later';
				}
				
				}
			}
			$delid = base64_decode($this->_getParam('delid'));
			$payid = base64_decode($this->_getParam('payid'));
			if(isset($delid) && !empty($delid)) {
				$deleteStatus = $this->transaction->deletePayment($delid,$id,1,$payid);
				$auditLog	  = $this->settings->insertAuditLog(3,11,'Income',$id);
				if($deleteStatus) {
					$sessSuccess = new Zend_Session_Namespace('delete_success_income_payment');
					$sessSuccess->status = 1;
				}
					$this->_redirect('transaction/income/edit/id/'.$this->_getParam('id'));
			}
			$getAccountArray            =  $this->accountData->getData(array('currencies','creditTermArray','payMethod','supplyTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->creditTerm     =  $getAccountArray['creditTermArray'];
			$this->view->payMethod      =  $getAccountArray['payMethod'];
			$supply 					= array();
			//$this->view->supply         =  $getAccountArray['supplyTaxCodes'];
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->cashAccount	=  $this->transaction->getCashAccount();
			$this->view->payAccount		=  $this->transaction->getPaymentIncomeAccount();
			$this->view->customer 		=  $this->transaction->getCustomerDetails();
			//$this->view->receipts 		=  $this->business->getReceipts('',1);
			$this->view->creditSet 		=  3;
			$this->view->incomeAccount	=  $this->transaction->getIncomeAccount();
			$this->view->location       =  $this->settings->getLocation();
			$this->iras 	    		=  $this->transaction->getIrasTax(2);
			foreach ($this->iras as $iras) {
				$supply[$iras['id']]['name']	    = $iras['name'];
				$supply[$iras['id']]['percentage']  = $iras['percentage'];
				$supply[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->supply 		= $supply;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(2);
			//echo '<pre>'; print_r($this->view->income); echo '</pre>'; 
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
			$this->view->nextId 	 =  $this->transaction->getNextIncomeTransaction();
			$id = base64_decode($this->_getParam('id'));
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/income');
			} else {
				$this->view->income  =  $this->transaction->getIncomeTransaction($id);
				if(!$this->view->income) {
					$this->_redirect('transaction/income');
				} 
			}
			if($this->_request->isPost()) {
					$postArray  				= $this->getRequest()->getPost();
/*					$adapter    =  new Zend_File_Transfer_Adapter_Http();
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

					
					$postArray['tax_id'] = ' ';
					$postArray['tax_percentage'] = ' ';
					$postArray['date'] = date("Y-m-d",strtotime(trim($postArray['date'])));
					$taxes = explode("_",$postArray['tax_code']);
					$postArray['tax_id'] = $taxes[0];
					if(isset($taxes[1]) && !empty($taxes[1])) {
						$postArray['tax_percentage'] = $taxes[1];
					}
					$postArray['amount'] = str_replace(",","",$postArray['amount']);
					//$payment_account = explode("_", $postArray['payment_account']);
					//$postArray['pay_account'] = $payment_account[0];
					//echo '<pre>'; print_r($postArray); echo '</pre>'; die();
					 if(isset($postArray['approve_income']) && !empty($postArray['approve_income'])) {
					 	//$postArray['approval_for'] = $logSession->id;
						$incomeTransaction = $this->transaction->insertIncomeTransaction($postArray,$cid,1);
						$auditId           = $this->transaction->insertIncomeAuditTransaction($postArray,$incomeTransaction,2);
						$accountEntry = $this->transaction->accountEntry($incomeTransaction,1);
						$auditLog	  = $this->settings->insertAuditLog(1,1,'Income',$auditId);
						$auditLog	  = $this->settings->insertAuditLog(6,1,'Income',$incomeTransaction);
					}  else if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
						$incomeTransaction = $this->transaction->insertIncomeTransaction($postArray,$cid,2);
						$auditId           = $this->transaction->insertIncomeAuditTransaction($postArray,$incomeTransaction,2);
						$sendNotify		   = $this->sendMail($postArray['approval_for']);
						$auditLog	  = $this->settings->insertAuditLog(1,1,'Income',$auditId);
					}
					if($incomeTransaction) {
						$sessSuccess = new Zend_Session_Namespace('insert_success_income');
						$sessSuccess->status = 1;
						$this->_redirect('transaction/income/');
					} else {
							$this->view->error = 'Income Transaction cannot be added. Kindly try again later';
					}
				
			}
			$getAccountArray            =  $this->accountData->getData(array('payMethod','currencies','creditTermArray','supplyTaxCodes'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->creditTerm     =  $getAccountArray['creditTermArray'];
			$this->view->payMethod      =  $getAccountArray['payMethod'];
			$supply 					= array();
			//$this->view->supply         =  $getAccountArray['supplyTaxCodes'];
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->payAccount		=  $this->transaction->getPaymentAccount();
			$this->view->cashAccount	=  $this->transaction->getCashAccount();
			$this->view->customer 		=  $this->transaction->getCustomerDetails();
			$this->view->creditSet 		=  3;
		//	$this->view->receipts 		=  $this->business->getReceipts('',1);
			$this->view->incomeAccount	=  $this->transaction->getIncomeAccount();
			$this->view->location       =  $this->settings->getLocation();
			$this->iras 	    		=  $this->transaction->getIrasTax(2);
			foreach ($this->iras as $iras) {
				$supply[$iras['id']]['name']	    = $iras['name'];
				$supply[$iras['id']]['percentage']  = $iras['percentage'];
				$supply[$iras['id']]['description'] = $iras['description'];
			}
			$this->view->supply 		= $supply;
			$this->view->taxCode    	=  $this->transaction->getSalesTax(2);
			//echo '<pre>'; print_r($this->view->income); echo '</pre>'; 
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
				$bodyContent = 'Dear Approver, <br/> Income Transaction has been created by user '.$user.' and is awaiting for your approval. <a href='.$this->view->sitePath."default/notification/transactions".'>Click here </a> to approve the transaction.';
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
							//echo '<select class="select2 form-control" name="customer" id="customer">';
							echo '<option value="">Select</option>';
						foreach ($this->customer as $customer) {
							$coa = $customer['coa_link'].",".$customer['other_coa_link'];
							if($ajaxVal['id']==$customer['id'])
                                $customerSelect = 'selected';
                            else
                                $customerSelect = '';
							echo '<option value='.$customer['id'].' '.$customerSelect.' data-coa='.$coa.'>'.$customer['customer_name'].'</option>';
						}
						//echo '</select>';
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
							echo '<option value='.$pay['id']."_".$pay['account_id']."_".$pay['account_type'].' '.$paySelect.'>'.$pay['account_name'].'</option>';
						}
						echo '</select>';
					}
				} else if($ajaxVal['action']=='incomeRefresh') {
					$this->incomeAccount	=  $this->transaction->getIncomeAccount();
					if($this->incomeAccount) {
						echo '<select class="form-control" name="income_type" id="income_type">';
						foreach ($this->incomeAccount as $income) {
							if($ajaxVal['id']==$income['id'])
                                $incomeSelect = 'selected';
                            else
                                $incomeSelect = '';
							echo '<option value='.$income['id'].' '.$incomeSelect.'>'.$income['account_name'].'</option>';
						}
						echo '</select>';
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
				} else if($ajaxVal['action']=='check_receipt') {
					$checkReceipt = $this->transaction->checkIncomeReceipt($ajaxVal['receipt_no']);
					if($checkReceipt) {
						echo "2";
					} else {
						echo "1";
					}
				}  else if($ajaxVal['action']=='check_receipt_update') {
					$checkReceipt = $this->transaction->checkIncomeReceipt($ajaxVal['receipt_no'],$ajaxVal['id']);
					if($checkReceipt) {
						echo "2";
					} else {
						echo "1";
					}
				}
			}
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
				$this->view->nextId 	 =  $this->transaction->getNextIncomeTransaction();

				$uploader = new FileUpload('uploadfile');   
				$uploader->allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'doc');
				$uploader->sizeLimit = 10485760;
				$extension = $uploader->getExtension();
				$newfilename  = $this->view->nextId."_".rand(10,10000)."_income.".$extension;
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
				$newfilename  = $fileid."_".rand(10,10000)."_income.".$extension;
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
				if($ajaxVal['action']=='save_draft_income') {
					$ajaxVal['tax_id'] = ' ';
					$ajaxVal['tax_percentage'] = ' ';
					$ajaxVal['date'] = date("Y-m-d",strtotime(trim($ajaxVal['date'])));
					$taxes = explode("_",$ajaxVal['tax_code']);
					$ajaxVal['tax_id'] = $taxes[0];
					if(isset($taxes[1]) && !empty($taxes[1])) {
						$ajaxVal['tax_percentage'] = $taxes[1];
					}
					if(isset($ajaxVal['customer']) && !empty($ajaxVal['customer'])) {
						$ajaxVal['customer'] = trim($ajaxVal['customer']);
					} else {
						$ajaxVal['customer'] = NULL;
					}
					if(isset($ajaxVal['receipt_id']) && !empty($ajaxVal['receipt_id'])) {
						$ajaxVal['receipt_id'] = trim($ajaxVal['receipt_id']);
					} else {
						$ajaxVal['receipt_id'] = NULL;
					}
					$ajaxVal['amount'] = str_replace(",","",$ajaxVal['amount']);
					$incomeTransaction = $this->transaction->insertIncomeTransaction($ajaxVal,$cid,3);
					$auditId           = $this->transaction->insertIncomeAuditTransaction($ajaxVal,$incomeTransaction,3);
					$auditLog	  = $this->settings->insertAuditLog(8,1,'Income',$auditId);
					if($incomeTransaction) {
						$sessDraft = new Zend_Session_Namespace('sess_draft_income_insert');
						$sessDraft->status = 1;
						echo "success";
					} else {
						echo "Failure";
					}
				} else if($ajaxVal['action']=='update_draft_income') {
					$ajaxVal['tax_id'] = ' ';
					$ajaxVal['tax_percentage'] = ' ';
					$ajaxVal['date'] = date("Y-m-d",strtotime(trim($ajaxVal['date'])));
					$taxes = explode("_",$ajaxVal['tax_code']);
					$ajaxVal['tax_id'] = $taxes[0];
					if(isset($taxes[1]) && !empty($taxes[1])) {
						$ajaxVal['tax_percentage'] = $taxes[1];
					}
					if(isset($ajaxVal['customer']) && !empty($ajaxVal['customer'])) {
						$ajaxVal['customer'] = trim($ajaxVal['customer']);
					} else {
						$ajaxVal['customer'] = NULL;
					}
					if(isset($ajaxVal['receipt_id']) && !empty($ajaxVal['receipt_id'])) {
						$ajaxVal['receipt_id'] = trim($ajaxVal['receipt_id']);
					} else {
						$ajaxVal['receipt_id'] = NULL;
					}
					$ajaxVal['amount'] = str_replace(",","",$ajaxVal['amount']);
					$incomeTransaction = $this->transaction->updateIncomeTransaction($ajaxVal,$ajaxVal['income_id'],3);
					$auditId           = $this->transaction->insertIncomeAuditTransaction($ajaxVal,$ajaxVal['income_id'],3);
					$auditLog	  = $this->settings->insertAuditLog(8,1,'Income',$auditId);
					if($incomeTransaction) {
						$sessDraft = new Zend_Session_Namespace('sess_draft_income_insert');
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