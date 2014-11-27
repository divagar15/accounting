<?php 
class Transaction_FixedAssetsController extends Zend_Controller_Action {
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
			if(Zend_Session::namespaceIsset('insert_success_fixed')) {
				$this->view->success = 'Fixed Assets Added successfully';
				Zend_Session::namespaceUnset('insert_success_fixed');
			}
			if(Zend_Session::namespaceIsset('update_success_fixed')) {
				$this->view->success = 'Fixed Assets Updated successfully';
				Zend_Session::namespaceUnset('update_success_fixed');
			}
			if(Zend_Session::namespaceIsset('delete_success_fixed')) {
				$this->view->success = 'Fixed Assets deleted successfully';
				Zend_Session::namespaceUnset('delete_success_fixed');
			}
			if(Zend_Session::namespaceIsset('delete_error_fixed')) {
				$this->view->error = 'Fixed Assets cannot be deleted because it was started posting journals as per the depreciation schedule';
				Zend_Session::namespaceUnset('delete_error_fixed');
			}
			if(Zend_Session::namespaceIsset('verify_success_fixed_transaction')) {
				$this->view->success = 'Fixed Assets Approved successfully';
				Zend_Session::namespaceUnset('verify_success_fixed_transaction');
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

				$deleteStatus = $this->transaction->deleteFixedAssetsTransaction($delid,2);
				if($deleteStatus) {
					$sessSuccess = new Zend_Session_Namespace('delete_success_fixed');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/fixedassets?location='.$location1.'&financial_year='.$financeSet1);
				} else {
					$sessError = new Zend_Session_Namespace('delete_error_fixed');
					$sessError->status = 1;
					$this->_redirect('transaction/fixedassets?location='.$location1.'&financial_year='.$financeSet1);
				}
			}
			$getAccountArray            =  $this->accountData->getData(array('faYearsArray','faFrequencyArray'));
			$this->view->faYears        =  $getAccountArray['faYearsArray'];
			$this->view->faFrequency    =  $getAccountArray['faFrequencyArray'];
			$this->view->location       =  $this->settings->getLocation();
			$this->view->locations      =  $this->settings->getLocations();
			$this->view->finance        =  $this->settings->getFinanceYears();
			$this->view->vendor 		=  $this->transaction->getVendorDetails();
			$this->view->result 		=  $this->transaction->getFixedTransaction($id='',$sort,$location,$financeSet);
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

			$id = base64_decode($this->_getParam('id'));
			if(!isset($id) || $id=='') {
				$this->_redirect('transaction/fixedassets');
			} else {
				$this->view->expense  =  $this->transaction->getFixedExpenseTransaction($id);
				if(!$this->view->expense) {
					$this->_redirect('transaction/fixedassets');
				} 
			}


			if($this->_request->isPost()) {
				$postArray  = $this->getRequest()->getPost();

				$postArray['date'] 	= date("Y-m-d",strtotime(trim($postArray['date'])));

				$postArray['price'] = str_replace(',', '', $postArray['purchase_price']);

				$splitCoa           = explode('_', $postArray['fa_coa']);

				$postArray['coa']   = $splitCoa[0];


				$fixedTransaction   =  $this->transaction->insertFixedAssetTransaction($postArray,$cid,$this->view->fend);   


				
				if($fixedTransaction) {
					$sessSuccess = new Zend_Session_Namespace('insert_success_fixed');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/fixedassets/');
				} else {
						$this->view->error = 'Fixed Assets cannot be added. Kindly try again later';
				}
			}
			$getAccountArray            =  $this->accountData->getData(array('faYearsArray','faFrequencyArray'));
			$this->view->faYears        =  $getAccountArray['faYearsArray'];
			$this->view->faFrequency    =  $getAccountArray['faFrequencyArray'];
			$this->view->location       =  $this->settings->getLocation();
			$this->view->finance        =  $this->settings->getFinanceYears();
			$this->view->vendor 		=  $this->transaction->getVendorDetails();
			$this->view->fixedAccount	=  $this->transaction->getFixedAccount();
		}
	}



	public function createAction() {
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
				$postArray  = $this->getRequest()->getPost();

				$postArray['date'] 	= date("Y-m-d",strtotime(trim($postArray['date'])));

				$postArray['price'] = str_replace(',', '', $postArray['purchase_price']);

				$splitCoa           = explode('_', $postArray['fa_coa']);

				$postArray['coa']   = $splitCoa[0];

				$fixedTransaction   =  $this->transaction->insertFixedAssetTransaction($postArray,$cid,$this->view->fend);   


				
				if($fixedTransaction) {
					$sessSuccess = new Zend_Session_Namespace('insert_success_fixed');
					$sessSuccess->status = 1;
					$this->_redirect('transaction/fixedassets/');
				} else {
						$this->view->error = 'Fixed Assets cannot be added. Kindly try again later';
				}
			}
			$getAccountArray            =  $this->accountData->getData(array('faYearsArray','faFrequencyArray'));
			$this->view->faYears        =  $getAccountArray['faYearsArray'];
			$this->view->faFrequency    =  $getAccountArray['faFrequencyArray'];
			$this->view->location       =  $this->settings->getLocation();
			$this->view->finance        =  $this->settings->getFinanceYears();
			$this->view->vendor 		=  $this->transaction->getVendorDetails();
			$this->view->fixedAccount	=  $this->transaction->getFixedAccount();
		}
	}




	public function editAction() {
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
				$this->_redirect('transaction/fixedassets');
			} else {
				$this->view->expense  =  $this->transaction->getFixedTransaction($id);
				if(!$this->view->expense) {
					$this->_redirect('transaction/fixedassets');
				}  else {
					$accumulated = $this->transaction->getDepnSchedule($id,1);
					if($accumulated) {
						$this->view->accumulated = $accumulated;
					} else {
						$this->view->accumulated = 0;
					}
					
				}
			}


			if($this->_request->isPost()) {
				$postArray  = $this->getRequest()->getPost();

				if(isset($postArray['dispose_type'])) {

					$postArray['dispose_date'] 	 = date("Y-m-d",strtotime(trim($postArray['dispose_date'])));
					$postArray['dispose_amount'] = str_replace(',', '', $postArray['dispose_amount']);
					$postArray['netdispose']     = str_replace(',', '', $postArray['netdispose']);
					$postArray['accdepn']        = str_replace(',', '', $postArray['accdepn']);
					$postArray['total']          = str_replace(',', '', $postArray['total']);
					$postArray['pl']             = $postArray['dispose_amount']-$postArray['netdispose'];

					$fixedTransaction   =  $this->transaction->updateFixedAssetDisposalTransaction($postArray,$cid,$this->view->fend);   


					
					if($fixedTransaction) {
						$sessSuccess = new Zend_Session_Namespace('update_success_fixed');
						$sessSuccess->status = 1;
						$this->_redirect('transaction/fixedassets/');
					} else {
							$this->view->error = 'Fixed Assets cannot be added. Kindly try again later';
					}

				} else {

					$postArray['date'] 	= date("Y-m-d",strtotime(trim($postArray['date'])));

					$postArray['price'] = str_replace(',', '', $postArray['purchase_price']);

					$splitCoa           = explode('_', $postArray['fa_coa']);

					$postArray['coa']   = $splitCoa[0];


					$fixedTransaction   =  $this->transaction->updateFixedAssetTransaction($postArray,$cid,$this->view->fend);   


					
					if($fixedTransaction) {
						$sessSuccess = new Zend_Session_Namespace('update_success_fixed');
						$sessSuccess->status = 1;
						$this->_redirect('transaction/fixedassets/');
					} else {
							$this->view->error = 'Fixed Assets cannot be added. Kindly try again later';
					}

				}

				
			}
			$getAccountArray            =  $this->accountData->getData(array('faYearsArray','faFrequencyArray'));
			$this->view->faYears        =  $getAccountArray['faYearsArray'];
			$this->view->faFrequency    =  $getAccountArray['faFrequencyArray'];
			$this->view->location       =  $this->settings->getLocation();
			$this->view->finance        =  $this->settings->getFinanceYears();
			$this->view->vendor 		=  $this->transaction->getVendorDetails();
			$this->view->fixedAccount	=  $this->transaction->getFixedAccount();
			$this->view->cashAccount	=  $this->transaction->getCashAccount();
			$this->view->depnsch        =  $this->transaction->getDepnSchedule($id,2);
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
				if($ajaxVal['action']=='check_faid') {
					$checkFaid = $this->transaction->checkFixedFaid($ajaxVal['faid']);
					if($checkFaid) {
						echo "2";
					} else {
						echo "1";
					}
				}  else if($ajaxVal['action']=='check_faid_update') {
					$checkFaid = $this->transaction->checkFixedFaid($ajaxVal['faid'],$ajaxVal['id']);
					if($checkFaid) {
						echo "2";
					} else {
						echo "1";
					}
				}
			}
		}
	}

   public function viewReportAction() { 
	   if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			$fromdate = date('Y-m-d', strtotime($this->_getParam('fromdate')));
			$todate = date('Y-m-d', strtotime($this->_getParam('todate')));
			$branchCode =$this->_getParam('branch');
			$fa_coa_id =$this->_getParam('id');
			$prev_date = date('Y-m-d', strtotime($fromdate .' -1 day'));
			$last_date = date('Y-m-d', strtotime($todate .' +1 day'));
			$Coaname   =  $this->transaction->viewFixedAssetCoaNameTransaction($fa_coa_id);   
			if(!empty($branchCode)) { 
				$branchname   =  $this->transaction->viewFixedAssetbaranchNameTransaction($branchCode);   
			} else { 
				$branchname = '';
			}
			$fixedAllTransaction   =  $this->transaction->viewFixedAssetTotalReportTransaction($fromdate,$todate,$branchCode,$fa_coa_id,$prev_date,$last_date);   
			$this->view->coaName = $Coaname;
			$this->view->branchName = $branchname;
			$this->view->fromDate = date('d.m.Y',strtotime($fromdate));
			$this->view->toDate = date('d.m.Y',strtotime($todate));
			$this->view->prevDate = date('d.m.Y',strtotime($prev_date));
			$this->view->lastDate = date('d.m.Y',strtotime($last_date));
			$this->view->allReports = $fixedAllTransaction;	 
		 			 
		}
   }
    public function reportAction() {
    	error_reporting(E_ALL & ~E_NOTICE);
	   if(!Zend_Session::namespaceIsset('sess_login')) {
			 $this->_redirect('index');
		} else {
			if($this->_request->isPost()) { 
					$postArray  = $this->getRequest()->getPost(); 
					$fromdate = date('Y-m-d', strtotime($postArray['fromdate']));
					$todate = date('Y-m-d', strtotime($postArray['todate']));
					$branchCode = $postArray['location'];				
			} else {
					$todate = date('Y-m-d');
					$fromdate = date('Y-m-d', strtotime($todate .' -1 month'));				   
				    $branchCode = '';
			}
			$prev_date = date('Y-m-d', strtotime($fromdate .' -1 day'));
			$last_date = date('Y-m-d', strtotime($todate .' +1 day')); 
			$reportTransaction   =  $this->transaction->FixedAssetReportTransaction($fromdate,$todate,$branchCode,$prev_date,$last_date);  
			$this->view->fromDate = date('d-m-Y',strtotime($fromdate));
			$this->view->toDate = date('d-m-Y',strtotime($todate));
			$this->view->prevDate = date('d-m-Y',strtotime($prev_date));
			$this->view->lastDate = date('d-m-Y',strtotime($last_date));
			$this->view->branchCode = $branchCode;
			$this->view->locations       = $this->transaction->getLocations();
			$this->view->allReports = $reportTransaction; 			 
		}
   }
   	
}

?>