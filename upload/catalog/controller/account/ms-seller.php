<?php

class ControllerAccountMsSeller extends Controller {
	private $name = 'ms-seller';
	private $seller;
	
	public function __construct($registry) {
		parent::__construct($registry);
		
		$seller_account_status = 1;
		
		// commented out for testing purposes
		/*
    	if (!$this->seller->isLogged()) {
	  		$this->session->data['redirect'] = $this->url->link('account/ms-seller', '', 'SSL');
	  		$this->redirect($this->url->link('account/login', '', 'SSL')); 
    	} else if (!$this->seller->isSeller()) {
    		// redirect to seller info edit page
    	}
		*/
		
		$this->document->addStyle('catalog/view/theme/' . $this->config->get('config_template') . '/stylesheet/multiseller.css');
		$this->data = array_merge($this->data, $this->load->language('module/multiseller'),$this->load->language('account/account'));
		
		
		
		//$config = $this->registry->get('config');
		$this->load->config('ms-config');
		
		$this->seller =& $this->customer;
		
		$parts = explode('/', $this->request->get['route']);
		if ($seller_account_status !== 1 && $parts[2] != 'sellerstatus') {
			$this->redirect($this->url->link('account/ms-seller/sellerstatus', '', 'SSL'));
		}
	}
	
	private function _setBreadcrumbs($textVar, $function) {
      	$this->data['breadcrumbs'] = array();

      	$this->data['breadcrumbs'][] = array(
        	'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home'),     	
        	'separator' => false
      	); 

      	$this->data['breadcrumbs'][] = array(
        	'text'      => $this->language->get('text_account'),
			'href'      => $this->url->link('account/account', '', 'SSL'),        	
        	'separator' => $this->language->get('text_separator')
      	);

      	$this->data['breadcrumbs'][] = array(
        	'text'      => $this->language->get($textVar),
			'href'      => $this->url->link("account/{$this->name}/" . strtolower($function), '', 'SSL'),       	
        	'separator' => $this->language->get('text_separator')
      	);
	}
	
	private function _renderTemplate($templateName) {
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . "/template/module/multiseller/$templateName.tpl")) {
			$this->template = $this->config->get('config_template') . "/template/module/multiseller/$templateName.tpl";
		} else {
			$this->template = "default/template/module/multiseller/$templateName.tpl";
		}
		
		$this->children = array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'	
		);

		$this->response->setOutput($this->render());
	}
	
	public function jxSaveProduct() {
		//var_dump($this->request->post);
	}
	
	public function jxSaveSellerInfo() {
		$this->load->model('module/multiseller/seller');
		//require_once(DIR_APPLICATION . 'model/module/multiseller/validator.php');
		$data = $this->request->post;
		/*$data = $this->request->post;
		
		var_dump($data);
		$validator = new MsValidator($data);
		
		$validator->isEmpty('sellerinfo_nickname', 'error');
		
		$errors = $validator->getErrors();
		
		var_dump($data);
		//var_dump($errors);

		return;*/
		
		$json = array();
		
		if (empty($data['sellerinfo_nickname'])) {
			$json['errors']['sellerinfo_nickname'] = 'Display name cannot be empty'; 
		} else if (!ctype_alnum($data['sellerinfo_nickname'])) {
			$json['errors']['sellerinfo_nickname'] = 'Display name can only contain alphanumeric characters';
		} else if (strlen($data['sellerinfo_nickname']) < 4 || strlen($data['sellerinfo_nickname']) > 50 ) {
			$json['errors']['sellerinfo_nickname'] = 'Display name should be between 4 and 50 characters';			
		} else if ($this->model_module_multiseller_seller->nicknameTaken($data['sellerinfo_nickname'])) {
			$json['errors']['sellerinfo_nickname'] = 'This display name is already taken';
		}
		
		if (strlen($data['sellerinfo_company']) > 50 ) {
			$json['errors']['sellerinfo_company'] = 'Company name cannot be longer than 50 characters';			
		}		
		
		
		if (empty($json['errors'])) {
			// 1 = active, 0 = inactive
			if (!$this->config->get('msconf_seller_validation')) {
				$data['seller_status_id'] = 1;				
			} else {
				$data['seller_status_id'] = 0;
			}
			
			$data['avatar_path'] = '';
			$this->model_module_multiseller_seller->saveSellerData($data);
		}
		
		if (strcmp(VERSION,'1.5.1.3') >= 0) {
			$this->response->setOutput(json_encode($json));
		} else {
			$this->load->library('json');
			$this->response->setOutput(Json::encode($json));			
		}
	}

	public function sellerStatus() {
		$this->load->model('module/multiseller/seller');
		$this->document->setTitle($this->language->get('ms_account_status_heading'));
		
		$seller = $this->registry->get('seller');
		
		$this->data['thankyou'] = sprintf($this->language->get('ms_account_sellerinfo_mail_account_thankyou'), $this->config->get('config_name'));
		
		switch ($seller->getStatus()) {
			case MS_SELLER_STATUS_TOBEACTIVATED:
				$this->data['status'] = $this->language->get('ms_account_status_activation');
				$this->data['message1'] = $this->language->get('ms_account_status_pleaseactivate');
				break;
			case MS_SELLER_STATUS_TOBEAPPROVED:
				$this->data['status'] = $this->language->get('ms_account_status_approval');
				$this->data['message1'] = $this->language->get('ms_account_status_willbeapproved');
				break;
			case MS_SELLER_STATUS_ACTIVE:
			default:
				$this->data['status'] = $this->language->get('ms_account_status_active');
				$this->data['message1'] = $this->language->get('ms_account_status_fullaccess');
				break;
		}
		
		$this->data['continue'] = $this->url->link('account/account', '', 'SSL');		
		$this->_setBreadcrumbs('ms_account_status_breadcrumbs', __FUNCTION__);
		$this->_renderTemplate('ms-account-sellerstatus');
	}
		
	//
	public function newProduct() {
		$this->load->model('module/multiseller/seller');
		$this->data['categories'] = $this->model_module_multiseller_seller->getCategories(0);

		$this->data['product'] = FALSE;

		$this->data['heading'] = $this->language->get('ms_account_newproduct_heading');
		$this->document->setTitle($this->language->get('ms_account_newproduct_heading'));
		$this->_setBreadcrumbs('ms_account_newproduct_breadcrumbs', __FUNCTION__);
		$this->_renderTemplate('ms-account-newproduct');
	}
	
	public function products() {
		$this->load->model('module/multiseller/seller');

		$page = isset($this->request->get['page']) ? $this->request->get['page'] : 1;

		$sort = array(
			'order_by'  => 'date_added',
			'order_way' => 'DESC',
			'page' => $page,
			'limit' => 5
		);

		$seller_id = $this->seller->getId();
		
		
		$products = $this->model_module_multiseller_seller->getSellerProducts($seller_id, $sort);
		
		foreach ($products as &$product) {
			$product['edit_link'] = $this->url->link('account/ms-seller/editproduct', 'product_id=' . $product['product_id'], 'SSL');
			$product['delete_link'] = $this->url->link('account/ms-seller/deleteproduct', 'product_id=' . $product['product_id'], 'SSL');
		}
		
		$this->data['products'] = $products; 
		$pagination = new Pagination();
		$pagination->total = $this->model_module_multiseller_seller->getTotalSellerProducts($seller_id);
		$pagination->page = $sort['page'];
		$pagination->limit = $sort['limit']; 
		$pagination->text = $this->language->get('text_pagination');
		$pagination->url = $this->url->link('account/' . $this->name . '/' . __FUNCTION__, 'page={page}', 'SSL');
		
		$this->data['pagination'] = $pagination->render();
		$this->data['continue'] = $this->url->link('account/account', '', 'SSL');
		
		$this->document->setTitle($this->language->get('ms_account_products_heading'));		
		$this->_setBreadcrumbs('ms_account_products_breadcrumbs', __FUNCTION__);		
		$this->_renderTemplate('ms-account-products');
	}
	
	public function editProduct() {
		$this->load->model('module/multiseller/seller');
		$this->data['categories'] = $this->model_module_multiseller_seller->getCategories(0);		
		
		$product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;
		$seller_id = $this->seller->getId();
		
    	$this->data['product'] = $this->model_module_multiseller_seller->getProduct($product_id,$seller_id);		
		
		var_dump($this->data['product']);
		
		$this->data['heading'] = $this->language->get('ms_account_editproduct_heading');
		$this->document->setTitle($this->language->get('ms_account_editproduct_heading'));		
		$this->_setBreadcrumbs('ms_account_editproduct_breadcrumbs', __FUNCTION__);		
		$this->_renderTemplate('ms-account-newproduct');
	}
	

	//
	public function sellerInfo() {
		$this->load->model('module/multiseller/seller');

		$this->load->model('localisation/country');
    	$this->data['countries'] = $this->model_localisation_country->getCountries();		

		$this->document->setTitle($this->language->get('ms_account_sellerinfo_heading'));
		$this->_setBreadcrumbs('ms_account_sellerinfo_breadcrumbs', __FUNCTION__);		
		$this->_renderTemplate('ms-account-sellerinfo');
	}
	
	public function transactions() {
		$this->_setBreadcrumbs('text_account_transactions', __FUNCTION__);		
		$this->_renderTemplate('ms-transactions');
	}
	
	public function requestMoney() {
		$this->_setBreadcrumbs('text_account_requestmoney', __FUNCTION__);		
		$this->_renderTemplate('ms-requestmoney');
	}

	public function index() {
		$this->load->language("module/{$this->name}");
		$this->load->model("module/{$this->name}");
		$this->load->model('setting/setting');
		
		foreach($this->settings as $s=>$v) {
			$this->data[$s] = $this->config->get($s);
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			if (isset($this->request->post['saveComment'])) {
				
	        } else if (isset($this->request->post['delComment'])) {
	        	
	        } else if (isset($this->request->post['saveConfig']) || isset($this->request->post['submitPositions'])) {
	        	
        	}
	        $this->session->data['success'] = $this->language->get('text_success');
		}
		
 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		$this->setBreadcrumbs();
		$this->setTranslations();
				
        $this->data['action'] = $this->url->link("module/{$this->name}", 'token=' . $this->session->data['token'], 'SSL');
		$this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
		
		$this->data['token'] = $this->session->data['token'];
		$this->load->model('design/layout');
		$this->data['layouts'] = $this->model_design_layout->getLayouts();
		
		$this->template = "module/{$this->name}.tpl";
		$this->children = array(
			'common/header',	
			'common/footer'	
		);
		
		$this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
	}
}
?>