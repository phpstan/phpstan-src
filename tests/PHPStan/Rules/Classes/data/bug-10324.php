<?php

namespace Bug10324;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class HelloWorld
{
	public function findfile(string $directory, string $filename): ?string
	{
		$fontPath = null;
		$it = new RecursiveDirectoryIterator(
			$directory,
			RecursiveDirectoryIterator::SKIP_DOTS
			| RecursiveDirectoryIterator::FOLLOW_SYMLINKS
		);
		foreach (
			new RecursiveIteratorIterator(
				$it,
				RecursiveIteratorIterator::LEAVES_ONLY,
				//RecursiveIteratorIterator::CATCH_GET_CHILD
				RecursiveIteratorIterator::CHILD_FIRST
			) as $file
		) {
			if (basename($file) === $filename) {
				$fontPath = $file;
				break;
			}
		}
		return $fontPath;
	}
}
