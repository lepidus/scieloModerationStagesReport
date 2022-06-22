<?php

import ('plugins.generic.scieloModerationStages.classes.ModerationStage');
import ('plugins.reports.scieloModerationStagesReport.classes.ModerationStageDAO');

class ModerationStagesReportHelper {
    
    public function __construct() {
        $this->moderationStageDAO = new ModerationStageDAO();
    }

    public function setDAO($dao) {
        $this->moderationStageDAO = $dao;
    }

    private function getModerationStageName($stage) {
        $stageMap = [
            SCIELO_MODERATION_STAGE_FORMAT => 'plugins.generic.scieloModerationStagesReport.stages.formatStage',
            SCIELO_MODERATION_STAGE_CONTENT => 'plugins.generic.scieloModerationStagesReport.stages.contentStage',
            SCIELO_MODERATION_STAGE_AREA => 'plugins.generic.scieloModerationStagesReport.stages.areaStage',
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

    private function checkSubmissionOnFormatStage($submissionId): bool {
        $noModerators = !$this->moderationStageDAO->hasModerators($submissionId);
        $scieloBrasilAssigned = $this->moderationStageDAO->hasUserAssigned("scielo-brasil", $submissionId);
        $carolinaAssigned = $this->moderationStageDAO->hasUserAssigned("carolinatanigushi", $submissionId);
        $noNotes = !$this->moderationStageDAO->hasNotes($submissionId);

        return ($noModerators || $scieloBrasilAssigned || $carolinaAssigned) && $noNotes;
    }

    private function checkSubmissionOnContentStage($submissionId): bool {
        $abelAssigned = $this->moderationStageDAO->hasUserAssigned("abelpacker", $submissionId);
        $solangeAssigned = $this->moderationStageDAO->hasUserAssigned("solangesantos", $submissionId);
        $noAreaModerators = $this->moderationStageDAO->countAreaModerators($submissionId) == 0;
        $noNotes = !$this->moderationStageDAO->hasNotes($submissionId);
        
        return ($abelAssigned || $solangeAssigned) && $noAreaModerators && $noNotes;
    }

    private function checkSubmissionOnAreaStage($submissionId): bool {
        $abelAssigned = $this->moderationStageDAO->hasUserAssigned("abelpacker", $submissionId);
        $solangeAssigned = $this->moderationStageDAO->hasUserAssigned("solangesantos", $submissionId);
        $countAreaModerators = $this->moderationStageDAO->countAreaModerators($submissionId);
        $hasNotes = $this->moderationStageDAO->hasNotes($submissionId);
        $hasModerators = $this->moderationStageDAO->hasModerators($submissionId);

        return (($abelAssigned || $solangeAssigned) && $countAreaModerators == 0 && $hasNotes)
            || (($abelAssigned || $solangeAssigned) && $countAreaModerators == 1)
            || ($hasModerators && $countAreaModerators >= 1)
            || (!$hasModerators && ($hasNotes || $countAreaModerators == 1));
    }

}