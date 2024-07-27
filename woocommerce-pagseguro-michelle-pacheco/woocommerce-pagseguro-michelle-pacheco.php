<?php
/**
 * Plugin Name: WooCommerce PagSeguro Integration - Michelle Pacheco
 * Description: Integração personalizada com o PagSeguro para WooCommerce.
 * Version: 1.0.0
 * Author: Michelle Pacheco
 * Author URI: http://seusite.com
 * Text Domain: woocommerce-pagseguro-michelle-pacheco
 */

// Evita acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Inclui a biblioteca do PagSeguro
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Função para inicializar o plugin
function wc_pagseguro_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Gateway_PagSeguro extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'pagseguro';
            $this->icon = ''; // Adicione a URL do ícone se houver
            $this->has_fields = true;
            $this->method_title = __('PagSeguro', 'woocommerce-pagseguro-michelle-pacheco');
            $this->method_description = __('Integração personalizada com o PagSeguro.', 'woocommerce-pagseguro-michelle-pacheco');

            // Carrega as configurações
            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            // Hooks
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

            // Enfileira o script do checkout personalizado
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce-pagseguro-michelle-pacheco'),
                    'type' => 'checkbox',
                    'label' => __('Enable PagSeguro Payment', 'woocommerce-pagseguro-michelle-pacheco'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'woocommerce-pagseguro-michelle-pacheco'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-pagseguro-michelle-pacheco'),
                    'default' => __('PagSeguro Payment', 'woocommerce-pagseguro-michelle-pacheco'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', 'woocommerce-pagseguro-michelle-pacheco'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-pagseguro-michelle-pacheco'),
                    'default' => __('Pay securely using your credit card through PagSeguro.', 'woocommerce-pagseguro-michelle-pacheco')
                ),
                'email' => array(
                    'title' => __('PagSeguro Email', 'woocommerce-pagseguro-michelle-pacheco'),
                    'type' => 'text',
                    'description' => __('Enter your PagSeguro account email.', 'woocommerce-pagseguro-michelle-pacheco'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'token' => array(
                    'title' => __('PagSeguro Token', 'woocommerce-pagseguro-michelle-pacheco'),
                    'type' => 'text',
                    'description' => __('Enter your PagSeguro token.', 'woocommerce-pagseguro-michelle-pacheco'),
                    'default' => '',
                    'desc_tip' => true,
                ),
            );
        }

        public function payment_scripts() {
            if (!is_checkout()) {
                return;
            }

            // Enfileirar scripts e estilos personalizados aqui
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            
            // Configurar credenciais do PagSeguro
            \PagSeguro\Library::initialize();
            \PagSeguro\Library::cmsVersion()->setName("WooCommerce")->setRelease(WC()->version);
            \PagSeguro\Library::moduleVersion()->setName("WooCommerce PagSeguro Michelle Pacheco")->setRelease("1.0.0");

            $email = $this->get_option('email');
            $token = $this->get_option('token');

            $payment = new \PagSeguro\Domains\Requests\Payment();
            $payment->setReference($order->get_id());
            $payment->setCurrency("BRL");

            foreach ($order->get_items() as $item_id => $item) {
                $product = $item->get_product();
                $payment->addItems()->withParameters(
                    $item_id,
                    $item->get_name(),
                    $item->get_quantity(),
                    $product->get_price()
                );
            }

            $payment->setSender()->setName($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
            $payment->setSender()->setEmail($order->get_billing_email());

            try {
                $response = $payment->register(
                    \PagSeguro\Configuration\Configure::getAccountCredentials($email, $token)
                );

                $order->payment_complete();

                return array(
                    'result' => 'success',
                    'redirect' => $response->getLink()
                );
            } catch (Exception $e) {
                wc_add_notice(__('Payment error:', 'woocommerce-pagseguro-michelle-pacheco') . $e->getMessage(), 'error');
                return;
            }
        }
    }
}

// Adiciona o gateway de pagamento ao WooCommerce
function add_wc_gateway_pagseguro($methods) {
    $methods[] = 'WC_Gateway_PagSeguro';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_wc_gateway_pagseguro');

