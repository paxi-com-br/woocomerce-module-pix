<?php

if (!defined("ABSPATH")) {
    exit;
}

class WC_PAXI_PIX_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->paxi_pix_sdk = new WC_PAXI_PIX_SDK;
        $this->id = "paxi_pix";
        $this->icon = plugins_url("/../img/paxi-logo-branco.png", __FILE__);
        $this->has_fields = false;
        $this->method_title = "PAXI - Módulo PIX";
        $this->method_description = "Aceite pagamentos PIX na sua loja e receba em sua conta paxi.com.br";

        $this->supports = array(
            "products"
        );

        $this->init_form_fields();

        $this->init_settings();
        $this->title = $this->get_option("title");
        $this->description = $this->get_option("description");
        $this->enabled = $this->get_option("enabled");
        $this->pix_key = $this->get_option("pix_key");
        $this->api_key = $this->get_option("api_key");
        $this->api_secret = $this->get_option("api_secret");
        $this->webhook_secret = $this->get_option("webhook_secret");

        add_action("woocommerce_update_options_payment_gateways_" . $this->id, array($this, "process_admin_options"));
        add_action("wp_enqueue_scripts", array($this, "payment_scripts"));
        add_action("woocommerce_api_paxi_pix", array($this, "process_webhook"));
        add_action("woocommerce_order_details_after_order_table", array($this, "process_order_after"));
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            "enabled" => array(
                "title"       => "Ativo/Inativo",
                "label"       => "Ativar PAXI como forma de pagamento",
                "type"        => "checkbox",
                "default"     => "no"
            ),
            "title" => array(
                "title"       => "Nome",
                "type"        => "text",
                "description" => "O título que o usuário vê durante o checkout.",
                "default"     => "PIX"
            ),
            "description" => array(
                "title"       => "Descrição",
                "type"        => "textarea",
                "description" => "A descrição que o usuário vê durante o checkout.",
                "default"     => "Pagamento fácil e rápido com PIX!",
            ),
            "pix_key" => array(
                "title"       => "Chave",
                "type"        => "text",
                "description" => "Informe a chave PIX da sua conta PAXI",
            ),
            "api_key" => array(
                "title"       => "Api-Key",
                "type"        => "password",
                "description" => "Obtenha sua Api-Key em https://paxi.com.br/account/api",
            ),
            "api_secret" => array(
                "title"       => "Api-Secret",
                "type"        => "password",
                "description" => "Obtenha sua Api-Secret em https://paxi.com.br/account/api",
            ),
            "webhook_secret" => array(
                "title"       => "Webhook-Secret",
                "type"        => "password",
                "description" => "Obtenha sua Webhook-Secret em https://paxi.com.br/account/api",
            )
        );
    }

    public function process_admin_options()
    {
        $this->init_settings();

        $post_data = $this->get_post_data();

        if (strlen($post_data["woocommerce_{$this->id}_pix_key"]) < 6) {
            $this->add_error("Chave PIX informada é inválida.");
        }

        if (strlen($post_data["woocommerce_{$this->id}_api_key"]) < 6) {
            $this->add_error("Api-Key informada é inválida.");
        }

        if (strlen($post_data["woocommerce_{$this->id}_api_secret"]) < 6) {
            $this->add_error("Api-Secret informada é inválida.");
        }

        if (strlen($post_data["woocommerce_{$this->id}_webhook_secret"]) < 6) {
            $this->add_error("Webhook-Secret informada é inválida.");
        }

        if ($this->get_errors()) {
            $this->display_errors();
            return false;
        }

        parent::process_admin_options();
    }

    public function payment_fields()
    {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }
    }

    public function payment_scripts()
    {
        if ($this->enabled == "no") {
            return;
        }

        if (is_wc_endpoint_url("order-received") || is_wc_endpoint_url("view-order")) {
            wp_enqueue_script("woocommerce_paxi_pix_qrcode", plugins_url("/../js/davidshimjs-qrcodejs.min.js", __FILE__), array("jquery"), "", true);
            wp_enqueue_script("woocommerce_paxi_pix_client", plugins_url("/../js/paxi_pix_client.js", __FILE__), array("jquery"), "", true);
        }
    }

    public function validate_fields()
    {
    }

    public function process_payment($order_id)
    {
        if ($_POST["payment_method"] != $this->id) {
            return;
        }

        $order = wc_get_order($order_id);
        $result = $this->paxi_pix_sdk->process_payment($this, $order);

        if ($result == null || $result == false) {
            return array(
                "result" => "false",
                "redirect" => ""
            );
        }

        $order->update_meta_data("paxi_pix_qrcode_id", $result["id"]);
        $order->update_meta_data("paxi_pix_emv", $result["emv"]);

        $order->save();

        if ($result == null || $result == false) {
            return array(
                "result" => "false",
                "redirect" => ""
            );
        }

        global $woocommerce;
        $order->update_status("pending");
        $order->reduce_order_stock();
        $woocommerce->cart->empty_cart();

        return array(
            "result" => "success",
            "redirect" => $this->get_return_url($order)
        );
    }

    public function process_order_after($order_id)
    {
        $order = wc_get_order($order_id);

        if (
            $order->payment_method != $this->id
            || $order->is_paid()
            || $order->get_status() === "processing"
        ) {
            return;
        }

        $qrId = $order->get_meta("paxi_pix_qrcode_id");
        $emv = $order->get_meta("paxi_pix_emv");

        if (!$qrId > 0) {
            return;
        }

        echo trim('
            <section class="woocommerce-customer-details">
                <h2 class="woocommerce-column__title">Pagamento com PIX</h2>
                <div style="margin-bottom: 10px;" id="paxi-pix-qrcode-container" data-emv="' . $emv . '"></div>

                <button class="woocommerce-button wp-element-button button" id="paxi-pix-qrcode-button">
                    Copiar
                </button>
            </section>
        ');
    }

    public function process_webhook()
    {
        $this->paxi_pix_sdk->process_webhook($this);
    }

    public function order_by_qrcode_id($value)
    {
        $args = array(
            "limit" => 1,
            "meta_key" => "paxi_pix_qrcode_id",
            "meta_value" => $value,
            "meta_compare" => "="
        );

        $orders = wc_get_orders($args);

        if (!empty($orders)) {
            return $orders[0];
        }

        return false;
    }
}
