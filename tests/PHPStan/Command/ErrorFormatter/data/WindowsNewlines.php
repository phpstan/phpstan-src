<?php

namespace BaselineIntegration;

class WindowsNewlines {

	/**
	 * The following phpdoc is invalid and should trigger a error message containing newlines.
	 *
	 * @param
	 *            $object
	 */
	public function phpdocWithNewlines($object) {
	}

	/**
	 *
	 * @return
	 *
	 */
	public function myfunc()
	{
	}
}
