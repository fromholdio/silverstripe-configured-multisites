<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Extension;

use Fromholdio\ConfiguredMultisites\Multisites;
use SilverStripe\Core\Extension;
use SilverStripe\View\SSViewer;

/**
 * Sets the site theme when someone tries to login on a particular URL
 *
 * @package silverstripe-multisites
 */
class MultisitesSecurityExtension extends Extension
{

    /**
     * Sets the theme to the current site theme
     * */
    public function onBeforeSecurityLogin(): void
    {
        $site = Multisites::inst()->getCurrentSite();
        if ($site) {
            $themes = $site->getSiteThemes();
            SSViewer::set_themes($themes);
        }
    }
}
