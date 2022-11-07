<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Console;

use Exception;
use Nette\Neon\Neon;
use Nette\Utils\Json;
use PHPStan\IssueBot\Playground\PlaygroundCache;
use PHPStan\IssueBot\Playground\PlaygroundError;
use PHPStan\IssueBot\Playground\PlaygroundResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_key_exists;
use function array_key_first;
use function exec;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_file;
use function serialize;
use function sprintf;
use function unserialize;

class RunCommand extends Command
{

	public function __construct(private string $playgroundCachePath, private string $tmpDir)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setName('run');
		$this->addArgument('phpVersion', InputArgument::REQUIRED);
		$this->addArgument('playgroundHashes', InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$playgroundHashes = explode(',', $input->getArgument('playgroundHashes'));
		$playgroundCache = $this->loadPlaygroundCache();
		$errors = [];
		foreach ($playgroundHashes as $hash) {
			if (!array_key_exists($hash, $playgroundCache->getResults())) {
				throw new Exception(sprintf('Hash %s must exist', $hash));
			}
			$errors[$hash] = $this->analyseHash((int) $input->getArgument('phpVersion'), $playgroundCache->getResults()[$hash]);
		}

		$writeSuccess = file_put_contents($this->tmpDir . '/results.tmp', serialize($errors));
		if ($writeSuccess === false) {
			throw new Exception('Result write unsuccessful');
		}

		return 0;
	}

	/**
	 * @return list<PlaygroundError>
	 */
	private function analyseHash(int $phpVersion, PlaygroundResult $result): array
	{
		$configFiles = [];
		if ($result->isBleedingEdge()) {
			$configFiles[] = __DIR__ . '/../../../conf/bleedingEdge.neon';
		}
		if ($result->isStrictRules()) {
			$configFiles[] = __DIR__ . '/../../../vendor/phpstan/phpstan-strict-rules/rules.neon';
		}
		$neon = Neon::encode([
			'includes' => $configFiles,
			'parameters' => [
				'level' => $result->getLevel(),
				'inferPrivatePropertyTypeFromConstructor' => true,
				'treatPhpDocTypesAsCertain' => $result->isTreatPhpDocTypesAsCertain(),
				'phpVersion' => $phpVersion,
			],
		]);

		$hash = $result->getHash();
		$neonPath = sprintf($this->tmpDir . '/%s.neon', $hash);
		$codePath = sprintf($this->tmpDir . '/%s.php', $hash);
		file_put_contents($neonPath, $neon);
		file_put_contents($codePath, $result->getCode());

		$commandArray = [
			__DIR__ . '/../../../bin/phpstan',
			'analyse',
			'--error-format',
			'json',
			'--no-progress',
			'-c',
			$neonPath,
			$codePath,
		];

		exec(implode(' ', $commandArray), $outputLines, $exitCode);

		if ($exitCode !== 0 && $exitCode !== 1) {
			throw new Exception(sprintf('PHPStan exited with code %d', $exitCode));
		}

		$json = Json::decode(implode("\n", $outputLines), Json::FORCE_ARRAY);
		$file = array_key_first($json['files']);
		if ($file === null) {
			return [];
		}

		$errors = [];
		foreach ($json['files'][$file]['messages'] as $message) {
			$errors[] = new PlaygroundError($message['line'], $message['message']);
		}

		return $errors;
	}

	private function loadPlaygroundCache(): PlaygroundCache
	{
		if (!is_file($this->playgroundCachePath)) {
			throw new Exception('Playground cache must exist');
		}

		$contents = file_get_contents($this->playgroundCachePath);
		if ($contents === false) {
			throw new Exception('Read unsuccessful');
		}

		return unserialize($contents);
	}

}
