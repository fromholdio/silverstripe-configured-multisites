<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Extension;

use Fromholdio\ConfiguredMultisites\Multisites;
use SilverStripe\Core\Extension;

/**
 * Publishes separate static error pages for each site.
 * Also prevents publishing of error pages on domains that the
 * user isn't logged into.
 *
 * @package silverstripe-multisites
 */
class MultisitesErrorPageExtension extends Extension
{
    public function updateErrorFilename(&$name, $statusCode): void
    {
        $site = $this->getOwner()->getSite() ?? null;
        $site ??= Multisites::inst()->getActiveSite();

        $nameParts = ['error'];
        if ($site) {
            $nameParts[] = $site->Host;
        }

        $nameParts[] = $this->getOwner()->ErrorCode . '.html';
        $name = implode('-', $nameParts);
    }
}
