<?php

import('plugins.generic.scieloModerationStages.classes.ModerationStage');
import('plugins.reports.scieloModerationStagesReport.classes.ModeratedSubmission');
import('plugins.reports.scieloModerationStagesReport.classes.ModerationStageDAO');

class ModeratedSubmissionHelper
{
    public function __construct()
    {
        $this->moderationStageDAO = new ModerationStageDAO();
    }

    public function setDAO($dao)
    {
        $this->moderationStageDAO = $dao;
    }

    public function createModeratedSubmission($submissionId, $locale): ModeratedSubmission
    {
        $title = $this->moderationStageDAO->getTitle($submissionId, $locale);
        $moderationStage = $this->getSubmissionModerationStage($submissionId);
        list($submitterName, $submitterIsScieloJournal) = $this->moderationStageDAO->getSubmitterData($submissionId);
        $submissionStatus = $this->moderationStageDAO->getSubmissionStatus($submissionId);
        $responsibles = $this->moderationStageDAO->getResponsibles($submissionId);
        $areaModerators = $this->moderationStageDAO->getAreaModerators($submissionId);
        $finalDecision = $this->moderationStageDAO->getFinalDecision($submissionId, $locale);
        $notes = $this->moderationStageDAO->getNotes($submissionId);

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
        $submissionStage = $this->moderationStageDAO->getSubmissionModerationStage($submissionId);
        if (!is_null($submissionStage)) {
            return $submissionStage;
        }

        if ($this->checkSubmissionOnAreaStage($submissionId)) {
            return SCIELO_MODERATION_STAGE_AREA;
        }

        if ($this->checkSubmissionOnContentStage($submissionId)) {
            return SCIELO_MODERATION_STAGE_CONTENT;
        }

        if ($this->checkSubmissionOnFormatStage($submissionId)) {
            return SCIELO_MODERATION_STAGE_FORMAT;
        }

        return null;
    }

    public function checkSubmissionOnFormatStage($submissionId): bool
    {
        $hasResponsibles = $this->moderationStageDAO->submissionHasResponsibles($submissionId);
        $scieloBrasilAssigned = $this->moderationStageDAO->submissionHasUserAssigned("scielo-brasil", $submissionId);
        $carolinaAssigned = $this->moderationStageDAO->submissionHasUserAssigned("carolinatanigushi", $submissionId);
        $countAreaModerators = $this->moderationStageDAO->countAreaModerators($submissionId);
        $hasNotes = $this->moderationStageDAO->submissionHasNotes($submissionId);

        return (!$hasResponsibles && $countAreaModerators == 0 && !$hasNotes)
            || ($hasResponsibles && ($carolinaAssigned || $scieloBrasilAssigned) && $countAreaModerators == 0);
    }

    public function checkSubmissionOnContentStage($submissionId): bool
    {
        $abelAssigned = $this->moderationStageDAO->submissionHasUserAssigned("abelpacker", $submissionId);
        $solangeAssigned = $this->moderationStageDAO->submissionHasUserAssigned("solangesantos", $submissionId);
        $noAreaModerators = $this->moderationStageDAO->countAreaModerators($submissionId) == 0;
        $noNotes = !$this->moderationStageDAO->submissionHasNotes($submissionId);

        return ($abelAssigned || $solangeAssigned) && $noAreaModerators && $noNotes;
    }

    public function checkSubmissionOnAreaStage($submissionId): bool
    {
        $abelAssigned = $this->moderationStageDAO->submissionHasUserAssigned("abelpacker", $submissionId);
        $solangeAssigned = $this->moderationStageDAO->submissionHasUserAssigned("solangesantos", $submissionId);
        $countAreaModerators = $this->moderationStageDAO->countAreaModerators($submissionId);
        $hasNotes = $this->moderationStageDAO->submissionHasNotes($submissionId);
        $hasResponsibles = $this->moderationStageDAO->submissionHasResponsibles($submissionId);

        return (($abelAssigned || $solangeAssigned) && $countAreaModerators == 0 && $hasNotes)
            || (($abelAssigned || $solangeAssigned) && $countAreaModerators == 1)
            || ($hasResponsibles && $countAreaModerators >= 1)
            || (!$hasResponsibles && ($hasNotes || $countAreaModerators == 1));
    }
}
