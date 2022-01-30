<?php
namespace Symbiote\Multisites\Extension;

use Symbiote\Multisites\Multisites;
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
    public function updateErrorFilename(&$name, $statusCode)
    {
        $site = $this->getOwner()->getSite() ?? null;
        if (is_null($site)) {
            $site = Multisites::inst()->getActiveSite();
        }

        $nameParts = ['error'];
        if ($site) {
            $nameParts[] = $site->Host;
        }
        $nameParts[] = $this->getOwner()->ErrorCode . '.html';
        $name = implode('-', $nameParts);
    }
}
