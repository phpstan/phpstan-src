<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\File\FileHelper;
use PHPStan\File\IncludedFilePathResolver;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use function dirname;
use function is_file;
use function sprintf;

/**
 * @implements Rule<Include_>
 */
#[RegisteredRule(level: 0)]
final class RequireFileExistsRule implements Rule
{

	/**
	 * Functions that, when they return true, guarantee the path exists on the
	 * filesystem, so guarding a require/include with them suppresses the error.
	 */
	private const FILE_EXISTENCE_FUNCTIONS = [
		'file_exists',
		'is_file',
		'is_readable',
		'is_writable',
		'is_writeable',
		'is_executable',
	];

	public function __construct(
		private ExprPrinter $exprPrinter,
		#[AutowiredParameter(ref: '%featureToggles.magicDirInInclude%')]
		private bool $checkMagicDirInInclude,
		private FileHelper $fileHelper,
		private IncludedFilePathResolver $includedFilePathResolver,
	)
	{
	}

	public function getNodeType(): string
	{
		return Include_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->isInFileExists($node, $scope)) {
			return [];
		}

		$errors = [];
		$usedMagicDirFallback = false;
		$paths = $this->resolveFilePaths($node->expr, $scope, $usedMagicDirFallback);

		foreach ($paths as $path) {
			$path = $path->getValue();

			if ($this->doesFileExist($path, $scope)) {
				continue;
			}

			if ($usedMagicDirFallback) {
				$pathExpr = $this->exprPrinter->printExpr($node->expr);
			} else {
				$pathExpr = '"' . $path . '"';
			}

			$errors[] = $this->getErrorMessage($node, $pathExpr, $this->includedFilePathResolver->resolve($path, $scope));
		}

		return $errors;
	}

	/**
	 * We cannot use `stream_resolve_include_path` as it works based on the calling script.
	 * This method simulates the behavior of `stream_resolve_include_path` but for the given scope.
	 * The priority order is the following:
	 * 	1. The current working directory.
	 * 	2. The include path.
	 *  3. The path of the script that is being executed.
	 */
	private function doesFileExist(string $path, Scope $scope): bool
	{
		foreach ($this->includedFilePathResolver->resolve($path, $scope) as $candidatePath) {
			if (is_file($candidatePath)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Both `__DIR__` and the "calling script's own directory" fallback of a relative
	 * include are resolved at compile time, so inside a trait they point at the file
	 * the trait is declared in - not at the file of the class that uses it, which is
	 * what Scope::getFile() returns in a trait context.
	 */
	private function getScopeFile(Scope $scope): string
	{
		if ($scope->isInTrait()) {
			$traitFileName = $scope->getTraitReflection()->getFileName();
			if ($traitFileName !== null) {
				return $this->fileHelper->normalizePath($traitFileName);
			}
		}

		return $scope->getFile();
	}

	/**
	 * @param list<string> $candidatePaths
	 */
	private function getErrorMessage(Include_ $node, string $filePath, array $candidatePaths): IdentifierRuleError
	{
		$message = 'Path in %s() %s is not a file or it does not exist.';

		switch ($node->type) {
			case Include_::TYPE_REQUIRE:
				$type = 'require';
				$identifierType = 'require';
				break;
			case Include_::TYPE_REQUIRE_ONCE:
				$type = 'require_once';
				$identifierType = 'requireOnce';
				break;
			case Include_::TYPE_INCLUDE:
				$type = 'include';
				$identifierType = 'include';
				break;
			case Include_::TYPE_INCLUDE_ONCE:
				$type = 'include_once';
				$identifierType = 'includeOnce';
				break;
			default:
				throw new ShouldNotHappenException('Rule should have already validated the node type.');
		}

		$identifier = sprintf('%s.fileNotFound', $identifierType);

		$builder = RuleErrorBuilder::message(
			sprintf(
				$message,
				$type,
				$filePath,
			),
		)->identifier($identifier);

		// The error is about a path, and a path is nothing the dependency graph tracks. Declaring the
		// paths makes the result cache re-analyse this file when one of them is created.
		foreach ($candidatePaths as $candidatePath) {
			$builder->fileDependency($candidatePath);
		}

		return $builder->build();
	}

	/**
	 * @return list<ConstantStringType>
	 */
	private function resolveFilePaths(Expr $expr, Scope $scope, bool &$magicDirFallback): array
	{
		$magicDirFallback = false;

		if (!$this->checkMagicDirInInclude) {
			return $scope->getType($expr)->getConstantStrings();
		}

		if (!$expr instanceof Expr\BinaryOp\Concat) {
			return $scope->getType($expr)->getConstantStrings();
		}

		if ($expr->left instanceof Node\Scalar\MagicConst\Dir) {
			$magicDirFallback = true;

			$paths = [];
			foreach ($scope->getType($expr->right)->getConstantStrings() as $constantString) {
				$paths[] = new ConstantStringType(dirname($this->getScopeFile($scope)) . $constantString->getValue());
			}
			return $paths;
		}

		$paths = [];
		$rightPaths = $this->resolveFilePaths($expr->right, $scope, $magicDirFallback);
		foreach ($this->resolveFilePaths($expr->left, $scope, $magicDirFallback) as $left) {
			foreach ($rightPaths as $rightPath) {
				$normalizedPath = $this->fileHelper->normalizeSeparator($left->getValue() . $rightPath->getValue());
				$paths[$normalizedPath] = $normalizedPath;
			}
		}

		$list = [];
		foreach ($paths as $path) {
			$list[] = new ConstantStringType($path);
		}
		return $list;
	}

	private function isInFileExists(Include_ $node, Scope $scope): bool
	{
		foreach (self::FILE_EXISTENCE_FUNCTIONS as $funcName) {
			$expr = new FuncCall(new FullyQualified($funcName), [
				new Arg($node->expr),
			]);

			if ($scope->getType($expr)->isTrue()->yes()) {
				return true;
			}
		}

		return false;
	}

}
