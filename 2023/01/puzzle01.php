<?php

declare(strict_types=1);
error_reporting(E_ALL);

class Main
{
    private static $NUMBERS_AS_STRINGS = array(
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
            'seven' => 7,
            'eight' => 8,
            'nine' => 9,
        );
    const string DEFAULT_INPUT = './test';
    const string DEBUG_MODE = '--debug';
    const string TEST_MODE = '--test';

    private bool $test_mode = false;
    private bool $debug_mode = false;

    private Parser $parser;

    public function __construct(array $args)
    {
        $path = $this->getPath($args);
        $this->setOptions($args);
        Logger::$should_log = $this->debug_mode;

        $path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        Logger::$logger = $path . '.log';
    }

    private function getPath(array $args): string
    {
        $path = array_filter($args, fn($arg) => strpos(haystack: $arg, needle: '--') === false);
        return reset($path) ?: static::DEFAULT_INPUT;
    }

    public function run(): void
    {
        if ($this->test_mode) $this->runTest();

        $digits = $this->parseCalibration($this->parser->getInput());
        echo sprintf("Step 1 - The entire document calibration is %d", array_sum($digits)), PHP_EOL;

        $adjustedDigits = $this->parseAdjustedCalibration($this->parser->getInput());
        echo sprintf("Step 2 - The adjusted document calibration is %d", array_sum($adjustedDigits)), PHP_EOL;
    }

    public function runTest(): void
    {
        $test_passed = 0;

        $tests_delimiter = '----------    TESTS    ----------';
        $tests_result = ($test_passed === 6) ? 'Success' : 'Failed';
        $tests_output = sprintf("Passed %d out of %d tests (%s)", $test_passed, 6, $tests_result);
        Logger::log('tests-all', $tests_output);
        echo
            $tests_delimiter, PHP_EOL,
            $tests_output, PHP_EOL,
            $tests_delimiter, str_repeat(PHP_EOL, 2);
    }

    private function parseCalibration(array $instructions): array
    {
        $calibrations = array_map(function ($i) {
            $digits = array();
            $i = trim($i);
            if (preg_match("/(\\d)/", $i, $matches)) {
                $digits[] = $matches[1];
            }

            if (preg_match("/(\\d)/", strrev($i), $matches)) {
                $digits[] = $matches[1];
            }

            return count($digits) === 2
                ? (int) "{$digits[0]}{$digits[1]}"
                : 0;
        }, $instructions);

        return array_filter($calibrations, fn($c) => $c >= 10 && $c <= 99);
    }

    private function parseAdjustedCalibration(array $instructions): array
    {
        $valid_digits = array_merge(
            array_keys(static::$NUMBERS_AS_STRINGS),
            array_values(static::$NUMBERS_AS_STRINGS)
        );
        $reversed_valid_digits = array_map(fn($d) => strrev((string) $d), $valid_digits);
        $regStart = sprintf("/(%s)/", implode('|', $valid_digits));
        $regEnd = sprintf("/(%s)/", implode('|', $reversed_valid_digits));
        $self = $this;
        $calibrations = array_map(function ($i) use ($regStart, $regEnd, $self) {
            $digits = array();
            $i = trim($i);
            if (preg_match($regStart, $i, $matches)) {
                $digits[] = $matches[1];
            }

            if (preg_match($regEnd, strrev($i), $matches)) {
                $digits[] = $matches[1];
            }

            return count($digits) === 2
                ? (int) "{$self->parseValidDigit($digits[0])}{$self->parseValidDigit($digits[1])}"
                : 0;
        }, $instructions);

        return array_filter($calibrations, fn($c) => $c >= 10 && $c <= 99);
    }

    private function parseValidDigit(string $d): int {
        return static::$NUMBERS_AS_STRINGS[(string) $d]
            ?? static::$NUMBERS_AS_STRINGS[strrev((string) $d)]
            ?? (int) $d;
    }

    private function setOptions(array $args): void
    {
        $this->debug_mode = (array_search(haystack: $args, needle: static::DEBUG_MODE) !== false);
        $this->test_mode = (array_search(haystack: $args, needle: static::TEST_MODE) !== false);
    }
}

class Parser
{
    public array $input;

    public function __construct(public string $path)
    {
        if (!is_readable($path)) throw new Exception("Unreadable input: '$path'");

        $this->input = file($path);
    }

    public function getInput(): array { return $this->input; }
}

class Logger
{
    static public ?string $logger = null;
    static public bool $should_log = true;

    static public function log(string $message, mixed $content, bool $append = false)
    {
        if (!static::$should_log) return;

        $debug = str_replace('    ', ' ', print_r($content, true));
        $filepath = trim(sprintf('%s-%s', $message, static::$logger ?? ''), '- ');
        if ($append) {
            file_put_contents($filepath, $debug . PHP_EOL, FILE_APPEND | LOCK_EX);
            return;
        }

        file_put_contents($filepath, $debug);
    }

    static public function sudoLog(string $message, mixed $content, bool $append = false)
    {
        $previous_log_state = static::$should_log;
        static::$should_log = true;
        static::log($message, $content, $append);
        static::$should_log = $previous_log_state;
    }
}

try {
    $default = Main::DEFAULT_INPUT;
    $args = isset($argv[1]) ? array_slice($argv, 1) : array($default);

    $main = new Main($args);
    $main->run();
} catch (Throwable $e) {
    die(sprintf('Error (%d): %s%s%s', $e->getLine(), $e->getMessage(), PHP_EOL, $e->getTraceAsString()));
}
