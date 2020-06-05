<?php

declare(strict_types = 1);

namespace Modules\ZbxWallboard;

require_once("partials/CScreenWallboard.php");

use APP;
use CController as CAction;

class Module extends \Core\CModule {
	public function init(): void {
		APP::Component()->get('menu.main')
			->findOrAdd(_('Monitoring'))
				->getSubmenu()
					->add((new \CMenuItem(_('Wallboard')))
						->setAction('zbxwallboard.view')
					);
	}
}
?>