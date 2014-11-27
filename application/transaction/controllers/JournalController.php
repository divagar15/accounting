<?php 
require_once "Account/Uploader.php";
class Transaction_JournalController extends Zend_Controller_Action {
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
			if(isset($logSession) && !empty($logSession->id) && !empty($logSession->cid)) {
				if($logSession->type==0 && !isset($logSession->proxy_type)) {
					$this->_redirect('developer');
				} 
			} else {
				$this->_redirect('index');
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
			if(Zend_Session::namespaceIsset('insert_success_journal')) {
				$this->view->success = 'Jounral Entries Added successfully';
				Zend_Session::namespaceUnset('insert_success_journal');
			}
			if(Zend_Session::namespaceIsset('delete_success_journal')) {
				$this->view->success = 'Journal Entry deleted successfully';
				Zend_Session::namespaceUnset('delete_success_journal');
			}
			if(Zend_Session::namespaceIsset('delete_error_journal')) {
				$this->view->error = 'Journal Entry cannot be deleted';
				Zend_Session::namespaceUnset('delete_error_journal');
			}
			if(Zend_Session::namespaceIsset('verify_success_journal_transaction')) {
				$this->view->success = 'Journal Entry verified successfully';
				Zend_Session::namespaceUnset('verify_success_journal_transaction');
			}
			if(Zend_Session::namespaceIsset('unverify_success_journal_transaction')) {
				$this->view->success = 'Journal Entry unverified successfully';
				Zend_Session::namespaceUnset('unverify_success_journal_transaction');
			}
			if(Zend_Session::namespaceIsset('sess_draft_journal_insert')) {
				$this->view->success = 'Journal Entry saved as draft';
				Zend_Session::namespaceUnset('sess_draft_journal_insert');
			}
			$verifyid  = base64_decode($this->_getParam('verifyid'));
			$status    = $this->_getParam('status');
			if(isset($verifyid) && !empty($verifyid) && isset($status) && !empty($status)) {
				$location2   = $this->_getParam('location'); 

				$financeSet2 = $this->_getParam('financial_year'); 
				
				$changeStatus = $this->transaction->changeJournalTransactionStatus($verifyid,$status);
				if($changeStatus) {
					if($status==1) {
						$auditLog	  = $this->settings->insertAuditLog(6,5,'Jounral Entry',$verifyid);
						$sessSuccess = new Zend_Session_Namespace('verify_success_journal_transaction');
						$sessSuccess->status = 1;
					} else if($status==2) {
						$auditLog	  = $this->settings->insertAuditLog(7,5,'Jounral Entry',$verifyid);
						$sessSuccess = new Zend_Session_Namespace('unverify_success_journal_transaction');
						$sessSuccess->status = 2;
					}
				}
					$this->_redirect('transaction/journal?location='.$location2.'&financial_year='.$financeSet2);
			}
			$delid = base64_decode($this->_getParam('delid'));
			if(isset($delid) && !empty($delid)) {
				$location1   = $this->_getParam('location'); 

				$financeSet1 = $this->_getParam('financial_year'); 

				$deleteStatus = $this->transaction->deleteJournalTransaction($delid,2);
				if($deleteStatus && $deleteStatus==1) {
					$auditLog	  = $this->settings->insertAuditLog(3,5,'Jounral Entry',$delid);
					$sessSuccess = new Zend_Session_Namespace('delete_success_journal');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/journal?location='.$location1.'&financial_year='.$financeSet1);
				} elseif ($deleteStatus && $deleteStatus==3) {
					$sessError = new Zend_Session_Namespace('delete_error_journal');
					$sessError->status = 1;
					$this->_redirect('transaction/journal?location='.$location1.'&financial_year='.$financeSet1);
				}
			}
			$getAccountArray            =  $this->accountData->getData(array('currencies'));
			$this->view->currencies     =  $getAccountArray['currencies'];
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->taxCode    	=  $this->transaction->getTax(1);
			$this->view->location       =  $this->settings->getLocation();
			$this->view->locations      =  $this->settings->getLocations();
			$this->view->finance        =  $this->settings->getFinanceYears();
			$this->view->result 		=  $this->transaction->getJournalTransaction($id='',$sort,$location,$financeSet);
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
			$this->view->fileuploadpath    =  $this->uploadPath.$cid."/journal/";
			$this->view->nextId 	 =  $this->transaction->getNextJournalTransaction();
			if($this->_request->isPost()) {
				$postArray  = $this->getRequest()->getPost();

			/*	$adapter    =  new Zend_File_Transfer_Adapter_Http();
				$fileInfo 	=  $adapter->getFileInfo('file'); 
				if(isset($fileInfo['file']['name']) && ($fileInfo['file']['name'] != '')) {
					$adapter->addValidator('Count', false, array('min' =>1, 'max' => 2))
					        ->addValidator('Size',false,array('max'=>2024000),'file')
							->addValidator('Extension',false,'pdf,jpg,doc,docx,png','file');
					$adapter->setDestination("..".$this->fileuploadpath,'file');
					$fileInfo 	         	  =   $adapter->getFileInfo('file');
					$fileArray		  		  =   explode('.',$fileInfo['file']['name']);
					$postArray['extension']   =   $fileArray['1'];
					$renameFile 		  	  =   trim($this->view->nextId."_".rand(10,10000)."_".$this->view->nextId.".".$fileArray['1']);
					$postArray['attach_file'] =   $renameFile;
					$adapter->addFilter('Rename',"..".$this->fileuploadpath.$renameFile);
						if ($adapter->isValid('file') && $adapter->receive('file')) {
							$postArray['attach_file'] =   $renameFile;
						} else {
							$postArray['attach_file'] =   '';
						}
				} else {
					$postArray['attach_file'] =   '';
				}*/
				$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
					$journalTransaction = $this->transaction->insertJournalTransaction($postArray,$cid,2);
					$auditId = $this->transaction->insertJournalAuditTransaction($postArray,$journalTransaction,2);
					$sendNotify		   = $this->sendMail($postArray['approval_for']);
					$auditLog	  = $this->settings->insertAuditLog(1,5,'Jounral Entry',$auditId);
				} else if(isset($postArray['approve_journal']) && !empty($postArray['approve_journal'])) {
					//$postArray['approval_for'] = $logSession->id;
					$journalTransaction = $this->transaction->insertJournalTransaction($postArray,$cid,1);
					$auditId = $this->transaction->insertJournalAuditTransaction($postArray,$journalTransaction,1);
					$auditLog	  = $this->settings->insertAuditLog(1,5,'Jounral Entry',$auditId);
					$auditLog	  = $this->settings->insertAuditLog(6,5,'Jounral Entry',$journalTransaction);
				} 
				if($journalTransaction) {
					$sessSuccess = new Zend_Session_Namespace('insert_success_journal');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/journal/');
				} else {
						$this->view->error = 'journal Entries cannot be added. Kindly try again later';
				}
			}
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->location       =  $this->settings->getLocation();
			$this->view->payAccount		=  $this->transaction->getAllAccount();
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
			$this->view->fileuploadpath    =  $this->uploadPath.$cid."/journal/";
			$id = base64_decode($this->_getParam('id'));
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/journal');
			} else {
				$this->view->journal  =  $this->transaction->getJournalTransaction($id);
				if(!$this->view->journal) {
					$this->_redirect('transaction/journal');
				} else {
					$this->view->journalEntryList  =  $this->transaction->getJournalEntryList($id);
					if(!$this->view->journalEntryList) {
						$this->_redirect('transaction/journal');
					} 
				}
			}
			$this->view->approveUser	=  $this->settings->getApproveUsers();
			$this->view->location       =  $this->settings->getLocation();
			$this->view->payAccount		=  $this->transaction->getAllAccount();
		}
	}


	public function editAction() {
		if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			if(Zend_Session::namespaceIsset('update_success_journal')) {
				$this->view->success = 'Journal Entry Updated successfully';
				Zend_Session::namespaceUnset('update_success_journal');
			}
			$logSession = new Zend_Session_Namespace('sess_login');
			if(isset($logSession->proxy_cid) && !empty($logSession->proxy_cid)) {
				$cid = $logSession->proxy_cid;
			} else {
				$cid = $logSession->cid;
			}
			$this->view->fileuploadpath    =  $this->uploadPath.$cid."/journal/";
			$id = base64_decode($this->_getParam('id'));
			$this->view->journ_id = $id;
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/journal');
			} else {
				$this->view->journal  =  $this->transaction->getJournalTransaction($id);
				if(!$this->view->journal) {
					$this->_redirect('transaction/journal');
				} else {
					$this->view->journalEntryList  =  $this->transaction->getJournalEntryList($id);
					if(!$this->view->journalEntryList) {
						$this->_redirect('transaction/journal');
					} 
				}
			}
			//print_r($this->view->journal);
			if($this->_request->isPost()) {
				$postArray  		   = $this->getRequest()->getPost();

				/*$adapter    =  new Zend_File_Transfer_Adapter_Http();
				$fileInfo 	=  $adapter->getFileInfo('file'); 
				if(isset($fileInfo['file']['name']) && ($fileInfo['file']['name'] != '')) {
					$adapter->addValidator('Count', false, array('min' =>1, 'max' => 2))
					        ->addValidator('Size',false,array('max'=>2024000),'file')
							->addValidator('Extension',false,'pdf,jpg,doc,docx,png','file');
					$adapter->setDestination("..".$this->view->fileuploadpath,'file');
					$fileInfo 	         	  =   $adapter->getFileInfo('file');
					$fileArray		  		  =   explode('.',$fileInfo['file']['name']);
					$postArray['extension']   =   $fileArray['1'];
					$renameFile 		  	  =   trim($id."_".rand(10,10000)."_".$id.".".$fileArray['1']);
					$postArray['attach_file'] =   $renameFile;
					$adapter->addFilter('Rename',"..".$this->view->fileuploadpath.$renameFile);
						if ($adapter->isValid('file') && $adapter->receive('file')) {
							//unlink($this->view->fileuploadpath.$postArray['attachment']);
							$postArray['attach_file'] =   $renameFile;
						} else {
							$postArray['attach_file'] =  $postArray['attachment'];
						}
				} else {
					$postArray['attach_file'] =  $postArray['attachment'];
				}
*/

				$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
					$journalTransaction = $this->transaction->updateJournalTransaction($postArray,$id,2);
					$auditId = $this->transaction->insertJournalAuditTransaction($postArray,$id,2);
					$sendNotify		   = $this->sendMail($postArray['approval_for']);
					$auditLog	  = $this->settings->insertAuditLog(2,5,'Jounral Entry',$auditId);
				} else if(isset($postArray['approve_journal']) && !empty($postArray['approve_journal'])) {
					//$postArray['approval_for'] = $logSession->id;
					$journalTransaction = $this->transaction->updateJournalTransaction($postArray,$id,1);
					$auditId = $this->transaction->insertJournalAuditTransaction($postArray,$id,1);
					$auditLog	  = $this->settings->insertAuditLog(2,5,'Jounral Entry',$auditId);
					$auditLog	  = $this->settings->insertAuditLog(6,5,'Jounral Entry',$id);
				} 

				
				if($journalTransaction) {
					$sessSuccess = new Zend_Session_Namespace('update_success_journal');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/journal/edit/id/'.$this->_getParam('id'));
				} else {
						$this->view->error = 'Journal Entries cannot be updated. Kindly try again later';
				}
			}
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->location       =  $this->settings->getLocation();
			$this->view->payAccount		=  $this->transaction->getAllAccount();
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
			$this->view->fileuploadpath    =  $this->uploadPath.$cid."/journal/";
			$this->view->nextId 	 	   =  $this->transaction->getNextJournalTransaction();
			$id = base64_decode($this->_getParam('id'));
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/journal');
			} else {
				$this->view->journal  =  $this->transaction->getJournalTransaction($id);
				if(!$this->view->journal) {
					$this->_redirect('transaction/journal');
				} else {
					$this->view->journalEntryList  =  $this->transaction->getJournalEntryList($id);
					if(!$this->view->journalEntryList) {
						$this->_redirect('transaction/journal');
					} 
				}
			}
			if($this->_request->isPost()) {
				$postArray  = $this->getRequest()->getPost();
			/*	$adapter    =  new Zend_File_Transfer_Adapter_Http();
				$fileInfo 	=  $adapter->getFileInfo('file'); 
				if(isset($fileInfo['file']['name']) && ($fileInfo['file']['name'] != '')) {
					$adapter->addValidator('Count', false, array('min' =>1, 'max' => 2))
					        ->addValidator('Size',false,array('max'=>2024000),'file')
							->addValidator('Extension',false,'pdf,jpg,doc,docx,png','file');
					$adapter->setDestination("..".$this->view->fileuploadpath,'file');
					$fileInfo 	         	  =   $adapter->getFileInfo('file');
					$fileArray		  		  =   explode('.',$fileInfo['file']['name']);
					$postArray['extension']   =   $fileArray['1'];
					$renameFile 		  	  =   trim($this->view->nextId."_".rand(10,10000)."_".$this->view->nextId.".".$fileArray['1']);
					$postArray['attach_file'] =   $renameFile;
					$adapter->addFilter('Rename',"..".$this->view->fileuploadpath.$renameFile);
						if ($adapter->isValid('file') && $adapter->receive('file')) {
							$postArray['attach_file'] =   $renameFile;
						} else {
							$postArray['attach_file'] =  $postArray['attachment'];
						}
				} else {
					$postArray['attach_file'] =  $postArray['attachment'];
				}*/
				$postArray['date'] 	   = date("Y-m-d",strtotime(trim($postArray['date'])));
				if(isset($postArray['unapprove_save']) && !empty($postArray['unapprove_save'])) {
					$journalTransaction = $this->transaction->insertJournalTransaction($postArray,$cid,2);
					$auditId = $this->transaction->insertJournalAuditTransaction($postArray,$journalTransaction,2);
					$sendNotify		   = $this->sendMail($postArray['approval_for']);
					$auditLog	  = $this->settings->insertAuditLog(1,5,'Jounral Entry',$auditId);
				} else if(isset($postArray['approve_journal']) && !empty($postArray['approve_journal'])) {
					//$postArray['approval_for'] = $logSession->id;
					$journalTransaction = $this->transaction->insertJournalTransaction($postArray,$cid,1);
					$auditId = $this->transaction->insertJournalAuditTransaction($postArray,$journalTransaction,1);
					$auditLog	  = $this->settings->insertAuditLog(1,5,'Jounral Entry',$auditId);
					$auditLog	  = $this->settings->insertAuditLog(6,5,'Jounral Entry',$journalTransaction);
				} 
				if($journalTransaction) {
					$sessSuccess = new Zend_Session_Namespace('insert_success_journal');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/journal/');
				} else {
						$this->view->error = 'journal Entries cannot be added. Kindly try again later';
				}
			}
			$this->view->approveUser	=  $this->settings->getApproveUsers($cid);
			$this->view->location       =  $this->settings->getLocation();
			$this->view->payAccount		=  $this->transaction->getAllAccount();
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
				$bodyContent = 'Dear User, <br/> Journal Entry has been created by user '.$user.' and is awaiting for your approval. <a href='.$this->view->sitePath."default/notification/transactions".'>Click here </a> to approve the transaction.';
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
			if($ajaxVal['action']=='accountRefresh') {
					$this->payAccount		=  $this->transaction->getAllAccount();
					if($this->payAccount) {
						$jsonEncode = json_encode($this->payAccount);
						echo $jsonEncode;
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
		$this->view->filepath    =  $this->uploadPath.$cid."/journal/";
		$action = $this->_getParam('operation');

		if($action=='add') {
				$this->view->nextId 	 =  $this->transaction->getNextJournalTransaction();

				$uploader = new FileUpload('uploadfile');   
				$uploader->allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'doc');
				$uploader->sizeLimit = 10485760;
				$extension = $uploader->getExtension();
				$newfilename  = $this->view->nextId."_".rand(10,10000)."_journal.".$extension;
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
				$newfilename  = $fileid."_".rand(10,10000)."_journal.".$extension;
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
		$this->view->filepath    =  $this->uploadPath.$cid."/journal/";
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
		$this->fileuploadpath    =  $this->uploadPath.$cid."/journal/";
		if($this->_request->isXmlHttpRequest()) {
			if ($this->_request->isPost()) {
				$ajaxVal = $this->getRequest()->getPost();

				if($ajaxVal['action']=='save_draft_journal') {
					$ajaxVal['date'] 	 = date("Y-m-d",strtotime(trim($ajaxVal['date'])));

					/*$adapter    =  new Zend_File_Transfer_Adapter_Http();
					$fileInfo 	=  $adapter->getFileInfo('file'); 
					if(isset($fileInfo['file']['name']) && ($fileInfo['file']['name'] != '')) {
						$adapter->addValidator('Count', false, array('min' =>1, 'max' => 2))
						        ->addValidator('Size',false,array('max'=>2024000),'file')
								->addValidator('Extension',false,'pdf,jpg','file');
						$adapter->setDestination("..".$this->fileuploadpath,'file');
						$fileInfo 	         	  =   $adapter->getFileInfo('file');
						$fileArray		  		  =   explode('.',$fileInfo['file']['name']);
						$ajaxVal['extension']     =   $fileArray['1'];
						$renameFile 		  	  =   trim($cid."_".rand(10,10000)."_".$cid.".".$fileArray['1']);
						$postArray['attach_file'] =   $renameFile;
						$adapter->addFilter('Rename',"..".$this->fileuploadpath.$renameFile);
							if ($adapter->isValid('file') && $adapter->receive('file')) {
								$ajaxVal['attach_file'] =   $renameFile;
							} else {
								$ajaxVal['attach_file'] =   '';
							}
					} else {
						$ajaxVal['attach_file'] =   '';
					}*/
					$ajaxVal['attach_file'] =   '';
					$journalTransaction = $this->transaction->insertJournalTransaction($ajaxVal,$cid,3);
					$auditId = $this->transaction->insertJournalAuditTransaction($ajaxVal,$journalTransaction,3);
					$auditLog	  = $this->settings->insertAuditLog(8,5,'Jounral Entry',$auditId);
					if($journalTransaction) {
						$sessDraft = new Zend_Session_Namespace('sess_draft_journal_insert');
						$sessDraft->status = 1;
						echo "success";
					} else {
						echo "Failure";
					}
				} else if($ajaxVal['action']=='update_draft_journal') {
					$ajaxVal['date'] 	 = date("Y-m-d",strtotime(trim($ajaxVal['date'])));

					/*$adapter    =  new Zend_File_Transfer_Adapter_Http();
					$fileInfo 	=  $adapter->getFileInfo('file'); 
					if(isset($fileInfo['file']['name']) && ($fileInfo['file']['name'] != '')) {
						$adapter->addValidator('Count', false, array('min' =>1, 'max' => 2))
						        ->addValidator('Size',false,array('max'=>2024000),'file')
								->addValidator('Extension',false,'pdf,jpg','file');
						$adapter->setDestination("..".$this->view->fileuploadpath,'file');
						$fileInfo 	         	  =   $adapter->getFileInfo('file');
						$fileArray		  		  =   explode('.',$fileInfo['file']['name']);
						$ajaxVal['extension']     =   $fileArray['1'];
						$renameFile 		  	  =   trim($cid."_".rand(10,10000)."_".$cid.".".$fileArray['1']);
						$postArray['attach_file'] =   $renameFile;
						$adapter->addFilter('Rename',"..".$this->view->fileuploadpath.$renameFile);
							if ($adapter->isValid('file') && $adapter->receive('file')) {
								//unlink($this->view->fileuploadpath.$postArray['attachment']);
								$ajaxVal['attach_file'] =   $renameFile;
							} else {
								$ajaxVal['attach_file'] =  $ajaxVal['attachment'];
							}
					} else {
						$ajaxVal['attach_file'] =  $ajaxVal['attachment'];
					}*/
					$journalTransaction = $this->transaction->updateJournalTransaction($ajaxVal,$ajaxVal['journal_id'],3);
					$auditId = $this->transaction->insertJournalAuditTransaction($ajaxVal,$ajaxVal['journal_id'],3);
					$auditLog	  = $this->settings->insertAuditLog(8,5,'Jounral Entry',$auditId);
					if($journalTransaction) {
						$sessDraft = new Zend_Session_Namespace('sess_draft_journal_insert');
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