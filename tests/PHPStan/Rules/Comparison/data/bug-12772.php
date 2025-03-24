<?php declare(strict_types = 1);

namespace Bug12772;

class HelloWorld
{
	public function sayHello(DateTimeImutable $date): void
	{
		foreach ($tables as $table) {

			// If view exists, and 'add drop view' is selected: Drop it!
			if ($_POST['what'] !== 'nocopy' && isset($_POST['drop_if_exists']) && $_POST['drop_if_exists'] === 'true') {

			}
		}
	}
}
