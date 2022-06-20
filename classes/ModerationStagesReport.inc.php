<?php

class ModerationStagesReport {
    private $submissions;
    private $UTF8_BOM;

    public function __construct(array $submissions) {
        $this->submissions = $submissions;
        $this->UTF8_BOM = chr(0xEF).chr(0xBB).chr(0xBF);
    }

    private function getHeaders(): array {
        return [
            __("plugins.reports.scieloModerationStagesReport.headers.submissionId"),
            __("plugins.reports.scieloModerationStagesReport.headers.moderationStage")
        ];
    }

    public function buildCSV($fileDescriptor) : void {
        fprintf($fileDescriptor, $this->UTF8_BOM);
        fputcsv($fileDescriptor, $this->getHeaders());

        foreach($this->submissions as $submissionId => $moderationStage){
            fputcsv($fileDescriptor, [$submissionId, $moderationStage]);
        }
    }
}