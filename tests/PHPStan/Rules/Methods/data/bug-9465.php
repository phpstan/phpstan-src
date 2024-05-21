<?php declare(strict_types=1);

namespace Bug9465;

interface ISingleton
{
	public static function singleton(): ?static;
}

class Component
{
	public function getStuff(): mixed
	{
		return [1, 2, 3];
	}

	/**
	 * @param mixed[] $args
	 */
	public static function __callStatic(string $method, array $args): mixed
	{
		if (is_a(static::class, ISingleton::class, true) && ($singleton = static::singleton())) {
			//Call to an undefined static method static(Component)::singleton().

			$singleton->getStuff();
		}
		return null;
	}
}

/**
 * @method static void unavailableStatic()
 */
class Application extends Component implements ISingleton
{
	protected static Application $_app;

	public function __construct()
	{
		static::$_app = $this;
	}

	public static function singleton(): ?static
	{
		return static::$_app; //<- Method Application::singleton() should return static(Application)|null but returns Application.
	}
}

Application::unavailableStatic();
