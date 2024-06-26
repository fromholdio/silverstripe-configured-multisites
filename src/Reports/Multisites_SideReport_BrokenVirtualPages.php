<?php

namespace Fromholdio\ConfiguredMultisites\Reports;

use SilverStripe\CMS\Reports\BrokenVirtualPagesReport;
use Fromholdio\ConfiguredMultisites\Extension\MultisitesReport;

class Multisites_SideReport_BrokenVirtualPages extends BrokenVirtualPagesReport
{

    public function columns()
    {
        return MultisitesReport::getMultisitesReportColumns();
    }

    public function parameterFields()
    {
        $fields = parent::getParameterFields();
        $fields->push(MultisitesReport::getSiteParameterField());
        return $fields;
    }

    public function sourceRecords($params = null)
    {
        $records = parent::sourceRecords($params);
        $site    = isset($params['Site']) ? (int) $params['Site'] : 0;
        if ($site > 0) {
            $records = $records->filter('SiteID', $site);
        }
        return $records;
    }
}
