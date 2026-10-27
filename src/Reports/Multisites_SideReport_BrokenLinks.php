<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Reports;

use Fromholdio\ConfiguredMultisites\Extension\MultisitesReport;
use SilverStripe\CMS\Reports\BrokenLinksReport;
use SilverStripe\Forms\FieldList;

class Multisites_SideReport_BrokenLinks extends BrokenLinksReport
{

    public function columns(): array
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
            return $records->filter('SiteID', $site);
        }

        return $records;
    }
}
