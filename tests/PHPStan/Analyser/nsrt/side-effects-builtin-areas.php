<?php // lint >= 8.0

declare(strict_types = 1);

namespace SideEffectsBuiltinAreas;

use Socket;
use function PHPStan\Testing\assertType;

class Foo
{

	public function sleepAndOutputKeepGlobalState(): void
	{
		if (ini_get('precision') === '14') {
			sleep(1);
			flush();
			assertType("'14'", ini_get('precision'));
		}
	}

	public function seedingWritesGlobalState(): void
	{
		if (ini_get('precision') === '14') {
			mt_srand(1);
			assertType('numeric-string', ini_get('precision'));
		}
		if (error_reporting() === 0) {
			error_reporting(E_ALL);
			assertType('int', error_reporting());
		}
	}

	public function randomResultsAreNotRemembered(): void
	{
		if (str_shuffle('ab') === 'ab') {
			assertType('lowercase-string&non-falsy-string', str_shuffle('ab'));
		}
	}

	/** @param non-empty-list<string> $list */
	public function randomKeysAreNotRemembered(array $list): void
	{
		if (array_rand($list) === 0) {
			assertType('int<0, max>', array_rand($list));
		}
	}

	public function globalReadsAreRemembered(): void
	{
		if (session_status() === PHP_SESSION_NONE) {
			assertType('1', session_status());
		}
	}

	/**
	 * @param resource $handle
	 */
	public function fileWritesKeepFilesystemReads(string $file, $handle): void
	{
		if (is_file($file)) {
			fwrite($handle, 'x');
			assertType('true', is_file($file));
		}
		if (is_file($file)) {
			unlink($file);
			assertType('bool', is_file($file));
		}
	}

	public function networkAndProcessResultsAreNotRemembered(Socket $socket): void
	{
		if (socket_read($socket, 1) === 'a') {
			assertType('string|false', socket_read($socket, 1));
		}
		if (shell_exec('ls') === 'a') {
			assertType('string|false|null', shell_exec('ls'));
		}
	}

}
