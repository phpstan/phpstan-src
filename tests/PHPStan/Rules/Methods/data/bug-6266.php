<?php

namespace Bug6266;

class AbstractASTClassOrInterface
{
}

class ASTInterface extends AbstractASTClassOrInterface
{
}

class HelloWorld
{
	/**
	 * @template T of AbstractASTClassOrInterface
	 * @param T $classOrInterface
	 * @return T
	 */
	protected function parseTypeBody(AbstractASTClassOrInterface $classOrInterface)
	{
		if ($classOrInterface instanceof ASTInterface) {
			echo "It's ASTInterface";
		}
		return $classOrInterface;
	}
}
