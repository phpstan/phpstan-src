<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Testing\TypeInferenceTestCase;

class GlobalVariableTest extends TypeInferenceTestCase
{

	public function testGlobalVariableInScript(): void
	{
		self::processFile(__DIR__ . '/data/global-in-script.php', function (Node $node, Scope $scope): void {
			if (!($node instanceof Return_)) {
				return;
			}

			$this->assertTrue($scope->isGlobalVariable('FOO'));
			$this->assertFalse($scope->isGlobalVariable('whatever'));
		});
	}

	public function testGlobalVariableInFunction(): void
	{
		self::processFile(__DIR__ . '/data/global-in-function.php', function (Node $node, Scope $scope): void {
			if (!($node instanceof Return_)) {
				return;
			}

			$this->assertFalse($scope->isGlobalVariable('BAR'));
			$this->assertTrue($scope->isGlobalVariable('CONFIG'));
			$this->assertFalse($scope->isGlobalVariable('localVar'));
		});
	}

	public function testGlobalVariableInClassMethod(): void
	{
		self::processFile(__DIR__ . '/data/global-in-class-method.php', function (Node $node, Scope $scope): void {
			if (!($node instanceof Return_)) {
				return;
			}

			$this->assertFalse($scope->isGlobalVariable('count'));
			$this->assertTrue($scope->isGlobalVariable('GLB_A'));
			$this->assertTrue($scope->isGlobalVariable('GLB_B'));
			$this->assertFalse($scope->isGlobalVariable('key'));
			$this->assertFalse($scope->isGlobalVariable('step'));
		});
	}

}
