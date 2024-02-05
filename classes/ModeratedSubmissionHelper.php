<?php

namespace APP\plugins\reports\scieloModerationStagesReport\classes;

use APP\plugins\reports\scieloModerationStagesReport\classes\ModeratedSubmission;
use APP\plugins\reports\scieloModerationStagesReport\classes\ModerationStagesReportDAO;

class ModeratedSubmissionHelper
{
    public function __construct()
    {
        $this->moderationStagesReportDAO = new ModerationStagesReportDAO();
    }

    public function setDAO($dao)
    {
        $this->moderationStagesReportDAO = $dao;
    }

    public function createModeratedSubmission($submissionId, $locale): ModeratedSubmission
    {
        $title = $this->moderationStagesReportDAO->getTitle($submissionId, $locale);
        $moderationStage = $this->getSubmissionModerationStage($submissionId);
        list($submitterName, $submitterIsScieloJournal) = $this->moderationStagesReportDAO->getSubmitterData($submissionId);
        $submissionStatus = $this->moderationStagesReportDAO->getSubmissionStatus($submissionId);
        $responsibles = $this->moderationStagesReportDAO->getResponsibles($submissionId);
        $areaModerators = $this->moderationStagesReportDAO->getAreaModerators($submissionId);
        $finalDecision = $this->moderationStagesReportDAO->getFinalDecision($submissionId, $locale);
        $notes = $this->moderationStagesReportDAO->getNotes($submissionId);

        return new ModeratedSubmission(
            $submissionId,
            $title,
            $moderationStage,
            $submitterName,
            $submissionStatus,
            $submitterIsScieloJournal,
            $responsibles,
            $areaModerators,
            $finalDecision,
            $notes
        );
    }

    public function getSubmissionModerationStage($submissionId)
    {
        $submissionStage = $this->moderationStagesReportDAO->getSubmissionModerationStage($submissionId);
        if (!is_null($submissionStage)) {
            return $submissionStage;
        }

        if ($this->checkSubmissionOnAreaStage($submissionId)) {
            return ModeratedSubmission::SCIELO_MODERATION_STAGE_REPORT_AREA;
        }

        if ($this->checkSubmissionOnContentStage($submissionId)) {
            return ModeratedSubmission::SCIELO_MODERATION_STAGE_REPORT_CONTENT;
        }

        if ($this->checkSubmissionOnFormatStage($submissionId)) {
            return ModeratedSubmission::SCIELO_MODERATION_STAGE_REPORT_FORMAT;
        }

        return null;
    }

    public function checkSubmissionOnFormatStage($submissionId): bool
    {
        $hasResponsibles = $this->moderationStagesReportDAO->submissionHasResponsibles($submissionId);
        $scieloBrasilAssigned = $this->moderationStagesReportDAO->submissionHasUserAssigned("scielo-brasil", $submissionId);
        $carolinaAssigned = $this->moderationStagesReportDAO->submissionHasUserAssigned("carolinatanigushi", $submissionId);
        $countAreaModerators = $this->moderationStagesReportDAO->countAreaModerators($submissionId);
        $hasNotes = $this->moderationStagesReportDAO->submissionHasNotes($submissionId);

        return (!$hasResponsibles && $countAreaModerators == 0 && !$hasNotes)
            || ($hasResponsibles && ($carolinaAssigned || $scieloBrasilAssigned) && $countAreaModerators == 0);
    }

    public function checkSubmissionOnContentStage($submissionId): bool
    {
        $abelAssigned = $this->moderationStagesReportDAO->submissionHasUserAssigned("abelpacker", $submissionId);
        $solangeAssigned = $this->moderationStagesReportDAO->submissionHasUserAssigned("solangesantos", $submissionId);
        $noAreaModerators = $this->moderationStagesReportDAO->countAreaModerators($submissionId) == 0;
        $noNotes = !$this->moderationStagesReportDAO->submissionHasNotes($submissionId);

        return ($abelAssigned || $solangeAssigned) && $noAreaModerators && $noNotes;
    }

    public function checkSubmissionOnAreaStage($submissionId): bool
    {
        $abelAssigned = $this->moderationStagesReportDAO->submissionHasUserAssigned("abelpacker", $submissionId);
        $solangeAssigned = $this->moderationStagesReportDAO->submissionHasUserAssigned("solangesantos", $submissionId);
        $countAreaModerators = $this->moderationStagesReportDAO->countAreaModerators($submissionId);
        $hasNotes = $this->moderationStagesReportDAO->submissionHasNotes($submissionId);
        $hasResponsibles = $this->moderationStagesReportDAO->submissionHasResponsibles($submissionId);

        return (($abelAssigned || $solangeAssigned) && $countAreaModerators == 0 && $hasNotes)
            || (($abelAssigned || $solangeAssigned) && $countAreaModerators == 1)
            || ($hasResponsibles && $countAreaModerators >= 1)
            || (!$hasResponsibles && ($hasNotes || $countAreaModerators == 1));
    }
}
