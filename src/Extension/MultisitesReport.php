<?php

namespace Symbiote\Multisites\Extension;

use Symbiote\Multisites\Model\Site;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Core\Extension;

/**
 * Override default reports to provide columns and filters that help the user identify which site the
 * report or page being reported on is associated with
 * @package multisites
 * @author shea@symbiote.com.au
 * */
class MultisitesReport extends Extension
{

    public function updateCMSFields(FieldList $fields)
    {
        $gfc           = $fields->fieldByName('Report')->getConfig();
        $columns       = $this->owner->columns();
        $exportColumns = array();
        foreach ($columns as $k => $v) {
            $exportColumns[$k] = is_array($v) ? $v['title'] : $v;
        }
        $gfc->getComponentByType(GridFieldExportButton::class)->setExportColumns($exportColumns);
    }

    public static function getMultisitesReportColumns()
    {
        return array(
            "Title" => array(
                "title" => "Title",
                "link" => true,
            ),
            "Site.Title" => array(
                "title" => "Site"
            ),
            "AbsoluteLink" => array(
                "title" => "URL",
                "link" => true
            )
        );
    }

    public static function getSiteParameterField()
    {
        $source = Site::get()->map('ID', 'Title')->toArray();
        $source = array('0' => 'All') + $source; // works around ajax bug
        return DropdownField::create('Site', 'Site', $source)->setHasEmptyDefault(false);
    }
}
