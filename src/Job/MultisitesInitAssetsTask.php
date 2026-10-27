<?php

declare(strict_types=1);

namespace Fromholdio\ConfiguredMultisites\Job;

use Fromholdio\ConfiguredMultisites\Multisites;
use Override;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * move assets to a folder with the name of the current Multisite->Host
 *
 * @package default
 * @author
 * */
class MultisitesInitAssetsTask extends BuildTask
{
    #[Override]
    protected static string $commandName = 'MultisitesInitAssetsTask';

    protected $enabled      = true;

    #[Override]
    protected static string $description  = 'move assets to a folder with the name of the current multisite';

    /**
     * Implement this method in the task subclass to
     * execute via the TaskRunner
     */
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $currentSite = Multisites::inst()->getCurrentSite();
        if (Multisites::inst()->assetsSubfolderPerSite() && $folder = $currentSite->Folder()) {
            $files = File::get()->filter(['ParentID' => [0]])->exclude(['ID' => $folder->ID]);
            if (!$files->count()) {
                return Command::SUCCESS;
            }

            foreach ($files as $file) {
                if (file_exists($file->getFullPath())) {
                    $file->ParentID = $folder->ID;
                    $file->write();
                    echo $file->Filename . ' moved <br />';
                }
            }
        }

        return Command::SUCCESS;
    }
}
