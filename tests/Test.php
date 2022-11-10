<?php

namespace Tests;

class Test
{
    private const CLI_RED_COLOR = "\e[91m";
    private const CLI_GREEN_COLOR = "\e[92m";
    private const CLI_DEFAULT_COLOR = "\e[39m";

    private static int $testNumber = 1;

    /**
     * Запускает функцию оборачивая ее как тестовую.
     *
     * @param \Closure $test
     * @param string $desc
     * @return void
     */
    public static function run(\Closure $test, string $desc = ''): void
    {
        if (strlen($desc) === 0) {
            $desc = 'Тест ' . static::$testNumber;
        }

        try {
            $test();
            print(': ' . $desc . PHP_EOL);
        } catch (\Throwable $ex) {
            static::failed();
            print(': ' . $desc . PHP_EOL);
            print(<<<TEXT
                Ошибка выполнения кода:
                {$ex->getMessage()}
                Trace:\n
            TEXT);
            foreach ($ex->getTrace() as $stack) {
                $file = (key_exists('file', $stack) ? $stack['file'] : $stack['class']) . ': ';
                $line = key_exists('line', $stack) ? $stack['line'] : '';
                print(<<<TEXT
                        {$file}{$line} [{$stack['function']}]\n
                TEXT);
            }
        }

        static::$testNumber++;
    }

    /**
     * Проверяет, что значение true.
     *
     * @param mixed $val
     * @return void
     */
    public static function assertTrue(mixed $val): void
    {
        $val === true
            ? static::passed()
            : static::failed();
    }

    /**
     * Проверяет, что значение false.
     *
     * @param mixed $val
     * @return void
     */
    public static function assertFalse(mixed $val): void
    {
        $val === false
            ? static::passed()
            : static::failed();
    }

    public static function assertNull(mixed $val): void
    {
        is_null($val)
            ? static::passed()
            : static::failed();
    }

    /**
     * Проверяет, что значения равны.
     *
     * @param mixed $val
     * @return void
     */
    public static function assertEqual(mixed $val1, mixed $val2): void
    {
        $val1 === $val2
            ? static::passed()
            : static::failed();
    }

    /**
     * Проверяет, что значения равны.
     *
     * @param mixed $val
     * @return void
     */
    public static function assertNotEqual(mixed $val1, mixed $val2): void
    {
        $val1 !== $val2
            ? static::passed()
            : static::failed();
    }

    /**
     * Проверяет, что функция завершается с ошибкой.
     *
     * @param \Closure $func
     * @return void
     */
    public static function assertException(\Closure $func): void
    {
        try {
            $func();

            static::failed();
        } catch (\Throwable) {
            static::passed();
        }
    }

    /**
     * Проверяет, что функция завершается без ошибок.
     *
     * @param \Closure $func
     * @return void
     */
    public static function assertNonException(\Closure $func): void
    {
        try {
            $func();

            static::passed();
        } catch (\Throwable $ex) {
            throw $ex;
        }
    }

    /**
     * Проверяет пользовательское условие.
     *
     * @param mixed $val
     * @param \Closure $func
     * @return void
     */
    public static function assertCustom(mixed $val, \Closure $func): void
    {
        $func($val) === true
            ? static::passed()
            : static::failed();
    }

    /**
     * Выводит сообщение, что тест пройден.
     *
     * @return void
     */
    private static function passed(): void
    {
        print(static::CLI_GREEN_COLOR . '[Успешно]' . static::CLI_DEFAULT_COLOR);
    }

    /**
     * Выводит сообщение, что тест не пройден.
     *
     * @return void
     */
    private static function failed(): void
    {
        print(static::CLI_RED_COLOR . '[Провален]' . static::CLI_DEFAULT_COLOR);
    }
}
