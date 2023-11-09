<?php // lint >= 8.0

namespace NoncapturingCatch;

class HelloWorld
{

	public function hello(): void
	{
		try {
            throw new \Exception('Hello');
        } catch (\Exception) {
            echo 'Hi!';
        }
	}

}
