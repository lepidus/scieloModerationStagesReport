<?php

use PHPUnit\Framework\TestCase;

import('plugins.reports.scieloModerationStagesReport.classes.ModerationStagesReportHelper');

class ModerationStagesReportHelperTest extends TestCase
{
    private $helper;
    private $submissionId = 1;

    public function setUp(): void
    {
        $this->helper = new ModerationStagesReportHelper();
    }
}
