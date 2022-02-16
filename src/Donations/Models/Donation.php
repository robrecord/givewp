<?php

namespace Give\Donations\Models;

use DateTime;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Donors\Models\Donor;
use Give\Framework\Database\Exceptions\DatabaseQueryException;
use Give\Framework\Models\Model;
use Give\Subscriptions\Models\Subscription;

/**
 * Class Donation
 *
 * @unreleased
 *
 * @property int $id
 * @property DateTime $createdAt
 * @property DateTime $updatedAt
 * @property DonationStatus $status
 * @property int $amount
 * @property string $currency
 * @property string $gateway
 * @property int $donorId
 * @property string $firstName
 * @property string $lastName
 * @property string $email
 * @property int $parentId
 * @property int $subscriptionId
 */
class Donation extends Model
{
    /**
     * @var string[]
     */
    protected $properties = [
        'id' => 'int',
        'createdAt' => DateTime::class,
        'updatedAt' => DateTime::class,
        'status' => DonationStatus::class,
        'amount' => 'int',
        'currency' => 'string',
        'gateway' => 'string',
        'donorId' => 'int',
        'firstName' => 'string',
        'lastName' => 'string',
        'email' => 'string',
        'parentId' => 'int',
        'subscriptionId' => 'int',
    ];

    /**
     * Find donation by ID
     *
     * @unreleased
     *
     * @param  int  $id
     * @return Donation
     */
    public static function find($id)
    {
        return give()->donations->getById($id);
    }

    /**
     * @unreleased
     *
     * @return Donor
     */
    public function donor()
    {
        return give()->donorRepository->getById($this->donorId);
    }

    /**
     * @unreleased
     *
     * @return Subscription
     */
    public function subscription()
    {
        if ($this->subscriptionId) {
            return give()->subscriptions->getById($this->subscriptionId);
        }

        return give()->subscriptions->getByDonationId($this->id);
    }

    /**
     * @param  Donation  $donation
     * @return Donation
     * @throws DatabaseQueryException
     */
    public static function create(Donation $donation)
    {
        return give()->donations->insert($donation);
    }

    /**
     * @return Donation
     * @throws DatabaseQueryException
     */
    public function save()
    {
        if (!$this->id) {
            return give()->donations->insert($this);
        }

        return give()->donations->update($this);
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        return give()->donations->getCoreDonationMeta($this);
    }

    /**
     * @return int
     */
    public function getSequentialId()
    {
        return give()->donations->getSequentialId($this->id);
    }
}