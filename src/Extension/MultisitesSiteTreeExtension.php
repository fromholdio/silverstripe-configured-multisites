<?php

namespace Fromholdio\ConfiguredMultisites\Extension;

use Fromholdio\ConfiguredMultisites\Control\MultisitesRootController;
use Fromholdio\ConfiguredMultisites\Model\Site;
use Fromholdio\ConfiguredMultisites\Multisites;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;

/**
 * Modifies SiteTree behaviour when in a multisite
 *
 * @package silverstripe-multisites
 */
class MultisitesSiteTreeExtension extends SiteTreeExtension
{
    private static $has_one = array(
        'Site' => Site::class
    );

    /**
     * @return Site
     */
    public function CurrentSite()
    {
        return Multisites::inst()->getCurrentSite();
    }

    /**
     * @return Site
     */
    public function DefaultSite()
    {
        return Multisites::inst()->getDefaultSite();
    }

    public function updateLink(&$link, $action = null, $relativeLink = null)
    {
        $siteID = $this->getOwner()->getField('SiteID');
        $activeSiteID = Multisites::inst()->getActiveSite()?->getField('ID');
        if ($siteID !== $activeSiteID) {
            $link = $relativeLink;
        }
    }

    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner->ParentID) {
            $url = $this->owner->Parent()->AbsoluteLink();
            if (!$url) {
                $url = $this->owner->Parent()->getURL().'/';
            }
        } else {
            $url = Director::absoluteBaseURL();
        }


        $fields->dataFieldByName('URLSegment')->setURLPrefix($url);
    }

    /**
     * Make sure site home pages are loaded at the root of the site.
     */
    public function contentcontrollerInit($controller)
    {

        // If we've accessed the homepage as /home/, then we should redirect to /.
        if ($controller->dataRecord && $controller->dataRecord instanceof SiteTree && MultisitesRootController::should_be_on_root($controller->dataRecord)
            && (!isset($controller->urlParams['Action']) || !$controller->urlParams['Action'] ) && !$_POST && !$_FILES && !$controller->redirectedTo()) {
            $getVars = $_GET;
            unset($getVars['url']);
            if ($getVars) $url     = "?".http_build_query($getVars);
            else $url     = "";
            $controller->redirect($url, 301);
            return;
        }
    }

    /**
     * Keep the SiteID field consistent.
     */
    public function onBeforeWrite()
    {
        if ($this->owner instanceof Site) {
            if (!$this->owner->ID) {
                // Initialise a new Site to the top level
                $this->owner->SiteID   = 0;
                $this->owner->ParentID = 0;
            }
            return;
        }

        // Set the SiteID (and ParentID if required) for all new pages.
        if (!$this->owner->ID) {
            $parent = $this->owner->Parent();
            if ($parent && $parent->exists()) {
                // Page is beneath a Site
                if ($parent instanceof Site) {
                    $this->owner->SiteID = $parent->ID;
                } else {
                    $this->owner->SiteID = $parent->SiteID;
                }
            } else {
                // Create the page beneath the current Site
                $site = Multisites::inst()->getDefaultSite();
                if ($site) {
                    $this->owner->SiteID   = $site->ID;
                    $this->owner->ParentID = $this->owner->SiteID;
                }
            }
        }

        // Make sure SiteID is changed when site tree is reorganised.
        if ($this->owner->ID && $this->owner->isChanged('ParentID')) {
            // Get the new parent
            $parent = DataObject::get_by_id(SiteTree::class, $this->owner->ParentID);

            // Make sure the parent exists
            if ($parent) {
                // Recursively change SiteID for this and all child pages
                $siteId = ($parent instanceof Site) ? $parent->ID : $parent->SiteID;
                $this->owner->updateSiteID($siteId);
            }
        }
    }

    /**
     * Recursively update the site ID for this page and all child pages. This writes decendents to the
     * database, but does not write the current page as it is called from {@link onBeforeWrite}
     *
     * @todo This will mark all child pages as modified. Should it write directly to the database to avoid the publishing workflow?
     *
     * @param int $new The new SiteID
     */
    public function updateSiteID($new)
    {
        if ($this->owner->SiteID != $new) {
            $this->owner->SiteID = $new;
            if ($this->owner->isChanged('SiteID')) {
                foreach ($this->owner->Children() as $child) {
                    $child->updateSiteID($new);
                    $child->write();
                }
            }
        }
    }

    public function alternateAbsoluteLink($action = null)
    {
        $siteID = $this->getOwner()->SiteID;
        if (!empty($siteID))
        {
            $link = $this->getOwner()->RelativeLink($action);
            if (!Director::is_absolute_url($link))
            {
                $site = $this->getOwner()->Site();
                $siteAbsoluteLink = $site->AbsoluteLink();
                $link = Controller::join_links($siteAbsoluteLink, $link);
            }
            return Director::absoluteURL($link);
        }
        return $this->getOwner()->RelativeLink($action);
    }

    /**
     * Returns the current site object in case this is a fake page (like in the case of  pages served
     * by the {@link Security} controller)
     *
     * @return Site
     */
    public function getSite()
    {
        $site = $this->owner->getComponent('Site');
        return ($site->ID) ? $site : Multisites::inst()->getCurrentSite();
    }
}
