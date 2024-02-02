<?php

/**
 * @file plugins/reports/scieloModerationStagesReport/classes/ModerationStagesReportDAO.inc.php
 *
 * @class ModerationStagesReportDAO
 * @ingroup plugins_reports_scieloModerationStagesReport
 *
 * Operations for retrieving data to help identify submissions' moderation stage
 */

import('lib.pkp.classes.db.DAO');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

class ModerationStagesReportDAO extends DAO
{
    private const SUBMISSION_STAGE_ID = 5;

    public function getAllSubmissionsIds(): array
    {
        $result = Capsule::table('submissions')
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
        $result = Capsule::table('submissions')
        ->where('submission_id', '=', $submissionId)
        ->select('current_publication_id')
        ->first();
        $publicationId = get_object_vars($result)['current_publication_id'];

        $result = Capsule::table('publication_settings')
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
        $result = Capsule::table('submission_settings')
            ->where('submission_id', $submissionId)
            ->where('setting_name', 'currentModerationStage')
            ->select('setting_value')
            ->first();

        return !is_null($result) ? get_object_vars($result)['setting_value'] : null;
    }

    public function submissionHasUserAssigned($username, $submissionId): bool
    {
        $result = Capsule::table('users')
            ->where('username', $username)
            ->select('user_id')
            ->first();
        $userId = get_object_vars($result)['user_id'];

        $countAssignedUsers = Capsule::table('stage_assignments')
            ->where('submission_id', $submissionId)
            ->where('user_id', $userId)
            ->count();

        return $countAssignedUsers >= 1;
    }

    public function submissionHasResponsibles($submissionId): bool
    {
        return $this->countAssignedUsersOfGroup($submissionId, "RESP") > 0;
    }

    public function countAreaModerators($submissionId): int
    {
        return $this->countAssignedUsersOfGroup($submissionId, "AM");
    }

    public function submissionHasNotes($submissionId): bool
    {
        $numNotes = Capsule::table('notes')
            ->where('assoc_type', ASSOC_TYPE_SUBMISSION)
            ->where('assoc_id', $submissionId)
            ->count();

        return $numNotes > 0;
    }

    private function countAssignedUsersOfGroup($submissionId, $userGroupAbbrev): int
    {
        $result = Capsule::table('user_group_settings')
            ->where('setting_name', 'abbrev')
            ->where('setting_value', $userGroupAbbrev)
            ->select('user_group_id')
            ->first();
        $userGroupId = get_object_vars($result)['user_group_id'];

        $countAssignedUsersOfGroup = Capsule::table('stage_assignments')
            ->where('submission_id', $submissionId)
            ->where('user_group_id', $userGroupId)
            ->count();

        return $countAssignedUsersOfGroup;
    }

    public function getSubmitterData($submissionId): array
    {
        $result = Capsule::table('event_log')
        ->where('event_type', SUBMISSION_LOG_SUBMISSION_SUBMIT)
        ->where('assoc_type', ASSOC_TYPE_SUBMISSION)
        ->where('assoc_id', $submissionId)
        ->select('user_id')
        ->get();
        $result = $result->toArray();

        if (empty($result)) {
            return [null, null];
        }

        $submitterId = get_object_vars($result[0])['user_id'];
        $userDao = DAORegistry::getDAO('UserDAO');
        $submitter = $userDao->getById($submitterId);
        $submitterIsScieloJournal = $this->getSubmitterIsScieloJournal($submitterId);

        return [$submitter->getFullName(), $submitterIsScieloJournal];
    }

    private function getSubmitterIsScieloJournal($submitterId): bool
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');

        $submitterUserGroups = $userGroupDao->getByUserId($submitterId);
        while ($userGroup = $submitterUserGroups->next()) {
            $journalGroupAbbrev = "SciELO";
            if ($userGroup->getLocalizedData('abbrev', 'pt_BR') == $journalGroupAbbrev) {
                return true;
            }
        }

        return false;
    }

    public function getSubmissionStatus($submissionId): int
    {
        $result = Capsule::table('submissions')
        ->where('submission_id', '=', $submissionId)
        ->select('status')
        ->first();

        return get_object_vars($result)['status'];
    }

    public function getResponsibles($submissionId): array
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $moderatorUsers =  array();
        $stageAssignmentsResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, self::SUBMISSION_STAGE_ID);

        while ($stageAssignment = $stageAssignmentsResults->next()) {
            $user = $userDao->getById($stageAssignment->getUserId(), false);
            $userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
            $currentUserGroupAbbrev = strtolower($userGroup->getData('abbrev', 'en_US'));

            if ($currentUserGroupAbbrev == 'resp') {
                array_push($moderatorUsers, $user->getFullName());
            }
        }
        return $moderatorUsers;
    }

    public function getAreaModerators($submissionId): array
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $areaModeratorUsers =  array();
        $stageAssignmentsResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, self::SUBMISSION_STAGE_ID);

        while ($stageAssignment = $stageAssignmentsResults->next()) {
            $user = $userDao->getById($stageAssignment->getUserId(), false);
            $userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
            $currentUserGroupAbbrev = strtolower($userGroup->getData('abbrev', 'en_US'));

            if ($currentUserGroupAbbrev == 'am') {
                array_push($areaModeratorUsers, $user->getFullName());
            }
        }
        return $areaModeratorUsers;
    }

    public function getFinalDecision($submissionId, $locale): string
    {
        $submissionStatus = $this->getSubmissionStatus($submissionId);

        $result = Capsule::table('publications')
        ->where('submission_id', '=', $submissionId)
        ->select('date_published')
        ->first();
        $datePublished = get_object_vars($result)['date_published'];

        if (!is_null($datePublished) && $submissionStatus == STATUS_PUBLISHED) {
            return __('common.accepted', [], $locale);
        }

        $possibleFinalDecisions = [SUBMISSION_EDITOR_DECISION_ACCEPT, SUBMISSION_EDITOR_DECISION_DECLINE, SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE];

        $result = Capsule::table('edit_decisions')
        ->where('submission_id', $submissionId)
        ->whereIn('decision', $possibleFinalDecisions)
        ->orderBy('date_decided', 'asc')
        ->select('decision')
        ->first();

        if (is_null($result)) {
            return "";
        }

        $decision = get_object_vars($result)['decision'];
        if ($decision == SUBMISSION_EDITOR_DECISION_ACCEPT) {
            return __('common.accepted', [], $locale);
        } else {
            return  __('common.declined', [], $locale);
        }
    }

    public function getNotes($submissionId): array
    {
        $resultNotes = Capsule::table('notes')
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
