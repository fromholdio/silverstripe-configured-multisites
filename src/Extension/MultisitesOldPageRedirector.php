<?php

namespace Symbiote\Multisites\Extension;

use SilverStripe\CMS\Controllers\OldPageRedirector;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use Symbiote\Multisites\Control\MultisitesRootController;
use Symbiote\Multisites\Multisites;

class MultisitesOldPageRedirector extends OldPageRedirector
{
    public function onBeforeHTTPError404(HTTPRequest $request)
    {
        // We need to get the URL ourselves because $request->allParams() only has a max of 4 params
        $params = preg_split('|/+|', $request->getURL() ?? '');
        $cleanURL = trim(Director::makeRelative($request->getURL(false)) ?? '', '/');

        $getvars = $request->getVars();
        unset($getvars['url']);

        $siteID = Multisites::inst()->getCurrentSiteId();
        if (empty($siteID)) {
            throw new \Exception('MultisitesOldPageRedirector->onBeforeHTTPError404(): could not find a Current Site ID.');
        }

        $page = self::find_old_page($params, $siteID);
        if (!$page) {
            $page = self::find_old_page($params);
        }

        $cleanPage = trim(Director::makeRelative($page) ?? '', '/');
        if (!$cleanPage) {
            $cleanPage = Director::makeRelative(MultisitesRootController::get_homepage_link());
        }

        if ($page && $cleanPage != $cleanURL) {
            $res = new HTTPResponse();
            $res->redirect(
                Controller::join_links(
                    $page,
                    ($getvars) ? '?' . http_build_query($getvars) : null
                ),
                301
            );
            throw new HTTPResponse_Exception($res);
        }
    }
}
