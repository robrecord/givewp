<?php

namespace Give\Subscriptions\Repositories;

use Exception;
use Give\Donations\ValueObjects\DonationMetaKeys;
use Give\Framework\Database\DB;
use Give\Framework\Exceptions\Primitives\InvalidArgumentException;
use Give\Framework\Models\ModelQueryBuilder;
use Give\Framework\Support\Facades\DateTime\Temporal;
use Give\Helpers\Hooks;
use Give\Log\Log;
use Give\Subscriptions\Models\Subscription;

/**
 * @since 2.19.6
 */
class SubscriptionRepository
{

    /**
     * @var string[]
     */
    private $requiredSubscriptionProperties = [
        'donorId',
        'period',
        'frequency',
        'amount',
        'status',
        'donationFormId',
    ];

    /**
     * @since 2.19.6
     *
     * @param int $subscriptionId
     *
     * @return Subscription
     */
    public function getById($subscriptionId)
    {
        return $this->queryById($subscriptionId)->get();
    }

    /**
     * @since 2.19.6
     *
     * @param int $subscriptionId
     *
     * @return ModelQueryBuilder
     */
    public function queryById($subscriptionId)
    {
        return $this->prepareQuery()
            ->where('id', $subscriptionId);
    }

    /**
     * @since 2.19.6
     *
     * @param int $donationId
     *
     * @return ModelQueryBuilder
     */
    public function queryByDonationId($donationId)
    {
        return $this->prepareQuery()
            ->where('parent_payment_id', $donationId);
    }

    /**
     * @since 2.19.6
     *
     * @param int $donorId
     *
     * @return ModelQueryBuilder
     */
    public function queryByDonorId($donorId)
    {
        return $this->prepareQuery()
            ->where('customer_id', $donorId);
    }

    /**
     * @since 2.19.6
     *
     * @param int $id
     *
     * @return object[]
     */
    public function getNotesBySubscriptionId($id)
    {
        $notes = DB::table('comments')
            ->select(
                ['comment_content', 'note'],
                ['comment_date', 'date']
            )
            ->where('comment_post_ID', $id)
            ->where('comment_type', 'give_sub_note')
            ->orderBy('comment_date', 'DESC')
            ->getAll();

        if (!$notes) {
            return [];
        }

        return $notes;
    }

    /**
     * @since 2.19.6
     *
     * @param Subscription $subscription
     *
     * @return void
     * @throws Exception
     */
    public function insert(Subscription $subscription)
    {
        $this->validateSubscription($subscription);

        Hooks::doAction('give_subscription_creating', $subscription);

        $dateCreated = Temporal::withoutMicroseconds($subscription->createdAt ?: Temporal::getCurrentDateTime());

        DB::query('START TRANSACTION');

        try {
            DB::table('give_subscriptions')->insert([
                'created' => Temporal::getFormattedDateTime($dateCreated),
                'status' => $subscription->status->getValue(),
                'profile_id' => $subscription->gatewaySubscriptionId ?? '',
                'customer_id' => $subscription->donorId,
                'period' => $subscription->period->getValue(),
                'frequency' => $subscription->frequency,
                'initial_amount' => $subscription->amount->formatToDecimal(),
                'recurring_amount' => $subscription->amount->formatToDecimal(),
                'recurring_fee_amount' => $subscription->feeAmountRecovered !== null ? $subscription->feeAmountRecovered->formatToDecimal() : 0,
                'bill_times' => $subscription->installments,
                'transaction_id' => $subscription->transactionId ?? '',
                'product_id' => $subscription->donationFormId,
            ]);
        } catch (Exception $exception) {
            DB::query('ROLLBACK');

            Log::error('Failed creating a subscription', compact('subscription'));

            throw new $exception('Failed creating a subscription');
        }

        DB::query('COMMIT');

        $subscriptionId = DB::last_insert_id();

        $subscription->id = $subscriptionId;
        $subscription->createdAt = $dateCreated;

        if (!isset($subscription->expiresAt)) {
            $subscription->expiresAt = null;
        }

        Hooks::doAction('give_subscription_created', $subscription);
    }

    /**
     * @since 2.19.6
     *
     * @param Subscription $subscription
     *
     * @return Subscription
     * @throws Exception
     */
    public function update(Subscription $subscription)
    {
        $this->validateSubscription($subscription);

        Hooks::doAction('give_subscription_updating', $subscription);

        DB::query('START TRANSACTION');

        try {
            DB::table('give_subscriptions')
                ->where('id', $subscription->id)
                ->update([
                    'status' => $subscription->status->getValue(),
                    'profile_id' => $subscription->gatewaySubscriptionId,
                    'customer_id' => $subscription->donorId,
                    'period' => $subscription->period->getValue(),
                    'frequency' => $subscription->frequency,
                    'initial_amount' => $subscription->amount->formatToDecimal(),
                    'recurring_amount' => $subscription->amount->formatToDecimal(),
                    'recurring_fee_amount' => isset($subscription->feeAmountRecovered) ? $subscription->feeAmountRecovered->formatToDecimal() : 0,
                    'bill_times' => $subscription->installments,
                    'transaction_id' => $subscription->transactionId ?? '',
                    'product_id' => $subscription->donationFormId,
                ]);
        } catch (Exception $exception) {
            DB::query('ROLLBACK');

            Log::error('Failed updating a subscription', compact('subscription'));

            throw new $exception('Failed updating a subscription');
        }

        DB::query('COMMIT');

        $subscriptionId = DB::last_insert_id();

        $subscription = $this->getById($subscriptionId);

        Hooks::doAction('give_subscription_updated', $subscription);

        return $subscription;
    }

    /**
     * @since 2.20.0 consolidate meta deletion into a single query
     * @since 2.19.6
     *
     * @param Subscription $subscription
     *
     * @return bool
     *
     * @throws Exception
     */
    public function delete(Subscription $subscription)
    {
        Hooks::doAction('give_subscription_deleting', $subscription);

        DB::query('START TRANSACTION');

        try {
            DB::table('give_subscriptions')
                ->where('id', $subscription->id)
                ->delete();

            DB::table('give_subscriptionmeta')
                ->where('subscription_id', $subscription->id)
                ->delete();
        } catch (Exception $exception) {
            DB::query('ROLLBACK');

            Log::error('Failed deleting a subscription', compact('subscription'));

            throw new $exception('Failed deleting a subscription');
        }

        DB::query('COMMIT');

        Hooks::doAction('give_subscription_model_deleted', $subscription);

        return true;
    }

    /**
     * @since 2.19.6
     *
     * @param int $subscriptionId
     * @param array $columns
     *
     * @return bool
     * @throws Exception
     */
    public function updateLegacyColumns($subscriptionId, $columns)
    {
        foreach (Subscription::propertyKeys() as $key) {
            if (array_key_exists($key, $columns)) {
                throw new InvalidArgumentException("'$key' is not a legacy column.");
            }
        }

        DB::query('START TRANSACTION');

        try {
            DB::table('give_subscriptions')
                ->where('id', $subscriptionId)
                ->update($columns);
        } catch (Exception $exception) {
            DB::query('ROLLBACK');

            Log::error('Failed updating a subscription', compact('subscriptionId', 'columns'));

            throw new $exception('Failed updating a subscription');
        }

        DB::query('COMMIT');

        return true;
    }

    /**
     * @since 2.19.6
     *
     * @param int $subscriptionId
     *
     * @return int|null
     */
    public function getInitialDonationId($subscriptionId)
    {
        $query = DB::table('give_subscriptions')
            ->where('id', $subscriptionId)
            ->select(['parent_payment_id', 'initialDonationId'])
            ->get();

        if (!$query) {
            return null;
        }

        return (int)$query->initialDonationId;
    }

    /**
     * @param Subscription $subscription
     *
     * @return void
     */
    private function validateSubscription(Subscription $subscription)
    {
        foreach ($this->requiredSubscriptionProperties as $key) {
            if (!isset($subscription->$key)) {
                throw new InvalidArgumentException("'$key' is required.");
            }
        }

        if (!$subscription->donor) {
            throw new InvalidArgumentException("Invalid donorId, Donor does not exist");
        }
    }

    /**
     * @since 2.19.6
     *
     * @return ModelQueryBuilder<Subscription>
     */
    public function prepareQuery()
    {
        $builder = new ModelQueryBuilder(Subscription::class);

        return $builder->from('give_subscriptions')
            ->select(
                'id',
                ['created', 'createdAt'],
                ['expiration', 'expiresAt'],
                ['customer_id', 'donorId'],
                'period',
                ['frequency', 'frequency'],
                ['bill_times', 'installments'],
                ['transaction_id', 'transactionId'],
                ['recurring_amount', 'amount'],
                ['recurring_fee_amount', 'feeAmount'],
                'status',
                ['profile_id', 'gatewaySubscriptionId'],
                ['product_id', 'donationFormId']
            )
            ->attachMeta(
                'give_donationmeta',
                'parent_payment_id',
                'donation_id',
                [DonationMetaKeys::GATEWAY, 'gatewayId'],
                [DonationMetaKeys::CURRENCY, 'currency']
            );
    }
}
