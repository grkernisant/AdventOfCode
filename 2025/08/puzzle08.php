<?php

declare(strict_types=1);
error_reporting(E_ALL);

class Main
{
    const string DEFAULT_INPUT = './test';
    const string DEBUG_MODE = '--debug';
    const string TEST_MODE = '--test';
    const string MAX_CONNEX = '--max';

    private bool $test_mode = false;
    private bool $debug_mode = false;

    private Parser $parser;
    private Circuits $circuits;
    private int $max_connections;

    public function __construct(array $args)
    {
        $this->setOptions($args);
        $path = $this->getPath($args);
        Logger::$should_log = $this->debug_mode;

        $path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        Logger::$logger = $path . '.log';

        if (!isset($this->max_connections)) $this->max_connections = $path === 'test' ? 10 : 1000;
        $this->init();
    }

    private function getPath(array $args): string
    {
        $path = array_filter($args, fn($arg) => strpos(haystack: $arg, needle: '--') === false);
        return reset($path) ?: static::DEFAULT_INPUT;
    }

    private function init()
    {
        $loggers = array(
            'junction-boxes-connection',
            'merge-networks',
            'sort-indxes-by-created',
            'junction-boxes-store-networks',
            'one-network-to-rule-them-all'
        );
        foreach($loggers as $log_init) Logger::log($log_init, '');
    }

    public function run(): void
    {
        if ($this->test_mode) $this->runTest();

        // Part 1
        $this->circuits = new Circuits($this->parser->getInput());
        $nb = $this->circuits->connectJuntionBoxes($this->max_connections);
        echo sprintf("%d connections were established", $nb), PHP_EOL;
        $networks = $this->circuits->getLargestNetworks(Circuits::JUNCTION_BOX_DB, 3)
            |> (function($arr) {
                Logger::log('networks-top-3', print_r($arr, true));
                return array_reduce($arr, fn($acc, $curr) => $acc*= $curr->getSize(), 1);
            });
        echo sprintf("f(networks.sortDesc.limit(3).multi) = %d", $networks), PHP_EOL;

        // Part 2
        $this->circuits = new Circuits($this->parser->getInput());
        $multi_x = $this->circuits->connectJuntionBoxes(-1);
        echo sprintf("f(networks.findBigBrother |> multiX.on(... JunctionBox)) = %d", $multi_x), PHP_EOL;
    }

    public function runTest(): void
    {
        $test_passed = 0;

    }

    private function setOptions(array &$args): void
    {
        $this->debug_mode = (array_search(haystack: $args, needle: static::DEBUG_MODE) !== false);
        $this->test_mode = (array_search(haystack: $args, needle: static::TEST_MODE) !== false);
        if (($found = array_search(haystack: $args, needle: static::MAX_CONNEX)) !== false) {
            $this->max_connections = (int) $args[$found + 1];
            array_splice($args, $found, 2);
        }
    }
}

enum IndexMode {
    case CHECK_INDEX;
    case FORCE_INDEX;
}

class Circuits
{
    const JUNCTION_BOX_DB = 'junction_boxes';

    private object $db;
    private array $junction_boxes;
    private array $distancesBetween;

    public function __construct(array $junction_boxes_list)
    {
        $this->junction_boxes = array_reduce($junction_boxes_list, function ($acc, $curr) {
            $coords = trim($curr);
            if ($coords) {
                $jb = new JunctionBox($coords);
                $acc[$jb->name] = $jb;
            }
            return $acc;
        }, array());

        $this->db = (object) array(
            'junction_boxes' => (object) array(
                'data' => array(),
                'index' => array()
            ),
        );

        $this->setDistances();
    }

    private function addJunctionBoxConnection(string $jb1, string $jb2): int
    {
        $network_index = -1;

        // is 0, 1 or 2 already connected?
        $index_1 = $this->getIndex(static::JUNCTION_BOX_DB, $jb1, IndexMode::CHECK_INDEX);
        $index_2 = $this->getIndex(static::JUNCTION_BOX_DB, $jb2, IndexMode::CHECK_INDEX);
        $indexes = array_unique(array_filter([$index_1, $index_2], fn($i) => $i !== null));
        $checked_indexes = array(...$indexes);

        if (empty($indexes)) {
            $network_index_set = 'empty';
            // add to a new network_id
            $network_index = $this->getIndex(static::JUNCTION_BOX_DB, $jb1, IndexMode::FORCE_INDEX);
        } else if (count($indexes) === 1) {
            $network_index_set = 'only 1';
            // only 1 was previous added
            $network_index = array_shift($indexes);
        } else {
            $network_index_set = 'merge networks';
            // merge networks!
            $indexes = $this->sortIndexesByCreated(static::JUNCTION_BOX_DB, $index_1, $index_2);
            $src = end($indexes);
            $dst = $network_index = reset($indexes);
            (void) $this->mergeNetworks(db_name: static::JUNCTION_BOX_DB, src: $src, dst: $dst);
        }
        Logger::log(
            'junction-boxes-store-networks',
            sprintf(
                "%s - %s => %s | %s | %s",
                $jb1,
                $jb2,
                implode(', ', $checked_indexes),
                $network_index_set,
                $network_index
            ),
            true
        );
        $this->store(static::JUNCTION_BOX_DB, $network_index, $jb1, $jb2);

        return $network_index;
    }

    public function connectJuntionBoxes(int $max): int
    {
        $keys = array_keys($this->distancesBetween);
        $multi_x = null;
        $junction_boxes_count = count($this->junction_boxes);
        $l = count($keys);
        $i = 0;
        $nb = 0;
        $max = $max > 0 ? $max : $l;
        $stop = false;
        while($i < $l && $i < $max && !$stop) {
            $pair = $keys[$i];
            list($k1, $k2) = explode('-', $pair);
            Logger::log(
                'junction-boxes-connection',
                sprintf("Trying to connect pair: (%s to %s)", $k1, $k2),
                true
            );
            if (!$this->sameNetwork($k1, $k2)) {
                $result_in = false;
                $result_out = $this->junction_boxes[$k1]->connect($this->junction_boxes[$k2]);
                Logger::log(
                    'junction-boxes-connection',
                    sprintf($result_out ? "%s -> YES. -> %s" : "%s -> !NO! -> %s", $k1, $k2),
                    true
                );
                if (!$result_out) {
                    $result_in = $this->junction_boxes[$k2]->connect($this->junction_boxes[$k1]);
                    Logger::log(
                        'junction-boxes-connection',
                        sprintf($result_in ? "%s -> .YES -> %s" : "%s -> !NO! -> %s", $k2, $k1),
                        true
                    );
                }

                if ($result_out || $result_in) {
                    $index = $this->addJunctionBoxConnection($k1, $k2);
                    // $nb+= $index > -1 ? 1 : 0;
                    Logger::log(
                        'junction-boxes-connection',
                        sprintf(
                            $index > -1
                                ? "%s and %s are added to the same networks %s"
                                : "Could not add %s and %s to same networks %s",
                            $k1,
                            $k2,
                            $index
                        ),
                        true
                    );
                } else {
                    Logger::log(
                        'junction-boxes-connection',
                        sprintf("%s and %s cannot be connected outbounds or inbounds", $k1, $k2),
                        true
                    );
                }
            } else {
                $network_index_1 = $this->getIndex(static::JUNCTION_BOX_DB, $k1, IndexMode::CHECK_INDEX);
                $network_index_2 = $this->getIndex(static::JUNCTION_BOX_DB, $k2, IndexMode::CHECK_INDEX);
                // $nb+= $network_index_1 === $network_index_2 ? 1 : 0;
                Logger::log(
                    'junction-boxes-connection',
                    sprintf("%s and %s are added already in the same networks %d === %d", $k1, $k2, $network_index_1, $network_index_2),
                    true
                );
            }

            // readability = sanity
            Logger::log('junction-boxes-connection', '', true);

            // true successful connections???
            // $nb++;

            $stop = ($find = $this->findNetworkSize(static::JUNCTION_BOX_DB, $junction_boxes_count)) !== null;
            if ($stop) {
                Logger::log('one-network-to-rule-them-all', print_r($find, true));
                // could aslo use array find
                $jb1 = new JunctionBox($k1);
                $jb2 = new JunctionBox($k2);
                $multi_x = $jb1->p->x * $jb2->p->x;
            }

            $i++;
        }

        return $multi_x ?? $i;
    }

    static public function distanceJunctionBoxes(JunctionBox $jb1, JunctionBox $jb2): float
    {
        return Point3D::distance($jb1->p, $jb2->p);
    }

    public function findNetworkSize(string $db_name, int $size): ?array
    {
        if (!isset($this->db->$db_name)) return null;

        return array_find($this->db->$db_name->data, function($item) use ($size) {
            if (is_array($item)) return count($item) === $size;

            return false;
        });
    }

    public function getDatabase(): object
    {
        return $this->db;
    }

    private function getIndex(string $db_name, string $key, IndexMode $mode = IndexMode::CHECK_INDEX): ?int
    {
        if (!isset($this->db->$db_name)) return null;

        $index = $this->db->$db_name->index[$key] ?? null;
        if ($mode === IndexMode::CHECK_INDEX || $index !== null) return $index;

        return count($this->db->$db_name->data);
    }

    public function getLargestNetworks(string $type, ?int $limit = null): array
    {
        if (!isset($this->db->$type)) return array();

        $largest_networks = array();
        foreach($this->db->$type->index as $name => $network_id) {
            if (!isset($largest_networks[$network_id])) $largest_networks[$network_id] = new Network((string) $network_id);
            $largest_networks[$network_id]->addNode($name);
        }
        uasort($largest_networks, ['Network', 'sortSizeDesc']);

        return $limit !== null && $limit > 0
            ? array_slice($largest_networks, 0, $limit)
            : $largest_networks;
    }

    public function getJunctionBoxes(): array
    {
        return $this->junction_boxes;
    }

    private function mergeNetworks(string $db_name, int $src, int $dst): int
    {
        Logger::log('merge-networks', sprintf("Merging networks %d into %d", $src, $dst), true);

        // src and dst exist?
        if (!isset($this->db->$db_name->data[$src])) Throw new Error("Cannot merge source does not exist: $src");

        if (empty($this->db->$db_name->data[$src])) {
            Logger::log('merge-networks', "Nothing to move", true);
            return 0;
        }

        // find all junkboxes from src and confirm move
        $items = array(
            ...$this->db->$db_name->data[$src]
        );
        $confirm = array_keys($this->db->$db_name->index, $src);
        $diff = array_diff($items, $confirm);
        if (count($diff) === 0) {
            // move data from src to dst
            if (!isset($this->db->$db_name->data[$dst])) $this->db->$db_name->data[$dst] = array();
            array_splice(
                $this->db->$db_name->data[$dst],
                count($this->db->$db_name->data[$dst]),
                0,
                $confirm
            );
            // duplicates?
            $this->db->$db_name->data[$dst] = array_unique($this->db->$db_name->data[$dst]);
            // clear src container
            $this->db->$db_name->data[$src] = array();
            // move index from src to dst
            foreach($confirm as $item_name) {
                $this->db->$db_name->index[$item_name] = $dst;
            }

            Logger::log(
                'merge-networks',
                sprintf(
                    "Moved %d items (%s ... %s) into Network %d",
                    count($confirm),
                    reset($confirm),
                    end($confirm),
                    $dst
                ),
                true
            );

            return count($confirm);
        } else {
            throw new Error(sprintf(
                "Cannot merge due to data inconsistency, extra items: %s",
                implode(', ', $diff)
            ));
        }
    }

    private function sameNetwork(string $jb1, string $jb2): bool
    {
        $index_1 = $this->getIndex(static::JUNCTION_BOX_DB, $jb1, IndexMode::CHECK_INDEX);
        $index_2 = $this->getIndex(static::JUNCTION_BOX_DB, $jb2, IndexMode::CHECK_INDEX);

        return $index_1 !== null && $index_1 === $index_2;
    }

    private function setDistances(): void
    {
        $this->distancesBetween = array();
        $keys = array_keys($this->junction_boxes);
        $l = count($keys);
        for($i = 0; $i < $l - 1; $i++) {
            $jb1 = $this->junction_boxes[$keys[$i]];
            for ($j = $i + 1; $j < $l; $j++) {
                $jb2 = $this->junction_boxes[$keys[$j]];

                $d_name = [$jb1->name, $jb2->name];
                sort($d_name, SORT_NATURAL);
                $d_name_str = implode('-', $d_name);

                $this->distancesBetween[$d_name_str] = static::distanceJunctionBoxes($jb1, $jb2);
            }
        }

        asort($this->distancesBetween);
        Logger::log('junction-boxes-distances', print_r($this->distancesBetween, true));
    }

    private function sortIndexesByCreated(string $db_name, int $index_1, int $index_2): array
    {
        if (!isset($this->db->$db_name->index)) throw new Error("This database does not exists $db_name");

        if ($index_1 === $index_2) return [$index_1, $index_2];

        $key_1 = array_keys($this->db->$db_name->index, $index_1);
        $key_2 = array_keys($this->db->$db_name->index, $index_2);

        $indexes = [$index_1, $index_2]; // default
        if ($key_1 === null && $key_2 === null) {
            // unlikely to get 2 forces indexes
            $log_msg = sprintf("Could not find 2 indexes to sort: %d, %d", $index_1, $index_2);
            Logger::log('sort-indxes-by-created', $log_msg, true);
            echo $log_msg, PHP_EOL;
            print_r($this->getDatabase());
            exit;
            $indexes = [min($index_1, $index_2), max($index_1, $index_2)];
        } else if ($key_1 !== null && $key_2 === null) {
            $indexes = [$index_1, $index_2]; // index 2 is new forced index?
        } else if ($key_1 === null && $key_2 !== null) {
            $indexes = [$index_2, $index_1]; // index 1 is new forced index?
        } else if ($key_1 <= $key_2) {
            $indexes = [$index_1, $index_2]; // index 1 created first
        } else {
            $indexes = [$index_2, $index_1]; // index 2 created first
        }

        return $indexes;
    }

    private function store(string $db_name, int $index, string $item_1, string $item_2): void
    {
        // create missing networks
        if (!isset($this->db->$db_name->data[$index])) $this->db->$db_name->data[$index] = array();

        // save items in all networks
        $items = array_unique([$item_1, $item_2]);
        foreach($items as $item) {
            // index
            $this->db->$db_name->index[$item] = $index;

            // unique
            if (array_find($this->db->$db_name->data[$index], fn($jb_name) => $jb_name === $item) === null) {
                $this->db->$db_name->data[$index][] = $item;
            }
        }
    }
}

class JunctionBox
{
    const MAX_CONNECTIONS = 0;

    public Point3D $p;

    public object $conn;

    public function __construct(public string $name)
    {
        $this->p = explode(',', $this->name)
            |> (function($arr) {
                return array_map(fn($a) => (int) $a, $arr);
            })
            |> (function($coords) {
                return Point3D::fromCoords($coords);
            });

        $this->conn = (object) array(
            'in' => array(),
            'out' => array()
        );
    }

    public function __toString(): string
    {
        $conn_list_out = array_reduce($this->conn->out, function($acc, $curr) {
            array_push($acc, $curr->name);
            return $acc;
        }, array());
        $conn_list_in = array_reduce($this->conn->in, function($acc, $curr) {
            array_push($acc, $curr->name);
            return $acc;
        }, array());

        $tab = str_repeat(' ', 1);
        return sprintf(
            "JunctionBox (name: %s, point: %s, conns: %d)\n%s- out: %s\n%s- in: %s",
            $this->name,
            (string) $this->p,
            $this->nbConnections(),
            $tab,
            $conn_list_out
                ? (sprintf("%s", implode('; ', $conn_list_out)))
                : '',
            $tab,
            $conn_list_in
                ? (sprintf("%s", implode('; ', $conn_list_in)))
                : '',
        );
    }

    public function connect(JunctionBox $jb): bool
    {
        // prevent loop backs
        $already_connected_in = array_find($this->conn->in, function($c) use ($jb) {
            return $c->name === $jb->name;
        });
        if ($already_connected_in) return true;

        // unique connections
        $already_connected_out = array_find($this->conn->out, function($c) use ($jb) {
            return $c->name === $jb->name;
        });
        if ($already_connected_out) return true;

        $max = static::MAX_CONNECTIONS;
        $unlimited = $max === 0;
        $limited = (count($this->conn->out) < $max && count($jb->conn->in) < $max);
        $can_connect = $unlimited || $limited;

        if ($can_connect) {
            array_push($this->conn->out, $jb);
            array_push($jb->conn->in, $this);
            return true;
        }

        return false;
    }

    public function isConnected(): bool
    {
        return count($this->conn->out) > 0 || count($this->conn->in) > 0;
    }

    public function nbConnections(): int
    {
        return count($this->conn->out) + count($this->conn->in);
    }
}

class Point3D
{
    public function __construct(public int $x, public int $y, public int $z) {}

    public function __toString(): string
    {
        return sprintf("Point (%d, %d, %d)", $this->x, $this->y, $this->z);
    }

    static public function fromCoords(array $coords): Point3D
    {
        return new Point3D(...$coords);
    }

    static public function distance (Point3D $a, Point3D $b): float
    {
        return sqrt(
            pow(((float) $b->x - (float) $a->x), 2.0) +
            pow(((float) $b->y - (float) $a->y), 2.0) +
            pow(((float) $b->z - (float) $a->z), 2.0)
        );
    }
}

class Network
{
    private array $nodes;

    public function __construct(public ?string $network_id = null)
    {
        if (!isset($this->network_id)) $this->network_id = sha1((new DateTime())->format('c'));

        $this->nodes = array();
    }

    public function addNode(string $node): void
    {
        array_push($this->nodes, $node);
    }

    public function getName($delimiter = '.'): string
    {
        $names = array(...$this->nodes);
        sort($names);
        return implode($delimiter, $names);
    }

    public function getSize(): int
    {
        return count($this->nodes);
    }

    static public function sortSize(Network $a, Network $b): int
    {
        if ($a->getSize() < $b->getSize()) return -1;
        if ($a->getSize() > $b->getSize()) return 1;

        return 0;
    }

    static public function sortSizeDesc(Network $a, Network $b): int
    {
        return -1 * static::sortSize($a, $b);
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
