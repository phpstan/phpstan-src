<?php

namespace NeverReturnStubFiles;

use function PHPStan\Testing\assertType;

class Redirector {
	public function gotoUrlAndExit($url)
	{
		exit();
	}
}


class foo {
	/**
	 * @return no-return
	 */
	public function gotoUrlAndExit($url)
	{
		$redirect = new Redirector();
		assertType('*NEVER*', $redirect->gotoUrlAndExit($url));
	}

	public function ifXRedirect() {
		if (rand(0,1)) {
			assertType('*NEVER*', $this->gotoUrlAndExit());
		}
	}
}
