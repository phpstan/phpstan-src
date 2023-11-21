<?php declare(strict_types = 1);

namespace InvalidLexicalVariablesInClosureUse;

class HelloWorld
{

    /**
     * @return \Closure(): string
     */
    public function foo(): \Closure
    {
        $message = 'hello';

        return function () use ($message): string {
            return $message;
        };
    }

    /**
     * @return \Closure(): string
     */
    public function this(): \Closure
    {
        return function () use ($this): string {
            return ($this->foo())();
        };
    }

    /**
     * @return \Closure(): array<string, string>
     */
    public function superglobals(): \Closure
    {
        return function () use ($GLOBALS, $_COOKIE, $_ENV, $_FILES, $_GET, $_POST, $_REQUEST, $_SERVER, $_SESSION): array {
            return array_merge(
                $GLOBALS,
                $_COOKIE,
                $_ENV,
                $_FILES,
                $_GET,
                $_POST,
                $_REQUEST,
                $_SERVER,
                $_SESSION,
            );
        };
    }

    /**
     * @return \Closure(int, string, bool): bool
     */
    public function sameAsParameter(): \Closure
    {
        return function (int $foo, string $bar, bool $baz) use ($baz): bool {
            return $baz;
        };
    }

    /**
     * @return \Closure(): string
     */
    public function multilineThis(): \Closure
    {
        $message = 'hello';

        return function () use (
            $this,
            $message
        ): string {
            return ($this->foo())() . $message;
        };
    }

    /**
     * @return \Closure(): array<string, string>
     */
    public function multilineSuperglobals(): \Closure
    {
        return function () use (
            $GLOBALS,
            $_COOKIE,
            $_ENV,
            $_FILES,
            $_GET,
            $_POST,
            $_REQUEST,
            $_SERVER,
            $_SESSION
        ): array {
            return array_merge(
                $GLOBALS,
                $_COOKIE,
                $_ENV,
                $_FILES,
                $_GET,
                $_POST,
                $_REQUEST,
                $_SERVER,
                $_SESSION,
            );
        };
    }

    /**
     * @return \Closure(int, string, bool): bool
     */
    public function multilineSameAsParameter(): \Closure
    {
        return function (int $foo, string $bar, bool $baz) use (
            $baz,
            $bar,
        ): bool {
            return (bool) ($baz . $bar);
        };
    }

}
