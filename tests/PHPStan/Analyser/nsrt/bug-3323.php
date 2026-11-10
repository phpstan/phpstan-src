<?php

namespace Bug3323;

use function PHPStan\Testing\assertType;
use ArrayAccess;

/**
 * @implements ArrayAccess<string, self>
 */
class FormView implements \ArrayAccess
{
	public array $vars = [];

	public function offsetExists($offset) {
		return array_key_exists($offset, $this->vars);
	}
	public function offsetGet($offset) {
		return $this->vars[$offset] ?? null;
	}
	public function offsetSet($offset, $value) {
		$this->vars[$offset] = $value;
	}
	public function offsetUnset($offset) {
		unset($this->vars[$offset]);
	}
}

function doFoo() {
	$formView = new FormView();
	assertType('Bug3323\FormView', $formView);
	if ($formView->offsetExists('_token')) {
		assertType("Bug3323\FormView&hasOffsetValue('_token', Bug3323\FormView)", $formView);

		$a = $formView->offsetGet('_token');
		assertType("Bug3323\FormView", $a);

		$a = $formView->offsetGet(123);
		assertType("Bug3323\FormView|null", $a);
	} else {
		assertType('Bug3323\FormView', $formView);

		$a = $formView->offsetGet('_token');
		assertType("Bug3323\FormView|null", $a); // could be "null" only
	}
	assertType('Bug3323\FormView', $formView);

	$a = $formView->offsetGet('_token');
	assertType("Bug3323\FormView|null", $a);

	$a = $formView->offsetGet(123);
	assertType("Bug3323\FormView|null", $a);
}

