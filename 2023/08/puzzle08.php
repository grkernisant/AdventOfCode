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

    public function run(): void
    {
        if ($this->runTest()) return;

        $dm = DesertMap::fromStringArray($this->parser->getInput());
        echo sprintf("Part 1: %d\n", $dm->run());
        echo sprintf("Part 2: %d\n", $dm->run(RunMode::CONCURRENT));
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

class Node
{
    public object $nodes;

    public function __construct(public string $name, ?object $nodes = null)
    {
        $this->nodes = $nodes ?? (object) [];
    }
}

class DesertMap
{
    public const END_NODE = 'ZZZ';
    public const END_WITH = 'Z';
    public const START_NODE = 'AAA';
    public const START_WITH = 'A';

    public function __construct(public array $instructions, public array $nodes_map) {}

    private function filterNodeEndWith(array $nodes,string $letter): array
    {
        return array_filter(
            $nodes,
            fn($node) => str_ends_with($node->name, $letter)
        );
    }

    public static function fromStringArray(array $input): DesertMap
    {
        $first_line = trim(array_shift($input));
        if (preg_match('/^[RL]+$/', $first_line) === 0) {
            throw new \Exception("Invalid instructions format: '$first_line'");
        }

        $instructions = array_map(fn($char) => Direction::from($char), str_split($first_line));
        $nodes_map = [];
        $charset = '([A-Z0-9]{3})';
        $regex = sprintf('/^%s = \(%s, %s\)$/', $charset, $charset, $charset);
        foreach ($input as $line) {
            if (trim($line) === '') continue;

            if (preg_match($regex, $line, $matches)) {
                $name = $matches[1];
                $left = $matches[2];
                $right = $matches[3];

                $nodes_map[$name] = new Node($name, (object)[
                    'LEFT' => $left,
                    'RIGHT' => $right
                ]);
            } else {
                throw new \Exception("Invalid node format: '$line'");
            }
        }

        return new DesertMap($instructions, $nodes_map);
    }

    private function getNode(string $name): Node
    {
        if (!isset($this->nodes_map[$name])) {
            throw new \Exception("Node not found: '$name'");
        }

        return $this->nodes_map[$name];
    }

    public function run(RunMode $mode = RunMode::LINEAR): int
    {
        $this->validateNodes($mode);

        return match ($mode) {
            RunMode::LINEAR => $this->runLinear(),
            RunMode::CONCURRENT => $this->runConcurrent(),
        };
    }

    // finds the greatest common divisor of two numbers
    private function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }
        return $a;
    }

    // finds the least common multiple of two numbers
    private function lcm(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return (int) (($a / $this->gcd($a, $b)) * $b);
    }

    private function runConcurrent(): int
    {
        $start_nodes = $this->filterNodeEndWith($this->nodes_map, self::START_WITH);
        $steps_list = [];

        foreach ($start_nodes as $start_node) {
            $current_node = $start_node;
            $steps = 0;
            $nb_instructions = count($this->instructions);

            while (!str_ends_with($current_node->name, self::END_WITH)) {
                $instruction = $this->instructions[$steps % $nb_instructions];
                $next_node_name = $current_node->nodes->{$instruction->name};
                $current_node = $this->getNode($next_node_name);
                $steps++;
            }
            $steps_list[] = $steps;
        }

        $lcm = array_shift($steps_list);
        foreach ($steps_list as $steps) {
            $lcm = $this->lcm($lcm, $steps);
        }

        return $lcm;
    }

    private function runLinear(): int
    {
        $found = false;
        $current_iteration = 0;
        $nb_instructions = count($this->instructions);
        $current_node = $this->getNode(self::START_NODE);
        while (!$found) {
            $instruction = $this->instructions[$current_iteration % $nb_instructions];
            $next_node_name = $current_node->nodes->{$instruction->name};
            $current_node = $this->getNode($next_node_name);
            $found = $current_node->name === self::END_NODE;
            $current_iteration++;
        }

        return $current_iteration;
    }

    private function validateNodes(RunMode $mode = RunMode::LINEAR): void
    {
        if (empty($this->instructions)) {
            throw new \Exception("Instructions are empty");
        }

        if (empty($this->nodes_map)) {
            throw new \Exception("Nodes map is empty");
        }

        if ($mode === RunMode::LINEAR) {
            $this->nodes_map[self::START_NODE] ?? throw new \Exception("Missing start node: " . self::START_NODE);
            $this->nodes_map[self::END_NODE] ?? throw new \Exception("Missing end node: " . self::END_NODE);            
        } else {
            $start_nodes = $this->filterNodeEndWith($this->nodes_map, 'A');
            if (count($start_nodes) === 0) {
                throw new \Exception("Concurrent mode requires exactly at least one start node ending with 'A'");
            }

            $end_nodes = $this->filterNodeEndWith($this->nodes_map, 'Z');
            if (count($end_nodes) < count($start_nodes)) {
                throw new \Exception("Concurrent mode requires at least as many end nodes as start nodes");
            }
        }
    }
}

enum Direction: string
{
    case LEFT = 'L';
    case RIGHT = 'R';
}

enum RunMode: string
{
    case LINEAR = 'linear';
    case CONCURRENT = 'concurrent';
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
