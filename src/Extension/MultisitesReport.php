<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Extension;

use Fromholdio\ConfiguredMultisites\Model\Site;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldExportButton;

/**
 * Override default reports to provide columns and filters that help the user identify which site the
 * report or page being reported on is associated with
 * @package multisites
 * @author shea@symbiote.com.au
 * */
class MultisitesReport extends Extension
{

    public function updateCMSFields(FieldList $fields): void
    {
        $gfc           = $fields->fieldByName('Report')->getConfig();
        $columns       = $this->getOwner()->columns();
        $exportColumns = [];
        foreach ($columns as $k => $v) {
            $exportColumns[$k] = is_array($v) ? $v['title'] : $v;
        }

        $gfc->getComponentByType(GridFieldExportButton::class)->setExportColumns($exportColumns);
    }

    public static function getMultisitesReportColumns(): array
    {
        return [
            "Title" => [
                "title" => "Title",
                "link" => true,
            ],
            "Site.Title" => [
                "title" => "Site"
            ],
            "AbsoluteLink" => [
                "title" => "URL",
                "link" => true
            ]
        ];
    }

    public static function getSiteParameterField()
    {
        $source = Site::get()->map('ID', 'Title')->toArray();
        $source = ['0' => 'All'] + $source; // works around ajax bug
        return DropdownField::create('Site', 'Site', $source)->setHasEmptyDefault(false);
    }
}
