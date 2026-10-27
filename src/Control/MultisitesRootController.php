<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Control;

use Fromholdio\ConfiguredMultisites\Multisites;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;

/**
 * @package silverstripe-multisites
 */
class MultisitesRootController extends RootURLController
{

    public function handleRequest(HTTPRequest $request): HTTPResponse
    {
        self::$is_at_root = true;
        $this->beforeHandleRequest($request);


        if (!$this->getResponse()->isFinished()) {
            if (!$site = Multisites::inst()->getCurrentSiteId()) {
                return $this->httpError(404);
            }

            $page = SiteTree::get()->filter([
                'ParentID'   => $site,
                'URLSegment' => RootURLController::get_homepage_link()
            ]);

            if (!$page = $page->first()) {
                return $this->httpError(404);
            }

            $pageRequest = new HTTPRequest(
                $request->httpMethod(),
                '/' . RootURLController::get_homepage_link(),
                $request->getVars(),
                $request->postVars()
            );

            $headers = $request->getHeaders();
            foreach ($headers as $key => $value) {
                $pageRequest->addHeader($key, $value);
            }

            $pageRequest->setSession($request->getSession());
            $pageRequest->match('$URLSegment//$Action', true);

            $front    = MultisitesFrontController::create();
            $response = $front->handleRequest($pageRequest);

            $this->prepareResponse($response);
        }

        $this->afterHandleRequest();

        return $this->getResponse();
    }

    /**
     * The the (relative) homepage link.
     * TODO: Should this deal with HomepageForDomain and Translatable the same way the core equivalent does?
     *
     * @return string
     */
    public static function get_homepage_link()
    {
        return Config::inst()->get(static::class, 'default_homepage_link');
    }

    /**
     * Returns TRUE if a request to a certain page should be redirected to the site root (i.e. if the page acts as the
     * home page).
     *
     * TODO: This function wouldn't be required if core called static::get_homepage_link() rather than self::get_homepage_link(). Raise a bug?
     */
    public static function should_be_on_root(SiteTree $page): bool
    {
        if (!self::$is_at_root && self::get_homepage_link() == trim($page->RelativeLink(true), '/')) {
            return !(
                class_exists('Translatable') && $page->hasExtension('Translatable') && $page->Locale && $page->Locale != Translatable::default_locale()
            );
        }

        return false;
    }
}
