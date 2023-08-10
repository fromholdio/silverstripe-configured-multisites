<?php

namespace Symbiote\Multisites\Reports;

use SilverStripe\CMS\Reports\EmptyPagesReport;
use SilverStripe\Forms\FieldList;
use Symbiote\Multisites\Extension\MultisitesReport;

class Multisites_SideReport_EmptyPages extends EmptyPagesReport
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
