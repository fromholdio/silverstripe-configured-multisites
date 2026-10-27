<?php

namespace Fromholdio\ConfiguredMultisites\Forms;

use Fromholdio\ConfiguredMultisites\Multisites;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Forms\CMSMainAddForm;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\ORM\FieldType\DBField;

class MultisitesCMSMainAddForm extends CMSMainAddForm
{
    public function __construct(CMSMain $controller)
    {
        parent::__construct($controller);
        $this->setValidator(RequiredFieldsValidator::create('ParentID'));
    }

    protected function createFields(): FieldList
    {
        $fields = parent::createFields();

        $numericLabelTmpl = '<span class="step-label"><span class="flyout">Step %d. </span><span class="title">%s</span></span>';

        $parentWrapper = CompositeField::create();
        $parentWrapper->setName('ParentWrapper');
        $parentWrapper->setTitle(
            DBField::create_field(
                'HTMLFragment',
                sprintf(
                    $numericLabelTmpl,
                    1,
                    _t(
                        'SilverStripe\\CMS\\Controllers\\CMSMain.ChoosePageParentMode',
                        'Choose where to create this page'
                    )
                )
            )
        );

        $parentModeField = OptionsetField::create('ParentModeField', null, ['child' => 'child'], 'child');
        $parentWrapper->push($parentModeField);

        $hideParentModeField = LiteralField::create(
            'HideParentModeField',
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
        $parentID = $parentID ?: Multisites::inst()->getCurrentSiteId();

        $parentField->setValue($parentID);
        $parentWrapper->push($parentField);

        $fields->replaceField('ParentModeField', $parentWrapper);
        $parentWrapper->setForm($this);

        return $fields;
    }
}
