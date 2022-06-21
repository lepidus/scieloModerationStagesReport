<?php
use PHPUnit\Framework\TestCase;
import ('plugins.reports.scieloModerationStagesReport.classes.ModerationStagesReportHelper');

class ModerationStagesReportHelperTest extends TestCase {
    private $helper;
    private $submissionId = 1;

    public function setUp() : void {
        $this->helper = new ModerationStagesReportHelper();
    }

    private function getModerationStageWithMockedDAO($hasModerators, $hasNotes, $mapUsersAssigned, $countAreaModerators): string {
        $mockedDAO = $this->createMock(ModerationStageDAO::class);
        $mockedDAO->method('hasModerators')->willReturn($hasModerators);
        $mockedDAO->method('hasNotes')->willReturn($hasNotes);
        $mockedDAO->method('countAreaModerators')->willReturn($countAreaModerators);
        
        if(!is_null($mapUsersAssigned))
            $mockedDAO->method('hasUserAssigned')->willReturnMap($mapUsersAssigned);
        else
            $mockedDAO->method('hasUserAssigned')->willReturn(false);
        
        $this->helper->setDAO($mockedDAO);

        $submissionModerationStage = $this->helper->getSubmissionModerationStage($this->submissionId);
    }

    public function testChecksFormatStageCaseOne(): void {
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(false, false, null, 0);
        $expectedModerationStage = __("plugins.generic.scieloModerationStages.stages.formatStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testChecksFormatStageCaseTwo(): void {
        $mapUsersAssigned = [
            ["scielo-brasil", $this->submissionId, true],
            ["carolinatanigushi", $this->submissionId, true]
        ];
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, $mapUsersAssigned, 0);
        $expectedModerationStage = __("plugins.generic.scieloModerationStages.stages.formatStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testChecksContentStageCaseOne(): void {
        $mapUsersAssigned = [
            ["abelpacker", $this->submissionId, true],
            ["solangesantos", $this->submissionId, true]
        ];
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, $mapUsersAssigned, 0);
        $expectedModerationStage = __("plugins.generic.scieloModerationStages.stages.contentStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }
}