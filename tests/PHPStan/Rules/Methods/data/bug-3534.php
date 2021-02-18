<?php

namespace Bug3534;

class MyClassWithErrorInPHPDocs
{

	/**
	 * The PHP docs return type below is wrong
	 * @return void
	 */
	public function terminate(): bool
	{
		return true;
	}

	public function foo(){
		if ($this->terminate()) {
			echo "C'est fini";
		}
	}
}
