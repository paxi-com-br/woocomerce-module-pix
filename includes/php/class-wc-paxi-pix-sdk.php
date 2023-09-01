<?php

if (!defined("ABSPATH")) {
    exit;
}

use PAXI\SDK\PAXI as PAXI_API_SDK;

class WC_PAXI_PIX_SDK
{
    public function process_payment($paxi_woocommerce, $order)
    {
        $apiKey = $paxi_woocommerce->api_key;
        $apiSecret = $paxi_woocommerce->api_secret;
        $depositKey = $paxi_woocommerce->pix_key;
        $depositAmount = $order->get_total();

        try {
            $paxi = new PAXI_API_SDK($apiKey, $apiSecret);
            $qrcode = $paxi->withPix()->generateQRCode($depositKey, $depositAmount);
        
            return $qrcode;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function process_webhook($paxi_woocommerce)
    {
        $webhookSecret = $paxi_woocommerce->webhook_secret;

        try {
            $paxi = new PAXI_API_SDK("", "", false);
            $payload = $paxi->handleWebhook($webhookSecret);

            if (
                $payload["event"] == "transaction.PixIn"
                && $order = $paxi_woocommerce->order_by_qrcode_id($payload["id"])
            ) {
                $this->event_pixin($order, $payload);
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function event_pixin($order, $payload)
    {
        if ($order->get_total() == $payload["transactions"][0]["details"]["amount"]) {
            $order->update_meta_data("paxi_pix_transaction_id", $payload["transactions"][0]["id"]);
            $order->update_status("processing");
            $order->save();
        }
    }
}
