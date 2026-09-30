<?php declare(strict_types = 1);

namespace ResultCacheE2EFileDependency;

use PHPStan\Analyser\DependencyEmitter;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;

final class DataFile
{

	/**
	 * The return type comes from a data file the extension reads on its own: int when it says so,
	 * otherwise the string it contains, 'missing' when there is no such file.
	 *
	 * @param Scope&DependencyEmitter $scope
	 */
	public static function type(string $name, Scope $scope): Type
	{
		$file = dirname(__DIR__) . '/data/' . $name . '.txt';
		$scope->fileDependency($file);

		if (!is_file($file)) {
			return new ConstantStringType('missing');
		}

		$contents = file_get_contents($file);
		$contents = $contents === false ? '' : trim($contents);
		if ($contents === 'int') {
			return new IntegerType();
		}

		return new ConstantStringType($contents);
	}

}
