<?php
use PHPUnit\Framework\TestCase;
import ('plugins.reports.scieloModerationStagesReport.classes.ModerationStagesReport');

class ModerationStagesReportTest extends TestCase {
    
    private $report;
    private $filePath = "/tmp/test.csv";

    public function setUp() : void {
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

    public function testGeneratedReportHasHeaders(): void {
        $this->createCSVReport();
        $csvRows = array_map('str_getcsv', file($this->filePath));

        $expectedFirstHeaders = [
            __("plugins.reports.scieloModerationStagesReport.headers.submissionId"),
            __("plugins.reports.scieloModerationStagesReport.headers.moderationStage")
        ];
        $firstRow = $csvRows[0];

        $this->assertEquals($expectedFirstHeaders, $firstRow);

        $expectedSecondHeaders = [
            __("plugins.reports.scieloModerationStagesReport.headers.nonDetectedSubmissionIds"),
        ];
        $fourthRow = $csvRows[4];
        $this->assertEquals($expectedSecondHeaders, $fourthRow);
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

        $expectedBOM = chr(0xEF).chr(0xBB).chr(0xBF);
        $csvFile = fopen($this->filePath, 'r');
        $BOMRead = fread($csvFile, strlen($expectedBOM));
        fclose($csvFile);

        $this->assertEquals($expectedBOM, $BOMRead);
    }

}