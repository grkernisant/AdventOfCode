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

    public static function parseRaces(array $input): array
    {
        if (count($input) > 2) throw new \Exception("INVALID_INPUT_FORMAT");

        $numbers = array();
        $prefixes = array('Time', 'Distance');
        foreach($prefixes as $l => $p) {
            $regex = sprintf('/^%s:(?:\s+(\d+))+$/', $p);
            preg_match($regex, $input[$l], $matches);
            if ($matches === null)  throw new \Exception("INVALID_INPUT_FORMAT");
            $numbers[] = static::getLineNumbers($input[$l], "{$p}:");
        }

        if (count($numbers[0]) !== count($numbers[1])) throw new \Exception("INVALID_NB_RACES_DATA");

        $races = array();
        $data = array_combine($numbers[0], $numbers[1]);
        foreach($data as $time => $distance) {
            $races[] = new Race(time: $time, distance: $distance);
        }

        return $races;
    }

    public static function getErrorMargin(array $races): int
    {
        return array_reduce(
            $races,
            function(int $acc, Race $curr) {
                return $acc * $curr->ways_to_win;
            },
            1
        );
    }

    private static function getLineNumbers(string $line, string $prefix): array
    {
        $line = trim(substr($line, strlen($prefix)));
        $line = preg_replace('/\s+/', ' ', $line);
        return array_map(fn($x) => (int) $x, explode(' ', $line));
    }

    public function run(): void
    {
        if ($this->runTest()) return;

        $races = static::parseRaces($this->parser->getInput());
        echo sprintf("Part 1: %d\n", static::getErrorMargin($races));
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

class Race
{
    public array $results;
    public int $ways_to_win;

    public function __construct(public int $time, public int $distance)
    {
        $this->ways_to_win = 0;
        $this->solve();
    }

    public function getBoatDistance(int $buttonPressedFor): int
    {
        $bpf = max($buttonPressedFor, 0);
        if ($bpf === 0) return 0;

        $v = min($bpf, $this->time);
        $time_remaining = $this->time - $v;
        if ($time_remaining === 0) return 0;

        return $v * $time_remaining;
    }

    public function solve(): void
    {
        $speed = range(0, $this->time);
        foreach($speed as $s) {
            $d = $this->getBoatDistance($s);
            $this->results[$s] = $d;
            if ($d > $this->distance) $this->ways_to_win++;
        }
    }
}

class Utils
{
    public static function getLineNumbers(string $line): array
    {
        return array_map(fn($str) => (int) $str, explode(' ', trim($line)));
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
        if (!is_readable($path)) throw new Exception("Unreadable input: '$path'");

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
