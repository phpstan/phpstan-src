<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\CollectorWithPaths;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;

/**
 * @implements CollectorWithPaths<never, array{class-string<Rule<covariant Node>>, trait-string, string, null}|array{class-string<Rule<covariant Node>>, trait-string, string, bool, Error|array<mixed>}>
 */
final class ConstantConditionInTraitCollector implements CollectorWithPaths
{

	public function getNodeType(): string
	{
		throw new ShouldNotHappenException();
	}

	public function processNode(Node $node, Scope $scope)
	{
		throw new ShouldNotHappenException();
	}

	/**
	 * @param array{class-string<Rule<covariant Node>>, trait-string, string, null}|array{class-string<Rule<covariant Node>>, trait-string, string, bool, Error|array<mixed>} $data
	 * @param callable(string): string $transformPath
	 * @return array{class-string<Rule<covariant Node>>, trait-string, string, null}|array{class-string<Rule<covariant Node>>, trait-string, string, bool, Error|array<mixed>}
	 */
	public static function transformCollectedDataPaths($data, callable $transformPath)
	{
		if (!array_key_exists(4, $data)) {
			return $data;
		}

		$data[4] = CollectedConstantConditionError::transformPaths($data[4], $transformPath);

		return $data;
	}

}
