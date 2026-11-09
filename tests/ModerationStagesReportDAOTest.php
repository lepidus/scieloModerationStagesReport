<?php

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.security.Role');
import('plugins.reports.scieloModerationStagesReport.classes.ModerationStagesReportDAO');

use Illuminate\Database\Capsule\Manager as Capsule;

class ModerationStagesReportDAOTest extends DatabaseTestCase
{
    private const AREA_MODERATOR_ABBREV = 'AM';
    private const FIRST_CONTEXT_ID = 990001;
    private const SECOND_CONTEXT_ID = 990002;

    private $dao;
    private $submissionIds = [];
    private $userGroupIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = new ModerationStagesReportDAO();
    }

    protected function tearDown(): void
    {
        Capsule::table('stage_assignments')->whereIn('submission_id', $this->submissionIds)->delete();
        Capsule::table('user_group_settings')->whereIn('user_group_id', $this->userGroupIds)->delete();
        Capsule::table('user_groups')->whereIn('user_group_id', $this->userGroupIds)->delete();
        Capsule::table('submissions')->whereIn('submission_id', $this->submissionIds)->delete();
        parent::tearDown();
    }

    private function createSubmission(int $contextId, ?string $dateSubmitted): int
    {
        $submissionId = Capsule::table('submissions')->insertGetId([
            'context_id' => $contextId,
            'date_submitted' => $dateSubmitted,
            'locale' => 'en_US',
        ], 'submission_id');
        $this->submissionIds[] = $submissionId;

        return $submissionId;
    }

    private function createAreaModeratorGroup(int $contextId): int
    {
        $userGroupId = Capsule::table('user_groups')->insertGetId([
            'context_id' => $contextId,
            'role_id' => ROLE_ID_SUB_EDITOR,
        ], 'user_group_id');
        Capsule::table('user_group_settings')->insert([
            'user_group_id' => $userGroupId,
            'locale' => 'en_US',
            'setting_name' => 'abbrev',
            'setting_value' => self::AREA_MODERATOR_ABBREV,
            'setting_type' => 'string',
        ]);
        $this->userGroupIds[] = $userGroupId;

        return $userGroupId;
    }

    public function testListsOnlySubmittedSubmissionsOfTheGivenContext(): void
    {
        $this->createSubmission(self::FIRST_CONTEXT_ID, '2026-01-10 10:00:00');
        $secondContextSubmissionId = $this->createSubmission(self::SECOND_CONTEXT_ID, '2026-01-11 10:00:00');
        $this->createSubmission(self::SECOND_CONTEXT_ID, null);

        $this->assertEquals([$secondContextSubmissionId], $this->dao->getAllSubmissionsIds(self::SECOND_CONTEXT_ID));
    }

    public function testCountsAreaModeratorsUsingTheGroupOfTheSubmissionContext(): void
    {
        $this->createAreaModeratorGroup(self::FIRST_CONTEXT_ID);
        $secondContextGroupId = $this->createAreaModeratorGroup(self::SECOND_CONTEXT_ID);
        $submissionId = $this->createSubmission(self::SECOND_CONTEXT_ID, '2026-01-11 10:00:00');

        Capsule::table('stage_assignments')->insert([
            'submission_id' => $submissionId,
            'user_group_id' => $secondContextGroupId,
            'user_id' => Capsule::table('users')->min('user_id'),
            'date_assigned' => '2026-01-12 10:00:00',
        ]);

        $this->assertEquals(1, $this->dao->countAreaModerators($submissionId));
    }

    public function testCountsNoAreaModeratorsWhenTheSubmissionContextHasNoSuchGroup(): void
    {
        $this->createAreaModeratorGroup(self::FIRST_CONTEXT_ID);
        $submissionId = $this->createSubmission(self::SECOND_CONTEXT_ID, '2026-01-11 10:00:00');

        $this->assertEquals(0, $this->dao->countAreaModerators($submissionId));
    }
}
