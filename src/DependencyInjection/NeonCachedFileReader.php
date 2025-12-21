<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Adapter;
use Nette\Neon\Exception;
use Nette\Neon\Neon;
use Override;
use PHPStan\File\FileReader;
use function sprintf;

final class NeonCachedFileReader implements Adapter
{

	private NeonAdapter $adapter;

	/** @var array<string, mixed[]> */
	private static array $decodedCache = [];

	/**
	 * @param list<string> $expandRelativePaths
	 */
	public function __construct(private array $expandRelativePaths)
	{
		$this->adapter = new NeonAdapter($this->expandRelativePaths);
	}

	/**
	 * @return mixed[]
	 */
	#[Override]
	public function load(string $file): array
	{
		try {
			if (isset(self::$decodedCache[$file])) {
				$neon = self::$decodedCache[$file];
			} else {
				$contents = FileReader::read($file);
				$neon = (array) Neon::decode($contents);
				self::$decodedCache[$file] = $neon;
			}

			return $this->adapter->process($neon, '', $file);
		} catch (Exception $e) {
			throw new Exception(sprintf('Error while loading %s: %s', $file, $e->getMessage()));
		}
	}

}
