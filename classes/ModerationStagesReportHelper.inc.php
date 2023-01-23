<?php

import('plugins.generic.scieloModerationStages.classes.ModerationStage');
import('plugins.reports.scieloModerationStagesReport.classes.ModeratedSubmissionHelper');
import('plugins.reports.scieloModerationStagesReport.classes.ModerationStagesReport');
import('plugins.reports.scieloModerationStagesReport.classes.ModerationStageDAO');

class ModerationStagesReportHelper
{
    public function __construct()
    {
        $this->moderationStageDAO = new ModerationStageDAO();
    }

    public function createModerationStagesReport(): ModerationStagesReport
    {
        $allSubmissionsIds = $this->moderationStageDAO->getAllSubmissionsIds();
        $locale = AppLocale::getLocale();

        $detectedSubmissions = [];
        $nonDetectedSubmissions = [];
        $moderatedSubmissionHelper = new ModeratedSubmissionHelper();

        foreach ($allSubmissionsIds as $submissionId) {
            $moderatedSubmission = $moderatedSubmissionHelper->createModeratedSubmission($submissionId, $locale);

            if ($moderatedSubmission->hasModerationStage()) {
                $detectedSubmissions[] = $moderatedSubmission;
            } else {
                $nonDetectedSubmissions[] = $moderatedSubmission;
            }
        }

        return new ModerationStagesReport($detectedSubmissions, $nonDetectedSubmissions);
    }
}
