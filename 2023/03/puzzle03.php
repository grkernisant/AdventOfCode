<?php

declare(strict_types=1);
error_reporting(E_ALL);

class Main
{
    const string DEFAULT_INPUT = './test';
    const string DEBUG_MODE = '--debug';
    const string TEST_MODE = '--test';

    private bool $test_mode = false;
    private bool $debug_mode = false;
    private array $options;

    private Parser $parser;
    private array $part_numbers;

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

    private function getAdjacentCells(array $grid, int $x, int $y, int $number, bool $is_assoc): array
    {
        $adjacent = [];
        $rows = count($grid);
        $cols = strlen($grid[0]);

        $x_min = max(0, $x - 1);
        $x_max = min($cols - 1, $x + strlen((string)$number));
        $range = range($x_min, $x_max);

        // row above
        $y_above = $y - 1;
        if ($y_above >= 0) {
            if ($is_assoc) {
                foreach($range as $rx) {
                    $adjacent["$y_above,$rx"] = $grid[$y_above][$rx];
                }
            } else {
                $row_above = substr($grid[$y_above], $x_min, $x_max - $x_min + 1);
                array_splice($adjacent, count($adjacent), 0, str_split($row_above));
            }
        }

        // current row
        if ($x_min >=0 && !is_numeric($grid[$y][$x_min])) {
            if ($is_assoc) {
                $adjacent["$y,$x_min"] = $grid[$y][$x_min];
            } else {
                $adjacent[] = $grid[$y][$x_min];
            }
        }
        if ($x_max <= ($cols - 1) && !is_numeric($grid[$y][$x_max])) {
            if ($is_assoc) {
                $adjacent["$y,$x_max"] = $grid[$y][$x_max];
            } else {
                $adjacent[] = $grid[$y][$x_max];
            }
        }

        // row below
        $y_below = $y + 1;
        if ($y_below < $rows) {
            if ($is_assoc) {
                reset($range);
                foreach($range as $rx) {
                    $adjacent["$y_below,$rx"] = $grid[$y_below][$rx];
                }
            } else {
                $row_below = substr($grid[$y_below], $x_min, $x_max - $x_min + 1);
                array_splice($adjacent, count($adjacent), 0, str_split($row_below));
            }
        }

        return $adjacent;
    }
    
    private function getValidPartNumbersSum(array $part_numbers): int
    {
        return array_reduce(
            $part_numbers,
            fn($sum, $part_number) => $sum + ($part_number->is_valid ? $part_number->part_number : 0),
            0
        );
    }

    private function hasPartNumberSymbol(array $adjacent): array
    {
        if (empty($adjacent)) return array();

        return array_filter($adjacent, fn($cell) => $cell !== '.' && !is_numeric($cell));
    }

    private function parseNumbers(array $input): array
    {
        $numbers = [];
        foreach ($input as $y => $line) {
            $parsed_line = trim($line);
            if (empty($line)) continue;

            preg_match_all("/\d+/", $parsed_line, $matches, PREG_OFFSET_CAPTURE);
            if (isset($matches[0]) && !empty($matches[0])) {
                $repl = array();
                foreach($matches[0] as $match) {
                    $number = (int) $match[0];
                    $position = (int) $match[1];
                    $adjacent = $this->getAdjacentCells($input, $position, $y, $number, true);
                    $numbers[] = new PartNumber(part_number: $number, adjacent: $adjacent);
                    $clr = end($numbers)->is_valid ? "green" : "red";
                    $repl[] = (object) array(
                        'find' => $number,
                        'repl' => ColoredOutput::paintText((string) $number, $clr),
                        'pos' => $position
                    );
                }
                usort($repl, fn($a, $b) => $b->pos - $a->pos);
                foreach ($repl as $r) {
                    $before = $r->pos > 0 ? substr($line, 0, $r->pos) : "";
                    $after = substr($parsed_line, $r->pos + strlen((string) $r->find));
                    $parsed_line = sprintf("%s%s%s", $before, $r->repl, $after);
                }
                Logger::log('std_err', $parsed_line);
            }
        }

        return $numbers;
    }

    public function run(): void
    {
        if ($this->test_mode) $this->runTest();

        $this->part_numbers = $this->parseNumbers($this->parser->getInput());
        echo sprintf("Sum of valid part numbers: %d", $this->getValidPartNumbersSum($this->part_numbers)), PHP_EOL;
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

    private function setOptions(array $args): void
    {
        $this->options = $args;
        $this->debug_mode = $this->hasOption(static::DEBUG_MODE);
        $this->test_mode = $this->hasOption(static::TEST_MODE);
    }
}

class PartNumber {
    public bool $is_valid;
    public array $symbols;

    public function __construct(public int $part_number, array $adjacent = []) {
        $this->symbols = $this->hasPartNumberSymbol($adjacent);
        $this->is_valid = !empty($this->symbols);
    }

    private function hasPartNumberSymbol(array $adjacent): array
    {
        if (empty($adjacent)) return array();

        return array_filter($adjacent, fn($cell) => $cell !== '.' && !is_numeric($cell));
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
