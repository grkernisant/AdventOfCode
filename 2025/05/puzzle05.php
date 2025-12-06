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
    private IngredientRanges $ingredient_ranges;

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
        $this->ingredient_ranges = new IngredientRanges($this->parser->getInput());
    }

    public function run(): void
    {
        try {
            if ($this->test_mode) $this->runTest();

            // Part 1
            $this->ingredient_ranges->validateFreshness();
            echo sprintf("There are %d fresh ingredients", $this->ingredient_ranges->freshnessCount()), PHP_EOL;

            // Part 2
            (void) $this->ingredient_ranges->getUniqueRanges();
            echo sprintf("There are %d unique fresh ingredients", $this->ingredient_ranges->freshnessCountUnique()), PHP_EOL;

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

class IngredientRanges
{
    const RANGE_REGEX = '#^(\d+)-(\d+)$#';
    const INGREDIENT_ID_REGEX = '#^(\d+)$#';

    public array $ranges;
    public array $ingredients;
    public ?array $unique_ranges;

    public function __construct(array $ingredient_list)
    {
        $this->ranges = array();
        $this->ingredients = array();
        $this->unique_ranges = null;

        foreach($ingredient_list as $line) {
            $line = trim($line);
            if (preg_match(static::RANGE_REGEX, $line, $matches)) {
                array_push($this->ranges, new Range((float) $matches[1], (float) $matches[2]));
            }

            if (preg_match(static::INGREDIENT_ID_REGEX, $line, $matches)) {
                array_push($this->ingredients, new Ingredient((float) $matches[1]));
            }
        }
    }

    public function freshnessCount(): int
    {
        $count = array_reduce($this->ingredients, function ($acc, $curr) {
            $acc+= ($curr->fresh) ? 1 : 0;
            return $acc;
        }, 0);

        return $count;
    }

    public function freshnessCountUnique(): float
    {
        $count = array_reduce($this->unique_ranges, function ($acc, $curr) {
            $acc+= ($curr->max - $curr->min + 1.0);
            return $acc;
        }, 0.0);

        return $count;
    }

    static public function find(array $haystack, Ingredient $needle): ?Range
    {
        return array_find($haystack, function(Range $r) use($needle) {
            return Range::inRange($r, $needle);
        });
    }

    static public function findKey(array $haystack, Ingredient $needle): ?int
    {
        return array_find_key($haystack, function(Range $r) use($needle) {
            return Range::inRange($r, $needle);
        });
    }

    #[\NoDiscard]
    public function getUniqueRanges(): array
    {
        if ($this->unique_ranges !== null) return $this->unique_ranges;

        // check if ranges collapses
        usort($this->ranges, [__CLASS__, 'sortRange']);

        Logger::log('unique-ranges', ''); // start new logger
        $this->unique_ranges = array();
        foreach($this->ranges as $range) {
            $min_i = new Ingredient($range->min);
            $max_i = new Ingredient($range->max);

            $range_min_index = static::findKey($this->unique_ranges, $min_i);
            $range_max_index = static::findKey($this->unique_ranges, $max_i);
            $find_key_log = "{$range} -> range_min_index: {$range_min_index}, range_max_index: {$range_max_index}";
            if ($range_min_index === null && $range_max_index === null) {
                // new range
                Logger::log(
                    'unique-ranges',
                    "{$find_key_log} - Adding new range $range",
                    true
                );
                array_push($this->unique_ranges, $range);
                usort($this->unique_ranges, [__CLASS__, 'sortRange']);
            } elseif ($range_min_index !== null && $range_max_index === null) {
                // extend the upper bound
                $extended = $this->unique_ranges[$range_min_index]->withMax($range->max);
                Logger::log(
                    'unique-ranges',
                    "{$find_key_log} - {$this->unique_ranges[$range_min_index]} increasing upper bound to $extended",
                    true
                );
                $this->unique_ranges[$range_min_index]->max = $range->max;
            } elseif ($range_min_index === null && $range_max_index !== null) {
                // extend the lower bound
                $extended = $this->unique_ranges[$range_max_index]->withMin($range->min);
                Logger::log(
                    'unique-ranges',
                    "{$find_key_log} - {$this->unique_ranges[$range_max_index]} decreasing lower bound to $extended",
                    true
                );
                $this->unique_ranges[$range_max_index]->min = $range->min;
                usort($this->unique_ranges, [__CLASS__, 'sortRange']);
            } else {
                // already included
                Logger::log(
                    'unique-ranges',
                    "{$find_key_log} - Already includes range $range",
                    true
                );
            }
        }

        return $this->unique_ranges;
    }

    static public function sortRange(Range $a, Range $b): int
    {
        if ($a->min < $b->min) return -1;
        if ($a->min > $b->min) return 1;

        if ($a->max < $b->max) return -1;
        if ($a->max > $b->max) return 1;

        return 0;
    }

    public function validateFreshness(): void
    {
        foreach($this->ingredients as $ingredient)
        {
            $ingredient->fresh = static::find($this->ranges, $ingredient) !== null;
        }
    }
}

class Range
{
    public function __construct(public float $min, public float $max) {}

    public function __toString(): string
    {
        return sprintf("Range(min: %d, max: %d)", $this->min, $this->max);
    }

    public function contains(Ingredient $ingredient): bool
    {
        return ($ingredient->id >= $this->min) && ($ingredient->id <= $this->max);
    }

    public static function inRange(Range $range, Ingredient $ingredient): bool
    {
        return $range->contains($ingredient);
    }

    public function withMin(float $min): Range
    {
        return clone($this, ['min' => $min]);
    }

    public function withMax(float $max): Range
    {
        return clone($this, ['max' => $max]);
    }
}

class Ingredient
{
    public function __construct(public float $id, public ?bool $fresh = false) {}
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
