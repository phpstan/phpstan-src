<?php declare(strict_types = 1);

namespace PHPStan\File;

use Exception;
use function sprintf;

final class PathNotFoundException extends Exception
{

	public function __construct(private string $path)
	{
		parent::__construct(sprintf('Path %s does not exist', $path));
	}

	public function getPath(): string
	{
		return $this->path;
	}

}
