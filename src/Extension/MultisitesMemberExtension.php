<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Extension;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;

/**
 * Ensures a user who logs in has their active site reset
 */
class MultisitesMemberExtension extends Extension
{

    public function memberLoggedIn(): void
    {
        $request = Injector::inst()->get(HTTPRequest::class);
        $session = $request->getSession();
        if ($session) {
            $session->clear('Multisites_ActiveSite');
            $session->clear('MultisitesModelAdmin_SiteID'); // legacy
        }
    }
}
