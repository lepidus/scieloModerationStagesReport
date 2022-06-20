<?php

/**
 * @file plugins/reports/scieloModerationStagesReport/ScieloModerationStagesReportPlugin.inc.php
 *
 * Copyright (c) 2022 Lepidus Tecnologia
 * Copyright (c) 2022 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @class ScieloModerationStagesReportPlugin
 * @ingroup plugins_reports_scieloModerationStagesReport
 *
 * @brief SciELO Moderation Stages Report plugin
 */

import('lib.pkp.classes.plugins.ReportPlugin');
import('classes.submission.Submission');

class scieloModerationStagesReportPlugin extends ReportPlugin {
    public function register($category, $path, $mainContextId = null) {
        $success = parent::register($category, $path, $mainContextId);

        if ($success && Config::getVar('general', 'installed')) {
            $this->addLocaleData();
            return $success;
        }
    }

    public function getName() {
        return 'ScieloModerationStagesReportPlugin';
    }

    public function getDisplayName() {
        return __('plugins.reports.scieloModerationStagesReport.displayName');
    }

    public function getDescription() {
        return __('plugins.reports.scieloModerationStagesReport.description');
    }

    public function display($args, $request) {
        //generate report
    }
}
