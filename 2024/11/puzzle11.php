<?php

class Sudo
{
    private Parser $parser;
    private array $namespaces;
    private array $total_rounds;

    public function __construct(public string $path, public int $nb_blinks, public int $enable_caching_loop_stop)
    {
        $this->parser = new Parser($path);
        $this->setNamespaces($this->parser->getInput());

        // lets divide and conquer
        $this->total_rounds = array();
        $x_times = floor($this->nb_blinks / Main::BLINKS_PER_ROUND);
        $modulo  = $this->nb_blinks % Main::BLINKS_PER_ROUND;
        if ($x_times > 0) $this->total_rounds = array_fill(0, $x_times, Main::BLINKS_PER_ROUND);
        if ($modulo !== 0) array_push($this->total_rounds, $modulo);
    }

    public function run()
    {
        // lets blink in each namespace separately
        foreach($this->namespaces as $ns) {
            // empty log
            Logger::debug('calculator-' . $ns, '');
            Logger::debug('input_shards-' . $ns, '');
            Logger::debug('shard_subtotal-' . $ns, '');
            // Logger::debug('shard_subtotal-' . $ns, '');
            // for 1st time, 1 number in queue
            Main::setEnvVars($ns);
            $rounds = array(...$this->total_rounds);
            while(($blink = array_shift($rounds))) {
                Main::startRoundEvent($blink);

                if (count(Main::$input_shards) == 0) break;
                do {
                    $shard = array_shift(Main::$input_shards);
                    $sudo_main = new Main($ns, $shard, $blink, $this->enable_caching_loop_stop);
                    $sudo_main->run();
                } while(count(Main::$input_shards) > 0);

                // round end
                Main::endRoundEvent($ns, $blink, $sudo_main->getLine());
                // was this the last round?
                if (!empty($rounds)) {}
            }
            // end of blinking round for shards in namespace
            Main::$grand_total[$ns] = array_sum(Main::$calculator);
            echo str_repeat(PHP_EOL, 2);
        }

        // ok lets get the calculator out!
        echo sprintf('%s%s%s', PHP_EOL, '*** Grand Finale! ***', PHP_EOL);
        print_r(Main::$grand_total);
        echo array_sum(Main::$grand_total);
    }

    private function setNamespaces(string $numbers)
    {
        $this->namespaces = array();
        // lets get all unique stones required for calculations
        $shards = explode(' ', trim($numbers));
        foreach($shards as $shard) {
            $this->namespaces[] = (int) $shard;
        }
    }
}

class Main
{
    const BLINKS_PER_ROUND = 10;
    const BLINKS = 1;
    const ENABLE_CACHING_LOOP_STOP = 0;
    const INPUT_SHARDS_FILE = './cache/input-shards-file';
    const INPUT_SHARD_FILE = './cache/input-shard-d%';

    public static $namespaces = array();
    public static $calculator = array();
    public static $grand_total = array();
    public static $input_shards = array();
    public static $shard_subtotal = array();
    public static $shard_transform = array();

    public Stones $stones;
    public array $line;

    public function __construct(public string $namespace, public int $shard, public int $nb_blinks, public int $enable_caching_loop_stop)
    {
        $this->stones = new Stones((string) $shard);
        $this->line = array();
    }

    public function addShardSubtotal(int $shard, string $subtotal_key): void
    {
        // update count
        static::$shard_subtotal[$subtotal_key]->occurance+= static::$calculator[$shard];
        //static::$calculator[$shard];
        // update current progress
        $this->line = static::$shard_transform[$subtotal_key];
    }

    public static function endRoundEvent(string $namespace, int $blink, array $line): void
    {
        // reset for next round
        static::$calculator = array();
        static::$input_shards = array();
        foreach(static::$shard_subtotal as $st_key => $subtotal) {
            foreach(static::$shard_transform[$st_key] as $shard_number_key => $transform_count) {
                $number = Stones::getStoneKeyNumber($shard_number_key);
                if (array_search($number, static::$input_shards) === false) {
                    static::$input_shards[] = (int) $number;
                }

                if(!isset(static::$calculator[$number])) {
                    static::$calculator[$number] = 0;
                }

                static::$calculator[$number]+= $transform_count * $subtotal->occurance;
            }
        }
        static::$shard_subtotal = array();
        Logger::debug('calculator-' . $namespace, Main::$calculator, true);
        Logger::debug('input_shards-' . $namespace, Main::$input_shards, true);
    }

    public function getLine(): array { return $this->line; }

    public static function getSubtotalKey(int $shard, int $nb_blinks): string
    {
        return sprintf('subtotal_%d_%d', $shard, $nb_blinks);
    }

    public function run(): void
    {
        // get cache key
        $subtotal_key = Main::getSubtotalKey($this->shard, $this->nb_blinks);
        if (isset(static::$shard_subtotal[$subtotal_key]) && $this->enable_caching_loop_stop === 1) {
            // add subtotal to calculator
            $this->addShardSubtotal($this->shard, $subtotal_key);
            return;
        }

        $result = '';
        $blinks = range(0, $this->nb_blinks-1);
        foreach($blinks as $i => $b) {
            [$result, $nb_new] = $this->stones->blink(remaining: $this->nb_blinks - $i);
            if ($result) $this->stones->set($result);
            if ($nb_new === 0 && $this->enable_caching_loop_stop !== 0) break;
        }

        # get stones count
        if ($this->enable_caching_loop_stop === 0) {
            $count = $this->stones->getStonesCount($result);
            Main::$grand_total[$this->shard]+= $count;
            echo sprintf(
                'After %d blinks we have %d stones',
                $this->nb_blinks,
                $count
            ), PHP_EOL;
        }

        if ($this->enable_caching_loop_stop === 1) {
            [$subtotal, $line] = $this->stones->getStonesCountRecursive($this->nb_blinks - $i, $this->nb_blinks);

            static::$shard_subtotal[$subtotal_key] = (object) array(
                'subtotal' => $subtotal,
                'occurance' => 0
            );
            static::$shard_transform[$subtotal_key] = $line;
            $this->addShardSubtotal($this->shard, $subtotal_key);
            return;
        }
    }

    public static function setEnvVars(int $namespace): void
    {
        static::$calculator   = array((int) $namespace => 1);
        static::$input_shards = array((int) $namespace);
        static::$grand_total[(int) $namespace] = 0;
    }

    public static function startRoundEvent(int $blink): void
    {
        // round start
        echo sprintf('New round: Blink(%d)', $blink), PHP_EOL;
        echo PHP_EOL, '*** Numbers: ', substr(implode(', ', static::$input_shards), 0, 80), '...', PHP_EOL;
    }
}

class Parser
{
    public string $input;

    public function __construct(public string $path)
    {
        if (!is_readable($path)) throw new Exception('Unreadable input');

        $file = file_get_contents($path);
        $this->input = trim($file);
    }

    public function getInput(): string { return $this->input; }
}

class Logger
{
    public static function cache(string $message, $content, bool $append = false): void
    {
        $debug = serialize($content);
        $filepath = trim(sprintf('./cache/%s', $message));
        static::log($filepath, $debug, $append);
    }

    public static function debug(string $message, $content, bool $append = false): void
    {
        $debug = str_replace('    ', ' ', print_r($content, true));
        $filepath = trim(sprintf('./debug/%s', $message));
        static::log($filepath, $debug, $append);
    }

    public static function log(string $filepath, $content, bool $append = false): void
    {
        if ($append) {
            file_put_contents($filepath, $content . PHP_EOL, FILE_APPEND | LOCK_EX);
            return;
        }

        file_put_contents($filepath, $content);
    }
}

class Stones
{
    const MAX_STONES = 10;
    const PROCESS_LIST_REGEX = '/^(?:\d+\s){%d,}?(.+)$/';

    public string $stones;
    public array $line;
    public array $stone_map;

    public function __construct(string $numbers)
    {
        $this->set($numbers);
        $this->line = array();
        $this->stone_map = array();
    }

    public function appendToLine(array $numbers): void
    {
        foreach($numbers as $n) {
            $k = $this->getCachedStoneKey($n);
            if (!isset($this->line[$k])) { $this->line[$k] = 0; }
            $this->line[$k]++;
        }
    }

    public function blink(int $remaining): array
    {
        $this->clearLine();
        $stones = '';
        $process_list = $this->getStonesProcessList($this->stones);
        $nb_new = 0;
        foreach($process_list as $list) {
            foreach($list as $number) {
                $stone = new Stone((int) $number);
                $tada_stones = $stone->tada();
                $new_number = $this->cacheStoneMapping((int) $number, $tada_stones);
                if ($new_number) $nb_new++;
                // when no new stone => different stone recursion mode
                $stones.= $tada_stones . ' ';
            }
        }

        return [trim($stones), $nb_new];
    }

    public function cacheStoneMapping(int $from_stone, string $to_stones, bool $append = true): bool
    {
        # stone map caching
        $new_number = false;
        $numbers = explode(' ', $to_stones);
        $numbers = array_map(fn($n) => (int) ltrim($n, '0'), $numbers);
        $key = $this->getCachedStoneKey($from_stone);
        if (!isset($this->stone_map[$key])) {
            $new_number = true;
            $this->stone_map[$key] = $numbers;
        }

        # line history
        if ($append) $this->appendToLine($numbers);

        return $new_number;
    }

    public function clearLine(): void { $this->line = array(); }

    public function getCachedStone(string $k_number): array
    {
        if (isset($this->stone_map[$k_number])) return $this->stone_map[$k_number];

        // do not append to current line
        $number = (int) substr($k_number, 1);
        $stone = new Stone($number);
        $this->cacheStoneMapping($number, $stone->tada(), false);

        return $this->stone_map[$k_number];
    }

    public function getCachedStoneKey(int $n): string { return '_' . $n; }

    public static function getStoneKeyNumber(string $k): int { return (int) substr($k, 1); }

    public function getStonesCount(string $stone_list): int
    {
        if (!empty(trim($stone_list))) {
            $stones = explode(' ', trim($stone_list));
            return count($stones);
        }

        return 0;
    }

    public function getStonesCountRecursive(int $blinks_remaining, int $nb_blinks): array
    {
        // use credit calculator
        $nb_stones = array_sum($this->line);
        $before_change = array();
        do {
            $nb_stones = $nb_next_stones ?? $nb_stones;
            foreach($this->line as $k => $v) { $before_change[$k] = $v; }

            $next_line = array();
            // new line
            $nb_next_stones = 0;
            foreach($this->line as $number => $nb) {
                $transform = $this->getCachedStone($number);
                $nb_next_stones+= $nb * count($transform);
                foreach($transform as $new_stone) {
                    $new_stone_bulk = array_fill(0, $nb, $new_stone);
                    foreach($new_stone_bulk as $n) {
                        $k = $this->getCachedStoneKey($n);
                        if (!isset($next_line[$k])) $next_line[$k] = 0;
                        $next_line[$k]++;
                    }
                }
            }
            $this->clearLine();
            foreach($next_line as $k => $v) {
                $this->line[$k] = $v;
            }
            $blinks_remaining--;
        } while($blinks_remaining > 0);

        return [$nb_stones, $before_change];
    }

    public function getMaxStonesToProcess(): int
    {
        return static::MAX_STONES;
    }

    public function getStonesProcessList(string $stone_list): array
    {
        $process_list_regex = sprintf(static::PROCESS_LIST_REGEX, $this->getMaxStonesToProcess());

        $list = array();
        $remaining = $stone_list;
        do {
            $todo = '';
            if (preg_match_all($process_list_regex, $remaining, $matches, PREG_OFFSET_CAPTURE)) {
                $offset = $matches[1][0][1];
                $remaining = $matches[1][0][0];
                $todo = substr($matches[0][0][0], 0, $offset-1);
            } else if (strlen($remaining) > 0) {
                $todo = $remaining;
                $remaining = '';
            }

            if (strlen($todo)>0) $list[] = explode(' ', $todo);
        } while(strlen($remaining) > 0);

        return $list;
    }

    public function get(): array
    {
        return $this->stones;
    }

    public function set(string $numbers): void
    {
        $this->stones = $numbers;
    }
}

class Stone
{
    public function __construct(public int $number) {}

    public function __toString(): string
    {
        return (string) $this->number;
    }

    public function tada(): string
    {
        if ($this->number === 0) return '1';

        if ($this->getNbDigits() % 2 === 0) return $this->split();

        return (string) (2024 * $this->number);
    }

    public function getNbDigits(): int { return strlen($this->__toString()); }

    public function split(): string
    {
        $l = $this->getNbDigits();
        $num = $this->__toString();
        $split = str_split($num, $l/2);
        $split = array_map(fn($n) => (int) $n, $split);

        return implode(' ', $split);
    }
}


try {
    $path = './test.txt';
    $blink = Main::BLINKS;
    $caching = Main::ENABLE_CACHING_LOOP_STOP;

    if (count($argv)>=1) {
        $input = implode(' ', array_splice($argv, 1));
        if (preg_match('/[a-zA-Z\.]+/', $input, $matches)) $path = $matches[0];
        if (preg_match('/(\d+)(?:\D+(\d+))?/', $input, $matches)) {
            $blink = (int) ($matches[1] ?? $blink);
            $caching = (int) ($matches[2] ?? $caching);
        }
    }

    $above_main_lol = new Sudo($path, $blink, $caching);
    $above_main_lol->run();
} catch (Throwable $e) {
    die(sprintf('Error (%d): %s%s%s', $e->getLine(), $e->getMessage(), PHP_EOL, $e->getTraceAsString()));
}
