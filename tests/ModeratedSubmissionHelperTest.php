<?php

use PHPUnit\Framework\TestCase;

import('classes.submission.Submission');
import('plugins.reports.scieloModerationStagesReport.classes.ModeratedSubmissionHelper');

class ModeratedSubmissionHelperTest extends TestCase
{
    private $helper;
    private $locale = 'pt_BR';
    private $submissionId = 1;
    private $title = 'Schematics for electric guitars';
    private $moderationStage = SCIELO_MODERATION_STAGE_REPORT_FORMAT;
    private $submitterName = 'Leo Fender';
    private $submissionStatus = STATUS_QUEUED;
    private $submitterIsScieloJournal = false;
    private $responsibles = ['Taylor Miranda', 'Vinicius Dias'];
    private $areaModerators = ['Seizi Tagima', 'Tiguez'];
    private $finalDecision = '';
    private $notes = ['These schematics are game-changing!', 'The quality is very good'];

    public function setUp(): void
    {
        $this->helper = new ModeratedSubmissionHelper();
    }

    private function getModerationStageWithMockedDAO($hasResponsibles, $hasNotes, $mapUsersAssigned, $countAreaModerators): string
    {
        $mockedDAO = $this->createMock(ModerationStagesReportDAO::class);
        $mockedDAO->method('getSubmissionModerationStage')->willReturn(null);
        $mockedDAO->method('submissionHasResponsibles')->willReturn($hasResponsibles);
        $mockedDAO->method('submissionHasNotes')->willReturn($hasNotes);
        $mockedDAO->method('countAreaModerators')->willReturn($countAreaModerators);

        if (!is_null($mapUsersAssigned)) {
            $mockedDAO->method('submissionHasUserAssigned')->willReturnMap($mapUsersAssigned);
        } else {
            $mockedDAO->method('submissionHasUserAssigned')->willReturn(false);
        }

        $this->helper->setDAO($mockedDAO);

        return $this->helper->getSubmissionModerationStage($this->submissionId);
    }

    private function getUsersAssignedMap($assignedUsernames): array
    {
        $map = [];
        $commonUsernames = ["scielo-brasil", "carolinatanigushi", "abelpacker", "solangesantos"];
        foreach ($commonUsernames as $username) {
            $userIsAssigned = in_array($username, $assignedUsernames);
            $map[] = [$username, $this->submissionId, $userIsAssigned];
        }

        return $map;
    }

    public function testChecksFormatStageCaseOne(): void
    {
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(false, false, null, 0);

        $this->assertEquals(SCIELO_MODERATION_STAGE_REPORT_FORMAT, $submissionModerationStage);
    }

    public function testChecksFormatStageCasesTwoAndThree(): void
    {
        $mapUsersAssigned = $this->getUsersAssignedMap(["scielo-brasil", "carolinatanigushi"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, $mapUsersAssigned, 0);

        $this->assertEquals(SCIELO_MODERATION_STAGE_REPORT_FORMAT, $submissionModerationStage);
    }

    public function testChecksFormatStageCaseFour(): void
    {
        $mapUsersAssigned = $this->getUsersAssignedMap(["scielo-brasil", "carolinatanigushi"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, true, $mapUsersAssigned, 0);

        $this->assertEquals(SCIELO_MODERATION_STAGE_REPORT_FORMAT, $submissionModerationStage);
    }

    public function testChecksContentStageCaseOne(): void
    {
        $mapUsersAssigned = $this->getUsersAssignedMap(["abelpacker", "solangesantos"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, $mapUsersAssigned, 0);

        $this->assertEquals(SCIELO_MODERATION_STAGE_REPORT_CONTENT, $submissionModerationStage);
    }

    public function testChecksContentStageCaseTwo(): void
    {
        $mapUsersAssigned = $this->getUsersAssignedMap(["scielo-brasil", "carolinatanigushi", "abelpacker", "solangesantos"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, $mapUsersAssigned, 0);

        $this->assertEquals(SCIELO_MODERATION_STAGE_REPORT_CONTENT, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseOne(): void
    {
        $mapUsersAssigned = $this->getUsersAssignedMap(["abelpacker", "solangesantos"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, true, $mapUsersAssigned, 0);

        $this->assertEquals(SCIELO_MODERATION_STAGE_REPORT_AREA, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseTwo(): void
    {
        $mapUsersAssigned = $this->getUsersAssignedMap(["scielo-brasil", "carolinatanigushi", "abelpacker", "solangesantos"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, true, $mapUsersAssigned, 0);

        $this->assertEquals(SCIELO_MODERATION_STAGE_REPORT_AREA, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseThree(): void
    {
        $mapUsersAssigned = $this->getUsersAssignedMap(["abelpacker", "solangesantos"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, $mapUsersAssigned, 1);

        $this->assertEquals(SCIELO_MODERATION_STAGE_REPORT_AREA, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseFour(): void
    {
        $mapUsersAssigned = $this->getUsersAssignedMap(["scielo-brasil", "carolinatanigushi", "abelpacker", "solangesantos"]);
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, $mapUsersAssigned, 1);

        $this->assertEquals(SCIELO_MODERATION_STAGE_REPORT_AREA, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseFive(): void
    {
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(true, false, null, 2);

        $this->assertEquals(SCIELO_MODERATION_STAGE_REPORT_AREA, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseSix(): void
    {
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(false, true, null, 0);

        $this->assertEquals(SCIELO_MODERATION_STAGE_REPORT_AREA, $submissionModerationStage);
    }

    public function testChecksAreaStageCaseSeven(): void
    {
        $submissionModerationStage = $this->getModerationStageWithMockedDAO(false, false, null, 1);

        $this->assertEquals(SCIELO_MODERATION_STAGE_REPORT_AREA, $submissionModerationStage);
    }

    public function testCantDetectModerationStage(): void
    {
        $mockedHelper = $this->createMock(ModeratedSubmissionHelper::class);
        $mockedHelper->method('checkSubmissionOnFormatStage')->willReturn(false);
        $mockedHelper->method('checkSubmissionOnContentStage')->willReturn(false);
        $mockedHelper->method('checkSubmissionOnAreaStage')->willReturn(false);

        $mockedDAO = $this->createMock(ModerationStagesReportDAO::class);
        $mockedDAO->method('getSubmissionModerationStage')->willReturn(null);
        $mockedHelper->setDAO($mockedDAO);

        $moderationStage = $mockedHelper->getSubmissionModerationStage($this->submissionId);
        $this->assertNull($moderationStage);
    }

    public function testGetSubmissionStageWhichHasData(): void
    {
        $mockedDAO = $this->createMock(ModerationStagesReportDAO::class);
        $mockedDAO->method('getSubmissionModerationStage')->willReturn($this->moderationStage);

        $this->helper->setDAO($mockedDAO);
        $submissionModerationStage = $this->helper->getSubmissionModerationStage($this->submissionId);

        $this->assertEquals($this->moderationStage, $submissionModerationStage);
    }

    public function testHelperCreatesModeratedSubmission(): void
    {
        $mockedDAO = $this->createMock(ModerationStagesReportDAO::class);
        $mockedDAO->method('getTitle')->willReturn($this->title);
        $mockedDAO->method('getSubmissionModerationStage')->willReturn($this->moderationStage);
        $mockedDAO->method('getSubmitterData')->willReturn([$this->submitterName, $this->submitterIsScieloJournal]);
        $mockedDAO->method('getSubmissionStatus')->willReturn($this->submissionStatus);
        $mockedDAO->method('getResponsibles')->willReturn($this->responsibles);
        $mockedDAO->method('getAreaModerators')->willReturn($this->areaModerators);
        $mockedDAO->method('getFinalDecision')->willReturn($this->finalDecision);
        $mockedDAO->method('getNotes')->willReturn($this->notes);

        $this->helper->setDAO($mockedDAO);
        $moderatedSubmission = $this->helper->createModeratedSubmission($this->submissionId, $this->locale);

        $expectedModeratedSubmission = new ModeratedSubmission($this->submissionId, $this->title, $this->moderationStage, $this->submitterName, $this->submissionStatus, $this->submitterIsScieloJournal, $this->responsibles, $this->areaModerators, $this->finalDecision, $this->notes);
        $this->assertEquals($expectedModeratedSubmission, $moderatedSubmission);
    }
}
