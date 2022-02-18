<?php

namespace Give\Subscriptions\DataTransferObjects;

use DateTime;
use Give\Framework\Models\Traits\InteractsWithTime;
use Give\Subscriptions\Models\Subscription;
use Give\Subscriptions\ValueObjects\SubscriptionPeriod;
use Give\Subscriptions\ValueObjects\SubscriptionStatus;

/**
 * Class SubscriptionObjectData
 *
 * @unreleased
 */
class SubscriptionQueryData
{
    use InteractsWithTime;

    /**
     * @var int
     */
    private $id;
    /**
     * @var DateTime
     */
    private $createdAt;
    /**
     * @var DateTime
     */
    private $expiresAt;
    /**
     * @var string
     */
    private $status;
    /**
     * @var int
     */
    private $donorId;
    /**
     * @var SubscriptionPeriod
     */
    private $period;
    /**
     * @var string
     */
    private $frequency;
    /**
     * @var int
     */
    private $installments;
    /**
     * @var string
     */
    private $transactionId;
    /**
     * @var int
     */
    private $amount;
    /**
     * @var int
     */
    private $feeAmount;
    /**
     * @var string
     */
    private $gatewaySubscriptionId;
    /**
     * @var int
     */
    private $donationFormId;

    /**
     * Convert data from Subscription Object to Subscription Model
     *
     * @unreleased
     *
     * @return self
     */
    public static function fromObject($subscriptionQueryObject)
    {
        $self = new static();

        $self->id = (int)$subscriptionQueryObject->id;
        $self->createdAt = $self->toDateTime($subscriptionQueryObject->created);
        $self->expiresAt = $self->toDateTime($subscriptionQueryObject->expiration);
        $self->donorId = (int)$subscriptionQueryObject->customer_id;
        $self->period = new SubscriptionPeriod($subscriptionQueryObject->period);
        $self->frequency = (int)$subscriptionQueryObject->frequency;
        $self->installments = (int)$subscriptionQueryObject->bill_times;
        $self->transactionId = $subscriptionQueryObject->transaction_id;
        $self->amount = (int)$subscriptionQueryObject->recurring_amount;
        $self->feeAmount = (int)$subscriptionQueryObject->recurring_fee_amount;
        $self->status = new SubscriptionStatus($subscriptionQueryObject->status);
        $self->gatewaySubscriptionId = $subscriptionQueryObject->profile_id;
        $self->donationFormId = (int)$subscriptionQueryObject->product_id;

        return $self;
    }

    /**
     * Convert DTO to Subscription
     *
     * @return Subscription
     */
    public function toSubscription()
    {
        return new Subscription([
            'id' => $this->id,
            'createdAt' => $this->createdAt,
            'amount' => $this->amount,
            'period' => $this->period,
            'frequency' => $this->frequency,
            'donorId' => $this->donorId,
            'installments' => $this->installments,
            'transactionId' => $this->transactionId,
            'feeAmount' => $this->feeAmount,
            'status' => $this->status,
            'gatewaySubscriptionId' => $this->gatewaySubscriptionId,
            'donationFormId' => $this->donationFormId
        ]);
    }
}