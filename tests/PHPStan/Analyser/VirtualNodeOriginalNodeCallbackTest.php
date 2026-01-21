<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Testing\TypeInferenceTestCase;

class VirtualNodeOriginalNodeCallbackTest extends TypeInferenceTestCase
{
	public function testOriginalNodeOfVirtualNodeIsPassedToCallback(): void
	{
		$sawVirtualNode = false;
		$sawOriginalNode = false;

		self::processFile(
			__DIR__ . '/data/virtual-node-original-node.php',
			static function (Node $node, Scope $scope) use (&$sawVirtualNode, &$sawOriginalNode): void {
				if ($node instanceof StaticMethodCallableNode) {
					$sawVirtualNode = true;
					return;
				}

				if ($node instanceof StaticCall) {
					$sawOriginalNode = true;
				}
			},
		);

		self::assertTrue($sawVirtualNode, 'Expected callback to receive StaticMethodCallableNode.');
		self::assertTrue($sawOriginalNode, 'Expected callback to receive original PhpParser\\Node\\Expr\\StaticCall.');
	}

}
