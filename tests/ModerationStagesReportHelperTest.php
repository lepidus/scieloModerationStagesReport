<?php
use PHPUnit\Framework\TestCase;
import ('plugins.reports.scieloModerationStagesReport.classes.ModerationStagesReportHelper');

class ModerationStagesReportHelperTest extends TestCase {
    private $helper;
    private $submissionId = 1;

    public function setUp() : void {
        $this->helper = new ModerationStagesReportHelper();
    }

    private function getModerationStageWithMockedDAO($hasResponsibles, $hasNotes, $mapUsersAssigned, $countAreaModerators): string {
        $mockedDAO = $this->createMock(ModerationStageDAO::class);
        $mockedDAO->method('getSubmissionModerationStage')->willReturn(null);
        $mockedDAO->method('submissionHasResponsibles')->willReturn($hasResponsibles);
        $mockedDAO->method('submissionHasNotes')->willReturn($hasNotes);
        $mockedDAO->method('countAreaModerators')->willReturn($countAreaModerators);
        
        if(!is_null($mapUsersAssigned))
            $mockedDAO->method('submissionHasUserAssigned')->willReturnMap($mapUsersAssigned);
        else
            $mockedDAO->method('submissionHasUserAssigned')->willReturn(false);
        
        $this->helper->setDAO($mockedDAO);

        return $this->helper->getSubmissionModerationStage($this->submissionId);
    }

    private function getUsersAssignedMap($assignedUsernames): array {
        $map = [];
        $commonUsernames = ["scielo-brasil", "carolinatanigushi", "abelpacker", "solangesantos"];
        foreach($commonUsernames as $username) {
            $userIsAssigned = in_array($username, $assignedUsernames);
            $map[] = [$username, $this->submissionId, $userIsAssigned];
        }

        return $map;
    }

    public function testChecksFormatStageCaseOne(): void {
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(false, false, null, 0);
        $expectedModerationStage = __("plugins.generic.scieloModerationStagesReport.stages.formatStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testChecksFormatStageCaseTwo(): void {
        $mapUsersAssigned = $this->getUsersAssignedMap(["scielo-brasil", "carolinatanigushi"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, $mapUsersAssigned, 0);
        $expectedModerationStage = __("plugins.generic.scieloModerationStagesReport.stages.formatStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testChecksContentStageCaseOne(): void {
        $mapUsersAssigned = $this->getUsersAssignedMap(["abelpacker", "solangesantos"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, $mapUsersAssigned, 0);
        $expectedModerationStage = __("plugins.generic.scieloModerationStagesReport.stages.contentStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testChecksContentStageCaseTwo(): void {
        $mapUsersAssigned = $this->getUsersAssignedMap(["scielo-brasil", "carolinatanigushi", "abelpacker", "solangesantos"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, $mapUsersAssigned, 0);
        $expectedModerationStage = __("plugins.generic.scieloModerationStagesReport.stages.contentStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseOne(): void {
        $mapUsersAssigned = $this->getUsersAssignedMap(["abelpacker", "solangesantos"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, true, $mapUsersAssigned, 0);
        $expectedModerationStage = __("plugins.generic.scieloModerationStagesReport.stages.areaStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseTwo(): void {
        $mapUsersAssigned = $this->getUsersAssignedMap(["scielo-brasil", "carolinatanigushi", "abelpacker", "solangesantos"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, true, $mapUsersAssigned, 0);
        $expectedModerationStage = __("plugins.generic.scieloModerationStagesReport.stages.areaStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseThree(): void {
        $mapUsersAssigned = $this->getUsersAssignedMap(["abelpacker", "solangesantos"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, $mapUsersAssigned, 1);
        $expectedModerationStage = __("plugins.generic.scieloModerationStagesReport.stages.areaStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseFour(): void {
        $mapUsersAssigned = $this->getUsersAssignedMap(["scielo-brasil", "carolinatanigushi", "abelpacker", "solangesantos"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, $mapUsersAssigned, 1);
        $expectedModerationStage = __("plugins.generic.scieloModerationStagesReport.stages.areaStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseFive(): void {
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, null, 2);
        $expectedModerationStage = __("plugins.generic.scieloModerationStagesReport.stages.areaStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseSix(): void {
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(false, true, null, 0);
        $expectedModerationStage = __("plugins.generic.scieloModerationStagesReport.stages.areaStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseSeven(): void {
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(false, false, null, 1);
        $expectedModerationStage = __("plugins.generic.scieloModerationStagesReport.stages.areaStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }

    public function testCantDetectModerationStage(): void {
        $mockedHelper = $this->createMock(ModerationStagesReportHelper::class);
        $mockedHelper->method('checkSubmissionOnFormatStage')->willReturn(false);
        $mockedHelper->method('checkSubmissionOnContentStage')->willReturn(false);
        $mockedHelper->method('checkSubmissionOnAreaStage')->willReturn(false);
        
        $mockedDAO = $this->createMock(ModerationStageDAO::class);
        $mockedDAO->method('getSubmissionModerationStage')->willReturn(null);
        $mockedHelper->setDAO($mockedDAO);

        $moderationStage = $mockedHelper->getSubmissionModerationStage($this->submissionId);
        $this->assertNull($moderationStage);
    }

    public function testGetSubmissionStageWhichHasData(): void {
        $formatStageId = 1;
        $mockedDAO = $this->createMock(ModerationStageDAO::class);
        $mockedDAO->method('getSubmissionModerationStage')->willReturn($formatStageId);

        $this->helper->setDAO($mockedDAO);
        $submissionModerationStage = $this->helper->getSubmissionModerationStage($this->submissionId);
        $expectedModerationStage = __("plugins.generic.scieloModerationStagesReport.stages.formatStage");

        $this->assertEquals($expectedModerationStage, $submissionModerationStage);
    }
}