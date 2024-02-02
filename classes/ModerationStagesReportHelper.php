<?php

import('plugins.reports.scieloModerationStagesReport.classes.ModeratedSubmissionHelper');
import('plugins.reports.scieloModerationStagesReport.classes.ModerationStagesReport');
import('plugins.reports.scieloModerationStagesReport.classes.ModerationStagesReportDAO');

class ModerationStagesReportHelper
{
    public function __construct()
    {
        $this->moderationStagesReportDAO = new ModerationStagesReportDAO();
    }

    public function createModerationStagesReport(): ModerationStagesReport
    {
        $allSubmissionsIds = $this->moderationStagesReportDAO->getAllSubmissionsIds();
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
