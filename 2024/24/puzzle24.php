<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('./vendor/autoload.php');

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Main
{
    const CIRCUIT = 'circuit';

    public Parser $parser;
    public Circuit $c;

    public function __construct(public string $path)
    {
        $this->path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        Logger::$logger = $this->path . '.log';

        $this->c = Circuit::getInstance();
        SharedResources::set(static::CIRCUIT, $this->c);

        $file = $this->parser->getInput();
        foreach($file as $line) {
            $this->c->parse($line);
        }
    }

    public function run(): void
    {
        $this->c->run();
    }
}

class Circuit
{
    private static ?Circuit $instance = null;

    const WIRE_ASSIGNMENT_REGEX = '#^(%s): (\d)$#';
    const WIRE_REGEX = '[a-z0-9]{3}';
    const X_WIRE_REGEX = '#^x\d{2}$#';
    const Y_WIRE_REGEX = '#^y\d{2}$#';
    const Z_WIRE_REGEX = '#^z\d{2}$#';

    public array $equations;
    public EventDispatcher $motherboard;
    public array $wires;

    private function __construct()
    {
        $this->equations = array();
        $this->motherboard = new EventDispatcher();
        $this->wires = array();

        $this->motherboard->addListener(Wire::EVENT_WIRE_SET, [static::class, 'onWiseSetEvent']);
    }

    public static function getInstance(): Circuit
    {
        if (static::$instance !== null) return static::$instance;

        static::$instance = new Circuit();
        return static::$instance;
    }

    public function parse(string $line): void
    {
        $wire_regex = sprintf(static::WIRE_ASSIGNMENT_REGEX, static::WIRE_REGEX);
        $equation_regex = Equation::getRegex();
        if (preg_match($wire_regex, $line, $matches)) {
            $this->wires[$matches[1]] = new Wire($matches[1], (int) $matches[2]);
        }

        if (preg_match($equation_regex, $line, $matches)) {
            $this->equations[] = new Equation(...array_splice($matches, 1));
        }
    }

    public static function onWiseSetEvent(Wire $e): void
    {
        $c = static::getInstance();
        foreach($c->equations as $eq) $eq->onWiseSetEvent($e);
    }

    public function run(): void
    {
        $x_number = array();
        $y_number = array();
        $z_number = array();
        foreach($this->equations as $eq) $eq->exec();

        asort($this->wires);
        foreach($this->wires as $var_name => $wire) {
            if (preg_match(static::Z_WIRE_REGEX, $var_name)) {
                array_unshift($z_number, $wire->value);
            } else if (preg_match(static::X_WIRE_REGEX, $var_name)) {
                array_unshift($x_number, $wire->value);
            } else if (preg_match(static::Y_WIRE_REGEX, $var_name)) {
                array_unshift($y_number, $wire->value);
            }
        }

        $x_number = implode('', $x_number);
        $y_number = implode('', $y_number);
        $z_number = implode('', $z_number);
        echo $x_number, ' => ', bindec($x_number), PHP_EOL;
        echo $y_number, ' => ', bindec($y_number), PHP_EOL;
        echo $z_number, ' => ', bindec($z_number), PHP_EOL;

        $x_number = (int) bindec($x_number);
        $y_number = (int) bindec($y_number);
        $sum = $x_number + $y_number;
        $z_real = decbin($sum);
        $z_real = str_pad($z_real, strlen($z_number), '0', STR_PAD_LEFT);
        echo $z_real, PHP_EOL, $z_number, PHP_EOL;

        $z_real = str_split((string) $z_real);
        $z_number = str_split((string) $z_number);
        $l = count($z_number);
        $n = 0;
        $i = $l-1;
        while ($i>0) {
            $key = str_pad((string) $n, 2, '0', STR_PAD_LEFT);
            if ($z_real[$i] !== $z_number[$i]) {
                echo 'z' . $key . ' is wrong', PHP_EOL;
            }
            $n++;
            $i--;
        }
    }
}

class Wire extends Event
{
    const EVENT_WIRE_SET = 'wire.set';

    public function __construct(public string $name, public int $value, bool $dispatch = false)
    {
        if ($dispatch) $this->broadcast();
    }

    public function setValue(int $value, bool $dispatch = false): void
    {
        $this->value = $value;
        if ($dispatch) $this->broadcast();
    }

    private function broadcast()
    {
        $c = Circuit::getInstance();
        $c->motherboard->dispatch($this, static::EVENT_WIRE_SET);
    }
}

class Equation
{
    const EQUATION_REGEX = '#^(%s) (%s) (%s) -> (%s)$#';

    public static ?array $operators = null;

    public string $checksum;
    public bool $executed;

    public function __construct(public string $left, public string $operator, public string $right, public string $result)
    {
        $this->checksum = md5(serialize(func_get_args()));
        $this->executed = false;
    }

    public function exec(): void
    {
        if (!$this->hasExecuted() && $this->isReady()) {
            $this->executed = true;

            $c = Circuit::getInstance();
            if (!isset($c->wires[$this->result])) $c->wires[$this->result] = new Wire($this->result, 0);

            switch ($this->operator) {
                case Operator::AND:
                    $c->wires[$this->result]->setValue(
                        $c->wires[$this->left]->value & $c->wires[$this->right]->value,
                        true
                    );
                    break;
                case Operator::OR:
                    $c->wires[$this->result]->setValue(
                        $c->wires[$this->left]->value | $c->wires[$this->right]->value,
                        true
                    );
                    break;
                case Operator::XOR:
                    $c->wires[$this->result]->setValue(
                        $c->wires[$this->left]->value ^ $c->wires[$this->right]->value,
                        true
                    );
                    break;
                default:
                    Throw new Exception('UNKNOWN_OPERATOR ' . $this->operator);
                    break;
            }
        }
    }

    public static function getOperators(): array
    {
        if (static::$operators !== null) return static::$operators;

        static::$operators = array(Operator::AND, Operator::OR, Operator::XOR);
        return static::$operators;
    }

    public static function getRegex(): string
    {
        $operators = Equation::getOperators();
        $regex = sprintf(
            static::EQUATION_REGEX,
            Circuit::WIRE_REGEX,
            implode('|', $operators),
            Circuit::WIRE_REGEX,
            Circuit::WIRE_REGEX,
        );

        return $regex;
    }

    public function hasExecuted(): bool
    {
        return $this->executed;
    }

    public function isReady(): bool
    {
        $c = Circuit::getInstance();
        return isset($c->wires[$this->left]) && isset($c->wires[$this->right]);
    }

    public function onWiseSetEvent(Wire $e): void
    {
        if (!$this->hasExecuted() && $this->isReady()) $this->exec();
    }
}

class Operator
{
    const AND = 'AND';
    const XOR = 'XOR';
    const OR = 'OR';
}

class SharedResources
{
    private static array $resources = array();

    public static function get(string $resource_name)
    {
        return static::$resources[$resource_name] ?? null;
    }

    public static function set(string $resource_name, $resource): void
    {
        static::$resources[$resource_name] = $resource;
    }
}

class Parser
{
    public array $input;

    public function __construct(public string $path)
    {
        if (!is_readable($path)) throw new Exception('Unreadable input');

        $file = file($path);
        $this->input = array_map(fn($l) => trim($l), $file);
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
