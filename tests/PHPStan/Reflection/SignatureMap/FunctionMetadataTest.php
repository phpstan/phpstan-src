<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use Nette\Schema\Expect;
use Nette\Schema\Processor;
use PHPStan\Testing\PHPStanTestCase;
use function count;

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
				'pureUnlessCallableIsImpureParameters' => Expect::arrayOf(Expect::bool(), Expect::string()),
				'pureUnlessParameterPassedParameters' => Expect::arrayOf(Expect::bool(), Expect::string()),
			])
				->assert(static fn ($v) => count((array) $v) > 0, 'Metadata entries must not be empty.')
				->required(),
		)->required(), $data);
	}

}
