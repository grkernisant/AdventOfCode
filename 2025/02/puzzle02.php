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
    private array $ranges;

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

    private function parseRanges(array $ranges): array
    {
        $output = array_map(function($line) {
            $bounds = explode(ProductRangeID::DELIMITER, trim($line));
            return new ProductRangeID((int) $bounds[0], (int) $bounds[1]);
        }, $ranges);

        return $output;
    }

    public function run(): void
    {
        try {
            if ($this->test_mode) $this->runTest();

            // Part 1
            $invalids = array();
            $this->ranges = $this->parseRanges($this->parser->getInput());
            foreach($this->ranges as $product_range) {
                array_splice(
                    $invalids,
                    count($invalids),
                    0,
                    $product_range->filterInvalidProductIds()
                );
            }

            echo sprintf("The sum of invalid product ids is %d", array_sum($invalids)), PHP_EOL;

        } catch(Throwable $e) {}
    }

    public function runTest(): void
    {
        $test_passed = 0;

        // 11-22 -> 11, 22
        $pr_11_22 = new ProductRangeID(11, 22);
        $invalids = implode(', ', $pr_11_22->filterInvalidProductIds());
        if ($invalids === "11, 22") { $test_passed++; }
        else {
            Logger::sudoLog('test_pr_11_22_eq_1122', sprintf("Expected PR invalids(11,22) = 11, 22 got %s", $invalids));
        }

        // 95-115 -> 99
        $pr_95_115 = new ProductRangeID(95, 115);
        $invalids = implode(', ', $pr_95_115->filterInvalidProductIds());
        if ($invalids === "99") { $test_passed++; }
        else {
            Logger::sudoLog('test_pr_95_115_eq_99', sprintf("Expected PR invalids(95,115) = 99 got %s", $invalids));
        }

        // 998-1012 -> 1010
        $pr_998_1012 = new ProductRangeID(998, 1012);
        $invalids = implode(', ', $pr_998_1012->filterInvalidProductIds());
        if ($invalids === "1010") { $test_passed++; }
        else {
            Logger::sudoLog('test_pr_998_1012_eq_1010', sprintf("Expected PR invalids(998,1012) = 1010 got %s", $invalids));
        }

        // 1188511880-1188511890 -> 1188511885
        $pr_1188511880_1188511890 = new ProductRangeID(1188511880, 1188511890);
        $invalids = implode(', ', $pr_1188511880_1188511890->filterInvalidProductIds());
        if ($invalids === "1188511885") { $test_passed++; }
        else {
            Logger::sudoLog('test_pr_1188511880_1188511890_eq_1188511885', sprintf("Expected PR invalids(1188511880, 1188511890) = 1188511885 got %s", $invalids));
        }

        // 222220-222224 -> 222220, 222224
        $pr_222220_222224 = new ProductRangeID(222220, 222224);
        $invalids = implode(', ', $pr_222220_222224->filterInvalidProductIds());
        if ($invalids === "222222") { $test_passed++; }
        else {
            Logger::sudoLog('test_pr_222220_222224_eq_222222', sprintf("Expected PR invalids(222220,222224) = 222222 got %s", $invalids));
        }

        // 1698522-1698528 -> 1698522, 1698528
        $pr_1698522_1698528 = new ProductRangeID(1698522, 1698528);
        $invalids = implode(', ', $pr_1698522_1698528->filterInvalidProductIds());
        if ($invalids === "") { $test_passed++; }
        else {
            Logger::sudoLog('test_pr_1698522_1698528_eq_', sprintf("Expected PR invalids(1698522,1698528) = '' got %s", $invalids));
        }

        // 446443-446449 -> 446443, 446449
        $pr_446443_446449 = new ProductRangeID(446443, 446449);
        $invalids = implode(', ', $pr_446443_446449->filterInvalidProductIds());
        if ($invalids === "446446") { $test_passed++; }
        else {
            Logger::sudoLog('test_pr_446443_446449_eq_446446', sprintf("Expected PR invalids(446443,446449) = 446446 got %s", $invalids));
        }

        // 38593856-38593862 -> 38593856, 38593862
        $pr_38593856_38593862 = new ProductRangeID(38593856, 38593862);
        $invalids = implode(', ', $pr_38593856_38593862->filterInvalidProductIds());
        if ($invalids === "38593859") { $test_passed++; }
        else {
            Logger::sudoLog('test_pr_38593856_38593862_eq_38593859', sprintf("Expected PR invalids(38593856,38593862) = 38593859 got %s", $invalids));
        }

        $tests_delimiter = '----------    TESTS    ----------';
        $tests_result = ($test_passed === 8) ? 'Success' : 'Failed';
        $tests_output = sprintf("Passed %d out of %d tests (%s)", $test_passed, 8, $tests_result);
        Logger::log('tests-all', $tests_output);
        echo
            $tests_delimiter, PHP_EOL,
            $tests_output, PHP_EOL,
            $tests_delimiter, str_repeat(PHP_EOL, 2);
    }

    private function setOptions(array $args): void
    {
        $this->debug_mode = (array_search(haystack: $args, needle: static::DEBUG_MODE) !== false);
        $this->test_mode = (array_search(haystack: $args, needle: static::TEST_MODE) !== false);
    }
}

class ProductRangeID
{
    const DELIMITER = '-';
    const INVALID_PRODUCT_ID = '/^((?i)\d+)\1$/';

    public function __construct(public int $min, public int $max) {
        if ($max < $min) throw new Error("Invalid Product Range ID: $min > $max, max must greater than min");
    }

    public function filterInvalidProductIds(): array
    {
        $invalid_product_ids = array_filter(range($this->min, $this->max), function($product_id) {
            return preg_match(static::INVALID_PRODUCT_ID, (string) $product_id);
        });

        return $invalid_product_ids;
    }
}

class Parser
{
    public array $input;

    public function __construct(public string $path)
    {
        if (!is_readable($path)) throw new Exception("Unreadable input: '$path'");

        $content = str_replace("\n", "", file_get_contents($path));
        $this->input = explode(",", $content);
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
