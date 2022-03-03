<?php

namespace Give\Subscriptions\Factories;

use Exception;
use Give\Donations\Models\Donation;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Framework\Database\DB;
use Give\Framework\Models\Factories\ModelFactory;
use Give\Subscriptions\Models\Subscription;
use Give\Subscriptions\ValueObjects\SubscriptionPeriod;
use Give\Subscriptions\ValueObjects\SubscriptionStatus;

class SubscriptionFactory extends ModelFactory
{

    /**
     * @unreleased
     *
     * @return array
     */
    public function definition()
    {
        return [
            'amount' => $this->faker->numberBetween(25, 50000),
            'period' => SubscriptionPeriod::MONTH(),
            'frequency' => $this->faker->numberBetween(1, 4),
            'donorId' => 1,
            'installments' => $this->faker->numberBetween(0, 12),
            'feeAmount' => 0,
            'status' => SubscriptionStatus::PENDING(),
            'donationFormId' => 1
        ];
    }

    /**
     * @unreleased
     *
     * @throws Exception
     */
    public function createRenewal($subscriptionId, array $attributes = [])
    {
        return Donation::factory()->create(
            array_merge([
                'status' => DonationStatus::RENEWAL(),
                'subscriptionId' => $subscriptionId,
                'parentId' => give()->subscriptions->getInitialDonationId($subscriptionId),
            ], $attributes)
        );
    }

    /**
     * @unreleased
     *
     * @param $model
     * @return void
     * @throws Exception
     */
    public function afterCreating($model)
    {
        /** @var Subscription $subscription */
        $subscription = $model;

        $query = DB::table('give_subscriptions')
            ->where('id', $subscription->id)
            ->select(['parent_payment_id', 'initialDonationId'])
            ->get();

        // for backwards compatability update the subscription parent_payment_id column
        if ($query && !$query->initialDonationId) {
            $donation = Donation::factory()->create(['donorId' => $subscription->donorId]);
            give()->subscriptions->updateLegacyColumns($subscription->id, ['parent_payment_id' => $donation->id]);
        }
    }
}
