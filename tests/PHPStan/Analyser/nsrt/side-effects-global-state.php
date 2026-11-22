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

	public function getterOfGlobalState(string $pattern): void
	{
		// read again until a call writes the state
		if (session_id()) {
			assertType('non-falsy-string', session_id());
		}
		if (session_id() === 'a') {
			session_id('b');
			assertType('string|false', session_id());
		}

		// written by every preg_*() call
		if (preg_last_error() === PREG_NO_ERROR) {
			preg_match($pattern, '');
			assertType('int', preg_last_error());
		}
	}

	/** a call that writes a named part of global state forgets only the calls that read it */
	public function namedGlobalState(string $file, string $s): void
	{
		if (is_file($file) && json_last_error() === JSON_ERROR_NONE && preg_last_error() === PREG_NO_ERROR && openssl_error_string() === false) {
			clearstatcache();
			assertType('bool', is_file($file));
			assertType('0', json_last_error());
			assertType('0', preg_last_error());
			assertType('false', openssl_error_string());
		}
		if (is_file($file) && json_last_error() === JSON_ERROR_NONE && preg_last_error() === PREG_NO_ERROR && openssl_error_string() === false) {
			preg_match('/a/', $s);
			assertType('true', is_file($file));
			assertType('0', json_last_error());
			assertType('int', preg_last_error());
			assertType('false', openssl_error_string());
		}
		if (is_file($file) && json_last_error() === JSON_ERROR_NONE && preg_last_error() === PREG_NO_ERROR && openssl_error_string() === false) {
			json_decode($s);
			assertType('true', is_file($file));
			assertType('0|1|2|3|4|5|6|7|8|9|10', json_last_error());
			assertType('0', preg_last_error());
			assertType('false', openssl_error_string());
		}
		if (is_file($file) && json_last_error() === JSON_ERROR_NONE && preg_last_error() === PREG_NO_ERROR && openssl_error_string() === false) {
			openssl_cipher_iv_length($s);
			assertType('true', is_file($file));
			assertType('0', json_last_error());
			assertType('0', preg_last_error());
			assertType('string|false', openssl_error_string());
		}
		if (is_file($file) && json_last_error() === JSON_ERROR_NONE && preg_last_error() === PREG_NO_ERROR && openssl_error_string() === false) {
			ini_set('precision', '10');
			assertType('bool', is_file($file));
			assertType('0|1|2|3|4|5|6|7|8|9|10', json_last_error());
			assertType('int', preg_last_error());
			assertType('string|false', openssl_error_string());
		}
	}

	public function mbstringState(string $s): void
	{
		if (mb_strlen($s) === 3) {
			clearstatcache();
			assertType('3', mb_strlen($s));
		}
		if (mb_strlen($s) === 3) {
			mb_internal_encoding('ISO-8859-1');
			assertType('int<1, max>', mb_strlen($s));
		}
		if (mb_ereg_search_getpos() === 0) {
			clearstatcache();
			assertType('0', mb_ereg_search_getpos());
		}
		if (mb_ereg_search_getpos() === 0) {
			mb_ereg_search();
			assertType('int', mb_ereg_search_getpos());
		}
	}

}
