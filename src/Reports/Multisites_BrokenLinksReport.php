<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Reports;

use Fromholdio\ConfiguredMultisites\Extension\MultisitesReport;
use SilverStripe\CMS\Reports\BrokenLinksReport;

class Multisites_BrokenLinksReport extends BrokenLinksReport
{

    public function columns(): float|int|array
    {
        return MultisitesReport::getMultisitesReportColumns() + parent::columns();
    }

    public function parameterFields()
    {
        $fields = parent::ParameterFields();
        $fields->push(MultisitesReport::getSiteParameterField());
        return $fields;
    }

    public function sourceRecords($params, $sort, $limit)
    {
        $records = parent::sourceRecords($params, $sort, $limit);
        $site    = isset($params['Site']) ? (int) $params['Site'] : 0;
        if ($site > 0) {
            return $records->filter('SiteID', $site);
        }

        return $records;
    }
}
