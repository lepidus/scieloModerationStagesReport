<?php

namespace APP\plugins\reports\scieloModerationStagesReport\classes;

use PKP\facades\Locale;
use APP\plugins\reports\scieloModerationStagesReport\classes\ModeratedSubmissionHelper;
use APP\plugins\reports\scieloModerationStagesReport\classes\ModerationStagesReport;
use APP\plugins\reports\scieloModerationStagesReport\classes\ModerationStagesReportDAO;

class ModerationStagesReportHelper
{
    public function __construct()
    {
        $this->moderationStagesReportDAO = new ModerationStagesReportDAO();
    }

    public function createModerationStagesReport(): ModerationStagesReport
    {
        $allSubmissionsIds = $this->moderationStagesReportDAO->getAllSubmissionsIds();
        $locale = Locale::getLocale();

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
