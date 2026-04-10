<?php declare(strict_types = 1);

namespace PHPStan\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;
use Override;
use PHPStan\Command\Bisect\BinarySearch;
use PHPStan\File\FileReader;
use PHPStan\Internal\HttpClientFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use function array_filter;
use function array_merge;
use function array_values;
use function chmod;
use function count;
use function escapeshellarg;
use function getenv;
use function implode;
use function is_array;
use function is_file;
use function is_string;
use function mkdir;
use function passthru;
use function preg_match_all;
use function sprintf;
use function strtok;
use function substr;
use function sys_get_temp_dir;
use function urlencode;
use const PHP_BINARY;

final class BisectCommand extends Command
{

	private const NAME = 'bisect';

	private const REPO_OWNER = 'phpstan';

	private const REPO_NAME = 'phpstan';

	public function __construct()
	{
		parent::__construct();
	}

	#[Override]
	protected function configure(): void
	{
		$this->setName(self::NAME)
			->setDescription('Binary search for the first bad PHPStan commit between two releases')
			->setDefinition([
				new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'),
				new InputOption('good', null, InputOption::VALUE_REQUIRED, 'Good (old) PHPStan release version (e.g. 2.1.0)'),
				new InputOption('bad', null, InputOption::VALUE_REQUIRED, 'Bad (new) PHPStan release version (e.g. 2.1.5)'),
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption(AnalyseCommand::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Memory limit for analysis'),
			]);
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);

		$good = $input->getOption('good');
		if (!is_string($good)) {
			if (!$input->isInteractive()) {
				$io->error('Both --good and --bad release versions are required in non-interactive mode.');
				return 1;
			}
			$good = $io->ask('Enter the good (working) PHPStan release version (e.g. 2.1.0)');
		}

		$bad = $input->getOption('bad');
		if (!is_string($bad)) {
			if (!$input->isInteractive()) {
				$io->error('Both --good and --bad release versions are required in non-interactive mode.');
				return 1;
			}
			$bad = $io->ask('Enter the bad (broken) PHPStan release version (e.g. 2.1.5)');
		}

		if (!is_string($good) || !is_string($bad)) {
			$io->error('Both good and bad release versions are required.');
			return 1;
		}

		$token = $this->getGitHubToken();
		if ($token === null) {
			$io->error([
				'GitHub token not found.',
				'Please set the GITHUB_TOKEN or GH_TOKEN environment variable,',
				'or add a GitHub OAuth token to ~/.composer/auth.json.',
			]);
			return 1;
		}

		$client = (new HttpClientFactory())->createClient([
			RequestOptions::TIMEOUT => 30,
			RequestOptions::CONNECT_TIMEOUT => 10,
			'headers' => [
				'Authorization' => 'token ' . $token,
				'Accept' => 'application/vnd.github.v3+json',
			],
		]);

		$io->section(sprintf('Fetching commits between %s and %s...', $good, $bad));

		try {
			$commits = $this->getCommitsBetween($client, $good, $bad);
		} catch (GuzzleException $e) {
			$io->error(sprintf('Failed to fetch commits from GitHub: %s', $e->getMessage()));
			return 1;
		}

		if (count($commits) === 0) {
			$io->error('No commits found between the specified releases.');
			return 1;
		}

		$io->writeln(sprintf('Found <info>%d</info> commits between %s and %s.', count($commits), $good, $bad));

		$rangeShas = [];
		foreach ($commits as $commit) {
			$rangeShas[$commit['sha']] = true;
		}

		try {
			$checksumShas = $this->getPharChecksumCommitShas($client, $bad, $rangeShas);
		} catch (GuzzleException $e) {
			$io->error(sprintf('Failed to fetch .phar-checksum commits from GitHub: %s', $e->getMessage()));
			return 1;
		}

		$commits = array_values(array_filter($commits, static fn (array $commit): bool => isset($checksumShas[$commit['sha']])));

		if (count($commits) === 0) {
			$io->error('No commits found that change phpstan.phar between the specified releases.');
			return 1;
		}

		$io->writeln(sprintf('<info>%d</info> of them change phpstan.phar.', count($commits)));

		$tmpDir = sys_get_temp_dir() . '/phpstan-bisect';
		@mkdir($tmpDir, 0777, true);

		$analyseArgs = $this->buildAnalyseArgs($input);

		while (count($commits) > 1) {
			$step = BinarySearch::getStep($commits);
			$commit = $step->item;
			$sha = $commit['sha'];
			$shortSha = substr($sha, 0, 7);
			$message = $commit['commit']['message'];
			$firstLine = strtok($message, "\n") ?: $shortSha;

			$io->section(sprintf(
				'Testing commit %s (%s) [~%d step%s left]',
				$shortSha,
				$firstLine,
				$step->stepsRemaining,
				$step->stepsRemaining === 1 ? '' : 's',
			));

			$pharPath = $tmpDir . '/phpstan-' . $shortSha . '.phar';
			if (!is_file($pharPath)) {
				$io->writeln('Downloading phpstan.phar...');
				try {
					$this->downloadPharForCommit($client, $sha, $pharPath, $output);
				} catch (GuzzleException $e) {
					$io->error(sprintf('Failed to download phpstan.phar: %s', $e->getMessage()));
					return 1;
				}
			}

			$io->writeln('Running analysis...');
			$io->newLine();
			$exitCode = $this->runAnalysis($pharPath, $analyseArgs);
			$io->newLine();
			$io->writeln(sprintf('Analysis exited with code: <info>%d</info>', $exitCode));

			if (!$input->isInteractive()) {
				$io->error('Cannot continue bisect in non-interactive mode.');
				return 1;
			}

			$answer = $io->choice(
				'Is this result good or bad?',
				['good', 'bad'],
			);

			$commits = $answer === 'good' ? $step->ifGood : $step->ifBad;
		}

		$badCommit = $commits[0];
		$this->printResult($badCommit, $io);

		return 0;
	}

	public function getGitHubToken(?string $composerHome = null): ?string
	{
		$envToken = getenv('GITHUB_TOKEN');
		if ($envToken !== false && $envToken !== '') {
			return $envToken;
		}

		$ghToken = getenv('GH_TOKEN');
		if ($ghToken !== false && $ghToken !== '') {
			return $ghToken;
		}

		if ($composerHome === null) {
			$composerHome = getenv('COMPOSER_HOME');
			if ($composerHome === false) {
				$home = getenv('HOME');
				if ($home === false) {
					$home = getenv('USERPROFILE');
				}
				if ($home === false) {
					return null;
				}
				$composerHome = $home . '/.composer';
			}
		}

		$authFile = $composerHome . '/auth.json';
		if (!is_file($authFile)) {
			return null;
		}

		try {
			/** @var array{github-oauth?: array<string, string>} $auth */
			$auth = Json::decode(FileReader::read($authFile), Json::FORCE_ARRAY);
			return $auth['github-oauth']['github.com'] ?? null;
		} catch (Throwable) {
			return null;
		}
	}

	/**
	 * @return list<array{sha: string, commit: array{message: string}}>
	 * @throws GuzzleException
	 */
	private function getCommitsBetween(Client $client, string $good, string $bad): array
	{
		$allCommits = [];
		$page = 1;
		$perPage = 100;

		while (true) {
			$response = $client->get(sprintf(
				'https://api.github.com/repos/%s/%s/compare/%s...%s?per_page=%d&page=%d',
				self::REPO_OWNER,
				self::REPO_NAME,
				urlencode($good),
				urlencode($bad),
				$perPage,
				$page,
			));

			/** @var array{commits: list<array{sha: string, commit: array{message: string}}>, total_commits: int} $data */
			$data = Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);
			$commits = $data['commits'];
			$allCommits = array_merge($allCommits, $commits);

			if (count($commits) < $perPage || count($allCommits) >= $data['total_commits']) {
				break;
			}

			$page++;
		}

		return $allCommits;
	}

	/**
	 * @param array<string, true> $rangeShas
	 * @return array<string, true>
	 * @throws GuzzleException
	 */
	private function getPharChecksumCommitShas(Client $client, string $bad, array $rangeShas): array
	{
		$checksumShas = [];
		$page = 1;
		$perPage = 100;

		while (true) {
			$response = $client->get(sprintf(
				'https://api.github.com/repos/%s/%s/commits?sha=%s&path=%s&per_page=%d&page=%d',
				self::REPO_OWNER,
				self::REPO_NAME,
				urlencode($bad),
				urlencode('.phar-checksum'),
				$perPage,
				$page,
			));

			/** @var list<array{sha: string}> $commits */
			$commits = Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);

			if (count($commits) === 0) {
				break;
			}

			$foundOutOfRange = false;
			foreach ($commits as $commit) {
				if (!isset($rangeShas[$commit['sha']])) {
					$foundOutOfRange = true;
					break;
				}

				$checksumShas[$commit['sha']] = true;
			}

			if ($foundOutOfRange || count($commits) < $perPage) {
				break;
			}

			$page++;
		}

		return $checksumShas;
	}

	/**
	 * @throws GuzzleException
	 */
	private function downloadPharForCommit(Client $client, string $sha, string $pharPath, OutputInterface $output): void
	{
		$url = sprintf(
			'https://raw.githubusercontent.com/%s/%s/%s/phpstan.phar',
			self::REPO_OWNER,
			self::REPO_NAME,
			$sha,
		);

		$progressBar = new ProgressBar($output);
		$bytes = 0;

		$client->get($url, [
			RequestOptions::SINK => $pharPath,
			RequestOptions::TIMEOUT => 120,
			RequestOptions::PROGRESS => static function (int $downloadTotal, int $downloadedBytes) use ($progressBar, &$bytes): void {
				if ($downloadTotal === 0) {
					return;
				}
				if ($progressBar->getMaxSteps() === 0) {
					$progressBar->setFormat('file_download');
					$progressBar->setMessage(sprintf('%.2f MB', $downloadTotal / 1000000), 'fileSize');
					$progressBar->start($downloadTotal);
				}
				if ($downloadedBytes <= $bytes) {
					return;
				}
				$bytes = $downloadedBytes;
				$progressBar->setProgress($bytes);
			},
		]);

		$progressBar->finish();
		$output->writeln('');

		chmod($pharPath, 0755);
	}

	public function buildAnalyseArgs(InputInterface $input): string
	{
		$args = [];

		$config = $input->getOption('configuration');
		if (is_string($config)) {
			$args[] = '-c ' . escapeshellarg($config);
		}

		$level = $input->getOption(AnalyseCommand::OPTION_LEVEL);
		if (is_string($level)) {
			$args[] = '-l ' . escapeshellarg($level);
		}

		$autoload = $input->getOption('autoload-file');
		if (is_string($autoload)) {
			$args[] = '-a ' . escapeshellarg($autoload);
		}

		$memory = $input->getOption('memory-limit');
		if (is_string($memory)) {
			$args[] = '--memory-limit=' . escapeshellarg($memory);
		}

		$args[] = '--no-progress';

		$paths = $input->getArgument('paths');
		if (is_array($paths)) {
			foreach ($paths as $path) {
				if (!is_string($path)) {
					continue;
				}

				$args[] = escapeshellarg($path);
			}
		}

		return implode(' ', $args);
	}

	private function runAnalysis(string $pharPath, string $analyseArgs): int
	{
		$command = sprintf(
			'%s %s analyse %s',
			escapeshellarg(PHP_BINARY),
			escapeshellarg($pharPath),
			$analyseArgs,
		);

		passthru($command, $exitCode);
		return $exitCode;
	}

	/**
	 * @param array{sha: string, commit: array{message: string}} $commit
	 */
	private function printResult(array $commit, SymfonyStyle $io): void
	{
		$sha = $commit['sha'];
		$message = $commit['commit']['message'];

		$io->success('Found the first bad commit!');
		$io->writeln(sprintf('Commit: <info>%s</info>', $sha));
		$io->writeln(sprintf('URL:    <info>https://github.com/%s/%s/commit/%s</info>', self::REPO_OWNER, self::REPO_NAME, $sha));
		$io->newLine();
		$io->writeln('Commit message:');
		$io->writeln($message);

		if (preg_match_all('#https://github\.com/phpstan/phpstan-src/commit/[a-f0-9]+(?:\s+.*)?#', $message, $matches) < 1) {
			return;
		}

		$io->newLine();
		$io->writeln('<info>Related phpstan-src commits:</info>');
		foreach ($matches[0] as $line) {
			$io->writeln(sprintf('  %s', $line));
		}
	}

}
