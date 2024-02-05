<?php

use PHPUnit\Framework\TestCase;

use APP\submission\Submission;
use APP\plugins\reports\scieloModerationStagesReport\classes\ModeratedSubmission;
use APP\plugins\reports\scieloModerationStagesReport\classes\ModerationStagesReport;

class ModerationStagesReportTest extends TestCase
{
    private $report;
    private $filePath = "/tmp/test.csv";
    private $UTF8_BOM;

    public function setUp(): void
    {
        $this->UTF8_BOM = chr(0xEF).chr(0xBB).chr(0xBF);
        $submissions = [
            new ModeratedSubmission(1, 'Submission 1', ModeratedSubmission::SCIELO_MODERATION_STAGE_REPORT_FORMAT, 'Author 1', Submission::STATUS_PUBLISHED, false, ['Responsible 1', 'Responsible 2'], ['Moderator 1', 'Moderator 2'], 'Accepted', ['Very good'])
        ];

        $nonDetectedSubmissions = [
            new ModeratedSubmission(2, 'Submission 2', null, 'Author 2', Submission::STATUS_DECLINED, false, ['Responsible 1', 'Responsible 2'], ['Moderator 1', 'Moderator 2'], 'Declined', ['Not that good'])
        ];

        $this->report = new ModerationStagesReport($submissions, $nonDetectedSubmissions);
    }

    public function tearDown(): void
    {
        if (file_exists(($this->filePath))) {
            unlink($this->filePath);
        }
    }

    protected function createCSVReport(): void
    {
        $csvFile = fopen($this->filePath, 'wt');
        $this->report->buildCSV($csvFile);
        fclose($csvFile);
    }

    public function testGeneratedReportHasPrimaryHeaders(): void
    {
        $this->createCSVReport();
        $csvFile = fopen($this->filePath, 'r');

        fread($csvFile, strlen($this->UTF8_BOM));

        $firstRow = fgetcsv($csvFile);
        $expectedPrimaryHeaders = [
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
        fclose($csvFile);

        $this->assertEquals($expectedPrimaryHeaders, $firstRow);
    }

    public function testGeneratedReportHasSecondaryHeaders(): void
    {
        $this->createCSVReport();
        $csvRows = array_map('str_getcsv', file($this->filePath));

        $expectedSecondaryHeaders = [
            __("plugins.reports.scieloModerationStagesReport.headers.nonDetectedSubmissions"),
        ];
        $fourthRow = $csvRows[3];
        $this->assertEquals($expectedSecondaryHeaders, $fourthRow);
    }

    public function testGeneratedReportHasSubmissions(): void
    {
        $this->createCSVReport();
        $csvRows = array_map('str_getcsv', file($this->filePath));
        $secondRow = $csvRows[1];

        $formatModerationTxt = __('plugins.reports.scieloModerationStagesReport.stages.formatStage');
        $statusTxt = __('submission.status.published');
        $submitterIsScieloJournalTxt = __('common.no');
        $expectedSubmissionRow = ['1', 'Submission 1', $formatModerationTxt, 'Author 1', $statusTxt, $submitterIsScieloJournalTxt, 'Responsible 1;Responsible 2', 'Moderator 1;Moderator 2', 'Accepted', 'Nota: Very good'];

        $this->assertEquals($expectedSubmissionRow, $secondRow);
    }

    public function testGeneratedReportHasNonDetectedSubmissions(): void
    {
        $this->createCSVReport();
        $csvRows = array_map('str_getcsv', file($this->filePath));
        $fifthRow = $csvRows[4];

        $messageNoModerationStage = __('plugins.reports.scieloModerationStagesReport.stages.noModerationStage');
        $statusTxt = __('submission.status.declined');
        $submitterIsScieloJournalTxt = __('common.no');
        $expectedNonDetectedSubmissionRow = ['2', 'Submission 2', $messageNoModerationStage, 'Author 2', $statusTxt, $submitterIsScieloJournalTxt, 'Responsible 1;Responsible 2', 'Moderator 1;Moderator 2', 'Declined', 'Nota: Not that good'];

        $this->assertEquals($expectedNonDetectedSubmissionRow, $fifthRow);
    }

    public function testGeneratedReportHasUTF8Bytes(): void
    {
        $this->createCSVReport();

        $csvFile = fopen($this->filePath, 'r');
        $BOMRead = fread($csvFile, strlen($this->UTF8_BOM));
        fclose($csvFile);

        $this->assertEquals($this->UTF8_BOM, $BOMRead);
    }
}
