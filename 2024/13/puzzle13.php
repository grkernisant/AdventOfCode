<?php

declare(strict_types=1);
error_reporting(E_ALL);

class Main
{
    const TOKEN_A = 3;
    const TOKEN_B = 1;
    const MAX_TOKENS = 100;

    public Parser $parser;
    public array $slot_machines;

    public function __construct(public string $path)
    {
        $this->path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        Logger::$logger = $this->path . '.log';

        $this->slot_machines = array();
        $file = $this->parser->getInput();
        foreach($file as $block) {
            $this->slot_machines[] = new SlotMachine($block);
        }
    }

    public function run(): void
    {
        // part 1
        $nb_tokens = 0;
        foreach($this->slot_machines as $sm) {
            $result = $sm->solve();
            if ($result && $result->b1 <= static::MAX_TOKENS && $result->b2 <= static::MAX_TOKENS) {
                $nb_tokens+= static::TOKEN_A * $result->b1 + static::TOKEN_B * $result->b2;
                echo sprintf('Press Button A %d times, Button B %d times', $result->b1, $result->b2), PHP_EOL;
            }
        }

        echo sprintf('I need to spend at least %d tokens to win most prizes', $nb_tokens), str_repeat(PHP_EOL, 3);

        // part 2
        $unit_error = new MatrixB(10000000000000, 10000000000000);
        $nb_tokens = 0;
        foreach($this->slot_machines as $sm) {
            $sm->prize->add($unit_error);
            $result = $sm->solve();
            if ($result) {
                $nb_tokens+= static::TOKEN_A * $result->b1 + static::TOKEN_B * $result->b2;
                echo sprintf('Press Button A %d times, Button B %d times', $result->b1, $result->b2), PHP_EOL;
            }
        }
        echo sprintf('I need to spend at least %d tokens to win most prizes after correction', $nb_tokens), PHP_EOL;
    }
}

class SlotMachine
{
    const BUTTON_REGEX = '#Button (A|B): X\+(\d+), Y\+(\d+)#';
    const PRIZE_REGEX = '#Prize: X=(\d+), Y=(\d+)#';

    public MatrixA $buttons;
    public MatrixB $prize;

    public function __construct(array $input)
    {
        preg_match_all(static::BUTTON_REGEX, implode(' ', $input), $matches);
        if ($matches === null) Throw new Exception('INVALID_SLOT_MACHINE_BUTTONS_INPUT');
        $this->buttons = new MatrixA(
            (int) $matches[2][0],
            (int) $matches[2][1],
            (int) $matches[3][0],
            (int) $matches[3][1]
        );

        preg_match(static::PRIZE_REGEX, implode(' ', $input), $matches);
        if ($matches === null) Throw new Exception('INVALID_SLOT_MACHINE_PRIZE_INPUT');
        $this->prize = new MatrixB((int) $matches[1], (int) $matches[2]);
    }

    public function solve(): MatrixB | null
    {
        $_denom = $this->buttons->denom;
        $buttons_inv = $this->buttons->inverse();

        $top = ($buttons_inv->a11 * $this->prize->b1 + $buttons_inv->a12 * $this->prize->b2);
        $bot = ($buttons_inv->a21 * $this->prize->b1 + $buttons_inv->a22 * $this->prize->b2);

        $modulo_top = $top % $_denom;
        $modulo_bot = $bot % $_denom;

        return ($modulo_top === 0 && $modulo_bot === 0)
            ? new MatrixB($top / $_denom, $bot / $_denom)
            : null;
    }
}

class MatrixA
{
    public int $denom;

    public function __construct(public int $a11, public int $a12, public int $a21, public int $a22)
    {
        $this->denom = $a11 * $a22 - ( $a21 * $a12 );
    }

    public function inverse(): MatrixA
    {
        $inverse = new MatrixA($this->a22, -1*$this->a12, -1*$this->a21, $this->a11);
        return $inverse;
    }
}

class MatrixB
{
    public function __construct(public int $b1, public int $b2) {}

    public function add(MatrixB $b): void
    {
        $this->b1+= $b->b1;
        $this->b2+= $b->b2;
    }
}

class Parser
{
    public array $input;

    public function __construct(public string $path)
    {
        if (!is_readable($path)) throw new Exception('Unreadable input');

        $file = file($path);
        $block = array();
        $this->input = array_reduce($file, function($acc, $curr) use (&$block) {
            if (!empty(trim($curr))) {
                $block[] = trim($curr);
            } else {
                $acc[] = $block;
                $block = array();
            }

            return $acc;
        }, []);

        if (!empty($block)) $this->input[] = $block;
    }

    public function getInput(): array { return $this->input; }
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
