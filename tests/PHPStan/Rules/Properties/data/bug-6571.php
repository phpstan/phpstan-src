<?php declare(strict_types = 1); // lint >= 7.4

namespace Bug6571;

interface ClassLoader{}

class HelloWorld
{
	/** @var \Threaded|\ClassLoader[]|null  */
	private ?\Threaded $classLoaders = null;

	/**
	 * @param \ClassLoader[] $autoloaders
	 */
	public function setClassLoaders(?array $autoloaders = null) : void{
		if($autoloaders === null){
			$autoloaders = [];
		}

		if($this->classLoaders === null){
			$this->classLoaders = new \Threaded();
		}else{
			foreach($this->classLoaders as $k => $autoloader){
				unset($this->classLoaders[$k]);
			}
		}
		foreach($autoloaders as $autoloader){
			$this->classLoaders[] = $autoloader;
		}
	}
}
