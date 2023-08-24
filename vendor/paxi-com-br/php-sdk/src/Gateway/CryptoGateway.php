<?php

namespace PAXI\SDK\Gateway;

use PAXI\SDK\PAXI;

class CryptoGateway extends BaseGateway
{
    /**
     * @param PAXI $paxi
     */
    public function __construct(PAXI $paxi)
    {
        parent::__construct($paxi);
    }
}
