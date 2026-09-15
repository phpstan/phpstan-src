<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\PhpDocsResolver;
use PHPStan\Analyser\PropertyHooksProcessor;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use function count;

/**
 * @implements StmtHandler<Property>
 */
#[AutowiredService]
final class PropertyHandler implements StmtHandler
{

	public function __construct(
		private PhpDocsResolver $phpDocsResolver,
		private PropertyHooksProcessor $propertyHooksProcessor,
	)
	{
	}

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Property;
	}

	public function processStmt(
		NodeScopeResolver $nodeScopeResolver,
		Stmt $stmt,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		$nodeScopeResolver->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $storage, $nodeCallback);

		$nativePropertyType = $stmt->type !== null ? ParserNodeTypeToPHPStanType::resolve($stmt->type, $scope->getClassReflection()) : null;

		[,,,,,,,,,,,,$isReadOnly, $docComment, ,,,$varTags, $isAllowedPrivateMutation] = $this->phpDocsResolver->getPhpDocs($scope, $stmt);
		$phpDocType = null;
		if (isset($varTags[0]) && count($varTags) === 1) {
			$phpDocType = $varTags[0]->getType();
		}

		foreach ($stmt->props as $prop) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $prop, $scope, $storage);
			if ($prop->default !== null) {
				$nodeScopeResolver->processExprNode($stmt, $prop->default, $scope, $storage, $nodeCallback, ExpressionContext::createDeep(), null);
			}

			if (!$scope->isInClass()) {
				throw new ShouldNotHappenException();
			}
			$propertyName = $prop->name->toString();

			if ($phpDocType === null) {
				if (isset($varTags[$propertyName])) {
					$phpDocType = $varTags[$propertyName]->getType();
				}
			}

			$propStmt = clone $stmt;
			$propStmt->setAttributes($prop->getAttributes());
			$propStmt->setAttribute('originalPropertyStmt', $stmt);
			$nodeScopeResolver->callNodeCallback(
				$nodeCallback,
				new ClassPropertyNode(
					$propertyName,
					$stmt->flags,
					$nativePropertyType,
					$prop->default,
					$docComment,
					$phpDocType,
					false,
					false,
					$propStmt,
					$isReadOnly,
					$scope->isInTrait(),
					$scope->getClassReflection()->isReadOnly(),
					$isAllowedPrivateMutation,
					$scope->getClassReflection(),
				),
				$scope,
				$storage,
			);
		}

		if (count($stmt->hooks) > 0) {
			if (!isset($propertyName)) {
				throw new ShouldNotHappenException('Property name should be known when analysing hooks.');
			}
			$this->propertyHooksProcessor->processPropertyHooks(
				$nodeScopeResolver,
				$stmt,
				$stmt->type,
				$phpDocType,
				$propertyName,
				$stmt->hooks,
				$scope,
				$storage,
				$nodeCallback,
			);
		}

		if ($stmt->type !== null) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt->type, $scope, $storage);
		}

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: []);
	}

}
