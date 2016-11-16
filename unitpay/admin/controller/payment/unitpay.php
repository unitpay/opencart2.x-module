<?php
class ControllerPaymentUnitpay extends Controller {

	private $error = array();

	public function index() {
		$this->load->language('payment/unitpay');
		
		$this->document->setTitle = $this->language->get('heading_title');

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			$this->model_setting_setting->editSetting('config_unitpay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');
			if (isset($this->request->post['save_stay']) && $this->request->post['save_stay']){
				$this->response->redirect($this->url->link('payment/unitpay', 'token=' . $this->session->data['token'], 'SSL'));
			}
			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_liqpay'] = $this->language->get('text_liqpay');
		$data['text_card'] = $this->language->get('text_card');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_result_url'] = $this->language->get('text_result_url');
		$data['text_success_url'] = $this->language->get('text_success_url');
		$data['text_fail_url'] = $this->language->get('text_fail_url');
		$data['text_save_and_stay'] = $this->language->get('text_save_and_stay');
		
		
		$data['entry_login'] = $this->language->get('entry_login');
		$data['entry_unitpay_key'] = $this->language->get('entry_unitpay_key');
		

		// URL
		$data['copy_result_url'] 	= HTTP_CATALOG . 'index.php?route=payment/unitpay/callback';
		$data['copy_success_url']	= HTTP_CATALOG . 'index.php?route=checkout/success';
		$data['copy_fail_url'] 	= HTTP_CATALOG . 'index.php?route=checkout/failure';

		$data['entry_order_status_after_pay'] = $this->language->get('entry_order_status_after_pay');
		$data['entry_order_status_after_create'] = $this->language->get('entry_order_status_after_create');
		$data['entry_delete_cart_after_confirm'] = $this->language->get('entry_delete_cart_after_confirm');
		$data['entry_set_status_after_create'] = $this->language->get('entry_set_status_after_create');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['tab_general'] = $this->language->get('tab_general');

		//
	
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		//
		if (isset($this->error['login'])) {
			$data['error_login'] = $this->error['login'];
		} else {
			$data['error_login'] = '';
		}

		if (isset($this->error['password1'])) {
			$data['error_password1'] = $this->error['password1'];
		} else {
			$data['error_password1'] = '';
		}

		
		$data['action'] = $this->url->link('payment/unitpay', 'token=' . $this->session->data['token'], 'SSL');
		$data['update'] = $this->url->link('payment/unitpay', 'token=' . $this->session->data['token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('payment/unitpay', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);

		//
		$data['config_unitpay_login'] = $this->config->get('config_unitpay_login');
		$data['config_unitpay_key'] = $this->config->get('config_unitpay_key');
		$data['config_unitpay_order_status_id_after_create'] = $this->config->get('config_unitpay_order_status_id_after_create');
		$data['config_unitpay_order_status_id_after_pay'] = $this->config->get('config_unitpay_order_status_id_after_pay');
		$data['config_unitpay_create_order'] = $this->config->get('config_unitpay_create_order');
		$data['config_unitpay_cart_reset'] = $this->config->get('config_unitpay_cart_reset');
		$data['config_unitpay_geo_zone_id'] = $this->config->get('config_unitpay_geo_zone_id');
		$data['config_unitpay_status'] = $this->config->get('config_unitpay_status');

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('payment/unitpay.tpl', $data));

	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/unitpay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->request->post['config_unitpay_login']) {
			$this->error['login'] = $this->language->get('error_login');
		}

		if (!$this->request->post['config_unitpay_key']) {
			$this->error['password1'] = $this->language->get('error_password1');
		}
		
		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}
?>