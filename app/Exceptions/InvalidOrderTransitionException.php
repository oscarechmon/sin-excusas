<?php

namespace App\Exceptions;

use App\Enums\OnlineOrderStatus;

class InvalidOrderTransitionException extends BusinessException
{
    public static function between(OnlineOrderStatus $from, OnlineOrderStatus $to): self
    {
        return new self(sprintf(
            'Un pedido "%s" no puede pasar a "%s".',
            $from->label(),
            $to->label()
        ));
    }
}
