<?php

import ('plugins.generic.scieloModerationStages.classes.ModerationStage');
import ('plugins.reports.scieloModerationStagesReport.classes.ModerationStagesReport');
import ('plugins.reports.scieloModerationStagesReport.classes.ModerationStageDAO');

class ModerationStagesReportHelper {
    
    public function __construct() {
        $this->moderationStageDAO = new ModerationStageDAO();
    }

    public function setDAO($dao) {
        $this->moderationStageDAO = $dao;
    }

    public function createModerationStagesReport(): ModerationStagesReport {
        $allSubmissionsIds = $this->moderationStageDAO->getAllSubmissionsIds();

        $detectedSubmissions = [];
        $nonDetectedSubmissions = [];

        foreach($allSubmissionsIds as $submissionId) {
            $moderationStage = $this->getSubmissionModerationStage($submissionId);

            if(is_null($moderationStage))
                $nonDetectedSubmissions[] = $submissionId;
            else
                $detectedSubmissions[$submissionId] = $moderationStage;
        }

        return new ModerationStagesReport($detectedSubmissions, $nonDetectedSubmissions);
    }

    private function getModerationStageName($stage) {
        $stageMap = [
            SCIELO_MODERATION_STAGE_FORMAT => 'plugins.reports.scieloModerationStagesReport.stages.formatStage',
            SCIELO_MODERATION_STAGE_CONTENT => 'plugins.reports.scieloModerationStagesReport.stages.contentStage',
            SCIELO_MODERATION_STAGE_AREA => 'plugins.reports.scieloModerationStagesReport.stages.areaStage',
        ];

        return __($stageMap[$stage]);
    }

    public function getSubmissionModerationStage($submissionId) {
        $submissionStage = $this->moderationStageDAO->getSubmissionModerationStage($submissionId);
        if(!is_null($submissionStage))
            return $this->getModerationStageName($submissionStage);

        if($this->checkSubmissionOnAreaStage($submissionId))
            return $this->getModerationStageName(SCIELO_MODERATION_STAGE_AREA);

        if($this->checkSubmissionOnContentStage($submissionId))
            return $this->getModerationStageName(SCIELO_MODERATION_STAGE_CONTENT);

        if($this->checkSubmissionOnFormatStage($submissionId))
            return $this->getModerationStageName(SCIELO_MODERATION_STAGE_FORMAT);

        return null;
    }

    public function checkSubmissionOnFormatStage($submissionId): bool {
        $noResponsibles = !$this->moderationStageDAO->submissionHasResponsibles($submissionId);
        $scieloBrasilAssigned = $this->moderationStageDAO->submissionHasUserAssigned("scielo-brasil", $submissionId);
        $carolinaAssigned = $this->moderationStageDAO->submissionHasUserAssigned("carolinatanigushi", $submissionId);
        $noNotes = !$this->moderationStageDAO->submissionHasNotes($submissionId);

        return ($noResponsibles || $scieloBrasilAssigned || $carolinaAssigned) && $noNotes;
    }

    public function checkSubmissionOnContentStage($submissionId): bool {
        $abelAssigned = $this->moderationStageDAO->submissionHasUserAssigned("abelpacker", $submissionId);
        $solangeAssigned = $this->moderationStageDAO->submissionHasUserAssigned("solangesantos", $submissionId);
        $noAreaModerators = $this->moderationStageDAO->countAreaModerators($submissionId) == 0;
        $noNotes = !$this->moderationStageDAO->submissionHasNotes($submissionId);
        
        return ($abelAssigned || $solangeAssigned) && $noAreaModerators && $noNotes;
    }

    public function checkSubmissionOnAreaStage($submissionId): bool {
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