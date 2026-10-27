<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Reports;

use Fromholdio\ConfiguredMultisites\Extension\MultisitesReport;
use SilverStripe\CMS\Reports\RecentlyEditedReport;
use SilverStripe\Forms\FieldList;

class Multisites_SideReport_RecentlyEdited extends RecentlyEditedReport
{

    /**
     * @return mixed[]
     */
    public function columns(): array
    {
        $columns               = MultisitesReport::getMultisitesReportColumns();
        $columns['LastEdited'] = [
            "title" => "Last Edited",
        ];
        $columns['LastEdited'] = [
            "title" => "Last Edited",
        ];
        return $columns;
    }

    public function parameterFields()
    {
        $fields = FieldList::create();
        $fields->push(MultisitesReport::getSiteParameterField());
        return $fields;
    }

    public function sourceRecords($params = null)
    {
        $records = parent::sourceRecords($params);
        $site    = isset($params['Site']) ? (int) $params['Site'] : 0;
        if ($site > 0) {
            return $records->filter('SiteID', $site);
        }

        return $records;
    }
}
