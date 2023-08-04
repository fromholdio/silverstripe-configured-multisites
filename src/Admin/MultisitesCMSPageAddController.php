<?php
namespace Symbiote\Multisites\Admin;

use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\ORM\FieldType\DBField;
use Symbiote\Multisites\Multisites;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\CMS\Controllers\CMSPageAddController;


/**
 * An extension to the default page add interface which doesn't allow pages to
 * be created on the root.
 *
 * Can't do a direct extension because that doesn't allow us to control the form object itself.
 *
 * @package silverstripe-multisites
 */
class MultisitesCMSPageAddController extends CMSPageAddController {

    private static $menu_title = 'Add page';


	private static $allowed_actions = array(
		'AddForm'
	);

	private static $url_priority = 43;

	public function AddForm()
    {
		$form   = parent::AddForm();
		$fields = $form->Fields();

        $numericLabelTmpl = '<span class="step-label"><span class="flyout">Step %d. </span><span class="title">%s</span></span>';

        $parentWrapper = CompositeField::create();
        $parentWrapper->setName('ParentWrapper');
        $parentWrapper->setTitle(
            DBField::create_field(
                'HTMLFragment',
                sprintf($numericLabelTmpl,
                    1,
                    _t(
                        'SilverStripe\\CMS\\Controllers\\CMSMain.ChoosePageParentMode',
                        'Choose where to create this page')
                )
            )
        );

        $parentModeField = OptionsetField::create('ParentModeField', null, ['child' => 'child'], 'child');
        $parentWrapper->push($parentModeField);

        $hideParentModeField = LiteralField::create('HideParentModeField',
            '<style>#Form_AddForm_ParentModeField_Holder {display: none !important;}</style>'
        );
        $parentWrapper->push($hideParentModeField);

        $parentField = HiddenField::create('Parent', null, true);
        $parentWrapper->push($parentField);

        /** @var TreeDropdownField $parentField */
        $parentField = $fields->dataFieldByName('ParentID');
        $parentField->setHasEmptyDefault(false);
        $parentField->setShowSearch(true);
        $parentField->setAttribute('style', 'padding-top:.5385rem;');

        $parentID = $this->getRequest()->getVar('ParentID');
        $parentID = $parentID ? $parentID : Multisites::inst()->getCurrentSiteId();
        $parentField->setValue($parentID);
        $parentWrapper->push($parentField);

        $fields->replaceField('ParentModeField', $parentWrapper);
        $parentWrapper->setForm($form);

        $form->setValidator(RequiredFields::create('ParentID'));
        return $form;
	}

}
