<?php

namespace Symbiote\Multisites\Reports;

use SilverStripe\CMS\Reports\RecentlyEditedReport;
use SilverStripe\Forms\FieldList;
use Symbiote\Multisites\Extension\MultisitesReport;

class Multisites_SideReport_RecentlyEdited extends RecentlyEditedReport
{

    public function columns()
    {
        $columns               = MultisitesReport::getMultisitesReportColumns();
        $columns['LastEdited'] = array(
            "title" => "Last Edited",
        );
        $columns['LastEdited'] = array(
            "title" => "Last Edited",
        );
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
            $records = $records->filter('SiteID', $site);
        }
        return $records;
    }
}
