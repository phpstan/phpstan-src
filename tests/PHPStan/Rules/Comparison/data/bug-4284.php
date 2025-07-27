<?php declare(strict_types = 1);

namespace Bug4284;

class HelloWorld
{
	/** @param mixed[] $browser */
	public function sayHello(array $browser): void
	{
		$browserName = '';
		$upgradeBrowserLink = '';

		if ($browser['name'] === 1) {
			$browserName = '123';
			$upgradeBrowserLink = '456';
		} elseif ($browser['name'] === 2) {
			$browserName = '789';
			$upgradeBrowserLink = '123';
		}

		if ($browserName && $upgradeBrowserLink) {
			//
		}
		if ($browserName) {
			if ($upgradeBrowserLink) {
				//
			}
		}
	}
}
