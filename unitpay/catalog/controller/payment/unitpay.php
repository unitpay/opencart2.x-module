<?php

class ControllerPaymentUnitpay extends Controller {
	public function index() {
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['button_back'] = $this->language->get('button_back');
        $data['text_loading'] = $this->language->get('text_loading');

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
  
        $data['unitpay_login'] = $this->config->get('unitpay_login');
        $data['unitpay_key']= $this->config->get('unitpay_key');
        $data['success_url']= $this->config->get('unitpay_success_url');
        // Номер заказа
        $data['inv_id'] = $this->session->data['order_id'];

        // Комментарий к заказу
        $data['inv_desc'] = $this->config->get('config_store') . ' ' . $order_info['payment_firstname'] . ' ' . $order_info['payment_address_1'] . ' ' . $order_info['payment_address_2'] . ' ' . $order_info['payment_city'] . ' ' . $order_info['email'];

        // Сумма заказа
		$rur_code = 'RUB';
		$rur_order_total = $this->currency->convert($order_info['total'], $order_info['currency_code'], $rur_code);
		$data['out_summ'] = $this->currency->format($rur_order_total, $rur_code, $order_info['currency_value'], FALSE);
        
        $data['action']="https://unitpay.ru/pay/";

		// Кодировка
		//$data['encoding'] = "utf-8";

		$data['merchant_url'] = $data['action'] .
				$data['unitpay_login'] .
    				'?sum='			. $data['out_summ'] .
    				'&account='		. $data['inv_id']	.
                    '&desc='        . $data['inv_desc'] .
    				'&unitpay_login='		. $data['unitpay_login'] .
                    '&resultUrl=' .  $data['success_url'];
			
//tesrt
		$this->id = 'payment';

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/unitpay.tpl')) {
            return $this->load->view($this->config->get('config_template') . '/template/payment/unitpay.tpl', $data);
        } else {
            return $this->load->view('default/template/payment/unitpay.tpl', $data);
        }

	}

    public function callback() {
    	echo $this->getResult();
	}
    
    public function getResult(){
        $request = $_GET;
//preg_match_all('/method=(.*)/', $request['route'], $d);
//$request['method']=$d['1']['0'];

        if (empty($request['method']) || empty($request['params']) || !is_array($request['params'])) {
            return $this->getResponseError('Invalid request');
        }

        $method = $request['method'];
        $params = $request['params'];
		
		$this->load->model('checkout/order');
    	$arOrder=$this->model_checkout_order->getOrder($params['account']);
		
        $total_price=round($arOrder['total']*$arOrder['currency_value'],2);

        if ($params['signature'] != $this->getSha256SignatureByMethodAndParams(
                $method, $params, $this->config->get('unitpay_key'))) {
            return $this->getResponseError('Incorrect digital signature');
        }

    
        if ($method == 'check'){
            
            if (!$arOrder){
                return $this->getResponseError('Unable to confirm payment database');
            }
            $itemsCount = floor($params['sum'] / $total_price);

            if ($itemsCount <= 0){
                return $this->getResponseError('Суммы ' . $params['sum'] . ' руб. не достаточно для оплаты товара ' .
                    'стоимостью ' . $total_price . ' руб.');
            }
            $checkResult = $this->check($params);
            if ($checkResult !== true){
                return $this->getResponseError($checkResult);
            }

            return $this->getResponseSuccess('CHECK is successful');
        }

        if ($method == 'pay'){
            if ($arOrder && $arOrder['order_status_id'] !== '0'){
                return $this->getResponseSuccess('Payment has already been paid');
            }

            if (!$arOrder){
                return $this->getResponseError('Unable to confirm payment database');
            }

            $this->pay($params);

            return $this->getResponseSuccess('PAY is successful');
        }

	return $this->getResponseError($method.' not supported');
    }
	
   
    private function getResponseSuccess($message){
        return json_encode(array(
            "jsonrpc" => "2.0",
            "result" => array(
                "message" => $message
            ),
            'id' => 1,
        ));
    }

    private function getResponseError($message){
        return json_encode(array(
            "jsonrpc" => "2.0",
            "error" => array(
                "code" => -32000,
                "message" => $message
            ),
            'id' => 1
        ));
    }

    /**
     * @param $method
     * @param array $params
     * @param $secretKey
     * @return string
     */
    function getSha256SignatureByMethodAndParams($method, array $params, $secretKey)
    {
        $delimiter = '{up}';
        ksort($params);
        unset($params['sign']);
        unset($params['signature']);

        return hash('sha256', $method.$delimiter.join($delimiter, $params).$delimiter.$secretKey);
    }

    private function getMd5Sign($params, $secretKey){
        ksort($params);
        unset($params['sign']);
        return md5(join(null, $params).$secretKey);
    }
	
    private function check($params){       
         if ($this->model_checkout_order->getOrder($params['account']))
         {
            return true;      
         }  
         return 'Character not found';
    }

    private function pay($params){
      $new_order_status_id = $this->config->get('unitpay_order_status_id');
	  $this->model_checkout_order->addOrderHistory($params['account'], $new_order_status_id, 'оплата через UnitPay');
    }
}
?>