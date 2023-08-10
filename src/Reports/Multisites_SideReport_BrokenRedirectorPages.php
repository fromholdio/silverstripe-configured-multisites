<?php

namespace Symbiote\Multisites\Reports;

use SilverStripe\CMS\Reports\BrokenRedirectorPagesReport;
use Symbiote\Multisites\Extension\MultisitesReport;

class Multisites_SideReport_BrokenRedirectorPages extends BrokenRedirectorPagesReport
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
