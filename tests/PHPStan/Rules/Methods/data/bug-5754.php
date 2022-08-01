<?php declare(strict_types = 1);

namespace Bug5754;

use RuntimeException;

interface Queue {}
class SnsQsQueue implements Queue {}
class SqsQueue implements Queue {}

interface Context {
	public function createQueue(string $name): Queue;
}

class SnsQsContext implements Context {
	public function createQueue(string $name): SnsQsQueue {
		return new SnsQsQueue;
	}
	public function declareQueue(SnsQsQueue $name): void {}
}

class SqsContext implements Context {
	public function createQueue(string $name): SqsQueue {
		return new SqsQueue;
	}
	public function declareQueue(SqsQueue $name): void {}
}


class Success
{
    /**
     * @var SnsQsQueue|SqsQueue
     */
    private $queue;

    public function __construct(Context $context)
    {
        if ($context instanceof SnsQsContext) {
            $this->queue = $context->createQueue('test');
	        $context->declareQueue($this->queue);
        } elseif ($context instanceof SqsContext) {
            $this->queue = $context->createQueue('test');
	        $context->declareQueue($this->queue);
        } else {
            throw new RuntimeException('nope');
        }
    }
}

/** not working **/
class Fail
{
    /**
     * @var SnsQsQueue|SqsQueue
     */
    private $queue;

    public function __construct(Context $context)
    {
        if ($context instanceof SnsQsContext) {
            $this->queue = $context->createQueue('test');
        } elseif ($context instanceof SqsContext) {
            $this->queue = $context->createQueue('test');
        } else {
            throw new RuntimeException('nope');
        }
        $context->declareQueue($this->queue);
    }
}
