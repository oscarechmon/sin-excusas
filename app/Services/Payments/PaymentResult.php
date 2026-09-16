<?php

namespace App\Services\Payments;

/** Resultado de un cobro, ya validada su firma. */
final readonly class PaymentResult
{
    /** @param array<string,mixed> $payload */
    public function __construct(
        public string $orderCode,
        public bool $paid,
        public ?string $transactionId,
        public int $amountInCents,
        public array $payload = [],
    ) {}
}
