<?php

/**
 * @defgroup plugins_reports_scieloModerationStagesReport SciELO Moderation Stages Report Plugin
 */

/**
 * @file plugins/reports/scieloModerationStagesReport/index.php
 *
 * Copyright (c) 2022 Lepidus Tecnologia
 * Copyright (c) 2022 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @ingroup plugins_reports_scieloModerationStagesReport
 * @brief Wrapper for SciELO Moderation Stages Report plugin.
 *
 */

require_once('ScieloModerationStagesReportPlugin.inc.php');

return new ScieloModerationStagesReportPlugin();
