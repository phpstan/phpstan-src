<?php // lint >= 8.0

declare(strict_types = 1);

namespace SideEffectsAdvancingHandles;

use CurlHandle;
use CurlMultiHandle;
use Directory;
use IntlBreakIterator;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param resource $dir
	 * @param resource $server
	 * @param resource $bz
	 * @param resource $zip
	 * @param resource $entry
	 * @param resource $dba
	 */
	public function nextCallReadsTheNextItem($dir, $server, $bz, $zip, $entry, $dba): void
	{
		if (readdir($dir) === '.') {
			assertType('non-empty-string|false', readdir($dir));
		}
		if (stream_socket_accept($server, 0) === false) {
			assertType('resource|false', stream_socket_accept($server, 0));
		}
		if (bzread($bz, 1) === 'a') {
			assertType('string|false', bzread($bz, 1));
		}
		if (zip_read($zip) === false) {
			assertType('int|resource|false', zip_read($zip));
		}
		if (zip_entry_read($entry, 1) === 'a') {
			assertType('string|false', zip_entry_read($entry, 1));
		}
		if (dba_nextkey($dba) === 'k') {
			assertType('string|false', dba_nextkey($dba));
		}
		if (pcntl_waitid(P_ALL) === true) {
			assertType('bool', pcntl_waitid(P_ALL));
		}
		if (readline('> ') === 'y') {
			assertType('string|false', readline('> '));
		}
	}

	/** @param resource $bz */
	public function readingForgetsTheHandle($bz): void
	{
		if (bzerrno($bz) === 0) {
			bzread($bz, 1);
			assertType('int', bzerrno($bz));
		}
	}

	public function curlMulti(CurlMultiHandle $mh, CurlHandle $ch): void
	{
		if (curl_multi_info_read($mh) === false) {
			assertType('array|false', curl_multi_info_read($mh));
		}
		if (curl_multi_getcontent($ch) === '') {
			curl_multi_exec($mh, $running);
			assertType('string|null', curl_multi_getcontent($ch));
		}
	}

	public function directory(Directory $d): void
	{
		if ($d->read() === '.') {
			assertType('string|false', $d->read());
		}
	}

	public function breakIterator(IntlBreakIterator $it): void
	{
		if ($it->next() === 2) {
			assertType('int', $it->next());
		}
		if ($it->previous() === 0) {
			assertType('int', $it->previous());
		}
		if ($it->current() === 0) {
			$it->next();
			assertType('int', $it->current());
		}
		if ($it->current() === 0) {
			$it->last();
			assertType('int', $it->current());
		}
		if ($it->current() === 0) {
			$it->following(1);
			assertType('int', $it->current());
		}
		if ($it->current() === 0) {
			$it->preceding(5);
			assertType('int', $it->current());
		}
		if ($it->current() === 0) {
			$it->isBoundary(6);
			assertType('int', $it->current());
		}
		if ($it->current() === 2) {
			$it->first();
			assertType('int', $it->current());
		}
		if ($it->current() === 2) {
			$it->setText('ab');
			assertType('int', $it->current());
		}
	}

}
