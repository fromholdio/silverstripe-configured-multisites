<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Control;

use Exception;
use Fromholdio\ConfiguredMultisites\Multisites;
use Psr\Log\LoggerInterface;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Parsers\URLSegmentFilter;

/**
 * @package silverstripe-multisites
 */
class MultisitesFrontController extends ModelAsController
{
    /**
     * Overrides ModelAsController->getNestedController to find the nested controller
     * on a per-site basis
     **/
    public function getNestedController(): ContentController
    {
        $request = $this->getRequest();
        $urlSegment = $request?->param('URLSegment');
        if (empty($urlSegment)) {
            $urlSegment = RootURLController::get_homepage_link();
            Injector::inst()->get(LoggerInterface::class)->info('No URLSegment to MultisitesFrontController for requested URL: "' . $request?->getURL(true) . '"');
        }

        $siteID = Multisites::inst()->getCurrentSiteId();
        if (empty($siteID)) {
            throw new Exception('MultisitesFrontController->getNestedController(): could not find a Current Site ID.');
        }

        // url encode unless it's multibyte (already pre-encoded in the database)
        $filter = URLSegmentFilter::create();
        if (!$filter->getAllowMultibyte()) {
            $urlSegment = rawurlencode($urlSegment);
        }

        // Select child page
        $conditions = ['URLSegment' => $urlSegment];
        $conditions['ParentID'] = $siteID;
        $sitetree = SiteTree::get()->setUseCache(true)->filter($conditions)->first();

        if (!$sitetree) {
            $this->httpError(404, 'The requested page could not be found.');
        }

        if (isset($_REQUEST['debug'])) {
            Debug::message("Using record #$sitetree->ID of type " . get_class($sitetree) . " with link {$sitetree->Link()}");
        }

        return static::controller_for($sitetree, $this->getRequest()->param('Action'));
    }
}
