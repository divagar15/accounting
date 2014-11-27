<?php 
/**
* Helper
* 
* Render header menu
*/
class Zend_View_Helper_RenderHeaderMenu
{
	/**
	* @var array associative array of header tabs.
	*/
	public 	$menus = array (	'index'					=> 'Accueil', 
							'location/index/search'	=> 'Locations', 
							'vente/index/search-vente'	=> 'Ventes', 
							'location_vacances/index/search'				=> 'Locations vacances',
							'echanges-de-biens/index/search'				=> 'Echange de biens',
							'annonces'				=> 'Annoncer'
				);
	/**
	* Constrcutor
	* 
	* @return string header tab table construction
	*/
	public function RenderHeaderMenu($module)
	{
		$sitePath = Zend_Registry::get('sitePath');
		$imagePath = Zend_Registry::get('siteImagePath');
		$menuHeader = '';
		$currentMenu = $this->getSelectedMenu();
		$currentPage = $this->getSelectedPage();
		$userSession =  Zend_Session::namespaceGet ('user');
		//$languageSession = new Zend_Session_Namespace ('sessionLanguage');
		//echo '<br>==>' .$languageSession->language;
		try {
			$this->translator = new Zend_View_Helper_Translate(Zend_Registry::get('Zend_Translate'));
		} catch (Exception $exception) {
			//echo '<br>==>' . $exception->getMessage();
		}
		
		$front = Zend_Controller_Front::getInstance();
		// Render annonces tab for annonce postings [location, vente]
		if ((strlen(strpos($front->getRequest()->getActionName(), 'search')) == 0) && (($currentMenu == 'location') || ($currentMenu == 'vente') || ($currentMenu == 'location_vacances') || ($currentMenu == 'echanges-de-biens')))
			$currentMenu = 'annonces';
		Zend_Registry::set('CurrentMenu', $currentMenu);
		$viewCache = array ('annonces-detail', 'annonces-location-vacances-detail', 'annonces-echanges-de-biens-detail',
									'annonces-colocation-detail');
		if (in_array ($front->getRequest()->getActionName(), $viewCache)) {
			if (isset ($userSession['user']->Id)){
				$fkProfilId = Zend_Registry::get('fkProfilId');
				if ($fkProfilId != $userSession['user']->Id){
					($currentPage == 'annonces-detail' && ($front->getRequest()->getParam('type') == '4' || $front->getRequest()->getParam('type') == '6' || $front->getRequest()->getParam('type') == '7')) ? $currentMenu = 'vente' : '';
					($currentPage == 'annonces-detail' && ($front->getRequest()->getParam('type') == '1' || $front->getRequest()->getParam('type') == '3')) ? $currentMenu = 'location' : '';
					($currentPage == 'annonces-colocation-detail') ? $currentMenu = 'location' : '';
					($currentPage == 'annonces-location-vacances-detail') ? $currentMenu = 'location_vacances' : '';
					($currentPage == 'annonces-echanges-de-biens-detail') ? $currentMenu = 'echanges-de-biens' : '';
				} else {
					$currentMenu = 'accueil';
				}
			} else {
				($currentPage == 'annonces-detail' && ($front->getRequest()->getParam('type') == '4' || $front->getRequest()->getParam('type') == '6' || $front->getRequest()->getParam('type') == '7')) ? $currentMenu = 'vente' : '';
				($currentPage == 'annonces-detail' && ($front->getRequest()->getParam('type') == '1' || $front->getRequest()->getParam('type') == '3')) ? $currentMenu = 'location' : '';
				($currentPage == 'annonces-colocation-detail') ? $currentMenu = 'location' : '';
				($currentPage == 'annonces-location-vacances-detail') ? $currentMenu = 'location_vacances' : '';
				($currentPage == 'annonces-echanges-de-biens-detail') ? $currentMenu = 'echanges-de-biens' : '';
			}
		}
		($currentPage == 'contact' || $currentPage == 'quisommesnous' || $currentPage == 'cgu' || $currentPage == 'user-annonces') ? $currentMenu = 'vente' : '';
		$depotAnnonceModules = array ('location', 'vente', 'location_vacances', 'echanges-de-biens');
		$depotAnnonceActions = array ('index', 'voir', 'colocation', 'colocation-voir', 'temporaires');
		if ($module == 1) {
			$menuHeader = '<ul id="tabs">
								' . '<li >' . (($currentMenu == 'vente' || $currentPage == 'parrainage' || $currentPage == 'parrainage-success' || $currentPage == 'toptenannonces') ? '<a href="'.$sitePath.'vente/index/search-vente" title="' . $this->translator->translate('Sale') . '" class="vente_sel"></a>' : '<a href="'.$sitePath.'vente/index/search-vente" class="vente" onmouseover="this.className=\'vente_sel\'" onmouseout="this.className=\'vente\'" title="' . $this->translator->translate('Sale') . '"></a>' ) . '</li>
								' . '<li>' . (($currentMenu == 'location') ? '<a href="'.$sitePath.'location/index/search" class="location_sel" title="' . $this->translator->translate('Location') . '"></a>' : '<a href="'.$sitePath.'location/index/search" class="location" title="' . $this->translator->translate('Location') . '" onmouseover="this.className=\'location_sel\'" onmouseout="this.className=\'location\'"></a>' ) . '</li>
								' . '<li>' . (($currentMenu == 'location_vacances') ? '<a href="'.$sitePath.'location_vacances/index/search" class="locationvancaces_sel" title="' . $this->translator->translate('VacationRentals') . '"></a>' : '<a href="'.$sitePath.'location_vacances/index/search" class="locationvancaces" onmouseover="this.className=\'locationvancaces_sel\'" onmouseout="this.className=\'locationvancaces\'"  title="' . $this->translator->translate('VacationRentals') . '"></a>' ) . '</li>
								' .'<li class="echangedebiens_leftspace"></li>
								' . '<li>' . (($currentMenu == 'echanges-de-biens') ? '<a href="'.$sitePath.'echanges-de-biens/index/search" class="echangedebiens_sel" title="' . $this->translator->translate('GoodsExchange') . '"></a>' : '<a href="'.$sitePath.'echanges-de-biens/index/search" class="echangedebiens" onmouseover="this.className=\'echangedebiens_sel\'" onmouseout="this.className=\'echangedebiens\'" title="' . $this->translator->translate('GoodsExchange') . '"></a>' ) . '</li>
								' .'<li class="echangedebiens_rightcorner"></li>
							</ul>';
		}
		else{
			if( ($currentMenu!='payment') || ($currentMenu=='payment' && $currentPage=='success') ) {
				$this->session = Zend_Session::namespaceGet ('user');
				$deposerArray = array ('index', 'colocation', 'voir', 'colocation-voir', 'annonces-complete', 'temporaires');
				if (isset ($this->session->user)){
					//Menu here........
					$menuHeader = '
					<ul id="EI_Menu">
						<li><a href="'.$sitePath.'index/accueil/render/' . base64_encode (1) . '" title="' . $this->translator->translate('MyAccount') . '" class="' . (($front->getRequest()->getParam('render') == base64_encode (1) || (strpos ($_SERVER['REQUEST_URI'], 'accueil') != false && $front->getRequest()->getParam('render') == '')) ? 'menulink_sel' : 'menulink' ) . '">' . $this->translator->translate('MyAccount') . '</a></li>';
					if ($this->session['user']->ProfilType == 1){
						$menuHeader .= '<li><a href="'.$sitePath.'index/accueil/render/' . base64_encode (2) . '" title="' . $this->translator->translate('MyInfo') . '" class="' . (($front->getRequest()->getParam('render') == base64_encode (2)) ? 'menulink_sel' : 'menulink' ) . '">' . $this->translator->translate('MyInfo') . '</a></li>';
					} else {
						$menuHeader .= '<li><a href="'.$sitePath.'index/accueil/render/' . base64_encode (3) . '" title="' . $this->translator->translate('MyInfo') . '" class="' . (($front->getRequest()->getParam('render') == base64_encode (3)) ? 'menulink_sel' : 'menulink' ) . '">' . $this->translator->translate('MyInfo') . '</a></li>';
					}
					$menuHeader .= '<li><a href="'.$sitePath.'index/accueil/render/' . base64_encode (4) . '" title="' . $this->translator->translate('MyAds') . '" class="' . (($front->getRequest()->getParam('render') == base64_encode (4)) ? 'menulink_sel' : 'menulink' ) . '">' . $this->translator->translate('MyAds') . '</a></li>
						<li><a href="'.$sitePath.'index/accueil/render/' . base64_encode (5) . '" title="' . $this->translator->translate('MaSelection') . '" class="' . (($front->getRequest()->getParam('render') == base64_encode (5)) ? 'menulink_sel' : 'menulink' ) . '">' . $this->translator->translate('MaSelection') . '</a></li>';
					
					if ($this->session['user']->ProfilType == 2) {
						$menuHeader .= '<li><a id="MyMessages" href="'.$sitePath.'messegerie/" style="display:block;" title="' . $this->translator->translate('MyMessages') . '" class="' . ((strpos ($_SERVER['REQUEST_URI'], 'messegerie') != false) ? 'menulink_sel' : 'menulink' ) . '">' . $this->translator->translate('MyMessages') . '</a></li>';
					} else {
						$myMessageTotalCount 	= ($this->session['user']->inboxCount) + ($this->session['user']->trashboxCount);
						$mesAnnonceTotalCount = 0;
						
						if (isset ($this->session['user']->mesAnnonces))
							$mesAnnonceTotalCount 	= $this->session['user']->mesAnnonces;
						
						if ( $myMessageTotalCount >0 && $mesAnnonceTotalCount> 0)
							$menuHeader .= '<li><a id="MyMessages" href="'.$sitePath.'messegerie/" style="display:block;" title="' . $this->translator->translate('MyMessages') . '" class="' . ((strpos ($_SERVER['REQUEST_URI'], 'messegerie') != false) ? 'menulink_sel' : 'menulink' ) . '">' . $this->translator->translate('MyMessages') . '</a></li>';
						else $menuHeader .= '<li><a id="MyMessages" href="'.$sitePath.'messegerie/" class="menu_animate" style="display:none;" title="' . $this->translator->translate('MyMessages') . '">' . $this->translator->translate('MyMessages') . '</a></li>';
					}
					$menuHeader .= '
						<li><a href="'.$sitePath.'index/accueil/render/' . base64_encode (6) . '" title="' . $this->translator->translate('MyAlert') . '" class="' . (($front->getRequest()->getParam('render') == base64_encode (6)) ? 'menulink_sel' : 'menulink' ) . '">' . $this->translator->translate('MyAlert') . '</a></li>
						<li class="mesalertes" style="width:198px;float:right">
						<div class="deposerlink_sel" title="'. $this->translator->translate('DeposerTitle') .'" onmouseout="return hide_moncompteOnly(0);" onmouseover="return deposer_moncompteOnly();" id="DeposertabUnsel"';
						if (in_array ($front->getRequest()->getActionName(), $deposerArray)){
						$menuHeader .= ' style="display:none;"';
						}
						$menuHeader .= '>' . $this->translator->translate('DeposerTitle') . '</div>
							<div class="deposerlink_sel" title="' . $this->translator->translate('DeposerTitle') . '" onmouseout="return hide_moncompteOnly(1);" onmouseover="return deposer_moncompteOnly();"';
						if (!in_array ($front->getRequest()->getActionName(), $deposerArray)){
						$menuHeader .= ' style="display:none;"';
						}
						$menuHeader .= ' id="Deposertabsel">' . $this->translator->translate('DeposerTitle') . '</div>
							<div id="deposermenu_moncompteOnly" onmouseout="return hide_moncompteOnly(' . ((in_array ($front->getRequest()->getActionName(), $deposerArray)) ? 1 : 0) . ');" onmouseover="return deposer_moncompteOnly();">
								<div id="deposermenuInner">
									<div class="Innercontent_blue" style="width:193px"></div>
									<div class="deposerList">
										<ul class="DeposeruneList" style="margin-left:0px;padding:0px;">
											<li class="desc"><a href="'.$sitePath.'vente/index/index/typeDeAnnonce/4" class="accdeposte" title="' . $this->translator->translate('Sale') . '">' . $this->translator->translate('Sale') . '</a></li>
											<li class="desc"><a href="'.$sitePath.'vente/index/index/typeDeAnnonce/6" class="accdeposte" title="' . $this->translator->translate('PrestigeSales') . '">' . $this->translator->translate('PrestigeSales') . '</a></li>
											<li class="desc"><a href="'.$sitePath.'vente/index/index/typeDeAnnonce/7" class="accdeposte" title="' . $this->translator->translate('Viager') . '">' . $this->translator->translate('Viager') . '</a></li>
											<li class="desc cc"><a href="'.$sitePath.'location/" class="accdeposte" title="' . $this->translator->translate('Location') . '">' . $this->translator->translate('Location') . '</a></li>
											<li class="desc"><a href="'.$sitePath.'location/index/colocation" class="accdeposte" title="' . $this->translator->translate('Colocation') . '">' . $this->translator->translate('Colocation') . '</a></li>
											<li class="desc"><a href="'.$sitePath.'location/index/temporaires" class="accdeposte" title="' . $this->translator->translate('Rentals') . '">' . $this->translator->translate('Rentals') . '</a></li>
											<li class="desc cc"><a href="'.$sitePath.'location_vacances/" class="accdeposte" title="' . $this->translator->translate('VacationRentals') . '">' . $this->translator->translate('VacationRentals') . '</a></li>
											<li class="desc cc"><a href="'.$sitePath.'echanges-de-biens/" class="accdeposte" title="' . $this->translator->translate('GoodsExchange') . '">' . $this->translator->translate('GoodsExchange') . '</a></li>
											<li class="clear"></li>
										</ul>
									</div>
								</div>
							</div>
						</li>
						
						
					</ul>';
				}
			}
		}
		return $menuHeader;
	}

	/**
	* Gets the current selected tab of the header
	* 
	* @return string
	*/
	public function getSelectedMenu()
	{
		$front = Zend_Controller_Front::getInstance();
	    return $front->getRequest()->getModuleName();
	}
	/**
	* Gets the current selected page for the header tab
	*
	* @return string
	*/
	public function getSelectedPage()
	{
		$front = Zend_Controller_Front::getInstance();
	    return $front->getRequest()->getActionName();
	}
}?>