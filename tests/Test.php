<?php

namespace Tests;

class Test
{
    private const CLI_RED_COLOR = "\e[91m";
    private const CLI_GREEN_COLOR = "\e[92m";
    private const CLI_BLUE_COLOR = "\e[34m";
    private const CLI_DEFAULT_COLOR = "\e[39m";

    private const DEFAULT_SEPARATOR_LEN = 50;
    private const INFO_PREFIX = '[INFO] ';

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
            $desc = 'Тест ' . self::$testNumber;
        }

        try {
            $test();
            print(': ' . $desc . PHP_EOL);
        } catch (\Throwable $ex) {
            self::failed();
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

        self::$testNumber++;
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
            ? self::passed()
            : self::failed();
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
            ? self::passed()
            : self::failed();
    }

    public static function assertNull(mixed $val): void
    {
        is_null($val)
            ? self::passed()
            : self::failed();
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
            ? self::passed()
            : self::failed();
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
            ? self::passed()
            : self::failed();
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

            self::failed();
        } catch (\Throwable) {
            self::passed();
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

            self::passed();
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
            ? self::passed()
            : self::failed();
    }

    /**
     * Выводит сообщение, что тест пройден.
     *
     * @return void
     */
    private static function passed(): void
    {
        print(self::CLI_GREEN_COLOR . '[Успешно]' . self::CLI_DEFAULT_COLOR);
    }

    /**
     * Выводит сообщение, что тест не пройден.
     *
     * @return void
     */
    private static function failed(): void
    {
        print(self::CLI_RED_COLOR . '[Провален]' . self::CLI_DEFAULT_COLOR);
    }

    private static function printSeparator(string $color = self::CLI_BLUE_COLOR, int $length = self::DEFAULT_SEPARATOR_LEN): void
    {
        print($color . str_repeat('-', $length) . self::CLI_DEFAULT_COLOR . PHP_EOL);
    }

    /**
     * Выводит информационное сообщение.
     *
     * @param string $str
     * @return void
     */
    public static function printInfo(string $str): void
    {
        $message = self::INFO_PREFIX . $str;
        $separatorLength = strlen($message) > self::DEFAULT_SEPARATOR_LEN
            ? strlen($message)
            : self::DEFAULT_SEPARATOR_LEN;

        self::printSeparator(length: $separatorLength);
        print(self::CLI_BLUE_COLOR . $message . self::CLI_DEFAULT_COLOR . PHP_EOL);
        self::printSeparator(length: $separatorLength);
    }
}
