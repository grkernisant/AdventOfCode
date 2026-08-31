<?php

declare(strict_types=1);
error_reporting(E_ALL);

class Main
{
    public const DEFAULT_INPUT = './test';
    public const DEBUG_MODE = '--debug';
    public const TEST_MODE = '--test';

    private bool $test_mode = false;
    private bool $debug_mode = false;
    private array $options;

    private Parser $parser;
    private array $almanac;
    private array $maps;

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
        $path = array_filter($args, fn($arg) => strpos($arg, '--') === false);
        return reset($path) ?: static::DEFAULT_INPUT;
    }

    private function hasOption(string $option): bool {
        return array_search($option, $this->options) !== false;
    }

    private function initLogs(array $names)
    {
        foreach($names as $log) {
            Logger::log($log, '');
        }
    }

    public function run(): void
    {
        if ($this->runTest()) return;

        $almanac = Almanac::factorize($this->parser->getInput());
        echo sprintf("Part 1: %d", $almanac->getLowestLocationNumber()), PHP_EOL;

        $almanac2 = Almanac::factorize($this->parser->getInput(), SeedMode::AsRange);
        echo sprintf("Part 2: %d", $almanac2->getLowestLocationNumber()), PHP_EOL;
    }

    private function runTest(): bool
    {
        return defined('TEST_MODE') && TEST_MODE === 'TEST_MODE';
    }

    private function setOptions(array $args): void
    {
        $this->options = $args;
        $this->debug_mode = $this->hasOption(static::DEBUG_MODE);
        $this->test_mode = $this->hasOption(static::TEST_MODE);
    }
}

class Almanac
{
    private static $REGEX = array(
        'seeds' => '/^seeds:(?:\s+(\d+))+$/'
    );

    private function __construct(
        public SeedMode $mode,
        public array $seeds,
        public array $transforms,
        public array $maps
    ) {
        $this->initMaps();
    }

    public function convertTo(int $id, string $src_type = 'seed', string $dst_type = 'location'): int
    {
        if (!isset($this->maps[$src_type])) throw new \Exception(sprintf("Source type: %s does not exist", $src_type));

        $next_id = null;
        $found = false;
        $nb = count($this->maps[$src_type]);
        $i = 0;
        while($i < $nb && !$found) {
            $map = $this->maps[$src_type][$i];
            $gte = $id >= $map->src_range[0];
            $lte = $id <= $map->src_range[1];
            $found = $gte && $lte;
            if (!$found) $i++;
        }

        $next_type = $this->transforms[$src_type] ?? $this->convertError($id, $src_type, $dst_type);

        if ($found) {
            $map = $this->maps[$src_type][$i];
            $offset = $id - $map->src_range[0];
            $next_id = $map->dst_range[0] + $offset;
        } else {
            $next_id = $id;
        }

        if ($next_id === null) $this->convertError($id, $src_type, $next_type);

        if ($next_type === $dst_type) return $next_id;

        return $this->convertTo($next_id, $next_type, $dst_type);
    }

    private function convertError(int $id, string $src_type, string $dst_type)
    {
        throw new \Exception(sprintf("Cannot convert %d from %s to %s", $id, $src_type, $dst_type));
    }

    public static function factorize(array $input, SeedMode $seed_mode = SeedMode::AsList): Almanac
    {
        if (is_array($input) && isset($input[0])) {
            preg_match(static::$REGEX['seeds'], $input[0], $matches);
            if ($matches === null) throw new \Exception('Wrong Almanac format');

            $seeds = Utils::getLineNumbers(substr($input[0], 6));
            $range = range(2, count($input)-1);
            $src_type = $dst_type = "";
            $maps = $transforms = array();
            foreach($range as $r) {
                $line = trim($input[$r]);
                if (empty($line)) continue;

                if (preg_match(RangeMap::$REGEX_TYPES, $line, $matches)) {
                    $src_type = $matches[1];
                    $dst_type = $matches[2];
                    $transforms[$src_type] = $dst_type;
                }

                if (preg_match(RangeMap::$REGEX_BOUNDARIES, $line, $matches)) {
                    if (!isset($maps[$src_type])) $maps[$src_type] = array();
                    $boundaries = Utils::getLineNumbers($line);
                    if (count($boundaries) === 3) {
                        $dst_min = $boundaries[0];
                        $src_min = $boundaries[1];
                        $range = $boundaries[2];

                        $maps[$src_type][] = new RangeMap(
                            src_type: $src_type,
                            src_range: array($src_min, $src_min + $range  - 1),
                            dst_type: $dst_type,
                            dst_range: array($dst_min, $dst_min + $range - 1)
                        );
                    }
                }
            }

            return new Almanac($seed_mode, $seeds, $transforms, $maps);
        }

        throw new \Exception('Input should be an array of strings');
    }

    public function getLowestLocationNumber(): ?int
    {
        return $this->mode === SeedMode::AsList
            ? $this->getLowestLocationNumberAsList()
            : $this->getLowestLocationNumberAsRange();
    }

    private function getLowestLocationNumberAsList(): ?int
    {
        $lowest = null;
        foreach($this->seeds as $s) {
            $loc = $this->convertTo($s, 'seed', 'location');
            $lowest = $lowest !== null
                ? min($loc, $lowest) 
                : $loc;
        }

        return $lowest;
    }

    private function getLowestLocationNumberAsRange(): ?int
    {
        $found = false;
        $location_range = array(... $this->maps['humidity']);
        usort($location_range, RangeMap::sortByDstRange(...));
        foreach($location_range as $n => $l_range) {
            $rv = $this->revertFromRange($l_range->dst_range, 'seed', 'location');
            $i = 0;
            $l = count($rv);
            while ($i < $l && !$found) {
                $r = $rv[$i];
                // check range intersections
                $inter = $this->hasSeed($r);
                $found = !empty($inter);
                if ($found) {
                    return $this->convertTo($inter[0][0]);
                }
    
                $i++;
            }
        }

        return null;
    }

    private function getRevertFromRangeMap(int $id, string $src_type, string $dst_type): RangeMap
    {
        if (!in_array($dst_type, $this->transforms)) throw new \Exception(sprintf("Destination type: %s does not exist", $dst_type));

        $lbound = array();
        $prev_type = array_search($dst_type, $this->transforms) ?? $this->convertError($id, $src_type, $dst_type);
        $found = false;
        $nb = count($this->maps[$prev_type]);
        $i = 0;
        while($i < $nb && !$found) {
            $map = $this->maps[$prev_type][$i];
            $gte = $id >= $map->dst_range[0];
            $lte = $id <= $map->dst_range[1];
            // closest upper boundary has smallest difference
            if ($id < $map->dst_range[0]) {
                $lbound[$map->dst_range[0]] = $map->dst_range[0] - $id;
            }

            $found = $gte && $lte;
            if (!$found) $i++;
        }

        if ($found) {
            return $this->maps[$prev_type][$i];
        }

        // out of bounds?
        if (count($lbound) === 0) {
            return new RangeMap(
                src_type: $prev_type,
                src_range: array($id, PHP_INT_MAX),
                dst_type: $dst_type,
                dst_range: array($id, PHP_INT_MAX)
            );    
        }

        asort($lbound);
        $ubound = reset($lbound) - 1;
        // identity range map
        return new RangeMap(
            src_type: $prev_type,
            src_range: array($id, $ubound),
            dst_type: $dst_type,
            dst_range: array($id, $ubound)
        );
    }

    public function getSeeds(): Generator
    {
        if ($this->mode === SeedMode::AsList) {
            foreach($this->seeds as $s) yield $s;
        } else {
            $seed_chunks = array_chunk($this->seeds, 2);
            foreach($seed_chunks as $sc) {
                $current = $sc[0];
                $start = $current;
                $max = $start + abs($sc[1]) - 1;
                while ($current <= $max) {
                    yield $current++;
                }
            }
        }
    }

    private function initMaps() {
        foreach($this->maps as $k => $v) {
            // sort by src_range
            usort($this->maps[$k], array('RangeMap', 'sortBySrcRange'));

            // pad with equivalent 1:1 mapping
            // starting at 0 if missing
            $firstMapRange = reset($this->maps[$k]);
            if ($firstMapRange->src_range[0] !== 0) {
                $padded_range = array(0, $firstMapRange->src_range[0] - 1);
                $padding = new RangeMap(
                    src_type: $firstMapRange->src_type,
                    src_range: $padded_range,
                    dst_type: $firstMapRange->dst_type,
                    dst_range: $padded_range,
                );
                array_unshift($this->maps[$k], $padding);
            }
        }
    }

    public function isSeed(int $s): bool {
        return $this->mode === SeedMode::AsList
            ? $this->isSeedAsList($s)
            : $this->isSeedAsRange($s);
    }

    private function isSeedAsList(int $s): bool {
        return in_array($s, $this->seeds);
    }

    private function isSeedAsRange(int $s): bool {
        $found = false;
        $seed_chunks = array_chunk($this->seeds, 2);
        foreach($seed_chunks as $sc) {
            $found = $sc[0] <= $s && $s <= $sc[0] + abs($sc[1]) - 1;
            if ($found) break;
        }

        return $found;
    }

    public function hasSeed(array $range): array
    {
        return $this->mode === SeedMode::AsList
            ? $this->hasSeedAsList($range)
            : $this->hasSeedAsRange($range);
    }

    private function hasSeedAsList(array $range): array
    {
        $contains = array();
        foreach($this->getSeeds() as $seed) {
            if ($range[0] <= $seed && $seed <= $range[1]) {
                $contains[] = $seed;
            }
        }

        return $contains;
    }

    private function hasSeedAsRange(array $range): array
    {
        $contains = array();
        $chunks = array_chunk($this->seeds, 2);
        foreach($chunks as $c) {
            $seed_range_start = $c[0];
            $seed_range_end   = $c[0] + $c[1] - 1;
            $has_start = $seed_range_start <= $range[0] && $range[0] <= $seed_range_end;
            $has_end   = $seed_range_start <= $range[1] && $range[1] <= $seed_range_end;
            if ($has_start && $has_end) {
                $contains[] = array($range[0], $range[1]);
            } elseif ($has_start && !$has_end) {
                $contains[] = array($range[0], $seed_range_end);
            } elseif ($has_end) {
                $contains[] = array($seed_range_start, $range[1]);
            }
        }

        return $contains;
    }

    public function revertFrom(int $id, string $src_type = 'seed', string $dst_type = 'location'): int
    {
        if (!in_array($dst_type, $this->transforms)) throw new \Exception(sprintf("Destination type: %s does not exist", $dst_type));

        $prev_id = null;
        $prev_type = array_search($dst_type, $this->transforms) ?? $this->convertError($id, $src_type, $dst_type);
        $found = false;
        $nb = count($this->maps[$prev_type]);
        $i = 0;
        while($i < $nb && !$found) {
            $map = $this->maps[$prev_type][$i];
            $gte = $id >= $map->dst_range[0];
            $lte = $id <= $map->dst_range[1];
            $found = $gte && $lte;
            if (!$found) $i++;
        }

        if ($found) {
            $map = $this->maps[$prev_type][$i];
            $offset = $id - $map->dst_range[0];
            $prev_id = $map->src_range[0] + $offset;
        } else {
            $prev_id = $id;
        }

        if ($prev_id === null) $this->convertError($id, $prev_type, $dst_type);

        if ($prev_type === $src_type) return $prev_id;

        return $this->revertFrom($prev_id, $src_type, $prev_type);
    }

    public function revertFromRange(array $ids, string $src_type = 'seed', string $dst_type = 'location'): array
    {
        if (!in_array($dst_type, $this->transforms)) throw new \Exception(sprintf("Destination type: %s does not exist", $dst_type));

        $previous_ids = array();
        $current_src_type = null;
        // lets map all value ranges
        // from ids[0] to ids[1]
        $nb_values = RangeMap::getDistance($ids[0], $ids[1]);
        $current_id = $ids[0];
        while ($nb_values > 0) {
            $range_map = $this->getRevertFromRangeMap($current_id, $src_type, $dst_type);
            $current_src_type = $range_map->src_type;
            // lower bound
            $lb = $range_map->getOffsetFromDst($current_id);
            // upper bound
            $ub = $range_map->getUpperBoundFromDst($current_id, RangeMap::getDistance($current_id, $ids[1]));
            $previous_ids[] = array($lb, $ub);
            // did we cover all values?
            $new_values = RangeMap::getDistance($lb, $ub);
            $nb_values -= $new_values;
            $current_id+= $new_values;
        }

        if ($current_src_type === null) throw new \Exception(sprintf("Could not transfrom from %s to %s", $src_type, $dst_type));

        if (count($previous_ids) > 1) {
            $previous_ids = RangeMap::grouped($previous_ids);
        }

        // go from dst_type to src_type step by step
        while ($current_src_type !== $src_type) {
            $prev_src_type = array_search($current_src_type, $this->transforms);
            $result = array();
            foreach($previous_ids as $pid) {
                $r = $this->revertFromRange($pid, $prev_src_type, $current_src_type);
                array_splice($result, count($result), 0, $r);
            }

            $current_src_type = $prev_src_type;
            $previous_ids = RangeMap::grouped($result);
        }

        return $previous_ids;
    }
}

class RangeMap
{
    public static $REGEX_TYPES = '/^(\w+)-to-(\w+) map:$/';
    public static $REGEX_BOUNDARIES = '/^[0-9\ ]+$/';

    public function __construct(
        public string $src_type,
        public array $src_range,
        public string $dst_type,
        public array $dst_range,
    ) {}

    public function __toString() {
        return sprintf(
            "RangeMap (src_type: %s, src_range: %s, dst_type: %s, dst_range: %s)",
            $this->src_type,
            sprintf("[%d-%d]", $this->src_range[0], $this->src_range[1]),
            $this->dst_type,
            sprintf("[%d-%d]", $this->dst_range[0], $this->dst_range[1]),
        );
    }

    public static function getDistance(int $b1, int $b2): int
    {
        $min = min($b1, $b2);
        $max = max($b1, $b2);

        return $max - $min + 1;
    }

    private function getOffsetFrom(int $id, string $from = 'dst'): int
    {
        $dst = $from === 'dst' ? $this->dst_range : $this->src_range;
        $src = $from === 'dst' ? $this->src_range : $this->dst_range;
        $offset = $id - $dst[0];
        return $src[0] + $offset;
    }

    public function getOffsetFromDst(int $id): int
    {
        return $this->getOffsetFrom($id, 'dst');
    }

    public function getOffsetFromSrc(int $id): int
    {
        return $this->getOffsetFrom($id, 'src');
    }

    public function getUpperBoundFromDst(int $id, int $l): int
    {
        $offset = $this->getOffsetFromDst($id);
        return min(end($this->src_range), $offset + $l - 1);
    }

    public function getUpperBoundFromSrc(int $id, int $l): int
    {
        $offset = $this->getOffsetFromSrc($id);
        return min(end($this->dst_range), $offset + $l);
    }

    public static function grouped(array $input): array
    {
        $grouped = array();
        $current_start = $current_end = null;
        $i = 0;
        $l = count($input);
        while ($i < $l) {
            $current_start = reset($input[$i]);
            $current_end   = end($input[$i]);
            $j = $i + 1;
            while ($j < $l && reset($input[$j]) === ($current_end + 1)) {
                $current_end = end($input[$j]);
                $j++;
            }
            $grouped[] = array($current_start, $current_end);
            $i = $j;
        }

        return $grouped;
    }

    public function length(): int
    {
        return static::getDistance($this->src_range[0], $this->src_range[1]);
    }

    public static function sortByDstRange(RangeMap $a, RangeMap $b): int
    {
        return $a->dst_range[0] - $b->dst_range[0];
    }

    public static function sortBySrcRange(RangeMap $a, RangeMap $b): int
    {
        return $a->src_range[0] - $b->src_range[0];
    }
}

enum SeedMode
{
    case AsList;
    case AsRange;
}

class Utils
{
    public static function getLineNumbers(string $line): array
    {
        return array_map(fn($str) => (int) $str, explode(' ', trim($line)));
    }
}

class ColoredOutput
{
    private static $colors = [
        'reset'   => "\033[0m",   // Reset to default
        'red'     => "\033[31m",
        'green'   => "\033[32m",
        'yellow'  => "\033[33m",
        'blue'    => "\033[34m",
        'magenta' => "\033[35m",
        'cyan'    => "\033[36m",
        'white'   => "\033[37m",
        'bold'    => "\033[1m"
    ];

    // Function to print colored text
    static public function paintText(string $text, string $color): string {
        if(!isset(static::$colors[$color])) return $text;

        return sprintf(
            "%s%s%s",
            static::$colors[$color],
            $text,
            static::$colors['reset']
        );
    }
}

class Parser
{
    public array $input;

    public function __construct(public string $path = './test')
    {
        if (!is_readable($path)) throw new Exception("Unreadable input: '$path'");

        $this->input = file($this->path, FILE_IGNORE_NEW_LINES);
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
