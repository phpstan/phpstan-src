<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Console;

use Exception;
use Github\Api\Issue as GitHubIssueApi;
use Github\Client;
use PHPStan\IssueBot\Comment\BotComment;
use PHPStan\IssueBot\Issue\IssueCache;
use PHPStan\IssueBot\Playground\PlaygroundCache;
use PHPStan\IssueBot\Playground\PlaygroundError;
use PHPStan\IssueBot\Playground\TabCreator;
use PHPStan\IssueBot\PostGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function file_get_contents;
use function implode;
use function in_array;
use function is_file;
use function sprintf;
use function unserialize;

class EvaluateCommand extends Command
{

	public function __construct(
		private TabCreator $tabCreator,
		private PostGenerator $postGenerator,
		private Client $githubClient,
		private string $issueCachePath,
		private string $playgroundCachePath,
		private string $tmpDir,
		private string $gitBranch,
		private string $phpstanSrcCommitBefore,
		private string $phpstanSrcCommitAfter,
	)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setName('evaluate');
		$this->addOption('post-comments', null, InputOption::VALUE_NONE);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$issueCache = $this->loadIssueCache();
		$originalResults = $this->loadPlaygroundCache()->getResults();
		$newResults = $this->loadResults();
		$toPost = [];
		$totalCodeSnippets = 0;

		foreach ($issueCache->getIssues() as $issue) {
			$botComments = [];
			$deduplicatedExamples = [];
			foreach ($issue->getComments() as $comment) {
				if ($comment instanceof BotComment) {
					$botComments[] = $comment;
					continue;
				}
				foreach ($comment->getPlaygroundExamples() as $example) {
					if (isset($deduplicatedExamples[$example->getHash()])) {
						$deduplicatedExamples[$example->getHash()]['users'][] = $comment->getAuthor();
						$deduplicatedExamples[$example->getHash()]['users'] = array_values(array_unique($deduplicatedExamples[$example->getHash()]['users']));
						continue;
					}
					$deduplicatedExamples[$example->getHash()] = [
						'example' => $example,
						'users' => [$comment->getAuthor()],
					];
				}
			}

			$totalCodeSnippets += count($deduplicatedExamples);
			foreach ($deduplicatedExamples as ['example' => $example, 'users' => $users]) {
				$hash = $example->getHash();
				if (!array_key_exists($hash, $originalResults)) {
					throw new Exception(sprintf('Hash %s does not exist in original results.', $hash));
				}

				$originalErrors = $originalResults[$hash]->getVersionedErrors();
				$originalTabs = $this->tabCreator->create($originalErrors);

				if (!array_key_exists($hash, $newResults)) {
					throw new Exception(sprintf('Hash %s does not exist in new results.', $hash));
				}

				$newTabs = $this->tabCreator->create($this->filterErrors($originalErrors, $newResults[$hash]));
				$text = $this->postGenerator->createText($hash, $originalTabs, $newTabs, $botComments);
				if ($text === null) {
					continue;
				}

				/*if ($this->isIssueClosed($issue->getNumber())) {
					continue;
				}*/

				$textAgain = $this->postGenerator->createText($hash, $originalTabs, $newTabs, $botComments);
				if ($textAgain === null) {
					continue;
				}

				$toPost[] = [
					'issue' => $issue->getNumber(),
					'hash' => $hash,
					'users' => $users,
					'diff' => $text['diff'],
					'details' => $text['details'],
				];
			}
		}

		if (count($toPost) === 0) {
			$output->writeln(sprintf('No changes in results in %d code snippets from %d GitHub issues. :tada:', $totalCodeSnippets, count($issueCache->getIssues())));
		}

		foreach ($toPost as ['issue' => $issue, 'hash' => $hash, 'users' => $users, 'diff' => $diff, 'details' => $details]) {
			$text = sprintf(
				"Result of the [code snippet](https://phpstan.org/r/%s) from %s in [#%d](https://github.com/phpstan/phpstan/issues/%d) changed:\n\n```diff\n%s```",
				$hash,
				implode(' ', array_map(static fn (string $user): string => sprintf('@%s', $user), $users)),
				$issue,
				$issue,
				$diff,
			);
			if ($details !== null) {
				$text .= "\n\n" . sprintf('<details>
 <summary>Full report</summary>

%s
</details>', $details);
			}

			$text .= "\n\n---\n";

			$output->writeln($text);
		}

		$postComments = (bool) $input->getOption('post-comments');
		if ($postComments) {
			if (count($toPost) > 20) {
				$output->writeln('Too many comments to post, something is probably wrong.');
				return 1;
			}
			foreach ($toPost as ['issue' => $issue, 'hash' => $hash, 'users' => $users, 'diff' => $diff, 'details' => $details]) {
				$text = sprintf(
					"%s After [the latest push in %s](https://github.com/phpstan/phpstan-src/compare/%s...%s), PHPStan now reports different result with your [code snippet](https://phpstan.org/r/%s):\n\n```diff\n%s```",
					implode(' ', array_map(static fn (string $user): string => sprintf('@%s', $user), $users)),
					$this->gitBranch,
					$this->phpstanSrcCommitBefore,
					$this->phpstanSrcCommitAfter,
					$hash,
					$diff,
				);
				if ($details !== null) {
					$text .= "\n\n" . sprintf('<details>
 <summary>Full report</summary>

%s
</details>', $details);
				}

				/** @var GitHubIssueApi $issueApi */
				$issueApi = $this->githubClient->api('issue');
				$issueApi->comments()->create('phpstan', 'phpstan', $issue, [
					'body' => $text,
				]);
			}
		}

		return 0;
	}

	private function loadIssueCache(): IssueCache
	{
		if (!is_file($this->issueCachePath)) {
			throw new Exception('Issue cache must exist');
		}

		$contents = file_get_contents($this->issueCachePath);
		if ($contents === false) {
			throw new Exception('Read unsuccessful');
		}

		return unserialize($contents);
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

	/**
	 * @return array<string, array<int, list<PlaygroundError>>>
	 */
	private function loadResults(): array
	{
		$finder = new Finder();
		$tmpResults = [];
		foreach ($finder->files()->name('results-*.tmp')->in($this->tmpDir) as $resultFile) {
			$contents = file_get_contents($resultFile->getPathname());
			if ($contents === false) {
				throw new Exception('Result read unsuccessful');
			}
			$result = unserialize($contents);
			$phpVersion = (int) $result['phpVersion'];
			foreach ($result['errors'] as $hash => $errors) {
				$tmpResults[(string) $hash][$phpVersion] = array_values($errors);
			}
		}

		return $tmpResults;
	}

	/**
	 * @param array<int, list<PlaygroundError>> $originalErrors
	 * @param array<int, list<PlaygroundError>> $newErrors
	 * @return array<int, list<PlaygroundError>>
	 */
	private function filterErrors(
		array $originalErrors,
		array $newErrors,
	): array
	{
		$originalPhpVersions = array_keys($originalErrors);
		$filteredNewErrors = [];
		foreach ($newErrors as $phpVersion => $errors) {
			if (!in_array($phpVersion, $originalPhpVersions, true)) {
				continue;
			}

			$filteredNewErrors[$phpVersion] = $errors;
		}

		$newTabs = $this->tabCreator->create($newErrors);
		$filteredNewTabs = $this->tabCreator->create($filteredNewErrors);
		if (count($newTabs) !== count($filteredNewTabs)) {
			return $newErrors;
		}

		$firstFilteredNewTab = $filteredNewTabs[0];
		$firstNewTab = $newTabs[0];

		if (count($firstFilteredNewTab->getErrors()) !== count($firstNewTab->getErrors())) {
			return $newErrors;
		}

		foreach ($firstFilteredNewTab->getErrors() as $i => $error) {
			$otherError = $firstNewTab->getErrors()[$i];
			if ($error->getLine() !== $otherError->getLine()) {
				return $newErrors;
			}
			if ($error->getMessage() !== $otherError->getMessage()) {
				return $newErrors;
			}
		}

		return $filteredNewErrors;
	}

}
