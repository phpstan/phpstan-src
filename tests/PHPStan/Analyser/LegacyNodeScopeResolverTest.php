<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\Exit_;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use SomeNodeScopeResolverNamespace\Foo;

class LegacyNodeScopeResolverTest extends TypeInferenceTestCase
{

	public function testClassMethodScope(): void
	{
		self::processFile(__DIR__ . '/data/class.php', function (Node $node, Scope $scope): void {
			if (!($node instanceof Exit_)) {
				return;
			}

			$this->assertSame('SomeNodeScopeResolverNamespace', $scope->getNamespace());
			$this->assertTrue($scope->isInClass());
			$this->assertSame(Foo::class, $scope->getClassReflection()->getName());
			$this->assertSame('doFoo', $scope->getFunctionName());
			$this->assertSame('$this(SomeNodeScopeResolverNamespace\Foo)', $scope->getVariableType('this')->describe(VerbosityLevel::precise()));
			$this->assertTrue($scope->hasVariableType('baz')->yes());
			$this->assertTrue($scope->hasVariableType('lorem')->yes());
			$this->assertFalse($scope->hasVariableType('ipsum')->yes());
			$this->assertTrue($scope->hasVariableType('i')->yes());
			$this->assertTrue($scope->hasVariableType('val')->yes());
			$this->assertSame('SomeNodeScopeResolverNamespace\InvalidArgumentException', $scope->getVariableType('exception')->describe(VerbosityLevel::precise()));
			$this->assertTrue($scope->hasVariableType('staticVariable')->yes());
			$this->assertSame($scope->getVariableType('staticVariable')->describe(VerbosityLevel::precise()), 'mixed');
			$this->assertTrue($scope->hasVariableType('staticVariableWithPhpDocType')->yes());
			$this->assertSame($scope->getVariableType('staticVariableWithPhpDocType')->describe(VerbosityLevel::precise()), 'string');
			$this->assertTrue($scope->hasVariableType('staticVariableWithPhpDocType2')->yes());
			$this->assertSame($scope->getVariableType('staticVariableWithPhpDocType2')->describe(VerbosityLevel::precise()), 'int');
			$this->assertTrue($scope->hasVariableType('staticVariableWithPhpDocType3')->yes());
			$this->assertSame($scope->getVariableType('staticVariableWithPhpDocType3')->describe(VerbosityLevel::precise()), 'float');
		});
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../conf/bleedingEdge.neon',
			__DIR__ . '/typeAliases.neon',
		];
	}

	public static function dataDeclareStrictTypes(): array
	{
		return [
			[
				__DIR__ . '/data/declareWeakTypes.php',
				false,
			],
			[
				__DIR__ . '/data/noDeclare.php',
				false,
			],
			[
				__DIR__ . '/data/declareStrictTypes.php',
				true,
			],
		];
	}

	#[DataProvider('dataDeclareStrictTypes')]
	public function testDeclareStrictTypes(string $file, bool $result): void
	{
		self::processFile($file, function (Node $node, Scope $scope) use ($result): void {
			if (!($node instanceof Exit_)) {
				return;
			}

			$this->assertSame($result, $scope->isDeclareStrictTypes());
		});
	}

	public function testEarlyTermination(): void
	{
		self::processFile(__DIR__ . '/data/early-termination.php', function (Node $node, Scope $scope): void {
			if (!($node instanceof Exit_)) {
				return;
			}

			$this->assertTrue($scope->hasVariableType('something')->yes());
			$this->assertTrue($scope->hasVariableType('var')->yes());
			$this->assertTrue($scope->hasVariableType('foo')->no());
		});
	}

	protected static function getEarlyTerminatingMethodCalls(): array
	{
		return [
			\EarlyTermination\Foo::class => [
				'doFoo',
				'doBar',
			],
		];
	}

	/** @return list<string> */
	protected static function getEarlyTerminatingFunctionCalls(): array
	{
		return ['baz'];
	}

}
