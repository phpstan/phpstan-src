<?php declare(strict_types = 1);

namespace Bug14323;

class ProcessFailedException extends \RuntimeException {}

class Process {
	/** @param array<string> $a */
	public function __construct(array $a) {}
}

class Process2 {
	/**
	 * @param array<string> $a
	 * @throws ProcessFailedException
	 */
	public function __construct(array $a) {}
}

class Process3 {
	/**
	 * @param array<string> $a
	 * @throws void
	 */
	public function __construct(array $a) {}
}

abstract class DbCommand
{
    /**
     * @return int
     */
    public function handle()
    {
        try {
            new Process(
                array_merge([$command = $this->getCommand()])
            );
        } catch (ProcessFailedException $e) {
            echo ("{$command} not found in path.");

            return 1;
        }

        return 0;
    }

    /**
     * @return string
     */
    abstract public function getCommand();
}

abstract class DbCommand2
{
    /**
     * @return int
     */
    public function handle()
    {
        try {
            new Process(
                [$command = $this->getCommand()]
            );
        } catch (ProcessFailedException $e) {
            echo ("{$command} not found in path.");

            return 1;
        }

        return 0;
    }

    /**
     * @return string
     */
    abstract public function getCommand();
}

abstract class DbCommand3
{
    /**
     * @return int
     */
    public function handle()
    {
        try {
            new Process2(
                array_merge([$command = $this->getCommand()])
            );
        } catch (ProcessFailedException $e) {
            echo ("{$command} not found in path.");

            return 1;
        }

        return 0;
    }

    /**
     * @return string
     */
    abstract public function getCommand();
}

abstract class DbCommand4
{
    /**
     * @return int
     */
    public function handle()
    {
        try {
            new Process3(
                array_merge([$command = $this->getCommand()])
            );
        } catch (ProcessFailedException $e) {
            echo ("{$command} not found in path.");

            return 1;
        }

        return 0;
    }

    /**
     * @return string
     */
    abstract public function getCommand();
}
