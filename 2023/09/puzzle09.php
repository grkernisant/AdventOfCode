<?php

declare(strict_types=1);
error_reporting(E_ALL);

class Main
{
    public const DEFAULT_INPUT = './test';
    public const DEBUG_MODE = '--debug';
    public const TEST_MODE = '--test';

    private bool $test_mode = false;
    private bool $debug_mode = false;
    private array $options;

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
        $path = array_filter($args, fn($arg) => strpos($arg, '--') === false);
        return reset($path) ?: static::DEFAULT_INPUT;
    }

    private function hasOption(string $option): bool {
        return array_search($option, $this->options) !== false;
    }

    private function initLogs(array $names)
    {
        foreach($names as $log) {
            Logger::log($log, '');
        }
    }

    public static function parseReports(array $input): array
    {
        return array_map(
            function(string $in) {
                $result = explode(' ', $in) |> function($arr) {
                    return array_map(fn($e) => (int) $e, $arr);
                };
                return new ReportLine($result);
            },
            $input
        );
    }

    public static function getReportLinesNextNumbersSum(array $reports): int
    {
        return array_reduce(
            $reports,
            function(int $acc, ReportLine $curr) {
                $acc+= $curr->next_number;
                return $acc;
            },
            0
        );
    }

    public function run(): void
    {
        if ($this->runTest()) return;

        $reports = static::parseReports($this->parser->getInput());
        echo sprintf("Part 1: %d\n", static::getReportLinesNextNumbersSum($reports));
    }

    private function runTest(): bool
    {
        return defined('TEST_MODE') && TEST_MODE === 'TEST_MODE';
    }

    private function setOptions(array $args): void
    {
        $this->options = $args;
        $this->debug_mode = $this->hasOption(static::DEBUG_MODE);
        $this->test_mode = $this->hasOption(static::TEST_MODE);
    }

}

class ReportLine
{
    public int $next_number;

    public function __construct(public array $numbers)
    {
        $this->setNextNumber();
    }

    private function setNextNumber()
    {
        // difference until 0
        $diff = array(array(...$this->numbers));
        while(!static::arrayContainsOnlyValue(end($diff))) {
            $diff[] = static::arrayDifference(end($diff));
        }

        // prediction
        $next_number = 0;
        $l = count($diff);
        $i = $l - 1;
        while ($i >= 0) {
            $next_number+= end($diff[$i]);
            $i--;
        }
        
        $this->next_number = $next_number;
    }

    public static function arrayContainsOnlyValue(array $arr, int $value = 0): bool
    {
        if (count($arr) === 0) return false;

        $different_value = array_find($arr, fn($a) => $a !== $value);
        return $different_value === null;
    }

    public static function arrayDifference(array $arr): array
    {
        if (count($arr) < 2) throw new \Exception('Insufficient values to calculate array difference');

        $diff = array();
        $i = 0;
        $l = count($arr);
        while($i <= $l - 2) {
            $diff[] = $arr[$i+1] - $arr[$i];
            $i++;
        }

        return $diff;
    }
}

class ColoredOutput
{
    private static $colors = [
        'reset'   => "\033[0m",   // Reset to default
        'red'     => "\033[31m",
        'green'   => "\033[32m",
        'yellow'  => "\033[33m",
        'blue'    => "\033[34m",
        'magenta' => "\033[35m",
        'cyan'    => "\033[36m",
        'white'   => "\033[37m",
        'bold'    => "\033[1m"
    ];

    // Function to print colored text
    static public function paintText(string $text, string $color): string {
        if(!isset(static::$colors[$color])) return $text;

        return sprintf(
            "%s%s%s",
            static::$colors[$color],
            $text,
            static::$colors['reset']
        );
    }
}

class Parser
{
    public array $input;

    public function __construct(public string $path = './test')
    {
        if (!is_readable($path)) throw new \Exception("Unreadable input: '$path'");

        $this->input = file($this->path, FILE_IGNORE_NEW_LINES);
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
