<?php

define('SCIELO_MODERATION_STAGE_REPORT_FORMAT', 1);
define('SCIELO_MODERATION_STAGE_REPORT_CONTENT', 2);
define('SCIELO_MODERATION_STAGE_REPORT_AREA', 3);

class ModeratedSubmission
{
    private $submissionId;
    private $title;
    private $moderationStage;
    private $submitter;
    private $status;
    private $submitterIsScieloJournal;
    private $responsibles;
    private $areaModerators;
    private $finalDecision;
    private $notes;

    public function __construct(int $submissionId, string $title, ?int $moderationStage, string $submitter, int $status, bool $submitterIsScieloJournal, array $responsibles, array $areaModerators, string $finalDecision, array $notes)
    {
        $this->submissionId = $submissionId;
        $this->title = $title;
        $this->moderationStage = $moderationStage;
        $this->submitter = $submitter;
        $this->status = $status;
        $this->submitterIsScieloJournal = $submitterIsScieloJournal;
        $this->responsibles = $responsibles;
        $this->areaModerators = $areaModerators;
        $this->finalDecision = $finalDecision;
        $this->notes = $notes;
    }

    public function hasModerationStage(): bool
    {
        return !is_null($this->moderationStage);
    }

    public function getModerationStage(): string
    {
        if (is_null($this->moderationStage)) {
            return __('plugins.reports.scieloModerationStagesReport.stages.noModerationStage');
        }

        $stageMap = [
            SCIELO_MODERATION_STAGE_REPORT_FORMAT => 'plugins.reports.scieloModerationStagesReport.stages.formatStage',
            SCIELO_MODERATION_STAGE_REPORT_CONTENT => 'plugins.reports.scieloModerationStagesReport.stages.contentStage',
            SCIELO_MODERATION_STAGE_REPORT_AREA => 'plugins.reports.scieloModerationStagesReport.stages.areaStage',
        ];

        return __($stageMap[$this->moderationStage]);
    }

    private function getStatus(): string
    {
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);
        $statusMap = [
            STATUS_QUEUED => 'submissions.queued',
            STATUS_PUBLISHED => 'submission.status.published',
            STATUS_DECLINED => 'submission.status.declined',
            STATUS_SCHEDULED => 'submission.status.scheduled'
        ];

        return __($statusMap[$this->status]);
    }

    private function getSubmitterIsScieloJournal(): string
    {
        return $this->submitterIsScieloJournal ? __("common.yes") : __("common.no");
    }

    public function getResponsibles(): string
    {
        if (empty($this->responsibles)) {
            return __('plugins.reports.scieloModerationStagesReport.noResponsibles');
        }

        return implode(";", $this->responsibles);
    }

    public function getAreaModerators(): string
    {
        if (empty($this->areaModerators)) {
            return __('plugins.reports.scieloModerationStagesReport.noAreaModerators');
        }

        return implode(";", $this->areaModerators);
    }

    public function getNotes(): string
    {
        if (empty($this->notes)) {
            return __('plugins.reports.scieloModerationStagesReport.noNotes');
        }

        return trim(preg_replace('/\s+/', ' ', "Nota: " . implode(" Nota: ", $this->notes)));
    }

    public function asRecord(): array
    {
        return [
            $this->submissionId,
            $this->title,
            $this->getModerationStage(),
            $this->submitter,
            $this->getStatus(),
            $this->getSubmitterIsScieloJournal(),
            $this->getResponsibles(),
            $this->getAreaModerators(),
            $this->finalDecision,
            $this->getNotes()
        ];
    }
}
