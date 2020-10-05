<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use PHPStan\File\FileReader;
use PHPStan\Php8StubsMap;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\StubData;

class Php8StubsSourceStubber implements SourceStubber
{

	private const DIRECTORY = __DIR__ . '/../../../../vendor/phpstan/php-8-stubs';

	public function hasClass(string $className): bool
	{
		$className = strtolower($className);
		return array_key_exists($className, Php8StubsMap::CLASSES);
	}

	public function generateClassStub(string $className): ?StubData
	{
		$lowerClassName = strtolower($className);
		if (!array_key_exists($lowerClassName, Php8StubsMap::CLASSES)) {
			return null;
		}

		$relativeFilePath = Php8StubsMap::CLASSES[$lowerClassName];
		$file = self::DIRECTORY . '/' . $relativeFilePath;

		return new StubData(FileReader::read($file), $this->getExtensionFromFilePath($relativeFilePath), $file);
	}

	public function generateFunctionStub(string $functionName): ?StubData
	{
		$lowerFunctionName = strtolower($functionName);
		if (!array_key_exists($lowerFunctionName, Php8StubsMap::FUNCTIONS)) {
			return null;
		}

		$relativeFilePath = Php8StubsMap::FUNCTIONS[$lowerFunctionName];
		$file = self::DIRECTORY . '/' . $relativeFilePath;

		return new StubData(FileReader::read($file), $this->getExtensionFromFilePath($relativeFilePath), $file);
	}

	public function generateConstantStub(string $constantName): ?StubData
	{
		return null;
	}

	private function getExtensionFromFilePath(string $relativeFilePath): string
	{
		$pathParts = explode('/', $relativeFilePath);
		if ($pathParts[1] === 'Zend') {
			return 'Core';
		}

		return $pathParts[2];
	}

}
