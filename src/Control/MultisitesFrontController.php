<?php

namespace Symbiote\Multisites\Control;

use Psr\Log\LoggerInterface;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Parsers\URLSegmentFilter;
use Symbiote\Multisites\Multisites;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Controllers\ModelAsController;
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
            $urlSegment = 'home';
            Injector::inst()->get(LoggerInterface::class)->info('No URLSegment to MultisitesFrontController for requested URL: "' . $request?->getURL(true) . '"');
            //throw new \Exception('MultisitesFrontController->getNestedController(): was not passed a URLSegment value.');
        }

        $siteID = Multisites::inst()->getCurrentSiteId();
        if (empty($siteID)) {
            throw new \Exception('MultisitesFrontController->getNestedController(): could not find a Current Site ID.');
        }

        // url encode unless it's multibyte (already pre-encoded in the database)
        $filter = URLSegmentFilter::create();
        if (!$filter->getAllowMultibyte()) {
            $urlSegment = rawurlencode($urlSegment);
        }

        // Select child page
        $tableName = DataObject::singleton(SiteTree::class)->baseTable();
        $conditions = [sprintf('"%s"."URLSegment"', $tableName) => $urlSegment];
        $conditions[] = [sprintf('"%s"."ParentID"', $tableName) => $siteID];
        /** @var SiteTree $siteTree */
        $siteTree = DataObject::get_one(SiteTree::class, $conditions);

        if (!$siteTree) {
            $this->httpError(404);
        }

        return static::controller_for($siteTree, $request->param('Action'));
	}
}
