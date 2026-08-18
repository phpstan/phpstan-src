<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ConditionallyDeclaredSymbolDetectorTest extends PHPStanTestCase
{

	public static function dataIsConditionallyDeclaredFunction(): iterable
	{
		$namespaced = __DIR__ . '/data/conditionally-declared-symbols.php';
		$global = __DIR__ . '/data/conditionally-declared-symbols-no-namespace.php';

		yield [$namespaced, 'ConditionallyDeclaredSymbols\guardedByFunctionExists', true];
		yield [$namespaced, 'conditionallydeclaredsymbols\GUARDEDBYFUNCTIONEXISTS', true];
		yield [$namespaced, 'ConditionallyDeclaredSymbols\guardedByPhpVersionId', true];
		yield [$namespaced, 'ConditionallyDeclaredSymbols\declaredInElseIf', true];
		yield [$namespaced, 'ConditionallyDeclaredSymbols\declaredInElse', true];
		yield [$namespaced, 'ConditionallyDeclaredSymbols\declaredUnconditionally', false];
		yield [$namespaced, 'ConditionallyDeclaredSymbols\nonexistentFunction', false];

		yield [$global, 'conditionallyDeclaredFunctionWithoutNamespace', true];
		yield [$global, 'unconditionallyDeclaredFunctionWithoutNamespace', false];

		yield [__DIR__ . '/data/nonexistent-file.php', 'anything', false];
	}

	#[DataProvider('dataIsConditionallyDeclaredFunction')]
	public function testIsConditionallyDeclaredFunction(string $fileName, string $functionName, bool $expected): void
	{
		$this->assertSame($expected, $this->getDetector()->isConditionallyDeclaredFunction($fileName, $functionName));
	}

	public static function dataIsConditionallyDeclaredClass(): iterable
	{
		$namespaced = __DIR__ . '/data/conditionally-declared-symbols.php';
		$global = __DIR__ . '/data/conditionally-declared-symbols-no-namespace.php';

		yield [$namespaced, 'ConditionallyDeclaredSymbols\GuardedClass', true];
		yield [$namespaced, 'conditionallydeclaredsymbols\guardedinterface', true];
		yield [$namespaced, 'ConditionallyDeclaredSymbols\GuardedTrait', true];
		yield [$namespaced, 'ConditionallyDeclaredSymbols\UnconditionalClass', false];

		yield [$global, 'ConditionallyDeclaredClassWithoutNamespace', true];
	}

	#[DataProvider('dataIsConditionallyDeclaredClass')]
	public function testIsConditionallyDeclaredClass(string $fileName, string $className, bool $expected): void
	{
		$this->assertSame($expected, $this->getDetector()->isConditionallyDeclaredClass($fileName, $className));
	}

	public static function dataIsConditionallyDeclaredConstant(): iterable
	{
		$global = __DIR__ . '/data/conditionally-declared-symbols-no-namespace.php';

		yield [$global, 'GUARDED_DEFINE', true];
		yield [$global, 'guarded_define', false];
		yield [$global, 'UNCONDITIONAL_CONST', false];
	}

	#[DataProvider('dataIsConditionallyDeclaredConstant')]
	public function testIsConditionallyDeclaredConstant(string $fileName, string $constantName, bool $expected): void
	{
		$this->assertSame($expected, $this->getDetector()->isConditionallyDeclaredConstant($fileName, $constantName));
	}

	private function getDetector(): ConditionallyDeclaredSymbolDetector
	{
		return self::getContainer()->getByType(ConditionallyDeclaredSymbolDetector::class);
	}

}
