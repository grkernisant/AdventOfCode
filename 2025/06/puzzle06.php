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

    private Parser $parser;
    private Worksheet $worksheet;

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

    private function init()
    {
        
    }

    public function run(): void
    {
        try {
            if ($this->test_mode) $this->runTest();

            // Part 1
            $this->worksheet = new Worksheet($this->parser->getInput(), SolvingMode::LEFT_TO_RIGHT);
            echo sprintf("The math worksheet grand total is %d", $this->worksheet->solve()), PHP_EOL;

            // Part 2
            $this->worksheet = new Worksheet($this->parser->getInput(), SolvingMode::RIGTH_TO_LEFT);
            echo sprintf("The math worksheet RTL grand total is %d", $this->worksheet->solve()), PHP_EOL;

        } catch(Throwable $e) {}
    }

    public function runTest(): void
    {
        $test_passed = 0;

    }

    private function setOptions(array $args): void
    {
        $this->debug_mode = (array_search(haystack: $args, needle: static::DEBUG_MODE) !== false);
        $this->test_mode = (array_search(haystack: $args, needle: static::TEST_MODE) !== false);
    }
}

enum SolvingMode {
    case LEFT_TO_RIGHT;
    case RIGTH_TO_LEFT;
}

class Worksheet
{
    const OPERAND_REGEX = '/^((:?\d+)\s*)+$/';
    const OPERATOR_REGEX = '/^((:?[\+|\*])\s*)+$/';

    public array $operations;

    public function __construct(array $lines, SolvingMode $sm)
    {
        $this->operations = array();

        if ($sm === SolvingMode::LEFT_TO_RIGHT) $this->initLTR($lines);
        if ($sm === SolvingMode::RIGTH_TO_LEFT) $this->initRTL($lines);
    }

    public function initLTR(array $lines): void
    {
        $operations = array_reduce($lines, function ($acc, $curr) {
            $line = trim($curr);
            if (preg_match(static::OPERAND_REGEX, $line, $matches)) {
                $line_min_spaces = preg_replace("/\s+/", ' ', $line);
                $operands = explode(" ", $line_min_spaces);
                foreach($operands as $operation_index => $operand) {
                    if (!isset($acc->operands[$operation_index])) $acc->operands[$operation_index] = array();
                    array_push($acc->operands[$operation_index], (float) $operand);
                }
            }

            if (preg_match(static::OPERATOR_REGEX, $line, $matches)) {
                $line_min_spaces = preg_replace("/\s+/", ' ', $line);
                $operators = explode(" ", $line_min_spaces);
                foreach($operators as $operation_index => $operator) {
                    if (!isset($acc->operators[$operation_index])) $acc->operators[$operation_index] = array();
                    $acc->operators[$operation_index] = $operator;
                }
            }

            return $acc;
        }, (object) array(
            'operands' => array(),
            'operators' => array()
        ));

        foreach($operations->operands as $index => $operands) {
            array_push($this->operations, Operation::from($operands, $operations->operators[$index]));
        }
    }

    public function initRTL(array $lines): void
    {
        $operations = array_reduce($lines, function ($acc, $curr) {
            if (preg_match(static::OPERAND_REGEX, trim($curr), $matches)) {
                array_push($acc->lines, str_split(trim($curr, PHP_EOL)));
            }

            /*if (preg_match(static::OPERATOR_REGEX, $curr, $matches)) {
                $line_min_spaces = preg_replace("/\s+/", ' ', $curr);
                $acc->operators = explode(" ", $line_min_spaces);
            }*/
            if (preg_match(static::OPERATOR_REGEX, trim($curr), $matches)) {
                $line_min_spaces = preg_replace("/\s+/", ' ', trim($curr));
                $operators = explode(" ", $line_min_spaces);
                foreach($operators as $operation_index => $operator) {
                    if (!isset($acc->operators[$operation_index])) $acc->operators[$operation_index] = array();
                    $acc->operators[$operation_index] = $operator;
                }
            }

            return $acc;
        }, (object) array(
            'operands' => array(),
            'operators' => array(),
            'lines' => array()
        ));
        // read assign operands top to bottom
        $op_index = count($operations->operators) - 1;
        $col = count(reset($operations->lines)) - 1;
        $lines_index = range(0, count($operations->lines) - 1);
        do {
            // top to bottom
            $operand = array_reduce($operations->lines, function($acc, $curr) use ($col) {
                $acc.= $curr[$col];
                return $acc;
            }, '');
            if (!empty(trim($operand))) {
                if (!isset($operations->operands[$op_index])) $operations->operands[$op_index] = array();

                array_push($operations->operands[$op_index], (float) $operand);
            } else {
                $op_index--;
            }

            $col--;
        } while($op_index >= 0 && $col >= 0);
        unset($operations->lines);

        foreach($operations->operands as $index => $operands) {
            array_push($this->operations, Operation::from($operands, $operations->operators[$index]));
        }
    }

    public function solve(): float
    {
        $solutions = array_map(fn($op) => $op->solve(), $this->operations);
        return array_sum($solutions);
    }
}

enum Operator {
    case ADD;
    case MULTI;
}

class Operation
{
    public function __construct(public array $operands, public Operator $operator) {}

    static public function from(array $operands, string $operator): Operation
    {
        if ($operator === '+') return new Operation($operands, Operator::ADD);
        if ($operator === '*') return new Operation($operands, Operator::MULTI);

        throw new Error("Unknown operator $operator");
    }

    public function solve(): float
    {
        if ($this->operator === Operator::ADD) {
            return array_sum($this->operands);
        } else {
            return array_reduce($this->operands, function($acc, $curr) {
                $acc *= $curr;
                return $acc;
            }, 1.0);
        }
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

    static public function debug(string $message, mixed $content, bool $append = false): void
    {
        echo $content, PHP_EOL;

        if (!static::$should_log) return;
        static::log($message, $content, $append);
    }

    static public function log(string $message, mixed $content, bool $append = false): void
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

    static public function sudoLog(string $message, mixed $content, bool $append = false): void
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
