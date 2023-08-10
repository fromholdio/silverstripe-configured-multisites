<?php

namespace Symbiote\Multisites\Reports;

use SilverStripe\CMS\Reports\BrokenLinksReport;
use SilverStripe\Forms\FieldList;
use Symbiote\Multisites\Extension\MultisitesReport;

class Multisites_SideReport_BrokenLinks extends BrokenLinksReport
{

    public function columns()
    {
        return MultisitesReport::getMultisitesReportColumns();
    }

    public function parameterFields()
    {
        $fields = FieldList::create();
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
