<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PhpParser\Node;
use PhpParser\Node\Stmt\If_;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\UniversalRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UniversalRule<CollectedDataNode>>
 */
class JsonSerializableTest extends RuleTestCase
{

	/** @var Collector<If_, array<mixed>> */
	private Collector $collector;

	public function getRule(): Rule
	{
		return new UniversalRule(CollectedDataNode::class, static fn (Node $node): array => []);
	}

	public function testNotDecodable(): void
	{
		$this->collector = new UniversalCollector(
			If_::class,
			static fn (If_ $node): array => [$node],
		);

		$this->analyse([__DIR__ . '/data/json-serializable.php'], [
			[
				'Data collected by PHPStan\Collectors\UniversalCollector is not JSON decodable and will not work in parallel analysis.',
				-1,
			],
		]);
	}

	public function testNotJsonSerializable(): void
	{
		$this->collector = new UniversalCollector(
			If_::class,
			static fn (If_ $node): array => ["\x61\xb0\x62"], // a invalid utf8 string
		);

		$this->analyse([__DIR__ . '/data/json-serializable.php'], [
			[
				'Data collected by PHPStan\Collectors\UniversalCollector is not JSON serializable.',
				-1,
			],
		]);
	}

	public function getCollectors(): array
	{
		return [
			$this->collector,
		];
	}

}
