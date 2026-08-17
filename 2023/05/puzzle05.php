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
    private array $almanac;
    private array $maps;

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

    public function run(): void
    {
        if ($this->runTest()) return;

        $almanac = Almanac::factorize($this->parser->getInput());
        echo sprintf("Part 1: %d", $almanac->getLowestLocationNumber()), PHP_EOL;
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

class Almanac
{
    private static $REGEX = array(
        'seeds' => "/^seeds:(?:\s+(\d+))+$/"
    );

    private function __construct(
        public array $seeds,
        public array $transforms,
        public array $maps
    ) {}

    public function convertTo(int $id, string $src_type = 'seed', string $dst_type = 'location'): int
    {
        if (!isset($this->maps[$src_type])) throw new \Error(sprintf("Source type: %s does not exist", $src_type));

        $next_id = null;
        $found = false;
        $nb = count($this->maps[$src_type]);
        $i = 0;
        while($i < $nb && !$found) {
            $map = $this->maps[$src_type][$i];
            $gte = $id >= $map->src_range[0];
            $lte = $id <= $map->src_range[1];
            $found = $gte && $lte;
            if (!$found) $i++;
        }

        $next_type = $this->transforms[$src_type] ?? $this->convertError($id, $src_type, $dst_type);;

        if ($found) {
            $map = $this->maps[$src_type][$i];
            $offset = $id - $map->src_range[0];
            $next_id = $map->dst_range[0] + $offset;
        } else {
            $next_id = $id;
        }

        if ($next_id === null) $this->convertError($id, $src_type, $next_type);

        if ($next_type === $dst_type) return $next_id;

        return $this->convertTo($next_id, $next_type, $dst_type);
    }

    private function convertError(int $id, string $src_type, string $dst_type)
    {
        throw new \Error(sprintf("Cannot convert %d from %s to %s", $id, $src_type, $dst_type));
    }

    public static function factorize(array $input): Almanac
    {
        if (is_array($input) && isset($input[0])) {
            preg_match(static::$REGEX['seeds'], $input[0], $matches);
            if ($matches !== null) {
                $seeds = Utils::getLineNumbers(substr($input[0], 6));

                $range = range(2, count($input)-1);
                $src_type = $dst_type = "";
                $maps = $transforms = array();
                foreach($range as $r) {
                    $line = trim($input[$r]);
                    if (empty($line)) continue;

                    if (preg_match(RangeMap::$REGEX_TYPES, $line, $matches)) {
                        $src_type = $matches[1];
                        $dst_type = $matches[2];
                        $transforms[$src_type] = $dst_type;
                    }

                    if (preg_match(RangeMap::$REGEX_BOUNDARIES, $line, $matches)) {
                        if (!isset($maps[$src_type])) $maps[$src_type] = array();
                        $boundaries = Utils::getLineNumbers($line);
                        if (count($boundaries) === 3) {
                            $dst_min = $boundaries[0];
                            $src_min = $boundaries[1];
                            $range = $boundaries[2];

                            $maps[$src_type][] = new RangeMap(
                                src_type: $src_type,
                                src_range: array($src_min, $src_min + $range  - 1),
                                dst_type: $dst_type,
                                dst_range: array($dst_min, $dst_min + $range - 1)
                            );
                        }
                    }
                }
            }

            return new Almanac($seeds, $transforms, $maps);
        }

        throw new \Error('Input should be an array of strings');
    }

    public function getLowestLocationNumber(): ?int
    {
        $lowest = null;
        foreach($this->seeds as $s) {
            $loc = $this->convertTo($s, 'seed', 'location');
            $lowest = $lowest !== null
                ? min($loc, $lowest) 
                : $loc;
        }

        return $lowest;
    }
}

class RangeMap
{
    public static $REGEX_TYPES = '/^(\w+)-to-(\w+) map:$/';
    public static $REGEX_BOUNDARIES = '/^[0-9\ ]+$/';

    public function __construct(
        public string $src_type,
        public array $src_range,
        public string $dst_type,
        public array $dst_range,
    ) {}
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
