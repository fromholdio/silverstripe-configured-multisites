<?php

namespace Fromholdio\ConfiguredMultisites\Reports;

use SilverStripe\CMS\Reports\BrokenLinksReport;
use Fromholdio\ConfiguredMultisites\Extension\MultisitesReport;

class Multisites_BrokenLinksReport extends BrokenLinksReport
{

    public function columns()
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
            $records = $records->filter('SiteID', $site);
        }
        return $records;
    }
}
