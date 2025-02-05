<?php

namespace unit\tests\Donors\Models;

use Exception;
use Give\Donations\Models\Donation;
use Give\Donors\Models\Donor;
use Give\Framework\Database\DB;
use Give\Framework\Support\ValueObjects\Money;
use Give\Subscriptions\Models\Subscription;
use Give_Subscriptions_DB;

/**
 * @unreleased
 *
 * @coversDefaultClass \Give\Subscriptions\Models\Subscription
 */
class TestDonor extends \Give_Unit_Test_Case
{

    public function setUp()
    {
        parent::setUp();

        /** @var Give_Subscriptions_DB $legacySubscriptionDb */
        $legacySubscriptionDb = give(Give_Subscriptions_DB::class);

        $legacySubscriptionDb->create_table();
    }

    /**
     * @unreleased
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $donationsTable = DB::prefix('posts');
        $donationMetaTable = DB::prefix('give_donationmeta');
        $donorTable = DB::prefix('give_donors');
        $donorMetaTable = DB::prefix('give_donormeta');
        $subscriptionsTable = DB::prefix('give_subscriptions');
        $sequentialOrderingTable = DB::prefix('give_sequential_ordering');

        DB::query("TRUNCATE TABLE $donorTable");
        DB::query("TRUNCATE TABLE $donorMetaTable");
        DB::query("TRUNCATE TABLE $donationMetaTable");
        DB::query("TRUNCATE TABLE $donationsTable");
        DB::query("TRUNCATE TABLE $subscriptionsTable");
        DB::query("TRUNCATE TABLE $sequentialOrderingTable");
    }

    /**
     * @unreleased
     *
     * @return void
     *
     * @throws Exception
     */
    public function testCreateShouldInsertDonor()
    {
        $donor = Donor::factory()->create();

        $donorFromDatabase = Donor::find($donor->id);

        $this->assertEquals($donor->getAttributes(), $donorFromDatabase->getAttributes());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testShouldGetDonations()
    {
        /** @var Donor $donor */
        $donor = Donor::factory()->create();

        Donation::factory()->create(['donorId' => $donor->id]);
        Donation::factory()->create(['donorId' => $donor->id]);

        $this->assertCount(2, $donor->donations);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testShouldGetTotalDonations()
    {
        /** @var Donor $donor */
        $donor = Donor::factory()->create();

        Donation::factory()->create(['donorId' => $donor->id]);
        Donation::factory()->create(['donorId' => $donor->id]);

        $this->assertEquals(2, $donor->totalDonations());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testShouldGetSubscriptions()
    {
        /** @var Donor $donor */
        $donor = Donor::factory()->create();

        $subscription1 = Subscription::factory()->create(['donorId' => $donor->id]);
        $subscription2 = Subscription::factory()->create(['donorId' => $donor->id]);

        $this->assertCount(2, $donor->subscriptions);
    }
}
