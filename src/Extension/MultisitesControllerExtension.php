<?php

namespace Symbiote\Multisites\Extension;

use Symbiote\Multisites\Multisites;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\Dev\DevBuildController;
use SilverStripe\View\SSViewer;
use SilverStripe\Core\Config\Config;
use SilverStripe\Assets\Upload;
use SilverStripe\Core\Extension;

/**
 * 	@author Nathan Glasl <nathan@symbiote.com.au>
 */
class MultisitesControllerExtension extends Extension
{

    /**
     * Sets the theme to the current site theme
     * */
    public function onAfterInit()
    {
        if ($this->owner instanceof DatabaseAdmin) {
            //
            // 2016-12-16 -	This is disabled in sitetree.yml to stop users placing
            //				pages above a Site. However, during dev/build we don't
            //				want pages validated so they can be placed top-level, and
            //				then be moved underneath Site during it's
            //				requireDefaultRecords() call.
            //
			SiteTree::config()->can_be_root = true;
            return;
        }

        if ($this->owner instanceof DevelopmentAdmin ||
            $this->owner instanceof DevBuildController ||
            $this->owner instanceof DatabaseAdmin) {
            return;
        }

        $site = Multisites::inst()->getCurrentSite();
        if (!$site) {
            return;
        }

        // are we on the frontend?
        if (!$this->owner instanceof \SilverStripe\Admin\LeftAndMain) {
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
