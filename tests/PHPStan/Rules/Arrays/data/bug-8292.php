<?php declare(strict_types = 1);

namespace Bug8292;

class World{
	public function addOnUnloadCallback(\Closure $c) : void{}
}

interface Compressor{}

class ChunkCache
{
	/** @var self[][] */
	private static array $instances = [];

	/**
	 * Fetches the ChunkCache instance for the given world. This lazily creates cache systems as needed.
	 */
	public static function getInstance(World $world, Compressor $compressor) : void{
		$worldId = spl_object_id($world);
		$compressorId = spl_object_id($compressor);
		if(!isset(self::$instances[$worldId])){
			self::$instances[$worldId] = [];
			$world->addOnUnloadCallback(static function() use ($worldId) : void{
				foreach(self::$instances[$worldId] as $cache){
					$cache->caches = [];
				}
				unset(self::$instances[$worldId]);
			});
		}
	}
}
