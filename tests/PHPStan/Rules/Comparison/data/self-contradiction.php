<?php

namespace SelfContradiction;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Scalar;

class AssertStaticCall {
	/**
	 * @phpstan-assert-if-true Scalar|ClassConstFetch|ConstFetch $node
	 */
	private static function isSubjectNode(Expr $node): bool
	{
		return $node instanceof Scalar || $node instanceof ClassConstFetch || $node instanceof ConstFetch;
	}

	/**
	 * @return array{subject: Expr, value: Scalar|ClassConstFetch|ConstFetch}|null
	 */
	private function getSubjectAndValue(Identical $comparison): ?array
	{
		if (self::isSubjectNode($comparison->left) && !self::isSubjectNode($comparison->left)) {
			return ['subject' => $comparison->right, 'value' => $comparison->left];
		}

		if (!self::isSubjectNode($comparison->left) && self::isSubjectNode($comparison->right)) {
			return ['subject' => $comparison->left, 'value' => $comparison->right];
		}

		return null;
	}
}

class AssertMethodCall {
	/**
	 * @phpstan-assert-if-true Scalar|ClassConstFetch|ConstFetch $node
	 */
	private function isSubjectNode(Expr $node): bool
	{
		return $node instanceof Scalar || $node instanceof ClassConstFetch || $node instanceof ConstFetch;
	}

	/**
	 * @return array{subject: Expr, value: Scalar|ClassConstFetch|ConstFetch}|null
	 */
	private function getSubjectAndValue(Identical $comparison): ?array
	{
		if ($this->isSubjectNode($comparison->left) && !$this->isSubjectNode($comparison->left)) {
			return ['subject' => $comparison->right, 'value' => $comparison->left];
		}

		if (!$this->isSubjectNode($comparison->left) && $this->isSubjectNode($comparison->right)) {
			return ['subject' => $comparison->left, 'value' => $comparison->right];
		}

		return null;
	}
}

class AssertStaticConditionalReturn {
	/**
	 * @return ($node is Scalar|ClassConstFetch|ConstFetch ? true : false)
	 */
	private static function isSubjectNode(Expr $node): bool
	{
		return $node instanceof Scalar || $node instanceof ClassConstFetch || $node instanceof ConstFetch;
	}

	/**
	 * @return array{subject: Expr, value: Scalar|ClassConstFetch|ConstFetch}|null
	 */
	private function getSubjectAndValue(Identical $comparison): ?array
	{
		if (self::isSubjectNode($comparison->left) && !self::isSubjectNode($comparison->left)) {
			return ['subject' => $comparison->right, 'value' => $comparison->left];
		}

		if (!self::isSubjectNode($comparison->left) && self::isSubjectNode($comparison->right)) {
			return ['subject' => $comparison->left, 'value' => $comparison->right];
		}

		return null;
	}
}

class AssertInstanceConditionalReturn {
	/**
	 * @return ($node is Scalar|ClassConstFetch|ConstFetch ? true : false)
	 */
	private function isSubjectNode(Expr $node): bool
	{
		return $node instanceof Scalar || $node instanceof ClassConstFetch || $node instanceof ConstFetch;
	}

	/**
	 * @return array{subject: Expr, value: Scalar|ClassConstFetch|ConstFetch}|null
	 */
	private function getSubjectAndValue(Identical $comparison): ?array
	{
		if ($this->isSubjectNode($comparison->left) && !$this->isSubjectNode($comparison->left)) {
			return ['subject' => $comparison->right, 'value' => $comparison->left];
		}

		if (!$this->isSubjectNode($comparison->left) && $this->isSubjectNode($comparison->right)) {
			return ['subject' => $comparison->left, 'value' => $comparison->right];
		}

		return null;
	}
}
