<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use Nette\Schema\Expect;
use Nette\Schema\Processor;
use PHPStan\Testing\PHPStanTestCase;
use function array_slice;
use function in_array;
use function sprintf;

class FunctionMetadataTest extends PHPStanTestCase
{

	public function testSchema(): void
	{
		$data = require __DIR__ . '/../../../../resources/functionMetadata.php';
		$this->assertIsArray($data);

		$processor = new Processor();
		$processor->process(Expect::arrayOf(
			Expect::structure([
				'effects' => Expect::listOf(Expect::string()->pattern('\\*|[a-z]+(-[a-z]+)*(\\.[a-z]+(-[a-z]+)*)*(\\(\\$[a-zA-Z_][a-zA-Z0-9_]*(, \\$[a-zA-Z_][a-zA-Z0-9_]*)*\\))?'))->required(),
				'hasSideEffects' => Expect::anyOf(false),
				'pureUnlessCallableIsImpureParameters' => Expect::arrayOf(Expect::bool(), Expect::string()),
				'pureUnlessParameterPassedParameters' => Expect::arrayOf(Expect::bool(), Expect::string()),
			])
				->required(),
		)->required(), $data);

		foreach ($data as $name => $metadata) {
			if (!in_array('*', $metadata['effects'], true)) {
				continue;
			}

			// effects named next to '*' leave the others unknown, which the other keys cannot narrow
			$this->assertSame(['effects' => $metadata['effects']], $metadata, sprintf('%s: "*" cannot be combined with other keys.', $name));
			$this->assertSame('*', $metadata['effects'][0], sprintf('%s: "*" comes first.', $name));
			$this->assertNotContains('*', array_slice($metadata['effects'], 1), sprintf('%s: "*" is listed twice.', $name));
		}
	}

}
