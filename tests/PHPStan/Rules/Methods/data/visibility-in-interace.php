<?php

namespace VisibilityInInterface;

interface FooInterface
{
	private function sayPrivate() : void;
	protected function sayProtected() : void;
	public function sayPublic() : void;

	function noVisibility() : void;
}

trait FooTrait
{
	private function sayPrivate() : void {}
	protected function sayProtected() : void {}
	public function sayPublic() : void {}

	function noVisibility() : void {}
}

abstract class AbstractFoo
{
	abstract private function sayPrivate() : void;
	abstract protected function sayProtected() : void;
	abstract public function sayPublic() : void;

	abstract function noVisibility() : void;
}
