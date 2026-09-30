<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\DependencyInjection\AutowiredService;
use function time;
use const DIRECTORY_SEPARATOR;

/**
 * Tells from its stat alone that a file or directory has not been written to since it was last
 * read, the way git's index does: its size, mtime, ctime, inode and device are what they were.
 * ctime cannot be set back the way mtime can (touch, an extracted archive), and a replaced file
 * has a new inode. Adding, removing or renaming an entry of a directory updates its mtime and
 * ctime, so a directory whose signature matches still has the entries it had.
 *
 * A caller keeps the signature next to what it derived from the contents, and next time reuses
 * that as long as the signature it gets is the same - see FileStatSignatureReader.
 */
#[AutowiredService]
final class FileStatSignatures
{

	/**
	 * To be called before reading the contents the signatures are going to vouch for.
	 */
	public function begin(): FileStatSignatureReader
	{
		// On Windows, the ctime PHP reports is the creation time, which a file rewritten in place
		// keeps, and the mtime alone can be set back - nothing can be vouched for there.
		return new FileStatSignatureReader(time(), DIRECTORY_SEPARATOR === '/');
	}

}
