<?php

declare(strict_types=1);
error_reporting(E_ALL);

class Main
{
    const string DEFAULT_INPUT = './test';
    const string DEBUG_MODE = '--debug';
    const string TEST_MODE = '--test';

    const int LOCK_START = 50;
    const int LOCK_MIN = 0;
    const int LOCK_MAX = 99;

    private bool $test_mode = false;
    private bool $debug_mode = false;

    private array $instructions;
    private Parser $parser;
    private Lock $lock;

    public function __construct(array $args)
    {
        $path = $this->getPath($args);
        $this->setOptions($args);
        Logger::$should_log = $this->debug_mode;

        $path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        Logger::$logger = $path . '.log';

        $this->lock = new Lock(static::LOCK_START, static::LOCK_MIN, static::LOCK_MAX);
    }

    private function getPath(array $args): string
    {
        $path = array_filter($args, fn($arg) => strpos(haystack: $arg, needle: '--') === false);
        return reset($path) ?: static::DEFAULT_INPUT;
    }

    public function run(): void
    {
        if ($this->test_mode) $this->runTest();

        if ($this->debug_mode) Logger::log('lock-rotatation', ''); // clears log before appending
        $this->instructions = $this->parseInstructions($this->parser->getInput());
        $this->rotate();

        echo sprintf("Step 1 - The lock's password is {$this->lock->getPwd(Lock::PWD_INSECURE)}"), PHP_EOL;
        echo sprintf("Step 2 - The lock's password is {$this->lock->getPwd(Lock::PWD_SECURE_4B)}"), PHP_EOL;
    }

    public function runTest(): void
    {
        $test_passed = 0;

        // 11 + R8 = 19
        $lock_11a = new Lock(11, static::LOCK_MIN, static::LOCK_MAX);
        $this->rotate($lock_11a, array(new Instruction(Direction::R, 8)));
        if ($lock_11a->dial === 19) { $test_passed++; }
        else {
            Logger::sudoLog('test_lock-11-R8_eq_19', sprintf("Expected 11 + R8 = 19, got %d", $lock_11a->dial));
        }

        // 11 + R8 + L19 = 0
        // 11 + R8 = 19
        $lock_11b = new Lock(11, static::LOCK_MIN, static::LOCK_MAX);
        $this->rotate($lock_11b, array(
            new Instruction(Direction::R, 8),
            new Instruction(Direction::L, 19)
        ));
        if ($lock_11b->dial === 0) { $test_passed++; }
        else {
            Logger::sudoLog('test_lock-11-R8-L19_eq_0', sprintf("Expected 11 + R8 + L19 = 0, got %d", $lock_11b->dial));
        }

        // 0 + L1 = 99
        $lock_0a = new Lock(0, static::LOCK_MIN, static::LOCK_MAX);
        $this->rotate($lock_0a, array(new Instruction(Direction::L, 1)));
        if ($lock_0a->dial === 99) { $test_passed++; }
        else {
            Logger::sudoLog('test_lock-0-L1_eq_99', sprintf("Expected 0 + L1 = 99, got %d", $lock_0a->dial));
        }

        // 0 + L1 + R1 = 0
        $lock_0b = new Lock(0, static::LOCK_MIN, static::LOCK_MAX);
        $this->rotate($lock_0b, array(
            new Instruction(Direction::L, 1),
            new Instruction(Direction::R, 1),
        ));
        if ($lock_0b->dial === 0) { $test_passed++; }
        else {
            Logger::sudoLog('test_lock-0-L1-R1_eq_99', sprintf("Expected 0 + L1+ R1 = 0, got %d", $lock_0b->dial));
        }

        // 5 + L10 = 95
        $lock_5a = new Lock(5, static::LOCK_MIN, static::LOCK_MAX);
        $this->rotate($lock_5a, array(new Instruction(Direction::L, 10)));
        if ($lock_5a->dial === 95) { $test_passed++; }
        else {
            Logger::sudoLog('test_lock-5-L10_eq_95', sprintf("Expected 5 + L10 = 95, got %d", $lock_5a->dial));
        }

        // 5 + L10 + R5 = 0
        $lock_5b = new Lock(5, static::LOCK_MIN, static::LOCK_MAX);
        $this->rotate($lock_5b, array(
            new Instruction(Direction::L, 10),
            new Instruction(Direction::R, 5),
        ));
        if ($lock_5b->dial === 0) { $test_passed++; }
        else {
            Logger::sudoLog('test_lock-5-L10-R5_eq_0', sprintf("Expected 5 + L10 + R5 = 0, got %d", $lock_5b->dial));
        }

        $tests_delimiter = '----------    TESTS    ----------';
        $tests_result = ($test_passed === 6) ? 'Success' : 'Failed';
        $tests_output = sprintf("Passed %d out of %d tests (%s)", $test_passed, 6, $tests_result);
        Logger::log('tests-all', $tests_output);
        echo
            $tests_delimiter, PHP_EOL,
            $tests_output, PHP_EOL,
            $tests_delimiter, str_repeat(PHP_EOL, 2);
    }

    private function parseInstructions(array $instructions): array
    {
        return array_map(function ($i) {
            $i = trim($i);
            if (preg_match(Instruction::REGEX, $i, $matches) !== 1)  return null;

            return new Instruction(Instruction::DirectionFrom($matches[1]), (int) $matches[2]);
        }, $instructions);
    }

    private function rotate(Lock $lock = null, array $instructions = null): void
    {
        if ($instructions === null) $instructions = $this->instructions;
        if ($lock === null) $lock = $this->lock;

        foreach($instructions as $i) {
            $lock->rotate($i);
        }
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
    const PWD_INSECURE = 'PWD_INSECURE';
    const PWD_SECURE_4B = 'PWD_SECURE_0x434C49434B';

    public int $zero_count = 0;
    public int $pass_zero_count = 0;

    public function __construct(public int $dial, public $min = 0, public $max = 99) {}

    public function __toString()
    {
        return substr('0' . $this->dial, -2);
    }

    public function getPwd(string $method = self::PWD_INSECURE)
    {
        if ($method === static::PWD_SECURE_4B) return $this->zero_count + + $this->pass_zero_count;

        return $this->zero_count;
    }

    public function rotate(Instruction $i): void
    {
        $incr = $i->direction === Direction::R ? 1 : -1;
        $nb_passes_zero_amount = $this->getPassesZeroAmount($i);
        $prev = $this->__toString();
        $this->dial = ($this->dial + ($incr * $i->rotation)) % ($this->max + 1);
        if ($this->dial < $this->min) $this->dial+= $this->max + 1;

        $this->zero_count += ($this->dial === 0) ? 1 : 0;
        $this->pass_zero_count += $nb_passes_zero_amount;
        Logger::log(
            'lock-rotatation',
            sprintf("The dial is rotated $i to point at $this"),
            true
        );
    }

    private function getPassesZeroAmount(Instruction $i): Int
    {
        // how many revolutions
        $nb = (int) ($i->rotation / ($this->max+1));
        // equivalent rotation
        $incr = $i->direction === Direction::R ? 1 : -1;
        $diff = $i->rotation % ($this->max + 1);
        if ($this->dial === 0) return $nb;

        $after = $this->dial + $incr * $diff;
        $nb+= ($after < $this->min || $after > ($this->max+1)) ? 1 : 0;
        return $nb;
    }
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
