<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug12246;

final class FirstClassCallableInDo
{
	public function getSubscribedEvents(): void
	{
		do {

		} while ($this->textElement(...));
	}

	public function textElement(): int
	{
		return 1;
	}
}

final class StaticFirstClassCallableInDo
{
	public function getSubscribedEvents(): void
	{
		do {

		} while (self::textElement(...));
	}

	static public function textElement(): int
	{
		return 1;
	}
}

final class FunctionFirstClassCallableInDo
{
	public function getSubscribedEvents(): void
	{
		do {

		} while (doFoo(...));
	}
}

function doFoo():int {
	return 1;
}

final class InstationFirstClassCallableInDo
{
	public function getSubscribedEvents(): void
	{
		do {

		} while (new Foo(...));
	}
}

class Foo {}
