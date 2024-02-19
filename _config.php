<?php

use Fromholdio\ConfiguredMultisites\Admin\MultisitesCMSPageAddController;
use SilverStripe\Admin\CMSMenu;

CMSMenu::remove_menu_class(MultisitesCMSPageAddController::class);
