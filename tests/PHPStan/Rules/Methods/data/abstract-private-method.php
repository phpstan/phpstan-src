<?php declare(strict_types = 1);

namespace AbstractMethods;

abstract class HelloWorld
{
	public function doFoo(): void  {
		$this->sayHello();
		$this->sayWorld();
	}

	abstract private function sayPrivate() : void;
	abstract protected function sayProtected() : void;
	abstract public function sayPublic() : void;
}

trait fooTrait{
	abstract private function sayPrivate() : void;
	abstract protected function sayProtected() : void;
	abstract public function sayPublic() : void;
}

interface fooInterface {
	abstract private function sayPrivate() : void;
	abstract protected function sayProtected() : void;
	abstract public function sayPublic() : void;
}
