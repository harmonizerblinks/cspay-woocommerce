<?php
/*
Plugin Name: WooCommerce CsPay Payment Gateway
Plugin URI: http://cross-switch.com
Description: Cspay Payment gateway for woocommerce
Version: 1.0.0
Author: Harmony Alabi
Author URI: https://github.com/harmonizerblinks
License: MIT
License URI: http://www.gnu.org/licenses/mit
WC requires at least: 3.0.0
WC tested up to: 4.0
*/

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action('plugins_loaded', 'cspay_woocommerce_init', 11);
function cspay_woocommerce_init()
{
    if(!class_exists('WC_Payment_Gateway')) return;
    

    class WC_Cspay extends WC_Payment_Gateway
    {
       //Payment gateway configurations
       public $id;
       public $title;
       public $enabled;
       public $icon;
       public $payment_options;
       public $description;
       public $merchant_code;
       public $country_code;
       public $currency_code;
       public $client_callback_url;
       public $callback_url;
       public $redirect_url;
       public $default_currency;
       public $conversion_rate;
       public $checkout_url;

      public function __construct()
        {
          $this -> id = 'cspay';
          $this -> medthod_title = 'cspay';
          $this -> has_fields = false;
          $this->separator="-";
          $this->method_description = 'All customers to pay online using MOBILE MONEY, CARD and USSD';
          $this -> init_form_fields();
          $this -> init_settings();

          switch ($this->get_option('payment_options')) {
            case 'MOMO':
              $this -> icon = plugins_url()."/cspay-woocommerce/assets/momo_logo.png";
              break;
            case 'CARD':
              $this -> icon = plugins_url()."/cspay-woocommerce/assets/crd_logo.png";
              break;
            case 'USSD':
              $this -> icon = plugins_url()."/cspay-woocommerce/assets/crm_logo.png";
              break;
            default:
              $this -> icon = plugins_url()."/cspay-woocommerce/assets/crm_logo.png";
              break;
          }
          $this -> title = $this->get_option('title');
          $this -> description = $this->get_option('description');
          $this -> merchant_code = $this->get_option('merchant_code');
          $this -> country_code = $this->get_option('country_code');
          // $this -> app_id = $this->get_option('app_id'];
          // $this -> app_key = $this->get_option('app_key'];
          // $this -> secret_key = $this->get_option('secret_key'];
          // $this -> client_nickname = $this->get_option('client_nickname'];
          $this -> client_callback_url = $this->get_option('client_callback_url');
          $this -> client_redirect_url = $this->get_option('client_redirect_url');
          $this -> trnx_ref = $this->get_option('trnx_ref');
          $this -> payment_options = $this->get_option('payment_options');
          $this -> currency_code = $this->get_option('currency_code');
          // $this -> conversion_rate = $this->get_option('conversion_rate'];
          $this -> default_currency = "GHS";


          if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) 
            {
              add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
            } 
          else 
            {
              add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }

            add_action('woocommerce_api_wc_cspay', array($this, 'handle_callback'));
            
		        add_action( 'woocommerce_api_cspay_callback', array( $this, 'cspay_callback' ) );
       }
    



      function init_form_fields()
        {
          global $woocommerce;

          $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'cspay'),
                    'type' => 'checkbox',
                    'label' => __('Enable Cspay Payment Module.', 'cspay'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Title:', 'cspay'),
                    'type'=> 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'cspay'),
                    'default' => __('CsPay Online Payment', 'cspay')),
                'description' => array(
                    'title' => __('Description:', 'cspay'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'cspay'),
                    'default' => __('Pay online using your mobile money, cards account and Ussd.', 'cspay')),
                'merchant_code' => array(
                    'title' => __('Merchant code', 'cspay'),
                    'type' => 'text',
                    'description' => __('This is the Merchant Code generated by Cspay.','cspay')),
                // 'app_id' => array(
                //     'title' => __('App Id', 'cspay'),
                //     'type' => 'text',
                //     'description' => __('This is the App Id generated by Cspay.','cspay')),
                // 'app_key' => array(
                //     'title' => __('App Key', 'cspay'),
                //     'type' => 'text',
                //     'description' => __('This is the App Key generated by Cspay.','cspay')),
                // 'client_nickname' => array(
                //     'title' => __('client Nickname', 'cspay'),
                //     'type' => 'text',
                //     'description' => __('This is the name that would appear on the customers phone.','cspay')),
                'client_callback_url' => array(
                    'title' => __('Callback Url', 'cspay'),
                    'type' => 'text',
                    'description' => __('This is the callback url.'),
                    'default' => __(WC()->api_request_url( 'WC_Cspay' ), 'cspay')),
                'client_redirect_url' => array(
                    'title' => __('Redirect Page url', 'cspay'),
                    'type' => 'text',
                    'description' => __('This is the Redirect page url.'),
                    'default' => __('https://www.cross-switch.com', 'cspay')),
                'trnx_ref' => array(
                    'title' => __('client reference', 'cspay'),
                    'type' => 'text',
                    'description' => __('This is the description.'),
                    'default' => __('Order Payment', 'cspay')),
                'payment_options' => array(
                    'title' => __('Payment Options', 'cspay'),
                    'type' => 'select',
                    'required'    => true,
                    'description' => __('This is the payment mode.'),
                    'options' => array(
                      'CARD' => 'CARD only',
                      'MOMO' => 'MOMO only',
                      'USSD' => 'USSD only',
                      'CARD,MOMO' => 'CARD and MOMO only',
                      'CARD,USSD' => 'CARD and USSD only',
                      'USSD,MOMO' => 'USSD and MOMO only',
                      'CARD,MOMO,USSD' => 'All Available options'
                    ), // array of options for select/multiselects only
                    'default' => __('App Payment', 'cspay')),
                'country_code' => array(
                      'title' => __('Country/Environment', 'cspay'),
                      'type'=> 'select',
                      'required'    => true,
                      'description' => __('This refers to the currency code to be displayed on the Cspay checkout page. e.g. GHS/USD', 'GHS'),
                      'options' => array(
                        'dev' => 'DEV',
                        'test' => 'TEST',
                        'gh' => 'GH',
                        'bj' => 'BJ',
                        'ng' => 'NG'
                      ),
                      'default' => __('DEV', 'TEST', 'GH')),
                'currency_code' => array(
                  'title' => __('Currency', 'cspay'),
                  'type'=> 'select',
                  'required'    => true,
                  'description' => __('This refers to the currency code to be displayed on the Cspay checkout page. e.g. GHS/USD', 'GHS'),
                  'options' => array(
                    'GHS' => 'GHS',
                    'XOF' => 'XOF',
                    'NGN' => 'NGN',
                    'USD' => 'USD'
                  ), // array of options for select/multiselects only
                  'default' => __('GHS', 'XOF', 'NGN')),
                // 'conversion_rate' => array(
                //   'title' => __('Currency Conversion rate', 'cspay'),
                //   'type'=> 'text',
                //   'required'    => true,
                //   // 'step' => '.01',
                //   // 'custom_attributes' => array( 'step' => '.01', 'min' => '0' ),
                //   'description' => __('Conversion rate from the current displayed amount to GHS (default processing amount). e.g. 12.17', 'cspay'),
                //   'default' => __('1', 'GHS')),
          );
        }






      
      public function admin_options(){
        echo '<h3>'.__('Cspay Payment Gateway', 'cspay').'</h3>';
        echo '<p>'.__('Receive CARD / mobile money payments online using Cspay').'</p>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this -> generate_settings_html();
        echo '</table>';
      }



    /**
     * Process the payment and return the result
     **/
    public function process_payment( $order_id ) {
    
      // echo '<script>console.log("<br/> Order id: ' . $order_id . '")</script>';
      // var_dump($order_id);
      // $this->debug_to_console($order_id);

      $order = wc_get_order( $order_id );
      // echo '<script>console.log("<br/> Order: ' . $order . '")</script>';
      // $this->debug_to_console($order);
      
      // $data = json_decode($order);
      // echo '<script>console.log("Order json: ' . $data . '")</script>';
      // $this->debug_to_console($data);

      // echo '<script>console.log("<br/>Country Code: ' . $this->country_code . '")</script>';

      $cspay_url = 'https://api.cspay.app/app/CreateCheckout?country='.$this->country_code;
      // echo '<script>console.log("Url: ' . $cspay_url . '")</script>';

      $amount = $order->total;
      // echo '<script>console.log("AMOUNT: ' . $amount . '")</script>';

      $currency=$this->currency_code;
      // echo '<script>console.log("CURRENCY: ' . $currency . '")</script>';

      $first_name = method_exists( $order, 'get_billing_first_name' ) ? $order->get_billing_first_name() : $order->billing_first_name;
      $last_name  = method_exists( $order, 'get_billing_last_name' ) ? $order->get_billing_last_name() : $order->billing_last_name; 
      $name = $first_name . ' ' . $last_name;
      
      // echo '<script>console.log("NAME: ' . $name . '")</script>';
      
      $email = method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : $order->billing_email;
      // echo '<script>console.log("EMAIL: ' . $email . '")</script>';
      $mobile = method_exists( $order, 'get_billing_phone' ) ? $order->get_billing_phone() : $order->billing_phone;
      // echo '<script>console.log("MOBILE: ' . $mobile . '")</script>';
      // $trnx = $this->trnx_ref;
      // echo '<script>console.log("TRANx: ' . $trnx . '")</script>';
      $transaction_id = $order_id.$this->separator.time();
      // echo '<script>console.log("ORDER_ID: ' . $transaction_id . '")</script>';
      $options = $this->payment_options;
      // echo '<script>console.log("OPTIONS: ' . $options . '")</script>';
      $desc = 'Payment for order '.$order_id.' and transaction_ref '.$transaction_id;
      // echo '<script>console.log("ORDER_DESC: ' . $desc . '")</script>';
      $merchant = $this->merchant_code;
      // echo '<script>console.log("merchant: ' . $merchant . '")</script>';
      $callbackurl = $this->client_callback_url;
      // echo '<script>console.log("callbackurl: ' . $callbackurl . '")</script>';
      $redirecturl = get_site_url() . "/wc-api/cspay_callback";
      // echo '<script>console.log("redirecturl: ' . $redirecturl . '")</script>';

      $new_data = array('merchant'=> $merchant, 'name' => $name, 'mobile' => $mobile, 'email' => $email, 'amount' => $amount, 'order_id' => $transaction_id, 'order_desc' => $desc, 'options'=>$options, 'currency'=> $currency, 'callbackurl' => $callbackurl, 'redirecturl' => $redirecturl);
      // echo '<script>console.log("new_data: ' . json_encode($new_data) . '")</script>';
      $payload = json_encode($new_data);

      // echo '<script>console.log("payload: ' . $payload . '")</script>';
      
      $auth = $merchant.':'.$merchant;
      // echo '<script>console.log("auth: ' . $auth . '")</script>';
      $headers = array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $auth,
      );
      // echo '<script>console.log("header: ' . json_encode($headers) . '")</script>';
      $args = array(
        'headers' => $headers,
        'timeout' => 1000,
        'body' => $payload
      );
      // echo '<script>console.log("args: ' . $args . '")</script>';
      // 'sslverify' => false,
      $cspay_request = wp_remote_post( $cspay_url, $args );
      
      // echo '<script>console.log("request response: ' . json_encode($cspay_request) . '")</script>';

      if (is_wp_error($cspay_request)) 
			  throw new Exception( __( 'There is issue for connectin payment gateway. Sorry for the inconvenience.', 'cspay' ) );
      if (empty($cspay_request['body']))
        throw new Exception( __( 'CsPay could not process checkout.', 'cspay' ) );

      // echo '<script>console.log("response_body: ' . $cspay_request['body'] . '")</script>';

      $response = json_decode($cspay_request['body'], true);

      // echo '<script>console.log("decoded response_body: ' . json_encode($response) . '")</script>';

		  $resp = $response['data'];
      // echo '<script>console.log("response Data: ' .json_encode($resp). '")</script>';


      // echo '<script>console.log("raw response_body: ' . $cspay_request['body'] . '")</script>';
      // echo '<script>console.log("response_body: ' . $response_body . '")</script>';
      // $resp = $response['data'];
      // echo '<script>console.log("resp: ' . $resp . '")</script>';
      echo '<script>console.log("status_code: ' . $response['status_code'] . '")</script>';
      echo '<script>console.log("transaction_no: ' . $resp['transaction_no'] . '")</script>';
      echo '<script>console.log("checkout url: ' . $resp['checkout_url'] . '")</script>';
      if($response['status_code'] === 1) {
        $order->set_transaction_id($resp['transaction_no']);
        $order->save();
        
        // Mark as on-hold (we're awaiting the payment)
        $order->update_status( 'on-hold', __( 'Awaiting cspay payment', 'cspay' ) );

        return array(
          'result'    => 'success',
          'redirect'  => $resp['checkout_url']
        );
      } else {
        $order->add_order_note($response['status_message']);

        return array(
          'result'    => 'success'
        );
      }

      // $order->set_transaction_id($transaction_id);
      // $order->save();

              
              
      // Reduce stock levels
      // $order->reduce_order_stock();
              
      // Remove cart
      // WC()->cart->empty_cart();
              
      // Return thankyou redirect
      return array(
          'result'    => 'success'
          // 'redirect'  => $this->get_return_url($order)
      );
    }

    // function process_payment($order_id)
    // {
    //   echo $order_id;
    //   debug_to_console($order_id);
      // global $woocommerce;
    //   // $order = new WC_Order( $order_id );
    //   $order = wc_get_order( $order_id );
    //   debug_to_console($order);
    //   $data = json_decode($order);
    //   debug_to_console($data);
      

    //   $cspay_url = 'https://api.cspay.app/app/CreateCheckout?country='.$this->country_code;

    //   $currency=$this->extract_currency();

    //   $conversion_rate = floatval($this->conversion_rate);

    //   if ($currency == $this->default_currency) {
    //     $amount = floatval($data->total);
    //   }else{
    //     $amount = floatval($data->total) * $conversion_rate; //converting to GHS. if amount is already in GHS, then rate is expected to be 1, else USD 10 * 6.17(convesion rate) = GHS 61.72
    //   }

    //   if ($this->currency_code == $this->default_currency) {
    //     $currency_val = $amount;
    //   }else{
    //     if ($currency == $this->currency_code) {
    //       $currency_val = floatval($data->total);
    //     }else{
    //       $currency_val = floatval($data->total) * $conversion_rate; //converting to GHS. if amount is already in GHS, then rate is expected to be 1, else USD 10 * 6.17(convesion rate) = GHS 61.72
    //     }
    //   }

    //   // customer details
      // $first_name = method_exists( $order, 'get_billing_first_name' ) ? $order->get_billing_first_name() : $order->billing_first_name;
      // $last_name  = method_exists( $order, 'get_billing_last_name' ) ? $order->get_billing_last_name() : $order->billing_last_name; 
      // $name = $first_name . ' ' . $last_name;
    //   $name = "Test Customer";
      // $email = method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : $order->billing_email;
      // $mobile = method_exists( $order, 'get_billing_phone' ) ? $order->get_billing_phone() : $order->billing_phone;
    //   $email = "test@gmail.com";
    //   $mobile = "+233546467407";
      
    //   $trnx_ref = $this -> trnx_ref;
    //   $order_id = $data->order_key; //.'_'.time();
    //   $options = $this->payment_options;
    //   $desc = 'Payment for order _'.$order_id.'_'.$trnx_ref;
      
    //   $trans_type = "DR";
    //   // $nickname = $this->client_nickname;
    //   $merchant = $this->merchant_code;
    //   // $app_id = $this->app_id;
    //   // $app_key = $this->app_key;
    //   // $client_secret = $this -> secret_key;
    //   // $time = date("Y-m-d H:i:s");
      // $callbackurl = $this->client_callback_url;
      // // $redirecturl = $this->client_redirect_url;
      // $redirecturl = $this->get_return_url($order);

    //   $new_data = array('app_id' => $merchant, 'app_key' => $merchant, 'merchant'=> $merchant, 'name' => $name, 'mobile' => $mobile, 'email' => $email, 'amount' => $currency_val, 'order_id' => $order_id, 'order_desc' => $desc, 'options'=>$options, 'currency'=> $this->currency_code, 'callbackurl' => $callbackurl, 'redirecturl' => $this->get_return_url($order));

    //   $data_string = json_encode($new_data);

    //   // $signature =  hash_hmac ( 'sha256' , $data_string , $app_key );
    //   $auth = $merchant.':'.$merchant;
    //   $data_string = json_encode($new_data);

    //   // $signature =  hash_hmac ( 'sha256' , $data_string , $app_key );
    //   $auth = $merchant.':'.$merchant;
    //   debug_to_console($new_data);

      // $headers = array(
      //   'Content-Type' => 'application/json',
      //   'Accept' => 'application/json',
      //   'Authorization' => 'Bearer ' . $this->auth,
      // );
  
      // $args = array(
      //   'headers' => $headers,
      //   'timeout' => 60,
      // );

    //   $args['body'] = $new_data;
  
    //   $request = wp_remote_post( $cspay_url, $args );
    //   debug_to_console($request);
    //   if ( ! is_wp_error( $request ) && 200 === wp_remote_retrieve_response_code( $request ) ) {

    //     $payment_response = json_decode(wp_remote_retrieve_body($refund_request));
        
    //     debug_to_console($payment_response);

    //     if($payment_response->status_code) {
    //       // $amount         = wc_price( $amount, array( 'currency' => $order_currency ) );
    //       $redirect_data = $payment_response->data;
    //       $status_message = $payment_response->status_code;
    //       // $order->add_order_note( $status_message );

    //       debug_to_console($redirect_data);
    //       debug_to_console($response->data->checkout_url);

    //       // return true;
    //       return array(
    //         'result'    => "success",
    //         'redirect'  => $response->data->checkout_url
    //       );
    //     }else {

    //     }

    //   } else {

    //     $payment_response = json_decode( wp_remote_retrieve_body( $request ) );

    //     if ( isset( $payment_response->status_message ) ) {
    //       return new WP_Error( 'error', $payment_response->status_message );
    //     } else {
    //       return new WP_Error( 'error', __( 'Can&#39;t process payment at the moment. Try again later.', 'woo-paystack' ) );
    //     }
    //   }
        
    //   // $ch = curl_init($service_url);      
    //   // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");   
    //   // curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string); 
    //   // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    //   // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);      
    //   // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //   // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    //   //   'Authorization: '.$auth,
    //   //   'Content-Type: application/json' ,
    //   //   'timeout: 80',
    //   //   'open_timeout: 80'
    //   //   )  
    //   // ); 

    //   // $result = curl_exec($ch);
          
    //   // $callbackRequest = json_decode($result, true);

    //   // $resp_code = $callbackRequest['status_code'];
    //   // $resp_desc = $callbackRequest['status_message'];


    //   // if( $resp_code == 1) #successful
    //   //   {
    //   //     // Mark as on-hold (we're awaiting the payment)
    //   //     $order->update_status( 'on-hold', __( 'Awaiting offline payment', 'cspay' ) );
          
    //   //     // Reduce stock levels
    //   //     $order->reduce_order_stock();

    //   //     // Remove cart
    //   //     // WC()->cart->empty_cart();

    //   //     $redirect_url = $callbackRequest['data']['checkout_url'];
    //   //     return array('result' => 'success','redirect'  => $redirect_url);

    //   //   }
    //   // else
    //   //   {
    //   //     wc_add_notice(  'Error: '.$resp_desc, 'error' );
    //   //     return array(
    //   //      'result'    => $resp_desc,
    //   //      'redirect'  => $this->get_return_url( $order )
    //   //       );
    //   //   }
    // }




      function extract_currency(){
        $currency = get_woocommerce_currency();  

        $symbols = apply_filters( 'woocommerce_currency_symbols', array( 
            'AED' => 'د.إ',  
            'AFN' => '؋',  
            'ALL' => 'L',  
            'AMD' => 'AMD',  
            'ANG' => 'ƒ',  
            'AOA' => 'Kz',  
            'ARS' => '$',  
            'AUD' => '$',  
            'AWG' => 'ƒ',  
            'AZN' => 'AZN',  
            'BAM' => 'KM',  
            'BBD' => '$',  
            'BDT' => '৳ ',  
            'BGN' => 'лв.',  
            'BHD' => '.د.ب',  
            'BIF' => 'Fr',  
            'BMD' => '$',  
            'BND' => '$',  
            'BOB' => 'Bs.',  
            'BRL' => 'R$',  
            'BSD' => '$',  
            'BTC' => '฿',  
            'BTN' => 'Nu.',  
            'BWP' => 'P',  
            'BYR' => 'Br',  
            'BZD' => '$',  
            'CAD' => '$',  
            'CDF' => 'Fr',  
            'CHF' => 'CHF',  
            'CLP' => '$',  
            'CNY' => '¥',  
            'COP' => '$',  
            'CRC' => '₡',  
            'CUC' => '$',  
            'CUP' => '$',  
            'CVE' => '$',  
            'CZK' => 'Kč',  
            'DJF' => 'Fr',  
            'DKK' => 'DKK',  
            'DOP' => 'RD$',  
            'DZD' => 'د.ج',  
            'EGP' => 'EGP',  
            'ERN' => 'Nfk',  
            'ETB' => 'Br',  
            'EUR' => '€',  
            'FJD' => '$',  
            'FKP' => '£',  
            'GBP' => '£',  
            'GEL' => 'ლ',  
            'GGP' => '£',  
            'GHS' => '₵',  
            'GIP' => '£',  
            'GMD' => 'D',  
            'GNF' => 'Fr',  
            'GTQ' => 'Q',  
            'GYD' => '$',  
            'HKD' => '$',  
            'HNL' => 'L',  
            'HRK' => 'Kn',  
            'HTG' => 'G',  
            'HUF' => 'Ft',  
            'IDR' => 'Rp',  
            'ILS' => '₪',  
            'IMP' => '£',  
            'INR' => '₹',  
            'IQD' => 'ع.د',  
            'IRR' => '﷼',  
            'IRT' => 'تومان',  
            'ISK' => 'kr.',  
            'JEP' => '£',  
            'JMD' => '$',  
            'JOD' => 'د.ا',  
            'JPY' => '¥',  
            'KES' => 'KSh',  
            'KGS' => 'сом',  
            'KHR' => '៛',  
            'KMF' => 'Fr',  
            'KPW' => '₩',  
            'KRW' => '₩',  
            'KWD' => 'د.ك',  
            'KYD' => '$',  
            'KZT' => 'KZT',  
            'LAK' => '₭',  
            'LBP' => 'ل.ل',  
            'LKR' => 'රු',  
            'LRD' => '$',  
            'LSL' => 'L',  
            'LYD' => 'ل.د',  
            'MAD' => 'د.م.',  
            'MDL' => 'MDL',  
            'MGA' => 'Ar',  
            'MKD' => 'ден',  
            'MMK' => 'Ks',  
            'MNT' => '₮',  
            'MOP' => 'P',  
            'MRO' => 'UM',  
            'MUR' => '₨',  
            'MVR' => '.ރ',  
            'MWK' => 'MK',  
            'MXN' => '$',  
            'MYR' => 'RM',  
            'MZN' => 'MT',  
            'NAD' => '$',  
            'NGN' => '₦',  
            'NIO' => 'C$',  
            'NOK' => 'kr',  
            'NPR' => '₨',  
            'NZD' => '$',  
            'OMR' => 'ر.ع.',  
            'PAB' => 'B/.',  
            'PEN' => 'S/.',  
            'PGK' => 'K',  
            'PHP' => '₱',  
            'PKR' => '₨',  
            'PLN' => 'zł',  
            'PRB' => 'р.',  
            'PYG' => '₲',  
            'QAR' => 'ر.ق',  
            'RMB' => '¥',  
            'RON' => 'lei',  
            'RSD' => 'дин.',  
            'RUB' => '₽',  
            'RWF' => 'Fr',  
            'SAR' => 'ر.س',  
            'SBD' => '$',  
            'SCR' => '₨',  
            'SDG' => 'ج.س.',  
            'SEK' => 'kr',  
            'SGD' => '$',  
            'SHP' => '£',  
            'SLL' => 'Le',  
            'SOS' => 'Sh',  
            'SRD' => '$',  
            'SSP' => '£',  
            'STD' => 'Db',  
            'SYP' => 'ل.س',  
            'SZL' => 'L',  
            'THB' => '฿',  
            'TJS' => 'ЅМ',  
            'TMT' => 'm',  
            'TND' => 'د.ت',  
            'TOP' => 'T$',  
            'TRY' => '₺',  
            'TTD' => '$',  
            'TWD' => 'NT$',  
            'TZS' => 'Sh',  
            'UAH' => '₴',  
            'UGX' => 'UGX',  
            'USD' => '$',  
            'UYU' => '$',  
            'UZS' => 'UZS',  
            'VEF' => 'Bs F',  
            'VND' => '₫',  
            'VUV' => 'Vt',  
            'WST' => 'T',  
            'XAF' => 'Fr',  
            'XCD' => '$',  
            'XOF' => 'Fr',  
            'XPF' => 'Fr',  
            'YER' => '﷼',  
            'ZAR' => 'R',  
            'ZMW' => 'ZK',  
        ) ); 

        $currency_symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '';

        return $currency;

        // return apply_filters( 'woocommerce_currency_symbol', $currency_symbol, $currency );
      }


      function debug_to_console($data) {
        $output = $data;
        // if (is_array($output)) $output = implode(',', $output);
    
        echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
      }


      public function cspay_callback() {
        
      echo '<script>console.log("Callback Redirect: ")</script>';

        $logger = wc_get_logger();
        $ORDER_ID = explode($this->separator,$_GET['order_id'])[0];
        echo '<script>console.log("ORDERID : '. $ORDER_ID . '")</script>';
        $order = wc_get_order($ORDER_ID);
        $status_code = $_GET['status_code'];
        echo '<script>console.log("Status Code: '. $status_code . '")</script>';
        $status_message = $_GET['status_message'];
        echo '<script>console.log("Callback status_message: '.$status_message .'")</script>';
        $trans_ref_no = $_GET['transaction_no'];
        echo '<script>console.log("Callback transaction_no: '.$trans_ref_no .'")</script>';
        // $signature = $_GET['signature'];
        global $woocommerce;
        // if ( !function_exists( 'wc_add_notice' ) ) { 
        //   require_once '/includes/wc-notice-functions.php'; 
        // }

        if($status_code == 1)
        {
          $order->payment_complete();
          $order->reduce_order_stock();
          WC()->cart->empty_cart();
          update_option('webhook_debug', $_GET);
          $respMessage = 'Payment successful from CsPay <br>order_id='.$ORDER_ID.", CsPay Ref No = ".$trans_ref_no.", status_code = ".$status_code.",status_message = ".$status_message;
          $order->add_order_note($respMessage );
    
          wc_add_notice( sprintf( __( '%s payment Completed! Transaction ID: %d', 'woocommerce' ), $this->title, $trans_ref_no ), 'success' );
          $order_returl = $this->get_return_url( $order );
    
          // header('Location: ' . $order_returl);
        } else {
          $respMessage = 'Payment Error from CsPay <br>order_id='.$ORDER_ID.", CsPay Ref No = ".$trans_ref_no.", status_code = ".$status_code.",status_message = ".$status_message;
          $logger->info("Response Message = ". $respMessage, $this->$context );
          $order->add_order_note($respMessage);
          $cart_url = $woocommerce->cart->get_cart_url();
          // wc_add_notice( sprintf( __( '%s payment failed! Transaction ID: %d', 'woocommerce' ), $this->title, $trans_ref_no ), 'error' );
          // header('Location: ' . $cart_url);
          
          //  exit();
        }
      }
    
      /**
     * Callback handling form
     **/
    
    function handle_callback() 
    {
        @ob_clean();
        $status = isset($_GET['status_message']) ? $_GET['status_message'] : '';
        $cust_ref = isset($_GET['order_id']) ? $_GET['order_id'] : '';
        $transac_id = isset($_GET['transaction_no']) ? $_GET['transaction_no'] : '';
        $status_message = isset($_GET['status_message']) ? $_GET['status_message'] : '';
        

        $order_id = wc_get_order_id_by_order_key($cust_ref);
        $order = wc_get_order( $order_id );
         
        if (isset($_GET['status_message'])) #first
          {
            if ($_GET['status_message'] == "FAILED" || $_GET['status_message'] == "FAIL") 
              {
                error_log("From Page");
              }
          }
        else
          {
            #json callback from amfp itself
            $callbackRequest = json_decode(@file_get_contents('php://input'), true);
            
            
            $network_id = $callbackRequest['transaction_no'];
            $exttrid = $callbackRequest['order_id'];
            $resp_code = $callbackRequest['status_code'];
            $resp_desc = $callbackRequest['status_message'];
            $trnx_stat = explode("/", $resp_code);
            $the_stat = $trnx_stat[0];
      
            $order_id = wc_get_order_id_by_order_key($exttrid);
            $order = wc_get_order( $order_id );
      
      
            if ($the_stat == "1") #passed 
              {
                $order->add_order_note( __( $resp_desc, 'cspay' ) );
                $order->payment_complete();
                $order->update_status( 'completed', __( $resp_desc, 'cspay' ) );     
              } 
            elseif ($the_stat == "-1") 
              {
                $order->add_order_note( __( $resp_desc, 'cspay' ) );
                $order->update_status( 'cancelled', __( $resp_desc, 'cspay' ) );                
              }
            else #failed
              {
                $order->add_order_note( __( $resp_desc, 'cspay' ) );
                $order->update_status( 'failed', __( $resp_desc, 'cspay' ) );                 
              }
          }
        
    
        if($status == 0)
          {
            header('Location:'.$this->get_return_url( $order ));
          }
        elseif($status==-1)
          { 
            // Technical error contact
            wp_die( "Payment Was not successful", "cspay", array( 'response' => 200 ) );
          }
        elseif($status==-2)
          { 
            // User cancelled transaction
            $order->update_status( 'cancelled', __( 'Order cancelled by User on Cspay platform.', 'cspay' ) );
            header('Location:'.home_url());
            exit;
          }
    }
     
}

   /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_cspay_gateway($methods) 
      {
          $methods[] = 'WC_Cspay';
          return $methods;
      }


    add_filter('woocommerce_payment_gateways', 'woocommerce_add_cspay_gateway' );
}
