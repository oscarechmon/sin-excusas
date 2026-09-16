<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnlineOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'fulfillment' => $this->fulfillment->value,
            'fulfillment_label' => $this->fulfillment->label(),
            // Cuenta web (client_users) que hizo la compra.
            'customer' => $this->whenLoaded('clientUser', fn () => [
                'id' => $this->clientUser->id,
                'name' => $this->clientUser->name,
                'email' => $this->clientUser->email,
                'phone' => $this->clientUser->phone,
            ]),
            'client_id' => $this->client_id,
            'client_code' => $this->whenLoaded('client', fn () => $this->client?->code),
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'district' => $this->district,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'subtotal' => (float) $this->subtotal,
            'delivery_fee' => (float) $this->delivery_fee,
            'total' => (float) $this->total,
            'items_count' => $this->whenCounted('items'),
            'paid_at' => $this->paid_at,
            'payment_reference' => $this->payment_reference,
            'created_at' => $this->created_at,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'type' => $item->item_type,
                'name' => $item->name,
                'unit_price' => (float) $item->unit_price,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ])),
            'histories' => $this->whenLoaded('histories', fn () => $this->histories->map(fn ($history) => [
                'id' => $history->id,
                'status' => $history->status->value,
                'status_label' => $history->status->label(),
                'note' => $history->note,
                'internal' => $history->internal,
                'user' => $history->user?->name,
                'created_at' => $history->created_at,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'gateway' => $payment->gateway,
                'status' => $payment->status,
                'amount' => (float) $payment->amount,
                'transaction_id' => $payment->transaction_id,
                'created_at' => $payment->created_at,
            ])),
            'next_statuses' => collect($this->status->nextStatuses($this->fulfillment))
                ->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()])
                ->values(),
        ];
    }
}
