<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use Nette\Schema\Expect;
use Nette\Schema\Processor;
use PHPStan\Testing\PHPStanTestCase;
use function count;
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
				'hasSideEffects' => Expect::bool(),
				'effects' => Expect::listOf(Expect::string()->pattern('[a-z]+(-[a-z]+)*(\\.[a-z]+(-[a-z]+)*)*')),
				'pureUnlessCallableIsImpureParameters' => Expect::arrayOf(Expect::bool(), Expect::string()),
				'pureUnlessParameterPassedParameters' => Expect::arrayOf(Expect::bool(), Expect::string()),
			])
				->assert(static fn ($v) => count((array) $v) > 0, 'Metadata entries must not be empty.')
				->required(),
		)->required(), $data);

		foreach ($data as $name => $metadata) {
			if (!isset($metadata['effects'])) {
				continue;
			}

			$this->assertSame($metadata['effects'] !== [], $metadata['hasSideEffects'] ?? null, sprintf('%s: hasSideEffects must say whether there are effects.', $name));
		}
	}

}
