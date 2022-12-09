<?php
use PHPUnit\Framework\TestCase;
import ('plugins.reports.scieloModerationStagesReport.classes.ModerationStagesReport');

class ModerationStagesReportTest extends TestCase {
    
    private $report;
    private $filePath = "/tmp/test.csv";
    private $UTF8_BOM;

    public function setUp() : void {
        $this->UTF8_BOM = chr(0xEF).chr(0xBB).chr(0xBF);
        $submissions = [
            1 => __("plugins.reports.scieloModerationStagesReport.stages.formatStage"),
            2 => __("plugins.reports.scieloModerationStagesReport.stages.contentStage")
        ];

        $nonDetectedSubmissions = [3, 4];
        $this->report = new ModerationStagesReport($submissions, $nonDetectedSubmissions);
    }

    public function tearDown() : void {
        if (file_exists(($this->filePath))) 
            unlink($this->filePath);
    }

    protected function createCSVReport() : void {
        $csvFile = fopen($this->filePath, 'wt');
        $this->report->buildCSV($csvFile);
        fclose($csvFile);
    }

    public function testGeneratedReportHasPrimaryHeaders(): void {
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
    
    public function testGeneratedReportHasSecondaryHeaders(): void {
        $this->createCSVReport();
        $csvRows = array_map('str_getcsv', file($this->filePath));
        
        $expectedSecondaryHeaders = [
            __("plugins.reports.scieloModerationStagesReport.headers.nonDetectedSubmissions"),
        ];
        $fifthRow = $csvRows[4];
        $this->assertEquals($expectedSecondaryHeaders, $fifthRow);
    }

    public function testGeneratedReportHasSubmissions(): void {
        $this->createCSVReport();
        $csvRows = array_map('str_getcsv', file($this->filePath));

        $secondRow = $csvRows[1];
        $expectedSubmissionRow = ["1", __("plugins.reports.scieloModerationStagesReport.stages.formatStage")];

        $this->assertEquals($expectedSubmissionRow, $secondRow);
    }

    public function testGeneratedReportHasNonDetectedSubmissions(): void {
        $this->createCSVReport();
        $csvRows = array_map('str_getcsv', file($this->filePath));

        $fifthRow = $csvRows[5];
        $expectedNonDetectedSubmissionRow = ["3"];

        $this->assertEquals($expectedNonDetectedSubmissionRow, $fifthRow);
    }

    public function testGeneratedReportHasUTF8Bytes(): void {
        $this->createCSVReport();

        $csvFile = fopen($this->filePath, 'r');
        $BOMRead = fread($csvFile, strlen($this->UTF8_BOM));
        fclose($csvFile);

        $this->assertEquals($this->UTF8_BOM, $BOMRead);
    }

}