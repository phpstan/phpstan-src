<?php

declare(strict_types=1);

namespace ResultCacheE2E\Dependency;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\ResultCache\ResultCacheDependencyExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\ResultCacheDependencyCollector;
use PHPStan\Rules\Rule;
use RuntimeException;
use function file_put_contents;
use function getmypid;
use function sprintf;
use const FILE_APPEND;
use const LOCK_EX;

/** @implements Rule<FuncCall> */
final class ConfigResultCacheDependencyRule implements Rule
{
	public function __construct(
		private ConfigTypeRegistry $configTypeRegistry,
		private TenantConfigTypeRegistry $tenantConfigTypeRegistry,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	/** @param Scope&CollectedDataEmitter $scope */
	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node->name instanceof Name
			|| !isset($node->getArgs()[0])
			|| !$node->getArgs()[0]->value instanceof String_
		) {
			return [];
		}

		$functionName = $node->name->toString();
		if (
			$functionName !== 'configValue'
			&& $functionName !== 'configuredConnectionValue'
			&& $functionName !== 'tenantConfigValue'
		) {
			return [];
		}

		$pid = getmypid();
		if ($pid === false) {
			throw new RuntimeException('Could not determine the configuration dependency rule process.');
		}
		if (file_put_contents(
			__DIR__ . '/../tmp/rule-pids.log',
			sprintf("%d\n", $pid),
			FILE_APPEND | LOCK_EX,
		) === false) {
			throw new RuntimeException('Could not record the configuration dependency rule process.');
		}

		$key = $node->getArgs()[0]->value->value;
		$extension = $functionName === 'tenantConfigValue'
			? $this->tenantConfigTypeRegistry
			: $this->configTypeRegistry;
		$this->emitDependency($scope, $extension, $key);
		if ($functionName === 'configuredConnectionValue') {
			$this->emitDependency(
				$scope,
				$this->configTypeRegistry,
				$this->configTypeRegistry->getSelectedConnectionKey($key),
			);
		}

		return [];
	}

	private function emitDependency(
		CollectedDataEmitter $scope,
		ResultCacheDependencyExtension $extension,
		string $key,
	): void
	{
		$data = ResultCacheDependencyCollector::createData($extension, $key);
		if ($key === 'profile.name') {
			$data += ['hash' => 'extension-supplied'];
		}
		$scope->emitCollectedData(
			ResultCacheDependencyCollector::class,
			$data,
		);
	}
}
