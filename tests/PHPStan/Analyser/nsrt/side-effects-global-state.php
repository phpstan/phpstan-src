<?php declare(strict_types = 1);

namespace SideEffectsGlobalState;

use ReflectionClass;
use function PHPStan\Testing\assertType;

class Foo
{

	public function writesGlobal(): void
	{
		if (!class_exists('SideEffectsGlobalState\Missing')) {
			spl_autoload_register(static function (string $class): void {
			});
			assertType('bool', class_exists('SideEffectsGlobalState\Missing'));
		}
		if (ini_get('precision') === '14') {
			ini_set('precision', '10');
			assertType('numeric-string', ini_get('precision'));
		}
		if (date_default_timezone_get() === 'UTC') {
			date_default_timezone_set('Europe/Prague');
			assertType('string', date_default_timezone_get());
		}
	}

	public function effectOtherThanWritesGlobal(): void
	{
		if (!class_exists('SideEffectsGlobalState\Missing')) {
			printf('a');
			assertType('false', class_exists('SideEffectsGlobalState\Missing'));
		}
		if (ini_get('precision') === '14') {
			printf('a');
			assertType('\'14\'', ini_get('precision'));
		}
		if (date_default_timezone_get() === 'UTC') {
			printf('a');
			assertType('\'UTC\'', date_default_timezone_get());
		}
	}

	/** @param ReflectionClass<self> $class */
	public function staticAndMethod(ReflectionClass $class): void
	{
		if (isset($_GET['x'])) {
			\Locale::setDefault('cs');
			assertType('mixed', $_GET['x']);
		}
		if (isset($_GET['y'])) {
			$class->setStaticPropertyValue('foo', 1);
			assertType('mixed', $_GET['y']);
		}
		if (isset($_GET['z'])) {
			\Locale::getDefault();
			$class->getName();
			assertType('mixed~null', $_GET['z']);
		}
	}

}
