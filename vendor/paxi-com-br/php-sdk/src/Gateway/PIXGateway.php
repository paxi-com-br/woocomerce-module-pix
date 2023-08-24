<?php

namespace PAXI\SDK\Gateway;

use PAXI\SDK\PAXI;

class PIXGateway extends BaseGateway
{
    /**
     * @param PAXI $paxi
     */
    public function __construct(PAXI $paxi)
    {
        parent::__construct($paxi);
    }

    /**
     * @param string $key
     * @param float $amount
     * @return array|false
     */
    public function generateQRCode($key, $amount)
    {
        if (!is_string($key) || !is_numeric($amount)) {
            return false;
        }

        $data = [
            "key" => (string) $key,
            "amount" => (float) $amount
        ];
        $res = $this->httpClient->request("/api/v1/pix/qrcode", "POST", $data);
        
        if (!isset($res["success"]) || $res["success"] != true) {
            return false;
        }

        return [
            "id" => $res["result"]["id"],
            "emv" => $res["result"]["emv"]
        ];
    }
}
