<?php

$default = './test.txt';
$path = isset($argv[1]) ? $argv[1] : $default;

if (!is_readable($path)) Throw new Exception('Unreadable input');
$lines = file($path);

try {
    $parser = new Parser($lines);
    $parser->run();
} catch (Throwable $e) {
    die(sprintf('Error (%d): %s%s%s', $e->getLine(), $e->getMessage(), PHP_EOL, $e->getTraceAsString()));
}

class Parser
{
    const SPACE_REGEX = '/:?\s+/';

    public array $equations;

    public function __construct(array $lines)
    {
        $this->equations = array();
        foreach($lines as $line) {
            if ($matches = preg_split(static::SPACE_REGEX, trim($line))) {
                $matches = array_map(fn($x) => (int) $x, $matches);
                $result = array_shift($matches);
                $this->equations[] = new Equation($result, $matches);
            }
        }
    }

    public function run()
    {
        $operators = array_values(Equation::getOperators());
        // part 1
        $calibration = (object) array(
            'part_1' => 0,
            'part_2' => 0,
        );
        $calibration->part_1 = 0;
        $part1_ops = array_slice($operators, 0, 2);
        $unresolved = array_filter($this->equations, function($e) use (&$calibration, $part1_ops) {
            $valid = $e->solveWith($part1_ops);
            $calibration->part_1+= $valid ? $e->result : 0;
            return !$valid;
        });

        echo sprintf('Part1: the total calibration result using 2 operators is %d', $calibration->part_1), PHP_EOL;
        echo sprintf('%d equations were solved', count($this->equations) - count($unresolved)), str_repeat(PHP_EOL, 2);

        // part 2
        $nb_resolved = 0;
        $calibration->part_2 = array_reduce($unresolved, function($acc, $current) use (&$nb_resolved, $operators) { 
            $nb_resolved+= $current->solveWith($operators) ? 1 : 0;
            $acc+= $current->valid ? $current->result : 0;
            return $acc;
        }, 0); 
        echo sprintf('Part2: the partial calibration result using 3 operators is %d', $calibration->part_2), PHP_EOL;
        echo sprintf('%d equations were solved with 3 operators', $nb_resolved), str_repeat(PHP_EOL, 2);

        echo sprintf('The total calibration is %d', ($calibration->part_1 + $calibration->part_2)), PHP_EOL;
    }
}

class Equation
{
    static public $operators = null;
    static public $nb_operators = null;

    public function __construct(public int $result, public array $numbers, public bool $valid = false) {}

    public function solveWith(array $ops): bool
    {
        $head = new EquationMember(number: 0, operator: EquationMember::OPERATOR_ADD, subtotal: 0);
        $nb = count($ops);
        foreach($this->numbers as $n) {
            foreach($ops as $op) {
                $em = new EquationMember(number: $n, operator: $op);
                $head->add($em, $nb);
            }
        }

        $this->valid = $head->find($this->result);
        return $this->valid;
    }

    static public function getOperators(): array
    {
        if (static::$operators === null) {
            static::$operators = array();
            static::$operators[] = EquationMember::OPERATOR_ADD;
            static::$operators[] = EquationMember::OPERATOR_MULTIPLY;
            static::$operators[] = EquationMember::OPERATOR_CONCAT;
        }

        return static::$operators;
    }

    static public function getNbOperators(): int
    {
        if (static::$nb_operators !== null) return static::$nb_operators;

        static::$nb_operators = count(static::getOperators());
        return static::$nb_operators;
    }
}

class EquationMember
{
    const OPERATOR_ADD = '+';
    const OPERATOR_MULTIPLY = '*';
    const OPERATOR_CONCAT = '||';

    public array $children;

    public function __construct(public int $number, public string $operator, public ?int $subtotal = null)
    {
        $this->children = array();
    }

    public function __destruct()
    {
        foreach($this->children as $c) {
            $c->__destruct();
        }

        $this->children = array();
    }

    public function add(EquationMember $eq, int $nb_operators): void
    {
        if (count($this->children) < $nb_operators) {
            $n = new EquationMember(
                number: $eq->number,
                operator: $eq->operator
            );
            switch ($this->operator) {
                case static::OPERATOR_ADD:
                    $n->subtotal = $this->subtotal + $n->number;
                break;

                case static::OPERATOR_MULTIPLY:
                    $n->subtotal = $this->subtotal * $n->number;
                break;

                case static::OPERATOR_CONCAT:
                    $n->subtotal = (int) ((string) $this->subtotal . (string) $n->number);
                break;
            }
            array_push($this->children, $n);
        } else {
            foreach($this->children as $c) {
                $c->add($eq, $nb_operators);
            }
        }
    }

    public function find(int $result): bool
    {
        if ($this->subtotal === $result) return true;

        $found = false;
        $i = 0;
        $l = count($this->children);
        while ($i<$l && !$found) {
            $found = $this->children[$i]->find($result);
            $i++;
        }

        return $found;
    }
}