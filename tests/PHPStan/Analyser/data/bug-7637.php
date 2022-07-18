<?php declare(strict_types = 1);

namespace Bug7637;

use InvalidArgumentException;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * Returns a property.
	 *
	 * @param string $key     Key of the property
	 * @param mixed  $default Default value, will be returned if the property isn't set
	 *
	 * @throws InvalidArgumentException on invalid parameters
	 *
	 * @return mixed The value for $key or $default if $key cannot be found
	 * @psalm-return (
	 *     $key is 'login' ? rex_backend_login|null :
	 *     ($key is 'debug' ? array{enabled: bool, throw_always_exception: bool|int} :
	 *     ($key is 'lang_fallback' ? string[] :
	 *     ($key is 'use_accesskeys' ? bool :
	 *     ($key is 'accesskeys' ? array<string, string> :
	 *     ($key is 'editor' ? string|null :
	 *     ($key is 'editor_basepath' ? string|null :
	 *     ($key is 'timer' ? rex_timer :
	 *     ($key is 'timezone' ? string :
	 *     ($key is 'table_prefix' ? non-empty-string :
	 *     ($key is 'temp_prefix' ? non-empty-string :
	 *     ($key is 'version' ? string :
	 *     ($key is 'server' ? string :
	 *     ($key is 'servername' ? string :
	 *     ($key is 'error_email' ? string :
	 *     ($key is 'lang' ? non-empty-string :
	 *     ($key is 'instname' ? non-empty-string :
	 *     ($key is 'theme' ? non-empty-string :
	 *     ($key is 'start_page' ? non-empty-string :
	 *     ($key is 'socket_proxy' ? non-empty-string|null :
	 *     ($key is 'password_policy' ? array<string, scalar> :
	 *     ($key is 'backend_login_policy' ? array<string, bool|int> :
	 *     ($key is 'db' ? array<int, string[]> :
	 *     ($key is 'setup' ? bool|array<string, int> :
	 *     ($key is 'system_addons' ? non-empty-string[] :
	 *     ($key is 'setup_addons' ? non-empty-string[] :
	 *     mixed|null
	 *     )))))))))))))))))))))))))
	 * )
	 */
	public static function getProperty($key, $default = null)
	{
		/** @psalm-suppress TypeDoesNotContainType **/
		if (!is_string($key)) {
			throw new InvalidArgumentException('Expecting $key to be string, but ' . gettype($key) . ' given!');
		}
		/** @psalm-suppress MixedReturnStatement **/
		if (isset(self::$properties[$key])) {
			return self::$properties[$key];
		}
		/** @psalm-suppress MixedReturnStatement **/
		return $default;
	}
}

function () {
	assertType('array<int, array<string>>', HelloWorld::getProperty('db'));
};
