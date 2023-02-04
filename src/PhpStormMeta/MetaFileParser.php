<?php declare(strict_types = 1);

namespace PHPStan\PhpStormMeta;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PHPStan\File\FileHelper;
use PHPStan\Parser\CachedParser;
use PHPStan\PhpStormMeta\TypeMapping\CallReturnOverrideCollection;
use PHPStan\PhpStormMeta\TypeMapping\FunctionCallTypeOverride;
use PHPStan\PhpStormMeta\TypeMapping\MethodCallTypeOverride;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use function array_key_exists;
use function count;
use function file_exists;
use function is_dir;

class MetaFileParser
{

	private readonly CallReturnOverrideCollection $parsedMeta;

	/** @var array<string, string> */
	private array $parsedMetaPaths = [];

	private bool $metaParsed;

	/**
	 * @param list<string> $metaPaths
	 */
	public function __construct(
		private readonly CachedParser $parser,
		private readonly OverrideParser $overrideParser,
		private readonly FileHelper $fileHelper,
		private readonly array $metaPaths,
	)
	{
		$this->parsedMeta = new CallReturnOverrideCollection();
		$this->metaParsed = $this->metaPaths === [];
	}

	public function getMeta(): CallReturnOverrideCollection
	{
		if (!$this->metaParsed) {
			$this->parseMeta($this->parsedMeta, ...$this->metaPaths);
			$this->metaParsed = true;
		}

		return $this->parsedMeta;
	}

	private function parseMeta(
		CallReturnOverrideCollection $resultCollector,
		string ...$metaPaths,
	): void
	{
		$metaFiles = [];

		/** @var array<string, string> $newlyParsedMetaPaths */
		$newlyParsedMetaPaths = [];
		foreach ($metaPaths as $relativePath) {
			if (array_key_exists($relativePath, $this->parsedMetaPaths)) {
				continue;
			}

			$path = $this->fileHelper->absolutizePath($relativePath);

			if (is_dir($path)) {
				$finder = (new Finder())->in($path)->files()->ignoreDotFiles(false);
				foreach ($finder as $fileInfo) {
					$metaFiles [] = $fileInfo->getPathname();
				}
			} elseif (file_exists($path)) {
				$singleFile = new SplFileInfo($path);
				$metaFiles[] = $singleFile->getPathname();
			}

			$newlyParsedMetaPaths[$relativePath] = $path;
		}

		foreach ($metaFiles as $metaFile) {
			$stmts = $this->parser->parseFile($metaFile);

			foreach ($stmts as $topStmt) {
				if (!($topStmt instanceof Stmt\Namespace_)) {
					continue;
				}

				if ($topStmt->name === null || $topStmt->name->toString() !== 'PHPSTORM_META') {
					continue;
				}

				foreach ($topStmt->stmts as $metaStmt) {
					if (!($metaStmt instanceof Expression)
						|| !($metaStmt->expr instanceof FuncCall)
						|| !($metaStmt->expr->name instanceof Name)
						|| $metaStmt->expr->name->toString() !== 'override'
					) {
						continue;
					}

					$args = $metaStmt->expr->getArgs();
					if (count($args) < 2) {
						continue;
					}

					[$callableArg, $overrideArg] = $args;
					$parsedOverride = $this->overrideParser->parseOverride($callableArg, $overrideArg);

					if ($parsedOverride instanceof MethodCallTypeOverride) {
						$resultCollector->addMethodCallOverride($parsedOverride);
					} elseif ($parsedOverride instanceof FunctionCallTypeOverride) {
						$resultCollector->addFunctionCallOverride($parsedOverride);
					}
				}
			}

			foreach ($newlyParsedMetaPaths as $relativePath => $path) {
				$this->parsedMetaPaths[$relativePath] = $path;
			}
		}
	}

}
