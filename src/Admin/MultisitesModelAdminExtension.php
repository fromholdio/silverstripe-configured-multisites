<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Admin;

use Exception;
use Fromholdio\ConfiguredMultisites\Extension\MultisitesAware;
use Fromholdio\ConfiguredMultisites\Model\Site;
use Fromholdio\ConfiguredMultisites\Multisites;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;

/**
 * MultisitesModelAdminExtension
 *
 * @package silverstripe-multisites
 */
class MultisitesModelAdminExtension extends Extension
{

    private static array $allowed_actions = [
        'updateSiteFilter'
    ];

    /**
     * Cached instance of the data class currently being listed
     *
     * @var DataObject
     **/
    private $listDataClass;

    /**
     * If this dataClass is MultisitesAware, set the Multisites_ActiveSite
     * session variable to one of the follwing:
     * a) The SiteID passed in the request params, if it exists
     * b) The current site, if the current member is a manager of that site
     * c) The first site that the current member is a manager of
     **/
    public function onAfterInit(): void
    {
        // throws exception if using ss-recipe <= 4.3.0 and using MultiSitesAware
        $this->checkMultisitesAware();

        if ($this->modelIsMultiSitesAware()) {
            if ($siteID = $this->getOwner()->getRequest()->requestVar('SiteID')) {
                $this->setActiveSite($siteID);
            }

            if (!$this->getActiveSite()) {
                $managedByMember = Multisites::inst()->sitesManagedByMember();

                if (count($managedByMember)) {
                    $currentSiteID = Multisites::inst()->getCurrentSiteId();
                    if (in_array($currentSiteID, $managedByMember)) {
                        $siteID = $currentSiteID;
                    } else {
                        $siteID = $managedByMember[0];
                    }

                    $this->setActiveSite($siteID);
                }
            }
        }
    }

    /**
     * Prevents use of MultisitesAware extension when using
     * silvestripe-recipe <= 4.3.0
     */
    public function checkMultisitesAware(): void
    {
        // only true if using silverstripe-recipe <= 4.3.0
        if (!$this->getOwner()->hasMethod('getModelClass')) {
            $models = $this->getOwner()->getManagedModels();
            // throw error if any models use MultisitesAware extension
            foreach ($models as $model => $val) {
                if ($model::has_extension(MultisitesAware::class)) {
                    throw new Exception(
                        '`ModelAdmin::getModelClass()` missing. You are likely using `silverstripe-recipe:4.3.0` which introduced this bug. This can be fixed by updating to `silverstripe-recipe:4.3.1` or greater.'
                    );
                }
            }
        }
    }

    /**
     * If this dataClass is MultisitesAware, filter the list by the current Multisites_ActiveSite
     **/
    public function updateList(&$list): void
    {
        if ($this->modelIsMultiSitesAware()) {
            $site = $this->getActiveSite();
            if ($site) {
                $list = $list->filter('SiteID', $site->ID);
            }
        }
    }

    /**
     * If the current member is not a "Manager" of any sites, they shouldn't be able to manage MultisitesAware DataObjects.
     **/
    public function updateEditForm($form): void
    {
        if ($this->modelIsMultiSitesAware()) {
            $managedSites = Multisites::inst()->sitesManagedByMember();
            $source = Site::get()->filter(['ID' => Multisites::inst()->sitesManagedByMember()])->map('ID', 'Title')->toArray();
            $plural = singleton($this->getOwner()->modelClass)->plural_name();
            if (!count($source)) {
                $form->setFields(FieldList::create(
                    LiteralField::create('NotAManager', "You do not have permission to manage $plural on any Sites")
                ));
            }
        }
    }

    /**
     * Provide a Site filter
     **/
    public function updateSearchForm($form): void
    {
        if ($this->modelIsMultiSitesAware()) {
            $managedSites = Multisites::inst()->sitesManagedByMember();

            $source = Site::get()->filter(['ID' => Multisites::inst()->sitesManagedByMember()])->map('ID', 'Title')->toArray();
            $plural = singleton($this->getOwner()->modelClass)->plural_name();
            if (count($source)) {
                $activeSite = $this->getActiveSite();
                $form->Fields()->push(DropdownField::create(
                    'SiteID',
                    "Site: ",
                    $source,
                    $activeSite ? $activeSite->ID : null
                ));
            }
        }
    }

    /**
     * get the active site for this model admin
     *
     * @return Site
     **/
    public function getActiveSite()
    {
        if ($this->getOwner()->config()->use_active_site_session) {
            return Multisites::inst()->getActiveSite();
        }

        if ($this->modelIsMultiSitesAware()) {
            if ($active = $this->getOwner()->getRequest()->getSession()->get($this->getActiveSiteSessionKey())) {
                return Site::get()->byID($active);
            }
        }

        return null;
    }

    /**
     * Set the active site for this model admin
     *
     * @param int $siteID
     **/
    public function setActiveSite($siteID): void
    {
        if ($this->getOwner()->config()->use_active_site_session) {
            Multisites::inst()->setActiveSite($siteID);
        } else {
            $this->getOwner()->getRequest()->getSession()->set($this->getActiveSiteSessionKey(), $siteID);
        }
    }

    /**
     * Get the key used to store this model admin active site Session to
     **/
    public function getActiveSiteSessionKey(): string
    {
        return 'Multisites_ActiveSite_For_' . $this->getOwner()::class;
    }

    /**
     * Get and cache an instance of the data class currently being listed
     *
     * @return DataObject
     **/
    private function getListDataClass()
    {
        /**
         * Silverstripe-recipe 4.3.0 introduces changes that broke the
         * previous approach to retrieving the model class.
         *
         * This is/will be fixed in silverstripe-recipe 4.3.1
         *
         * We've taken this approach so that Multisites can work with
         * SS 4.3.0 so long as you don't use the MultisitesAware extension.
         */
        if ($this->listDataClass === null) {
            // only true if silverstripe-recipe > 4.3.0
            if ($this->getOwner()->hasMethod('getModelClass')) {
                $this->listDataClass = $this->getOwner()->getModelClass();
            }
            // else false to bypass the MultisitesAware extension
            else {
                $this->listDataClass = false;
            }
        }

        return $this->listDataClass;
    }

    /**
     * Determines whether the current model being managed is MultiSitesAware
     **/
    private function modelIsMultiSitesAware(): bool
    {
        $model = $this->getListDataClass();
        // always false if using silverstripe-recipe <= 4.3.0
        if ($model) {
            $isAware = $model::has_extension(MultisitesAware::class);
            $isSiteTree = in_array(SiteTree::class, ClassInfo::ancestry($model));
            return $isAware || $isSiteTree;
        }

        return false;
    }
}
