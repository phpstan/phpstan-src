@ -2,7 +2,7 @@

namespace Bug12246;

final class SkipFirstClassCallableInDo
final class FirstClassCallableInDo
{
	public function getSubscribedEvents(): void
	{
@ -16,3 +16,44 @@ final class SkipFirstClassCallableInDo
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
