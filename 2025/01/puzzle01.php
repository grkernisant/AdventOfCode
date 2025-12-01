<?php

declare(strict_types=1);
error_reporting(E_ALL);

class Main
{
    const int LOCK_START = 50;
    const int LOCK_MIN = 0;
    const int LOCK_MAX = 99;

    private array $instructions;
    private Parser $parser;
    private Lock $lock;

    public function __construct(public string $path)
    {
        $this->path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        Logger::$logger = $this->path . '.log';

        $this->lock = new Lock(static::LOCK_START, static::LOCK_MIN, static::LOCK_MAX);
    }

    public function run(): void
    {
        $this->parseInstructions($this->parser->getInput());
        $this->rotate();

        echo sprintf("The lock's dial has crossed '0' {$this->lock->zero_count} times"), PHP_EOL;
    }

    private function parseInstructions(array $instructions)
    {
        $this->instructions = array_map(function ($i) {
            $i = trim($i);
            if (preg_match(Instruction::REGEX, $i, $matches) !== 1)  return null;

            return new Instruction(Instruction::DirectionFrom($matches[1]), (int) $matches[2]);
        }, $instructions);
    }

    private function rotate()
    {
        foreach($this->instructions as $i) {
            $this->lock->rotate($i);
        }
    }
}

class Parser
{
    public array $input;

    public function __construct(public string $path)
    {
        if (!is_readable($path)) throw new Exception('Unreadable input');

        $this->input = file($path);
    }

    public function getInput(): array { return $this->input; }
}

enum Direction {
    case L;
    case R;
}

class Instruction
{
    const string REGEX = '/^(L|R)(\d+)$/';

    public function __construct(public Direction $direction, public Int $rotation) {}

    public function __toString(): string
    {
        return ($this->direction === Direction::L ? 'L' : 'R') . (string) $this->rotation;
    }

    public static function DirectionFrom(string $direction): Direction
    {
        if ($direction === 'L') return Direction::L;

        if ($direction === 'R') return Direction::R;

        throw new Error("Invalid direction $direction");
    }
}

class Lock
{
    public int $zero_count = 0;

    public function __construct(public int $dial, public $min = 0, public $max = 99) {}

    public function rotate(Instruction $i)
    {
        $incr = $i->direction === Direction::R ? 1 : -1;
        $this->dial = ($this->max + 1 + $this->dial + ($incr * $i->rotation)) % ($this->max + 1);
        $this->zero_count += ($this->dial === 0) ? 1 : 0;
        // echo sprintf("The dial is rotated $i to point at $this->dial"), PHP_EOL;
    }
}

class Logger
{
    static public ?string $logger = null;

    static public function log(string $message, $content, bool $append = false)
    {
        $debug = str_replace('    ', ' ', print_r($content, true));
        $filepath = trim(sprintf('%s-%s', $message, static::$logger ?? ''));
        if ($append) {
            file_put_contents($filepath, $debug . PHP_EOL, FILE_APPEND | LOCK_EX);
            return;
        }

        file_put_contents($filepath, $debug);
    }
}

try {
    $default = './test';
    $path = isset($argv[1]) ? $argv[1] : $default;

    $main = new Main($path);
    $main->run();
} catch (Throwable $e) {
    die(sprintf('Error (%d): %s%s%s', $e->getLine(), $e->getMessage(), PHP_EOL, $e->getTraceAsString()));
}
