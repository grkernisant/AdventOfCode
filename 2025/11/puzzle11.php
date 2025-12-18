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
    private array $devices;

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
        foreach(['trails-svr', 'trails-dac', 'trails-fft', 'trails-to-count'] as $logger) Logger::log($logger, '');

        $this->devices = array();
        $lines = $this->parser->getInput();
        foreach($lines as $line) {
            if (trim($line)) {
                list($name, $list) = explode(': ', trim($line));
                $connections = explode(' ', $list);
                $this->devices[] = new Device($name, $connections);
            }
        }

        // register devices
        Device::$devices[Device::DEVICE_OUT] = new Device(Device::DEVICE_OUT);
        foreach($this->devices as $d) {
            Device::$devices[$d->name] = $d;
        }

        // initial connections
        foreach($this->devices as $d) {
            foreach($d->connections as $name) {
                $d->addTrail(new Path($d->name, $name));
            }
        }
    }

    public function run(): void
    {
        if ($this->test_mode) $this->runTest();

        // Part 1 :
        if ($you = Device::getDeviceByName(Device::DEVICE_YOU)) {
            $trails = $you->getTrailsTo(Device::DEVICE_OUT);
            echo sprintf("There are %d paths that lead from 'you' to 'out'", count($trails)), PHP_EOL;
        }

        // Part 2
        $routes = array(
            'SVR - FFT - DAC - OUT' => array(
                (object) array('from' => Device::DEVICE_SVR, 'to' => Device::DEVICE_FFT),
                (object) array('from' => Device::DEVICE_FFT, 'to' => Device::DEVICE_DAC),
                (object) array('from' => Device::DEVICE_DAC, 'to' => Device::DEVICE_OUT)
            ),
            'SVR - DAC - FFT - OUT' => array(
                (object) array('from' => Device::DEVICE_SVR, 'to' => Device::DEVICE_DAC),
                (object) array('from' => Device::DEVICE_DAC, 'to' => Device::DEVICE_FFT),
                (object) array('from' => Device::DEVICE_FFT, 'to' => Device::DEVICE_OUT),
            )
        );
        $counts = array_map(function ($route) {
            $nb_trails = array_reduce($route, function($acc, $curr) {
                $from = Device::getDeviceByName($curr->from);
                $acc[] = $from->getTrailsToCount($curr->to);
                return $acc;
            }, []);

            return array_reduce($nb_trails, fn ($acc, $curr) => $acc*= $curr, 1.0);
        }, $routes);

        $output = "There are %d paths that lead from 'svr' -> 'fft|dac' -> 'out'";
        echo sprintf($output, array_sum($counts)), PHP_EOL;
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

class Device
{
    const DEVICE_DAC = 'dac';
    const DEVICE_FFT = 'fft';
    const DEVICE_OUT = 'out';
    const DEVICE_SVR = 'svr';
    const DEVICE_YOU = 'you';

    static public $devices = array();
    static private $trails_cache = array();
    static private $trails_count_cache = array();

    private array $trails;

    public function __construct(public string $name, public ?array $connections = null)
    {
        $this->trails = array();
    }

    public function addTrail(Path|Trail $t): int
    {
        $nb_added = 0;
        $trail = $t instanceof Trail
            ? clone($t)
            : new Trail($t);

        $ends = array_filter($this->trails, function($t) use ($trail) {
            return $t->endsIn($trail);
        });
        if (!empty($ends)) {
            foreach($ends as $matched_trail) {
                $matched_trail->add($trail);
            }

            return count($ends);
        }

        array_push($this->trails, new Trail($t));
        return 1;
    }

    static public function getDeviceByName(string $name): ?Device
    {
        return static::$devices[$name] ?? null;
    }

    public function getTrails(): array
    {
        return $this->trails;
    }

    public function getTrailsTo(string $device_to): array
    {
        $trails = array(
            ...$this->trails
        );

        $logger = sprintf('trails-%s-to-%s', $this->name, $device_to);
        Logger::log($logger, '');
        $trails_cache = array();
        $trails_hash = array();

        do {
            $iteration = 1;
            $index = 0;
            // path to explore
            $explore = array_reduce($trails, function($acc, $curr) use ($device_to, &$index) {
                if ($curr instanceof Trail && $curr->getEnd() !== $device_to && $curr->getEnd() !== Device::DEVICE_OUT) {
                    $acc[] = (object) array(
                        'path' => $curr->getEnd(),
                        'index' => $index,
                    );
                }

                $index++;
                return $acc;
            }, array());
            usort($explore, ['Utility', 'sortQueryIndex']);
            $explore = array_reverse($explore);

            $continue = !empty($explore);
            if ($continue) {
                $search = array();
                foreach($explore as $e) $search[] = $e->path;
                $search = array_unique($search);
                Logger::log($logger, sprintf("Searching trails from %s", implode(', ', $search)), true);
            }

            // current trails hashes
            $trails_hash = array_map(function($t) {
                if (!($t instanceof Trail)) return '';

                return $t->getHash();
            }, $trails) |> (fn($arr) => array_unique($arr));

            // append trails without loops nor duplicate trails
            $added = (object) array();
            foreach ($explore as $search) {
                $d = Device::getDeviceByName($search->path);
                $key = $search->path;
                if (!isset($added->$key)) $added->$key = 0;
                $new_trails = array();
                foreach($d->getTrails() as $connected_trails) {
                    $new_t = clone($trails[$search->index]);
                    // no loops
                    if ($new_t->add($connected_trails)) {
                        // keep unique trails
                        if (!in_array(needle: $new_t->getHash(), haystack: $trails_hash)) {
                            array_push($new_trails, $new_t);
                        }
                    }
                }
                $added->$key+= count($new_trails);
                array_splice($trails, $search->index, 1, $new_trails ?? "REMOVED");
            }
            $total_added = 0;
            foreach($added as $key => $subtotal) $total_added+= $subtotal;
            $continue = $total_added > 0;

            foreach($trails as $t) echo Logger::log($logger, (string) $t, true);
            Logger::log($logger, '', true);

            $iteration++;
        } while($continue);

        return array_filter($trails, fn($t) => $t instanceof Trail && $t->getEnd() === $device_to);
    }

    public function getTrailsToCount(string $device_to): float
    {
        $cache_index = $this->getTrailsToCountIndex($device_to);
        if (isset(static::$trails_count_cache[$cache_index])) return static::$trails_count_cache[$cache_index];

        $count_connections = $this->connections ? count($this->connections) : 0;
        $other_connections = $this->connections
            ? array_filter($this->connections, fn($c) => $c !== $device_to)
            : array();
        $nb_found = $count_connections - count($other_connections);
        $nb_other = count($other_connections);
        $trails_count = $nb_found + array_reduce($other_connections, function($acc, $curr) use ($device_to, $nb_other) {
            if ($d = Device::getDeviceByName($curr)) {
                $acc+= $d->getTrailsToCount($device_to);
            }

            return $acc;
        }, 0.0);
        static::$trails_count_cache[$cache_index] = $trails_count;

        return $trails_count;
    }

    private function getTrailsToCountIndex(string $device_to): string
    {
        return sprintf("trails_to_count_%s_%s", $this->name, $device_to);
    }
}

class Path
{
    public function __construct(public string $from, public string $to) {}
}

class Trail
{
    const TRAIL_CONNECTOR = '->';

    public string $trail;

    public function __construct(?Path $p = null)
    {
        if ($p) {
            $this->trail = sprintf("%s%s%s", $p->from, static::TRAIL_CONNECTOR, $p->to);
        }
    }

    public function __toString(): string
    {
        return sprintf("(Trail trail: %s)", $this->trail);
    }

    public function add(Trail $t): bool
    {
        $ends_match_beginning = $this->getEnd() === $t->getStart();

        $new_trails_parts = $t->getTrailParts();
        $recursion = false;
        $i = 1;
        $l = count($new_trails_parts);
        while ($i < $l && !$recursion) {
            $recursion = $this->contains($new_trails_parts[$i]);
            $i++;
        }

        if ($ends_match_beginning && !$recursion) {
            $trail_parts = $this->getTrailParts();
            array_splice($trail_parts, count($trail_parts)-1, 1, $t->getTrailParts());
            $this->trail = implode(separator: static::TRAIL_CONNECTOR, array: $trail_parts);

            return true;
        }

        return false;
    }

    public function contains(string $stop): bool
    {
        $trail_parts = explode(separator: static::TRAIL_CONNECTOR, string: $this->trail);
        return array_search(needle: $stop, haystack: $trail_parts) !== false;
    }

    /**
     * @param array<string> $paths
     *
     * @return Tail
     */
    static public function from(array $paths): Trail
    {
        $t = new Trail();
        $t->trail = implode(static::TRAIL_CONNECTOR, $paths);
        return $t;
    }

    public function extendFrom(Trail $t): bool
    {
        $extended_trail = $t->getTrailFrom($this->getEnd());
        if ($extended_trail === null) return false;

        return $this->add($extended_trail);
    }

    public function endsIn(Trail|String $t): bool
    {
        if (!($t instanceof Trail)) $t = new Trail(new Path(from: $t, to: ''));

        $other_parts = explode(separator: static::TRAIL_CONNECTOR, string: $t->trail, limit: 2);
        $trail_parts = explode(separator: static::TRAIL_CONNECTOR, string: $this->trail);
        return end($trail_parts) === reset($other_parts);
    }

    public function getEnd(): string
    {
        return $this->getTrailParts() |> (fn($arr) => end($arr));
    }
 
    public function getHash(): string
    {
        return md5($this->trail);
    }

    public function getStart(): string
    {
        return $this->getTrailParts() |> (fn($arr) => reset($arr));
    }

    public function getTrailFrom(string $from): ?Trail
    {
        if (!$this->contains($from)) return null;

        $trail_parts = $this->getTrailParts();
        $index = array_find_key($trail_parts, fn($t) => $t === $from);
        if ($index === null)  return null;

        return static::from(array_slice($trail_parts, $index));
    }

    public function getTrailParts(): array
    {
        return explode(separator: static::TRAIL_CONNECTOR, string: $this->trail);
    }

    public function prepend(Trail $t): bool
    {
        $ends_match_beginning = $this->getStart() === $t->getEnd();

        $new_trails_parts = $t->getTrailParts();
        $recursion = false;
        $i = 0;
        $l = count($new_trails_parts);
        while ($i < ($l -1) && !$recursion) {
            $recursion = $this->contains($new_trails_parts[$i]);
            $i++;
        }

        if ($ends_match_beginning && !$recursion) {
            $trail_parts = $this->getTrailParts();
            array_splice($trail_parts, 0, 1, $new_trails_parts);
            $this->trail = implode(separator: static::TRAIL_CONNECTOR, array: $trail_parts);

            return true;
        }

        return false;
    }
}

class Utility
{
    static public function sortQueryIndex(object $a, object $b): int
    {
        return $a->index <=> $b->index;
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
