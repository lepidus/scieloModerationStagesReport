<?php

use PHPUnit\Framework\TestCase;

import('classes.submission.Submission');
import('plugins.reports.scieloModerationStagesReport.classes.ModeratedSubmission');
import('plugins.reports.scieloModerationStagesReport.classes.ModerationStagesReportHelper');

class ModeratedSubmissionTest extends TestCase
{
    private $submission;
    private $submissionId = 1;
    private $title = 'Schematics for guitar pickups';
    private $moderationStage = SCIELO_MODERATION_STAGE_AREA;
    private $submitterName = 'Erico Malagoli';
    private $submissionStatus = STATUS_PUBLISHED;
    private $submitterIsScieloJournal = false;
    private $responsibles = ['Carlos Alberto', 'Vinicius Dias'];
    private $areaModerators = ['Seizi Tagima', 'Tiguez'];
    private $finalDecision = 'Accepted';
    private $notes = ['These schematics are really robust!', 'The quality is very good'];

    public function setUp(): void
    {
        $this->submission = new ModeratedSubmission($this->submissionId, $this->title, $this->moderationStage, $this->submitterName, $this->submissionStatus, $this->submitterIsScieloJournal, $this->responsibles, $this->areaModerators, $this->finalDecision, $this->notes);
    }

    public function testSubmissionRecord(): void
    {
        $areaModerationTxt = __('plugins.reports.scieloModerationStagesReport.stages.areaStage');
        $statusTxt = __('submission.status.published');
        $submitterIsScieloJournalTxt = __('common.no');
        
        $expectedRecord = [
            1,
            'Schematics for guitar pickups',
            $areaModerationTxt,
            'Erico Malagoli',
            $statusTxt,
            $submitterIsScieloJournalTxt,
            'Carlos Alberto;Vinicius Dias',
            'Seizi Tagima;Tiguez',
            'Accepted',
            'These schematics are really robust!; The quality is very good'
        ];
        $this->assertEquals($expectedRecord, $this->submission->asRecord());
    }

}
