<?php

namespace unit\tests\Donations\Repositories;

use Exception;
use Give\Donations\Models\Donation;
use Give\Donations\Models\DonationNote;
use Give\Donations\Repositories\DonationNotesRepository;
use Give\Donors\Models\Donor;
use Give\Framework\Database\DB;
use Give\Framework\Exceptions\Primitives\InvalidArgumentException;

/**
 * @coversDefaultClass DonationNotesRepository
 */
final class TestDonationNoteRepository extends \Give_Unit_Test_Case
{

    /**
     * @unreleased - truncate donationMetaTable to avoid duplicate records
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $donationMetaTable = DB::prefix('give_donationmeta');
        $donationsTable = DB::prefix('posts');
        $donorsTable = DB::prefix('give_donors');
        $donorMetaTable = DB::prefix('give_donormeta');
        $notesTable = DB::prefix('give_comments');

        DB::query("TRUNCATE TABLE $donationMetaTable");
        DB::query("TRUNCATE TABLE $donationsTable");
        DB::query("TRUNCATE TABLE $donorsTable");
        DB::query("TRUNCATE TABLE $donorMetaTable");
        DB::query("TRUNCATE TABLE $notesTable");
    }

    /**
     * @unreleased
     *
     * @return void
     *
     * @throws Exception
     */
    public function testGetByIdShouldReturnDonationNote()
    {
        $donor = Donor::factory()->create();
        $donation = Donation::factory()->create(['donorId' => $donor->id]);
        $donationNote = DonationNote::factory()->create(['donationId' => $donation->id]);
        $repository = new DonationNotesRepository();

        $donationNoteFromDatabase = $repository->getById($donationNote->id);

        $this->assertInstanceOf(DonationNote::class, $donationNoteFromDatabase);
        $this->assertEquals($donationNote->id, $donationNoteFromDatabase->id);
    }

    /**
     * @unreleased
     *
     * @return void
     *
     * @throws Exception
     */
    public function testInsertShouldAddDonationNoteToDatabase()
    {
        $donor = Donor::factory()->create();
        $donation = Donation::factory()->create(['donorId' => $donor->id]);
        $donationNote = new DonationNote(['donationId' => $donation->id, 'content' => 'im a note']);

        $repository = new DonationNotesRepository();

        $repository->insert($donationNote);

        /** @var DonationNote $query */
        $query = $repository->prepareQuery()
            ->where('comment_ID', $donationNote->id)
            ->get();


        // simulate asserting database has values
        $this->assertInstanceOf(DonationNote::class, $donationNote);
        $this->assertEquals($query->id, $donationNote->id);
        $this->assertEquals($query->donationId, $donationNote->donationId);
        $this->assertEquals($query->content, $donationNote->content);
    }

    /**
     * @unreleased
     *
     * @return void
     *
     * @throws Exception
     */
    public function testInsertShouldFailValidationWhenMissingKeyAndThrowException()
    {
        $this->expectException(InvalidArgumentException::class);

        $donationNoteMissingDonationId = new DonationNote([
            'content' => 'im a note',
        ]);

        $repository = new DonationNotesRepository();

        $repository->insert($donationNoteMissingDonationId);
    }

    /**
     * @unreleased
     *
     * @return void
     *
     * @throws Exception
     */
    public function testInsertShouldFailValidationWhenDonationDoesNotExistAndThrowException()
    {
        $this->expectException(InvalidArgumentException::class);

        $donationNoteWithInvalidDonation = new DonationNote([
            'donationId' => 10000,
            'content' => 'im a note'
        ]);

        $repository = new DonationNotesRepository();

        $repository->insert($donationNoteWithInvalidDonation);
    }

    /**
     * @unreleased
     *
     * @return void
     *
     * @throws Exception
     */
    public function testUpdateShouldFailValidationAndThrowException()
    {
        $this->expectException(InvalidArgumentException::class);

        $donationNoteMissingDonationId = new DonationNote([
            'content' => 'im a note'
        ]);

        $repository = new DonationNotesRepository();

        $repository->update($donationNoteMissingDonationId);
    }

    /**
     * @unreleased
     *
     * @return void
     *
     * @throws Exception
     */
    public function testUpdateShouldUpdateDonationNoteValuesInTheDatabase()
    {
        /** @var Donor $donor */
        $donor = Donor::factory()->create();

        /** @var Donation $donation */
        $donation = Donation::factory()->create(['donorId' => $donor->id]);

        /** @var DonationNote $donationNote */
        $donationNote = DonationNote::factory()->create(['donationId' => $donation->id]);

        $repository = new DonationNotesRepository();

        $donationNote->content = 'im an updated note';

        // call update method
        $repository->update($donationNote);

        /** @var DonationNote $query */
        $query = $repository->prepareQuery()
            ->where('comment_ID', $donationNote->id)
            ->get();

        $this->assertEquals('im an updated note', $query->content);
    }

    /**
     * @unreleased
     *
     * @return void
     *
     * @throws Exception
     */
    public function testDeleteShouldRemoveDonationNoteFromTheDatabase()
    {
        /** @var Donor $donor */
        $donor = Donor::factory()->create();

        /** @var Donation $donation */
        $donation = Donation::factory()->create(['donorId' => $donor->id]);

        /** @var DonationNote $donationNote */
        $donationNote = DonationNote::factory()->create(['donationId' => $donation->id]);

        $repository = new DonationNotesRepository();

        $repository->delete($donationNote);

        /** @var DonationNote $query */
        $query = $repository->prepareQuery()
            ->where('comment_ID', $donation->id)
            ->get();

        $this->assertNull($query);
    }
}
