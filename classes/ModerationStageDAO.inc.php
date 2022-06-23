<?php

/**
 * @file plugins/reports/scieloModerationStagesReport/classes/ModerationStageDAO.inc.php
 *
 * @class ModerationStageDAO
 * @ingroup plugins_reports_scieloModerationStagesReport
 *
 * Operations for retrieving data to help identify submissions' moderation stage
 */

import('lib.pkp.classes.db.DAO');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

class ModerationStageDAO extends DAO {
    public function getSubmissionModerationStage($submissionId): ?int {
        $result = Capsule::table('submission_settings')
            ->where('submission_id', $submissionId)
            ->where('setting_name', 'currentModerationStage')
            ->select('setting_value')
            ->first();

        return !is_null($result) ? get_object_vars($result)['setting_value'] : null;
    }
    
    public function submissionHasUserAssigned($username, $submissionId): bool {
        $result = Capsule::table('users')
            ->where('username', $username)
            ->select('user_id')
            ->first();
        $userId = get_object_vars($result)['user_id'];

        $countAssignedUsers = Capsule::table('stage_assignments')
            ->where('submission_id', $submissionId)
            ->where('user_id', $userId)
            ->count();

        return $countAssignedUsers == 1;
    }
    
    public function submissionHasModerators($submissionId): bool {
        return $this->countAssignedUsersOfGroup($submissionId, "Moderator") > 0;
    }
    
    public function countAreaModerators($submissionId): int {
        return $this->countAssignedUsersOfGroup($submissionId, "Area Moderator");
    }
    
    public function submissionHasNotes($submissionId): bool {
        $numNotes = Capsule::table('notes')
            ->where('assoc_type', ASSOC_TYPE_SUBMISSION)
            ->where('assoc_id', $submissionId)
            ->count();

        return $numNotes > 0;
    }

    private function countAssignedUsersOfGroup($submissionId, $userGroupName): int {
        $result = Capsule::table('user_group_settings')
            ->where('setting_name', 'name')
            ->where('setting_value', $userGroupName)
            ->select('user_group_id')
            ->first();
        $userGroupId = get_object_vars($result)['user_group_id'];

        $countAssignedUsersOfGroup = Capsule::table('stage_assignments')
            ->where('submission_id', $submissionId)
            ->where('user_group_id', $userGroupId)
            ->count();
        
        return $countAssignedUsersOfGroup;
    }
}