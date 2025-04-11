<?php
namespace Fromholdio\ConfiguredMultisites\Admin;

use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;
use SilverStripe\View\ThemeResourceLoader;
use Fromholdio\ConfiguredMultisites\Multisites;
use Fromholdio\ConfiguredMultisites\Model\Site;

use SilverStripe\Core\Config\Config;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\Form;
use SilverStripe\CMS\Controllers\SilverStripeNavigator;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Admin\LeftAndMainExtension;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
/**
 * @package silverstripe-multisites
 */
class MultisitesCMSMainExtension extends LeftAndMainExtension
{
    private static $is_add_site_enabled = false;

	private static $allowed_actions = array(
		'AddSiteForm'
	);

	public function getCMSTreeTitle() {
		return _t('Multisites.SITES', 'Sites');
	}

	/**
	 * @var Path to editor.css for themes.
	 *
	 * Key is the theme dir; value is the directory path beneath the theme dir if not "css" (e.g. "public/css")
	 */
	private static $multisites_editor_css_dir = array();

    public function isAddSiteEnabled(): bool
    {
        return $this->getOwner()->config()->get('is_add_site_enabled');
    }

	/**
	* init (called from LeftAndMain extension hook)
	**/
    public function init()
    {
        $htmlEditorConfig = HtmlEditorConfig::get_active();
        $site = Multisites::inst()->getActiveSite();
        if ($site)
        {
            $themes = $site->getSiteThemes();
            $editorCSSDirs = Config::inst()->get(CMSMain::class, 'multisites_editor_css_dir');
            foreach ($themes as $theme)
            {
                $cssFilePath = 'css/editor.css';
                if (isset($editorCSSDirs[$theme])) {
                    $cssFilePath = $editorCSSDirs[$theme] . '/editor.css';
                }

                $cssURL = ModuleResourceLoader::resourceURL(
                    ThemeResourceLoader::inst()->findThemedResource($cssFilePath, [$theme])
                );

                if ($cssURL)
                {
                    $htmlEditorConfig->setOption('content_css', $cssURL);
                    $contentCSS = $htmlEditorConfig->getContentCSS();
                    if (is_string($contentCSS)) {
                        $contentCSS = [$contentCSS];
                    }
                    else if (is_null($contentCSS)) {
                        $contentCSS = [];
                    }
                    $contentCSS[] = $cssURL;
                    $htmlEditorConfig = $htmlEditorConfig->setContentCSS($contentCSS);

                    if($this->owner->getRequest()->isAjax() && $this->owner instanceof CMSPageEditController){
                        // Add editor css path to header so javascript can update ssTinyMceConfig.content_css
                        $this->owner->getResponse()->addHeader('X-HTMLEditor_content_css', $cssURL);
                    }
                    break;
                }
            }
        }
    }


	/**
	 * AddSiteForm
	 * @return Form
	 **/
	public function AddSiteForm() {
        if (!$this->getOwner()->isAddSiteEnabled()) {
            return null;
        }
		return new Form(
			$this->owner,
			'AddSiteForm',
			new FieldList(),
			new FieldList(
				FormAction::create('doAddSite', _t('Multisites.ADDSITE', 'Add Site'))
					->addExtraClass('tool-button font-icon-plus ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only')
			        ->setUseButtonTag(true)
			)
		);
	}


	/**
	 * AddSiteForm action to add a new site
	 **/
	public function doAddSite() {
        if (!$this->getOwner()->isAddSiteEnabled()) {
            return $this->owner->redirectBack();
        }
		$site = $this->owner->getNewItem('new-' . Site::class .'-0', false);
		$site->write();

		return $this->owner->redirect(
			singleton(CMSPageEditController::class)->Link("show/$site->ID")
		);
	}

	/**
	 * If viewing 'Site', disable preview panel.
	 */
	public function updateEditForm($form) {
        $classNameField = $form->Fields()->dataFieldByName('ClassName');
        if ($classNameField) {
            $className = $classNameField->Value();
            if ($className === Site::class)
            {
            	$form->Fields()->removeByName(['SilverStripeNavigator']);
                $form->removeExtraClass('cms-previewable');
            }
        }
    }

	/**
	 * Adds a dropdown field to the search form to filter searches by Site
	 **/
	public function updateSearchForm(Form $form) {
		$cms = $this->owner;
		$req = $cms->getRequest();

		$sites = Site::get()->sort(array(
			'IsDefault' => 'DESC',
			'Title'     => 'ASC'
		));

		$site = new DropdownField(
			'q[SiteID]',
			_t('Multisites.SITE', 'Site'),
			$sites->map(),
			isset($req['q']['SiteID']) ? $req['q']['SiteID'] : null
		);
		$site->setEmptyString(_t('Multisites.ALLSITES', 'All sites'));

		$form->Fields()->insertAfter('q[Term]', $site);
	}


	/**
	 * Makes the default page id the first child of the current site
	 * This makes the site tree view load with the current site open instead of just the first one
	 **/
	public function updateCurrentPageID(&$id){
		if (!$id) {
			if($site = Multisites::inst()->getCurrentSite()){
				$id = $site->Children()->first()->ID;
			}
		}
	}

}
