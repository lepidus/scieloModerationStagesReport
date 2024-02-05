<?php

/**
 * @file plugins/reports/scieloModerationStagesReport/ScieloModerationStagesReportPlugin.inc.php
 *
 * Copyright (c) 2022 - 2024 Lepidus Tecnologia
 * Copyright (c) 2022 - 2024 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @class ScieloModerationStagesReportPlugin
 * @ingroup plugins_reports_scieloModerationStagesReport
 *
 * @brief SciELO Moderation Stages Report plugin
 */

namespace APP\plugins\reports\scieloModerationStagesReport;

use PKP\plugins\ReportPlugin;
use APP\core\Application;
use APP\submission\Submission;
use PKP\core\PKPString;
use APP\plugins\reports\scieloModerationStagesReport\classes\ModerationStagesReportHelper;

class ScieloModerationStagesReportPlugin extends ReportPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if (Application::isUnderMaintenance()) {
            return $success;
        }

        if ($success && $this->getEnabled($mainContextId)) {
            $this->addLocaleData();
            return $success;
        }
    }

    public function getName()
    {
        return 'ScieloModerationStagesReportPlugin';
    }

    public function getDisplayName()
    {
        return __('plugins.reports.scieloModerationStagesReport.displayName');
    }

    public function getDescription()
    {
        return __('plugins.reports.scieloModerationStagesReport.description');
    }

    public function display($args, $request)
    {
        $moderationStagesReportHelper = new ModerationStagesReportHelper();
        $moderationStagesReport = $moderationStagesReportHelper->createModerationStagesReport();

        $this->emitHttpHeaders($request);
        $csvFile = fopen('php://output', 'wt');
        $moderationStagesReport->buildCSV($csvFile);
    }

    private function emitHttpHeaders($request)
    {
        $context = $request->getContext();
        header('content-type: text/comma-separated-values');
        $acronym = PKPString::regexp_replace("/[^A-Za-z0-9 ]/", '', $context->getLocalizedAcronym());
        header('content-disposition: attachment; filename=submissions' . $acronym . '-' . date('YmdHis') . '.csv');
    }
}
