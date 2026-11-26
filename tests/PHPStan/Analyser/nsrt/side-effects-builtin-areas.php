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

	public function seedingWritesOnlyTheRandomState(): void
	{
		if (ini_get('precision') === '14') {
			mt_srand(1);
			assertType("'14'", ini_get('precision'));
		}
		if (error_reporting() === 0) {
			error_reporting(E_ALL);
			assertType('int', error_reporting());
		}
	}

	public function randomIntKeepsGlobalState(string $path): bool
	{
		if (!is_file($path)) {
			return false;
		}

		if (ini_get('x') === false) {
			return false;
		}

		random_int(1, 10);

		assertType('true', is_file($path));
		assertType('string', ini_get('x'));

		return true;
	}

	public function randomBytesKeepsGlobalState(string $path): bool
	{
		if (!is_file($path)) {
			return false;
		}

		if (ini_get('x') === false) {
			return false;
		}

		random_bytes(8);

		assertType('true', is_file($path));
		assertType('string', ini_get('x'));

		return true;
	}

	/**
	 * they write only the state of the seeded random number generator
	 *
	 * @param list<int> $a
	 * @param non-empty-list<string> $list
	 */
	public function seededRandomKeepsOtherGlobalState(string $case, string $file, array $a, array $list): void
	{
		if ($case === 'mt_rand') {
			if (ini_get('x') === false) {
				return;
			}
			if (!is_file($file)) {
				return;
			}
			if (!isset($_GET['q']) || !is_string($_GET['q'])) {
				return;
			}

			mt_rand();

			assertType('string', ini_get('x'));
			assertType('true', is_file($file));
			assertType('string', $_GET['q']);
		}

		if ($case === 'rand') {
			if (ini_get('x') === false) {
				return;
			}
			if (!is_file($file)) {
				return;
			}
			if (!isset($_GET['q']) || !is_string($_GET['q'])) {
				return;
			}

			rand();

			assertType('string', ini_get('x'));
			assertType('true', is_file($file));
			assertType('string', $_GET['q']);
		}

		if ($case === 'mt_srand') {
			if (ini_get('x') === false) {
				return;
			}
			if (!is_file($file)) {
				return;
			}
			if (!isset($_GET['q']) || !is_string($_GET['q'])) {
				return;
			}

			mt_srand(1);

			assertType('string', ini_get('x'));
			assertType('true', is_file($file));
			assertType('string', $_GET['q']);
		}

		if ($case === 'srand') {
			if (ini_get('x') === false) {
				return;
			}
			if (!is_file($file)) {
				return;
			}
			if (!isset($_GET['q']) || !is_string($_GET['q'])) {
				return;
			}

			srand(1);

			assertType('string', ini_get('x'));
			assertType('true', is_file($file));
			assertType('string', $_GET['q']);
		}

		if ($case === 'shuffle') {
			if (ini_get('x') === false) {
				return;
			}
			if (!is_file($file)) {
				return;
			}
			if (!isset($_GET['q']) || !is_string($_GET['q'])) {
				return;
			}

			shuffle($a);

			assertType('string', ini_get('x'));
			assertType('true', is_file($file));
			assertType('string', $_GET['q']);
		}

		if ($case === 'str_shuffle') {
			if (ini_get('x') === false) {
				return;
			}
			if (!is_file($file)) {
				return;
			}
			if (!isset($_GET['q']) || !is_string($_GET['q'])) {
				return;
			}

			str_shuffle('ab');

			assertType('string', ini_get('x'));
			assertType('true', is_file($file));
			assertType('string', $_GET['q']);
		}

		if ($case === 'array_rand') {
			if (ini_get('x') === false) {
				return;
			}
			if (!is_file($file)) {
				return;
			}
			if (!isset($_GET['q']) || !is_string($_GET['q'])) {
				return;
			}

			array_rand($list);

			assertType('string', ini_get('x'));
			assertType('true', is_file($file));
			assertType('string', $_GET['q']);
		}

		if ($case === 'lcg_value') {
			if (ini_get('x') === false) {
				return;
			}
			if (!is_file($file)) {
				return;
			}
			if (!isset($_GET['q']) || !is_string($_GET['q'])) {
				return;
			}

			lcg_value();

			assertType('string', ini_get('x'));
			assertType('true', is_file($file));
			assertType('string', $_GET['q']);
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

	/** they can run a user session handler, but also write global state */
	public function sessionLifecycleWritesGlobalState(): void
	{
		if (session_status() === PHP_SESSION_NONE) {
			assertType('1', session_status());
			session_start();
			assertType('0|1|2', session_status());
		}
		if (session_id() === '') {
			session_start();
			assertType('string|false', session_id());
		}
		if (session_status() === PHP_SESSION_ACTIVE) {
			assertType('2', session_status());
			session_write_close();
			assertType('0|1|2', session_status());
		}
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_commit();
			assertType('0|1|2', session_status());
		}
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_abort();
			assertType('0|1|2', session_status());
		}
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_destroy();
			assertType('0|1|2', session_status());
		}
		if (session_id() === 'a') {
			session_destroy();
			assertType('string|false', session_id());
		}
		if (session_id() === 'a') {
			session_regenerate_id();
			assertType('string|false', session_id());
		}
	}

	/** they write only the session state */
	public function sessionLifecycleKeepsOtherGlobalState(string $file): void
	{
		if (!isset($_POST['token']) || !is_string($_POST['token'])) {
			return;
		}
		if (ini_get('x') === false) {
			return;
		}
		if (!is_file($file)) {
			return;
		}

		session_start();
		assertType('string', $_POST['token']);
		assertType('string', ini_get('x'));
		assertType('true', is_file($file));
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
