<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Extension;

use Fromholdio\ConfiguredMultisites\Multisites;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Assets\Upload;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\Command\DbBuild;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\View\SSViewer;

/**
 *  @author Nathan Glasl <nathan@symbiote.com.au>
 */
class MultisitesControllerExtension extends Extension
{

    /**
     * Sets the theme to the current site theme
     * */
    public function onAfterInit(): void
    {
        if ($this->getOwner() instanceof DbBuild) {
            //
            // 2016-12-16 - This is disabled in sitetree.yml to stop users placing
            //              pages above a Site. However, during dev/build we don't
            //              want pages validated so they can be placed top-level, and
            //              then be moved underneath Site during it's
            //              requireDefaultRecords() call.
            //
            SiteTree::config()->can_be_root = true;
            return;
        }

        if ($this->getOwner() instanceof DevelopmentAdmin ||
            $this->getOwner() instanceof DbBuild) {
            return;
        }

        $site = Multisites::inst()->getCurrentSite();
        if (!$site) {
            return;
        }

        // are we on the frontend?
        if (!$this->getOwner() instanceof LeftAndMain) {
            $themes = $site->getSiteThemes();
            SSViewer::set_themes($themes);
        }

        // Update default uploads folder to site
        $folder = $site->Folder();
        if ($folder->exists()) {
            Config::modify()->set(Upload::class, 'uploads_folder', $folder->Name);
        }
    }
}
