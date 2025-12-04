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
    private array $battery_banks;

    public function __construct(array $args)
    {
        $path = $this->getPath($args);
        $this->setOptions($args);
        Logger::$should_log = $this->debug_mode;

        $path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        Logger::$logger = $path . '.log';

        $this->init();
    }

    private function getPath(array $args): string
    {
        $path = array_filter($args, fn($arg) => strpos(haystack: $arg, needle: '--') === false);
        return reset($path) ?: static::DEFAULT_INPUT;
    }

    private function init()
    {
        $this->battery_banks = $this->parseBatteryBanks($this->parser->getInput());
    }

    private function parseBatteryBanks(array $banks): array
    {
        $output = array_map(function($line, $index) {
            return new BatteryBank("battery-bank-{$index}", str_split(trim($line)));
        }, $banks, array_keys($banks));

        return $output;
    }

    public function run(): void
    {
        try {
            if ($this->test_mode) $this->runTest();

            // Part 1
            $joltages = array_map(function($battery_bank) {
                return $battery_bank->getLargestJoltage(BatteryBank::NB_JOLTAGE_PAIRING_MIN);
            }, $this->battery_banks);
            echo sprintf("The total output joltage is %d", array_sum($joltages)), PHP_EOL;

            // Part 2
            $joltages = array_map(function($battery_bank) {
                return $battery_bank->getLargestJoltage(BatteryBank::NB_JOLTAGE_PAIRING_MAX);
            }, $this->battery_banks);
            echo sprintf("The total output joltage is %d", array_sum($joltages)), PHP_EOL;

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

class BatteryBank
{
    const NB_JOLTAGE_PAIRING_MIN = 2;
    const NB_JOLTAGE_PAIRING_MAX = 12;

    public array $batteries;

    public function __construct(public string $name, array $bank)
    {
        $bank_size = count($bank);
        $this->batteries = array_map(function($battery_voltage, $index) use ($bank_size) {
            return new Battery($index, (int) $battery_voltage, $bank_size);
        }, $bank, array_keys($bank));
    }

    public function getLargestJoltage($nb_batteries): int
    {
        $largest_joltage = array();
        $log_name = sprintf("largest-joltages-%s", $this->name);
        // reset log before appending
        Logger::log($log_name, sprintf("Finding largest joltage for %s: %s\n", $this->name, $this->print($this->batteries)));
        $offset = 0;
        for($i = 0; $i < $nb_batteries; $i++) {
            // look for the highest voltage in 
            // by keeping voltages left to parse
            $iteration = count($largest_joltage) + 1;
            $max_index = count($this->batteries) - ($nb_batteries - count($largest_joltage));
            $splice_length = $max_index - $offset + 1;
            $sorted = array(...array_slice($this->batteries, $offset, $splice_length));
            Logger::log($log_name, sprintf("Sorting between %d and %d: %s", $offset, $max_index, $this->print($sorted)), true);
            // find the largest voltage
            usort($sorted, [__CLASS__, 'bankSort']);
            $sorted = array_reverse($sorted);
            // update the offset for next searches
            $offset = $sorted[0]->position + 1;
            Logger::log($log_name, "Found {$sorted[0]->voltage} at {$sorted[0]->position}", true);
            array_push($largest_joltage, $sorted[0]->voltage);
        }

        $output = (int) implode(separator: "", array: $largest_joltage);
        Logger::log($log_name, "Largest Joltage ({$nb_batteries}): {$output}", true);
        return $output;
    }

    static public function bankSort(Battery $a, Battery $b): int
    {
        if ($a->voltage > $b->voltage) return 1;
        if ($a->voltage < $b->voltage) return -1;

        if ($a->position < $b->position) return 1;
        if ($a->position > $b->position) return -1;

        return 0;
    }

    private function print(array $batteries): string
    {
        return implode('', array_map(fn($b) => $b->voltage, $batteries));
    }
}

class Battery
{
    const MAX_VOLTAGE = 9;

    public int $score;

    public function __construct(public int $position, public int $voltage, int $bank_size)
    {
        $this->setScore($bank_size);
    }

    private function setScore(int $bank_size)
    {
        $max_score = self::MAX_VOLTAGE * $bank_size;
        $voltage_missing = self::MAX_VOLTAGE - $this->voltage;
        $voltage_penalty = $voltage_missing * ($bank_size - $this->position);
        $this->score = $max_score - $voltage_penalty;
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
