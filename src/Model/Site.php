<?php

namespace Symbiote\Multisites\Model;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;
use Symbiote\Multisites\Control\MultisitesRootController;
use Symbiote\Multisites\Multisites;
use Symbiote\MultiValueField\Fields\MultiValueTextField;
use Page;
use SilverStripe\Assets\Folder;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\Security\Permission;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\View\Parsers\URLSegmentFilter;
use SilverStripe\ORM\DB;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\SSViewer;
use SilverStripe\ORM\HiddenClass;
use SilverStripe\Security\PermissionProvider;

/**
 * @package silverstripe-multisites
 */
class Site extends Page implements HiddenClass, PermissionProvider {

    private static $table_name = 'Site';

	private static $singular_name = 'Site';
	private static $plural_name = 'Sites';
	private static $description = 'A page type which provides a subsite.';

	private static $db = array(
		'Tagline'     => 'Varchar(255)',
		'Theme'       => 'Varchar(255)',
		'Scheme'      => 'Enum("any, http, https", "any")',
		'Host'        => 'Varchar(100)',
		'HostAliases' => 'MultiValueField',
		'IsDefault'   => 'Boolean',
		'DevID'       => 'Varchar', // developer identifier
        'RobotsTxt'   => 'Text'
	);

	private static $has_one = array(
		'Folder' => Folder::class
	);

	private static $defaults = array(
		'Scheme' => 'any',
        'RobotsTxt' => ''
	);

	private static $default_sort = '"Title"';

	private static $searchable_fields = array(
		'Title'     => 'Title',
		'Domain'    => 'Domain',
		'IsDefault' => 'Is Default'
	);

	private static $summary_fields = array(
		'Title'     => 'Title',
		'Url'       => 'URL',
		'IsDefault' => 'Is Default'
	);

    private static $available_themes = [];

	private static $icon = 'symbiote/silverstripe-multisites: client/images/world.png';

    public function getFilesUploadPath(): string
    {
        return rtrim($this->getComponent('Folder')->getFilename(), '/');
    }

	public function getCMSFields(): FieldList
    {
        if (!$this->canEdit() && Controller::curr() instanceof LeftAndMain) {
            if ($this->hasMethod('getNoEditPermissionCMSFields')) {
                return $this->getNoEditPermissionCMSFields();
            }
        }

        if ($this->hasMethod('runCMSFieldsScaffolderBeforeUpdate')) {
            $this->beforeUpdateSiteCMSFields(function (FieldList $fields) {
                return $this->runCMSFieldsScaffolderBeforeUpdate($fields);
            });
        }

        if ($this->hasMethod('runCMSFieldsScaffolderAfterUpdate')) {
            $this->afterUpdateSiteCMSFields(function (FieldList $fields) {
                if ($this->hasMethod('betweenUpdateSiteCMSFields')) {
                    $fields = $this->betweenUpdateSiteCMSFields($fields);
                }
                $areaField = $fields->fieldByName('Root.Main.ElementalArea');
                if ($areaField) {
                    $fields->removeByName('ElementalArea');
                    $fields->addFieldToTab('Root.ContentTabSet.ContentMainTab', $areaField);
                }
                return $this->runCMSFieldsScaffolderAfterUpdate($fields);
            });
        }

        $fields = FieldList::create(
            TabSet::create(
                'Root',
                $mainTab = Tab::create(
                    'Main',
                    $titleField = TextField::create('Title', _t('Multisites.SITENAME', 'Site name')),
                    $hostField = TextField::create('Host', _t('Multisites.DOMAIN', 'Domain')),
                )
            )
        );

        $titleField->setReadonly(true);
        $hostField->setReadonly(true);

        if ($this->getField('HostAliases')->getValue()) {
            $aliasesField = MultiValueTextField::create('HostAliases',_t('Multisites.HOSTALIASES','Host Aliases'));
            $aliasesField->setReadonly(true);
            $mainTab->push($aliasesField);
        }

		if(!Permission::check('SITE_EDIT_CONFIGURATION')){
			foreach ($fields->dataFields() as $field) {
				$fields->makeFieldReadonly($field);
			}
		}

		$this->extend('updateSiteCMSFields', $fields);

        if ($this->hasMethod('removeCMSFieldsScaffolderFields')) {
            $fields = $this->removeCMSFieldsScaffolderFields($fields);
        }
		return $fields;
	}

    /**
     * Allows user code to hook into DataObject::getCMSFields prior to updateCMSFields
     * being called on extensions
     *
     * @param callable $callback The callback to execute
     */
    protected function beforeUpdateSiteCMSFields($callback)
    {
        $this->beforeExtending('updateSiteCMSFields', $callback);
    }

    /**
     * Allows user code to hook into DataObject::getCMSFields after updateCMSFields
     * being called on extensions
     *
     * @param callable $callback The callback to execute
     */
    protected function afterUpdateSiteCMSFields(callable $callback)
    {
        $this->afterExtending('updateSiteCMSFields', $callback);
    }

	public function getUrl() {
		if($this->Host){
			if(!$this->Scheme || $this->Scheme == 'any') {
				return 'http://' . $this->Host;
			} else {
				return sprintf('%s://%s', $this->Scheme, $this->Host);
			}
		}else{
			return Director::absoluteBaseURL();
		}
	}

	public function AbsoluteLink($action = null){
	    return Controller::join_links(
	        $this->getURL(),
            $action
        );
	}

	public function Link($action = null) {
		if ($this->ID && $this->ID == Multisites::inst()->getActiveSite()?->getField('ID')) {
			return parent::Link($action);
		}
		return Controller::join_links($this->RelativeLink($action));
	}

	public function RelativeLink($action = null) {
		if($this->ID && $this->ID == Multisites::inst()->getActiveSite()?->getField('ID')) {
			return $action;
		} else {
			return Controller::join_links($this->getUrl(), $action);
		}
	}

	public function onBeforeWrite(): void
    {
		$normalise = function($url) {
			return trim(str_replace(array('http://', 'https://'), '', $url), '/');
		};

		$this->Host = $normalise($this->Host);

		if(!is_array($this->HostAliases) && ($aliases = $this->HostAliases->getValue())) {
			$this->HostAliases = array_map($normalise, $aliases);
		}

		if($this->IsDefault) {
			$others = static::get()->where('"SiteTree"."ID" <> ' . $this->ID)->filter('IsDefault', true);

			foreach($others as $other) {
				$other->IsDefault = false;
				$other->write();
			}
		}

		//Set MenuTitle to NULL so that Title is used
		$this->MenuTitle = NULL;

		if($this->ID && Multisites::inst()->assetsSubfolderPerSite() && !$this->Folder()->exists()){
			$this->FolderID = $this->createAssetsSubfolder();
		}

		parent::onBeforeWrite();
	}


	/**
	 * creates a subfolder in assets/ to store this sites files
	 * @param Boolean $write - writes the site object if set to true
	 * @return Int $folder->ID
	 **/
	public function createAssetsSubfolder($write = false){
		$siteFolderName = singleton(URLSegmentFilter::class)->filter($this->Title);
		$folder = Folder::find_or_make($siteFolderName);

		if($write){
			$this->FolderID = $folder->ID;
			$this->write();
			if($this->isPublished()) $this->doPublish();
		}

		return $folder->ID;
	}

    public function onBeforeDuplicate($original, $doWrite)
    {
        throw new \LogicException('Sorry, sites cannot be duplicated');
    }

	public function onAfterWrite(): void
    {
		Multisites::inst()->build();
		parent::onAfterWrite();
	}

	/**
	 * Make sure there is a site record.
	 */
	public function requireDefaultRecords(): void
    {
		parent::requireDefaultRecords();

        if (get_class($this) !== Site::class) {
            return;
        }

        $siteConfig = SiteConfig::current_site_config();

        $siteName = Environment::getEnv('CUST_DEFAULT_SITENAME');
        if (isset($siteName)) {
            $currentSiteName = $siteConfig->Title;
            if ($currentSiteName !== $siteName) {
                $siteConfig->Title = $siteName;
                $siteConfig->write();
                DB::alteration_message(
                    'SiteConfig title updated from "' . $currentSiteName . '" '
                    . 'to "' . $siteName . '"',
                    'changed'
                );
            }
        }

        $defaultSites = Environment::getEnv('CUST_DEFAULT_SITES');
        if (!isset($defaultSites)) return;

        $defaultSites = json_decode($defaultSites, true);
        if (!is_array($defaultSites) || count($defaultSites) < 1) return;

        $devIDs = Config::inst()->get(Multisites::class, 'developer_identifiers');
        if (is_array($devIDs)){
            if (ArrayLib::is_associative($devIDs)) {
                $devIDs = array_keys($devIDs);
            }
            $devIDs = ArrayLib::valuekey($devIDs);
        }
        else {
            throw new \LogicException(
                'To use env value CUST_DEFAULT_SITES you must define the $developer_identifiers '
                . 'on ' . Multisites::class . '.'
            );
        }

        $devSettings = Config::inst()->get(Multisites::class, 'default_settings');

        foreach ($defaultSites as $devID => $hosts)
        {
            if (!isset($devIDs[$devID])) {
                throw new \UnexpectedValueException(
                    'Multisites DevID "' . $devID . '" is not defined in $developer_identifiers '
                    . 'on ' . Multisites::class . '.'
                );
            }

            $siteAliases = null;
            if (is_string($hosts)) {
                $siteHost = $hosts;
            }
            else if (is_array($hosts)) {
                $siteHost = $hosts[0];
                if (count($hosts) > 1) {
                    unset($hosts[0]);
                    $siteAliases = $hosts;
                }
            }
            else {
                throw new \UnexpectedValueException(
                    'You have an invalid host value assigned to devID "' . $devID . '" '
                    . 'in env value CUST_DEFAULT_SITES.'
                );
            }

            $settings = $devSettings[$devID] ?? [];

            $siteClass = isset($settings['class']) ? $settings['class'] : Site::class;
            if (!is_a($siteClass, Site::class, true)) {
                throw new \UnexpectedValueException(
                    'You Multisites $default_settings contains an invalid site class of '
                    . $siteClass . '. Class must be a valid subclass of ' . Site::class . '.'
                );
            }

            $siteTitle = $settings['title'] ?? 'New Site';
            $siteIsDefault = $settings['isdefault'] ?? false;
            $siteIsDefault = (bool) $siteIsDefault;
            $siteFolder = $settings['folder'] ?? $devID;

            $changes = [];

            if (isset($settings['theme'])) {
                $siteTheme = $settings['theme'];
            }
            else {
                throw new \UnexpectedValueException(
                    'Your new Site "' . $siteTitle . '" is missing a default theme config '
                    . 'value for its Dev Identifier "' . $devID . '".'
                );
            }

            $site = Site::get()->find('DevID', $devID);
            if (!$site || !$site->exists()) {
                $site = $siteClass::create();
                $site->Title = $siteTitle;
                $site->DevID = $devID;
            }
            elseif ($site->Title !== $siteTitle) {
                $site->Title = $siteTitle;
                $changes[] = 'Title updated to "' . $siteTitle . '"';
            }

            if ($site->ClassName !== $siteClass) {
                $site->ClassName = $siteClass;
                $changes[] = 'Class updated to "' . $siteClass . '"';
            }

            if ($site->Host !== $siteHost) {
                $site->Host = $siteHost;
                $changes[] = 'Host updated to "' . $siteHost . '"';
            }

            if ($site->getField('HostAliases')->getValue() !== $siteAliases) {
                $site->HostAliases = $siteAliases;
                $changes[] = 'Host Aliases updated for ' . $site->Title;
            }

            $siteFolderID = (int) Folder::find_or_make($siteFolder)->ID;
            if ((int) $site->FolderID !== $siteFolderID) {
                $site->FolderID = $siteFolderID;
                $changes[] = 'Folder updated to "' . $siteFolder . '"';
            }

            if ((bool) $site->IsDefault !== $siteIsDefault) {
                $site->IsDefault = $siteIsDefault;
                $changes[] = 'Set as Default Site';
            }

            if ($site->Theme !== $siteTheme) {
                $site->Theme = $siteTheme;
                $changes[] = 'Theme updated to "' . $siteTheme . '"';
            }

            if ($site->isInDB()) {
                if (count($changes) > 0) {
                    $site->write();
                    $site->publishSingle();
                    $site->flushCache();
                    DB::alteration_message(
                        'Site "' . $site->Title . '" updated: ' . implode(' - ', $changes),
                        'changed'
                    );
                }
            }
            else {
                $site->write();
                $site->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
                DB::alteration_message(
                    'New site "' . $siteTitle . '" created as ' . $siteClass,
                    'created'
                );
            }

            $homeClass = $settings['home']['class'] ?? \Page::class;
            $homeTitle = $settings['home']['title'] ?? 'Home';
            $homeLink = MultisitesRootController::get_homepage_link();

            if (!is_a($homeClass, SiteTree::class, true)) {
                throw new \UnexpectedValueException(
                    ' You have set a $default_settings.devID.home.class value of ' . $homeClass
                    . '. This is not a valid SiteTree subclass.'
                );
            }

            $homePage = SiteTree::get()
                ->filter([
                    'ParentID' => $site->ID,
                    'URLSegment' => $homeLink
                ])
                ->first();

            if ($homePage && $homePage->exists())
            {
                if ($homePage->ClassName !== $homeClass)
                {
                    $currentClass = $homePage->ClassName;
                    $currentTitle = $homePage->Title;
                    $homePage->ClassName = $homeClass;
                    $homePage->write();
                    $homePage->publishSingle();
                    $homePage->flushCache();
                    $message = 'Existing home page titled "' . $currentTitle . '" of class ' . $currentClass
                        .  ' converted to class ' . $homeClass . ' for site "' . $siteTitle . '" (' . $devID . ')';
                    DB::alteration_message($message,'changed');
                }
            }
            else {
                $homePage = $homeClass::create();
                $homePage->Title = $homeTitle;
                $homePage->URLSegment = $homeLink;
                $homePage->ParentID = $site->ID;
                $homePage->Sort = 1;
                $homePage->write();
                $homePage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
                $message = $homeClass . ' titled "' . $homeTitle .'" created as home page for site '
                    . '"' . $siteTitle . '" (' . $devID . ')';
                DB::alteration_message($message,'created');
            }
        }

        $otherSites = Site::get()->exclude('DevID', array_keys($devIDs));
        foreach ($otherSites as $otherSite) {
            $otherSiteTitle = $otherSite->Title;
            $otherSite->doUnpublish();
            $otherSite->doArchive();
            DB::alteration_message(
                'Archived Site not assigned a DevID from $developer_identifiers: "' . $otherSiteTitle . '"',
                'deleted'
            );
        }
	}

	/**
	 * Alternative implementation that takes into account the current site
	 * as the root
	 *
	 * @param string $link
	 * @param bool $cache
	 * @return SiteTree|null
	 */
	static public function get_by_link($link, $cache = true): ?SiteTree
    {
		$current = Multisites::inst()->getCurrentSiteId();

		if(trim($link, '/')) {
			$link = trim(Director::makeRelative($link), '/');
		} else {
			$link = RootURLController::get_homepage_link();
		}

		$parts = Convert::raw2sql(preg_split('|/+|', $link));

		// Grab the initial root level page to traverse down from.
		$URLSegment = array_shift($parts);

        /** @var SiteTree $sitetree */
		$sitetree   = DataObject::get_one (
			SiteTree::class, "\"URLSegment\" = '$URLSegment' AND \"ParentID\" = " . $current, $cache
		);

		if (!$sitetree) {
			return null;
		}

		/// Fall back on a unique URLSegment for b/c.
		if(!$sitetree && self::nested_urls()) {
            /** @var SiteTree $page */
            $page = DataObject::get(SiteTree::class, "\"URLSegment\" = '$URLSegment'")->First();
            if ($page) {
                return $page;
            }
		}

		// Check if we have any more URL parts to parse.
		if(!count($parts)) return $sitetree;

		// Traverse down the remaining URL segments and grab the relevant SiteTree objects.
		foreach($parts as $segment) {
			$next = DataObject::get_one (
				SiteTree::class, "\"URLSegment\" = '$segment' AND \"ParentID\" = $sitetree->ID", $cache
			);

			if(!$next) {
				return null;
			}

			$sitetree->destroy();
			$sitetree = $next;
		}

		return $sitetree;
	}

    public function getHomePage()
    {
        return $this->getByLink('/home');
    }

    public function getByLink($link)
    {
        $current = $this->getField('ID');
        if(trim($link, '/')) {
            $link = trim(Director::makeRelative($link), '/');
        } else {
            $link = RootURLController::get_homepage_link();
        }

        $parts = Convert::raw2sql(preg_split('|/+|', $link));

        // Grab the initial root level page to traverse down from.
        $URLSegment = array_shift($parts);

        $sitetree   = DataObject::get_one (
            SiteTree::class, "\"URLSegment\" = '$URLSegment' AND \"ParentID\" = " . $current, false
        );

        if (!$sitetree) {
            return false;
        }

        /// Fall back on a unique URLSegment for b/c.
        if(!$sitetree && self::nested_urls() && $page = DataObject::get(SiteTree::class, "\"URLSegment\" = '$URLSegment'")->First()) {
            return $page;
        }

        // Check if we have any more URL parts to parse.
        if(!count($parts)) return $sitetree;

        // Traverse down the remaining URL segments and grab the relevant SiteTree objects.
        foreach($parts as $segment) {
            $next = DataObject::get_one (
                SiteTree::class, "\"URLSegment\" = '$segment' AND \"ParentID\" = $sitetree->ID", false
            );

            if(!$next) {
                return false;
            }

            $sitetree->destroy();
            $sitetree = $next;
        }

        return $sitetree;
    }


	/**
	 * Get the name of the theme applied to this site, allow extensions to override
	 * @return String
	 **/
    public function getSiteTheme()
    {
        $theme = $this->Theme;
        if (!$theme) {
            $theme = Config::inst()->get(SSViewer::class, 'theme');
            $theme = str_replace(' ', '', $theme);
        }
        $this->extend('updateGetSiteTheme', $theme);
        return $theme;
    }

    public function getSiteThemes()
    {
        $themes = $this->getSiteDefaultSetting('themes');
        if (is_null($themes))
        {
            $themes = [SSViewer::PUBLIC_THEME];
            $theme = $this->getSiteTheme();
            if (!is_null($theme)) $themes[] = $theme;
            $themes[] = ModuleManifest::config()->get('project');
            $themes[] = SSViewer::DEFAULT_THEME;
        }
        array_walk($themes, 'trim');
        return $themes;
    }

    public function getSiteErrorThemes()
    {
        $errorSettings = $this->getSiteDefaultSetting('errors');
        $themes = $errorSettings['themes'] ?? null;
        if (empty($themes)) {
            $themes = $this->getSiteThemes();
        }
        else {
            array_walk($themes, 'trim');
        }
        return $themes;
    }

    public function getSiteDefaultSetting(string $settingKey)
    {
        $devID = $this->DevID;
        if (!$devID) return null;
        $allSettings = Config::inst()->get(Multisites::class, 'default_settings');
        if (empty($allSettings[$devID])) return null;

        $settings = $allSettings[$devID];
        if (empty($settings[$settingKey])) return null;
        return $settings[$settingKey];
    }

	/**
	 * Checks to see if this site has a feature as defined in Muiltisites.site_features config
	 * @return Boolean
	 **/
	public function hasFeature($feature){
		if(!$this->DevID) return false;

        $sites = Config::inst()->get(Multisites::class, 'site_features');

		if(!isset($sites[$this->DevID])) return false;

		$features = ArrayLib::valuekey($sites[$this->DevID]);

		if(!isset($features[$feature])) return false;

		return true;
	}


	public function providePermissions() {
		return array(
			'SITE_EDIT_CONFIGURATION' => array(
				'name' => 'Edit Site configuration settings. Eg. Site Title, Host Name etc.',
				'category' => 'Sites',
			)
		);
	}

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }

    public function canArchive($member = null)
    {
        return false;
    }

	/**
	 *	This corrects an issue when duplicating a site, since the parent comes back as a false object.
	 */

	public function Parent() {

		return null;
	}

}
