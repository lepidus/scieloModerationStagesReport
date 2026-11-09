<?php

import('plugins.reports.scieloModerationStagesReport.classes.ModeratedSubmission');

class ModerationStagesReport
{
    private $submissions;
    private $nonDetectedSubmissions;
    private $UTF8_BOM;

    public function __construct(array $submissions, array $nonDetectedSubmissions)
    {
        $this->submissions = $submissions;
        $this->nonDetectedSubmissions = $nonDetectedSubmissions;
        $this->UTF8_BOM = chr(0xEF).chr(0xBB).chr(0xBF);
    }

    private function getHeaders(): array
    {
        return [
            __("plugins.reports.scieloModerationStagesReport.headers.submissionId"),
            __("plugins.reports.scieloModerationStagesReport.headers.title"),
            __("plugins.reports.scieloModerationStagesReport.headers.moderationStage"),
            __("plugins.reports.scieloModerationStagesReport.headers.submitter"),
            __("plugins.reports.scieloModerationStagesReport.headers.status"),
            __("plugins.reports.scieloModerationStagesReport.headers.scieloJournal"),
            __("plugins.reports.scieloModerationStagesReport.headers.responsibles"),
            __("plugins.reports.scieloModerationStagesReport.headers.areaModerators"),
            __("plugins.reports.scieloModerationStagesReport.headers.finalDecision"),
            __("plugins.reports.scieloModerationStagesReport.headers.notes")
        ];
    }

    private function getSecondHeaders(): array
    {
        return [
            __("plugins.reports.scieloModerationStagesReport.headers.nonDetectedSubmissions")
        ];
    }

    public function buildCSV($fileDescriptor): void
    {
        fprintf($fileDescriptor, $this->UTF8_BOM);
        $this->writeCSVRow($fileDescriptor, $this->getHeaders());

        foreach ($this->submissions as $submission) {
            $this->writeCSVRow($fileDescriptor, $submission->asRecord());
        }

        $blankLine = ["", "", ""];
        $this->writeCSVRow($fileDescriptor, $blankLine);
        $this->writeCSVRow($fileDescriptor, $this->getSecondHeaders());

        foreach ($this->nonDetectedSubmissions as $nonDetectedSubmission) {
            $this->writeCSVRow($fileDescriptor, $nonDetectedSubmission->asRecord());
        }
    }

    private function writeCSVRow($fileDescriptor, array $row): void
    {
        fputcsv($fileDescriptor, array_map([$this, 'neutralizeSpreadsheetFormula'], $row));
    }

    /**
     * Prefixes with an apostrophe the text cells that a spreadsheet would
     * evaluate as a formula, such as titles and names typed by users.
     */
    private function neutralizeSpreadsheetFormula($value)
    {
        if (!is_string($value) || $value === '' || is_numeric($value)) {
            return $value;
        }

        if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
