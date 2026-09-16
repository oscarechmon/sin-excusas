<?php

namespace App\Services\Payments;

use RuntimeException;

/** La pasarela no respondió o está mal configurada. */
class PaymentGatewayException extends RuntimeException {}
