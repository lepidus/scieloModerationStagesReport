<?php
use PHPUnit\Framework\TestCase;
import ('plugins.reports.scieloModerationStagesReport.classes.ModerationStagesReport');

class ModerationStagesReportTest extends TestCase {
    
    private $report;
    private $filePath = "/tmp/test.csv";

    public function setUp() : void {
        $submissions = [
            1 => __("plugins.generic.scieloModerationStages.stages.formatStage"),
            2 => __("plugins.generic.scieloModerationStages.stages.contentStage")
        ];
        $this->report = new ModerationStagesReport($submissions);
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

        $expectedHeaders = [
            __("plugins.reports.scieloModerationStagesReport.headers.submissionId"),
            __("plugins.reports.scieloModerationStagesReport.headers.moderationStage")
        ];
        $firstRow = $csvRows[0];

        $this->assertEquals($expectedHeaders, $firstRow);
    }
    
    public function testGeneratedReportHasSubmissions(): void {
        $this->createCSVReport();
        $csvRows = array_map('str_getcsv', file($this->filePath));

        $secondRow = $csvRows[1];
        $expectedSubmissionRow = ["1", __("plugins.generic.scieloModerationStages.stages.formatStage")];

        $this->assertEquals($expectedSubmissionRow, $secondRow);
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