<?php

declare(strict_types = 1);

namespace Bug9240Rule;

/**
 * @phpstan-type PhpFileArray array{error: int, name: string}
 */
class Upload
{
	/**
	 * @param \Closure(PhpFileArray, PhpFileArray, PhpFileArray): bool $fx
	 */
	public function onUpload(\Closure $fx): bool
	{
		$v = ['error' => 1, 'name' => 'x'];
		$postFiles = [$v, $v, $v];

		return $fx(...$postFiles);
	}
}

function test(): void
{
	$u = new Upload();
	$u->onUpload(function (...$postFiles): bool {
		foreach ($postFiles as $postFile) {
			if ($postFile['error'] !== 0) {
				return false;
			}
		}

		return true;
	});
}
