<?php

class ModerationStagesReport {
    private $submissions;
    private $nonDetectedSubmissions;
    private $UTF8_BOM;

    public function __construct(array $submissions, array $nonDetectedSubmissions) {
        $this->submissions = $submissions;
        $this->nonDetectedSubmissions = $nonDetectedSubmissions;
        $this->UTF8_BOM = chr(0xEF).chr(0xBB).chr(0xBF);
    }

    private function getHeaders(): array {
        return [
            __("plugins.reports.scieloModerationStagesReport.headers.submissionId"),
            __("plugins.reports.scieloModerationStagesReport.headers.moderationStage")
        ];
    }

    private function getSecondHeaders(): array {
        return [
            __("plugins.reports.scieloModerationStagesReport.headers.nonDetectedSubmissionIds")
        ];
    }

    public function buildCSV($fileDescriptor) : void {
        fprintf($fileDescriptor, $this->UTF8_BOM);
        fputcsv($fileDescriptor, $this->getHeaders());

        foreach($this->submissions as $submissionId => $moderationStage){
            fputcsv($fileDescriptor, [$submissionId, $moderationStage]);
        }

        $blankLine = ["", "", ""];
        fputcsv($fileDescriptor, $blankLine);
        fputcsv($fileDescriptor, $this->getSecondHeaders());

        foreach($this->nonDetectedSubmissions as $submissionId){
            fputcsv($fileDescriptor, [$submissionId]);
        }
    }
}