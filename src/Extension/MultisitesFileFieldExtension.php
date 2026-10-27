<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Extension;

use Fromholdio\ConfiguredMultisites\Multisites;
use SilverStripe\Core\Extension;

/**
 * @package silverstripe-multisites
 */
class MultisitesFileFieldExtension extends Extension
{

    /**
     * prepends an assets/currentsite folder to the upload folder name.
     * */
    public function useMultisitesFolder()
    {
        $site = Multisites::inst()->getActiveSite();
        if (!$site) {
            return $this->getOwner();
        }

        $multisiteFolder = $site->Folder();

        if (!$multisiteFolder->exists()) {
            $site->createAssetsSubfolder(true);
            $multisiteFolder = $site->Folder();
        }

        $this->getOwner()->setFolderName($multisiteFolder->Name . '/' . $this->getOwner()->getFolderName());

        return $this->getOwner();
    }
}
