<?php

/**
 * @file plugins/reports/scieloModerationStagesReport/classes/ModerationStagesReportDAO.inc.php
 *
 * @class ModerationStagesReportDAO
 * @ingroup plugins_reports_scieloModerationStagesReport
 *
 * Operations for retrieving data to help identify submissions' moderation stage
 */

namespace APP\plugins\reports\scieloModerationStagesReport\classes;

use PKP\db\DAO;
use Illuminate\Support\Facades\DB;
use APP\core\Application;
use APP\facades\Repo;
use PKP\db\DAORegistry;
use PKP\security\Role;
use APP\submission\Submission;
use APP\decision\Decision;
use PKP\log\event\PKPSubmissionEventLogEntry;

class ModerationStagesReportDAO extends DAO
{
    private const SUBMISSION_STAGE_ID = 5;
    private const AREA_MODERATOR_ABBREV = 'AM';
    private const RESPONSIBLE_ABBREV = 'RESP';
    private const SCIELO_JOURNAL_ABBREV = 'SciELO';

    public function getAllSubmissionsIds(): array
    {
        $result = DB::table('submissions')
            ->whereNotNull('date_submitted')
            ->select('submission_id')
            ->get();

        $submissionIds = [];
        foreach ($result->toArray() as $row) {
            $submissionIds[] = get_object_vars($row)['submission_id'];
        }

        return $submissionIds;
    }

    public function getTitle($submissionId, $locale): string
    {
        $result = DB::table('submissions')
        ->where('submission_id', '=', $submissionId)
        ->select('current_publication_id')
        ->first();
        $publicationId = get_object_vars($result)['current_publication_id'];

        $result = DB::table('publication_settings')
        ->where('publication_id', '=', $publicationId)
        ->where('setting_name', '=', 'title')
        ->select('locale', 'setting_value as title')
        ->get();

        $titles = [];
        foreach ($result->toArray() as $row) {
            $title = get_object_vars($row)['title'];
            $locale = get_object_vars($row)['locale'];
            $titles[$locale] = $title;
        }

        if (array_key_exists($locale, $titles)) {
            return $titles[$locale];
        }

        return array_pop(array_reverse($titles));
    }

    public function getSubmissionModerationStage($submissionId): ?int
    {
        $result = DB::table('submission_settings')
            ->where('submission_id', $submissionId)
            ->where('setting_name', 'currentModerationStage')
            ->select('setting_value')
            ->first();

        return !is_null($result) ? get_object_vars($result)['setting_value'] : null;
    }

    public function submissionHasUserAssigned($username, $submissionId): bool
    {
        $result = DB::table('users')
            ->where('username', $username)
            ->select('user_id')
            ->first();
        $userId = get_object_vars($result)['user_id'];

        $countAssignedUsers = DB::table('stage_assignments')
            ->where('submission_id', $submissionId)
            ->where('user_id', $userId)
            ->count();

        return $countAssignedUsers >= 1;
    }

    public function submissionHasResponsibles($submissionId): bool
    {
        return $this->countAssignedUsersOfGroup($submissionId, self::RESPONSIBLE_ABBREV) > 0;
    }

    public function countAreaModerators($submissionId): int
    {
        return $this->countAssignedUsersOfGroup($submissionId, self::AREA_MODERATOR_ABBREV);
    }

    public function submissionHasNotes($submissionId): bool
    {
        $numNotes = DB::table('notes')
            ->where('assoc_type', Application::ASSOC_TYPE_SUBMISSION)
            ->where('assoc_id', $submissionId)
            ->count();

        return $numNotes > 0;
    }

    private function countAssignedUsersOfGroup($submissionId, $userGroupAbbrev): int
    {
        $result = DB::table('user_group_settings')
            ->where('setting_name', 'abbrev')
            ->where('setting_value', $userGroupAbbrev)
            ->select('user_group_id')
            ->first();
        $userGroupId = get_object_vars($result)['user_group_id'];

        $countAssignedUsersOfGroup = DB::table('stage_assignments')
            ->where('submission_id', $submissionId)
            ->where('user_group_id', $userGroupId)
            ->count();

        return $countAssignedUsersOfGroup;
    }

    public function getSubmitterData($submissionId): array
    {
        $result = DB::table('event_log')
            ->where('event_type', PKPSubmissionEventLogEntry::SUBMISSION_LOG_SUBMISSION_SUBMIT)
            ->where('assoc_type', Application::ASSOC_TYPE_SUBMISSION)
            ->where('assoc_id', $submissionId)
            ->select('user_id')
            ->get();
        $result = $result->toArray();

        if (empty($result)) {
            return [null, null];
        }

        $submitterId = get_object_vars($result[0])['user_id'];
        $submitter = Repo::user()->get($submitterId);
        $submitterIsScieloJournal = $this->getSubmitterIsScieloJournal($submitterId);

        return [$submitter->getFullName(), $submitterIsScieloJournal];
    }

    private function getSubmitterIsScieloJournal($submitterId): bool
    {
        $submitterUserGroups = Repo::userGroup()->userUserGroups($submitterId);

        foreach ($submitterUserGroups as $userGroup) {
            if ($userGroup->getLocalizedData('abbrev', 'pt_BR') == self::SCIELO_JOURNAL_ABBREV) {
                return true;
            }
        }

        return false;
    }

    public function getSubmissionStatus($submissionId): int
    {
        $result = DB::table('submissions')
        ->where('submission_id', '=', $submissionId)
        ->select('status')
        ->first();

        return get_object_vars($result)['status'];
    }

    public function getResponsibles($submissionId): array
    {
        return $this->getUsersAssignedByGroup($submissionId, self::RESPONSIBLE_ABBREV);
    }

    public function getAreaModerators($submissionId): array
    {
        return $this->getUsersAssignedByGroup($submissionId, self::AREA_MODERATOR_ABBREV);
    }

    private function getUsersAssignedByGroup($submissionId, $userGroupAbbrev): array
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');

        $assignedUsers =  array();
        $stageAssignmentsResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, Role::ROLE_ID_SUB_EDITOR, self::SUBMISSION_STAGE_ID);

        while ($stageAssignment = $stageAssignmentsResults->next()) {
            $userGroup = Repo::userGroup()->get($stageAssignment->getUserGroupId());
            $currentUserGroupAbbrev = $userGroup->getData('abbrev', 'en');

            if ($currentUserGroupAbbrev == $userGroupAbbrev) {
                $user = Repo::user()->get($stageAssignment->getUserId());
                array_push($assignedUsers, $user->getFullName());
            }
        }
        
        return $assignedUsers;
    }

    public function getFinalDecision($submissionId, $locale): string
    {
        $submissionStatus = $this->getSubmissionStatus($submissionId);

        $result = DB::table('publications')
            ->where('submission_id', '=', $submissionId)
            ->select('date_published')
            ->first();
        $datePublished = get_object_vars($result)['date_published'];

        if (!is_null($datePublished) && $submissionStatus == Submission::STATUS_PUBLISHED) {
            return __('common.accepted', [], $locale);
        }

        $possibleFinalDecisions = [Decision::ACCEPT, Decision::DECLINE, Decision::INITIAL_DECLINE];

        $result = DB::table('edit_decisions')
            ->where('submission_id', $submissionId)
            ->whereIn('decision', $possibleFinalDecisions)
            ->orderBy('date_decided', 'asc')
            ->select('decision')
            ->first();

        if (is_null($result)) {
            return "";
        }

        $decision = get_object_vars($result)['decision'];
        if ($decision == Decision::ACCEPT) {
            return __('common.accepted', [], $locale);
        } else {
            return  __('common.declined', [], $locale);
        }
    }

    public function getNotes($submissionId): array
    {
        $resultNotes = DB::table('notes')
            ->where('assoc_type', 1048585)
            ->where('assoc_id', $submissionId)
            ->select('contents')
            ->get();

        $notes = array();
        foreach ($resultNotes as $noteObject) {
            $note = get_object_vars($noteObject);
            array_push($notes, $note['contents']);
        }

        return $notes;
    }
}
