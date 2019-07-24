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
        if ($site = Multisites::inst()->getActiveSite()) {
            $name = str_replace('error-', 'error-'.$site->Host.'-', $name);
        }
    }
}