<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Extension;

use Fromholdio\ConfiguredMultisites\Model\Site;
use Fromholdio\ConfiguredMultisites\Multisites;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;

/**
 * MultisitesAware
 *
 * @package silverstripe-multisites
 */
class MultisitesAware extends Extension
{
    private static array $has_one = [
        'Site' => Site::class
    ];

    public function onBeforeWrite(): void
    {
        if (!$this->getOwner()->SiteID) {
            $this->getOwner()->SiteID = Multisites::inst()->getActiveSite()->ID;
        }
    }

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->removeByName('SiteID');
    }

    /**
     * Check to see if the current user has permission to edit this MultisitesAware object
     * On the site this object is associated with.
     * */
    public function canEdit($member = null): ?bool
    {
        $managedSites = Multisites::inst()->sitesManagedByMember();
        if (count($managedSites) && in_array($this->getOwner()->SiteID, $managedSites)) {
            // member has permission to manage MultisitesAware objects on this site,
            // hand over to the object's canEdit method
            return null;
        }

        // member does not have permission to edit objects on this object's Site
        return false;
    }
}
