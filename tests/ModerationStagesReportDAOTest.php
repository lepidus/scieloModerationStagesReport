<?php

use Illuminate\Support\Facades\DB;
use PKP\security\Role;
use PKP\tests\DatabaseTestCase;
use APP\plugins\reports\scieloModerationStagesReport\classes\ModerationStagesReportDAO;

class ModerationStagesReportDAOTest extends DatabaseTestCase
{
    private const AREA_MODERATOR_ABBREV = 'AM';

    private $dao;
    private $serverIds = [];
    private $submissionIds = [];
    private $userGroupIds = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->dao = new ModerationStagesReportDAO();
    }

    protected function tearDown(): void
    {
        DB::table('stage_assignments')->whereIn('submission_id', $this->submissionIds)->delete();
        DB::table('user_group_settings')->whereIn('user_group_id', $this->userGroupIds)->delete();
        DB::table('user_groups')->whereIn('user_group_id', $this->userGroupIds)->delete();
        DB::table('submissions')->whereIn('submission_id', $this->submissionIds)->delete();
        DB::table('servers')->whereIn('server_id', $this->serverIds)->delete();
        parent::tearDown();
    }

    private function createServer(string $path): int
    {
        $serverId = DB::table('servers')->insertGetId([
            'path' => $path,
            'primary_locale' => 'en',
            'enabled' => 1,
        ], 'server_id');
        $this->serverIds[] = $serverId;

        return $serverId;
    }

    private function createSubmission(int $contextId, ?string $dateSubmitted): int
    {
        $submissionId = DB::table('submissions')->insertGetId([
            'context_id' => $contextId,
            'date_submitted' => $dateSubmitted,
            'locale' => 'en',
        ], 'submission_id');
        $this->submissionIds[] = $submissionId;

        return $submissionId;
    }

    private function createAreaModeratorGroup(int $contextId): int
    {
        $userGroupId = DB::table('user_groups')->insertGetId([
            'context_id' => $contextId,
            'role_id' => Role::ROLE_ID_SUB_EDITOR,
        ], 'user_group_id');
        DB::table('user_group_settings')->insert([
            'user_group_id' => $userGroupId,
            'locale' => 'en',
            'setting_name' => 'abbrev',
            'setting_value' => self::AREA_MODERATOR_ABBREV,
        ]);
        $this->userGroupIds[] = $userGroupId;

        return $userGroupId;
    }

    public function testListsOnlySubmittedSubmissionsOfTheGivenContext(): void
    {
        $firstServerId = $this->createServer('smsrTestServerOne');
        $secondServerId = $this->createServer('smsrTestServerTwo');
        $this->createSubmission($firstServerId, '2026-01-10 10:00:00');
        $secondServerSubmissionId = $this->createSubmission($secondServerId, '2026-01-11 10:00:00');
        $this->createSubmission($secondServerId, null);

        $this->assertEquals([$secondServerSubmissionId], $this->dao->getAllSubmissionsIds($secondServerId));
    }

    public function testCountsAreaModeratorsUsingTheGroupOfTheSubmissionContext(): void
    {
        $firstServerId = $this->createServer('smsrTestServerOne');
        $secondServerId = $this->createServer('smsrTestServerTwo');
        $this->createAreaModeratorGroup($firstServerId);
        $secondServerGroupId = $this->createAreaModeratorGroup($secondServerId);
        $submissionId = $this->createSubmission($secondServerId, '2026-01-11 10:00:00');

        DB::table('stage_assignments')->insert([
            'submission_id' => $submissionId,
            'user_group_id' => $secondServerGroupId,
            'user_id' => DB::table('users')->min('user_id'),
            'date_assigned' => '2026-01-12 10:00:00',
        ]);

        $this->assertEquals(1, $this->dao->countAreaModerators($submissionId));
    }
}
