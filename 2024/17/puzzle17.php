<?php

class Main
{
    public Parser $parser;
    public Computer $pc;

    public function __construct(public string $path)
    {
        $this->path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        Logger::$logger = $this->path . '.log';

        $this->pc = new Computer();

        $file = $this->parser->getInput();
        foreach($file as $line) {
            echo trim($line), PHP_EOL;
            if (preg_match(Register::REGISTER_REGEX, $line, $matches)) {
                $property = 'register' . $matches[1];
                $this->pc->{$property} = new Register($matches[1], (int) $matches[2]);
            } else if (preg_match(Instruction::INSTRUCTION_REGEX, $line, $matches)) {
                $instructions = array();
                $inst = explode(',', $matches[1]);
                while(count($inst) > 1) {
                    $opcode = array_shift($inst);
                    $operand = array_shift($inst);
                    $instructions[] = new Instruction(opcode: (int) $opcode, operand: (int) $operand);
                }
                $this->pc->program = new Program($instructions);
            }
        }
    }

    public function run(): void
    {
        $this->pc->run();
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

class Computer
{
    public static $modulo = array();
    public static $jump = -1;

    public Register $registerA;
    public Register $registerB;
    public Register $registerC;
    public Program $program;

    public function __construct() {}

    public function run()
    {
        Logger::log('debug', '');
        $l = count($this->program->instructions);
        $i = 0;
        do {
            $ins = $this->program->instructions[$i];
            if (($success = $this->runOpcode($ins))) {
                $i++;
            } elseif ($success === false && $ins->opcode === Instruction::OPCODE_JNZ) {
                $i = $this->getOperand($ins);
                if ($i instanceof Register) $i = $i->value;
            }
            Logger::log('debug', print_r($ins, true) . PHP_EOL . $this->systemOut($i), true);
            ;
        } while($i<$l);
        echo PHP_EOL, $this->systemOut($i), PHP_EOL;
    }

    public function systemOut(int $nb_steps): string
    {
        $output = '';
        if (!empty(static::$modulo)) {
            $output.= implode(',', static::$modulo) . PHP_EOL;
        }
        $output.= sprintf('%d. RegA: %d, RegB: %d, RegC: %d', $nb_steps, $this->registerA->value, $this->registerB->value, $this->registerC->value);
        return $output;
    }

    public function runOpcode(Instruction $ins): bool
    {
        switch($ins->opcode) {
            case Instruction::OPCODE_ADV: return $this->dv($ins);
            case Instruction::OPCODE_BDV: return $this->dv($ins);
            case Instruction::OPCODE_CDV: return $this->dv($ins);
            case Instruction::OPCODE_BXL: return $this->bxl($ins);
            case Instruction::OPCODE_BST: return $this->bst($ins);
            case Instruction::OPCODE_JNZ: return $this->jnz($ins);
            case Instruction::OPCODE_BXC: return $this->bxc($ins);
            case Instruction::OPCODE_OUT: return $this->out($ins);
        }

        return null;
    }

    public function getOperand(Instruction $ins): Register | int
    {
        switch($ins->operand) {
            case 0:
            case 1:
            case 2:
            case 3: return $ins->operand;
            case Register::REGISTER_A: return $this->registerA;
            case Register::REGISTER_B: return $this->registerB;
            case Register::REGISTER_C: return $this->registerC;
            case 7:
            default:
                Throw new Exception('Invalid instruction');
                break;
        }
    }

    public function getRegister(Instruction $ins): Register | false
    {
        switch($ins->opcode) {
            case Instruction::OPCODE_ADV: return $this->registerA;
            case 1:
            case 2:
            case 3:
            case 4:
            case 5: return false;
            case Instruction::OPCODE_BDV: return $this->registerB;
            case Instruction::OPCODE_CDV: return $this->registerC;
            default:
                Throw new Exception('Invalid instruction');
                break;
        }
    }

    public function dv(Instruction $ins): bool
    {
        $reg = $this->getRegister($ins) ?: $this->registerA;
        $den = $this->getOperand($ins);
        $den = $den instanceof Register ? $den->value : $den;
        $reg->store(floor($this->registerA->value / pow(2, $den)));

        return true;
    }

    public function bst(Instruction $ins): bool
    {
        $input = $this->getOperand($ins);
        $input = $input instanceof Register ? $input->value : $input;
        $result = $input % 8;
        $this->registerB->store($result);
        return true;
    }

    public function bxc(Instruction $ins): bool
    {
        $result = ($this->registerB->value ^ $this->registerC->value);
        $this->registerB->store($result);
        return true;
    }

    public function bxl(Instruction $ins): bool
    {
        $result = ($this->registerB->value ^ $ins->operand);
        $this->registerB->store($result);
        return true;
    }

    public function jnz(Instruction $ins): bool
    {
        if ($this->registerA->value === 0) return true;

        static::$jump = $this->getOperand($ins);
        return false;
    }

    public function out(Instruction $ins): bool
    {
        $input = $this->getOperand($ins);
        if (($input instanceof Register)) {
            $reg = $input;
            $input = $input->value;
        }

        $result = $input % 8;
        static::$modulo[] = $result;

        return true;
    }
}

class Register
{
    const REGISTER_REGEX = '#Register (A|B|C): (\d+)$#';

    const REGISTER_A = 4;
    const REGISTER_B = 5;
    const REGISTER_C = 6;

    public function __construct(public string $name, public int $value) {}

    public function store(int $v) { $this->value = $v; }
}

class Program
{
    public function __construct(public array $instructions) {}
}

class Instruction
{
    const OPCODE_ADV = 0;
    const OPCODE_BXL = 1;
    const OPCODE_BST = 2;
    const OPCODE_JNZ = 3;
    const OPCODE_BXC = 4;
    const OPCODE_OUT = 5;
    const OPCODE_BDV = 6;
    const OPCODE_CDV = 7;

    const INSTRUCTION_REGEX  = '#Program:\s(.+)$#';
    const INVALID_OPERAND = 7;

    public function __construct(public int $opcode, public int $operand)
    {
        if ($operand > 7 || $operand < 0) {
            throw new Exception('Invalud operand');
        }
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
