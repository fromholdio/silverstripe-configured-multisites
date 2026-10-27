<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Extension;

use Fromholdio\ConfiguredMultisites\Model\Site;
use Fromholdio\ConfiguredMultisites\Multisites;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
use SilverStripe\Model\List\ArrayList;

/**
 * @package silverstripe-multisites
 */
class MultisitesContentControllerExtension extends Extension
{

    /**
     * This method should be used to built navigation menus in templates,
     * instead of ContentController->getMenu()
     * @return ArrayList
     * */
    public function getSiteMenu($level = 1): ?ArrayList
    {
        $site   = Multisites::inst()->getCurrentSite();
        $page   = $this->getOwner()->data();
        $result = ArrayList::create();

        if ($level == 1) {
            $pages = SiteTree::get()->filter([
                'ParentID' => $site ? $site->ID : 0,
                'ShowInMenus' => true
            ]);
        } else {
            $parent = $page;
            $stack  = [$page];

            while (($parent = $parent->Parent()) && $parent->ID > 0 && !($parent instanceof Site)) {
                array_unshift($stack, $parent);
            }

            if (!isset($stack[$level - 2])) {
                return null;
            }

            $pages = $stack[$level - 2]->Children();
        }

        foreach ($pages as $page) {
            if ($page->canView()) {
                $result->push($page);
            }
        }

        return $result;
    }

    /**
     * Make sure a call to Site from templates always returns a valid Site object.
     * @return Site The current site.
     */
    public function Site()
    {
        return $this->getOwner()->data()->getSite();
    }
}
