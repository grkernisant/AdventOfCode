<?php

class Main
{
    static public Map $map;

    public Parser $parser;

    public function __construct(public string $path)
    {
        $this->path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        static::$map = new Map($this->parser->getInput());
        Logger::$logger = $this->path . '.log';
    }

    public function run(): void
    {
        static::$map->hiking_trails = static::$map->findTrailHeads();
        foreach(static::$map->hiking_trails as $ht) {
            $ht->buildTrail();
        }
        $scores_sum = array_reduce(static::$map->hiking_trails, function($acc, $curr) {
            $acc+= $curr->trailhead->score;
            return $acc;
        }, 0);
        echo sprintf('Sum of scores of trailer heads that leads to 9s is: %d', $scores_sum), PHP_EOL;

        $ratings_sum = array_reduce(static::$map->hiking_trails, function($acc, $curr) {
            $acc+= array_sum($curr->trailhead->score_sheet);
            return $acc;
        }, 0);
        echo sprintf('Sum of ratings of trailer heads that leads to 9s is: %d', $ratings_sum), PHP_EOL;

        $hiking_trails = array_map(function($o) {
            unset($o->trailhead->children);
        }, static::$map->hiking_trails);
        Logger::log('hiking_trails', static::$map->hiking_trails);
    }

    static public function getMap(): Map { return static::$map; }
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

class Map
{
    public array $map;
    public array $scores;
    public int $cols;
    public int $rows;
    public array $hiking_trails;

    public function __construct(array $map)
    {
        $this->map = array();
        foreach($map as $y => $line) {
            $this->map[$y] = str_split($line);
            foreach($this->map[$y] as $x => $h) {
                $this->map[$y][$x] = (int) $h;
            }
        }
        $this->rows = count($this->map);
        $this->cols = count($this->map[0]);
        $this->hiking_trails = array();
    }

    public function getRowAsString(int $r): string | null
    {
        if ($r < 0 || $r > $this->rows) return null;

        return implode('', $this->map[$r]);
    }

    public function findTrailHeads(): array
    {
        $heads = array();
        $keys = array_keys($this->map);
        foreach($keys as $y) {
            $row = $this->getRowAsString($y);
            if (preg_match_all('/(0)/', $row, $matches, PREG_OFFSET_CAPTURE)) {
                foreach($matches[0] as $m) {
                    $pos = new Position($y, x: $m[1]);
                    $trailhead = new Topo(h: 0, p: $pos);
                    $heads[] = new HikingTrail($trailhead);
                }
            }
        }

        return $heads;
    }

    public function getTopology(Position $p): int | null
    {
        if ($this->outOfBounds($p)) return null;

        return $this->map[$p->y][$p->x];
    }

    public function getSiblings(Position $p, ?int $topo = null): array
    {
        $self = $this;
        $siblings = array_filter(
            array(
                'north' => Position::getFromOffset($p, [-1, 0]),
                'east'  => Position::getFromOffset($p, [0, 1]),
                'south' => Position::getFromOffset($p, [1, 0]),
                'west'  => Position::getFromOffset($p, [0, -1]),
            ),
            function($p) use ($self) {
                return !$self->outOfBounds($p);
            }
        );

        if ($topo !== null && !empty($siblings)) {
            $siblings = array_filter($siblings, fn($p) => $self->getTopology($p) === $topo);
        }

        return $siblings;
    }

    public function outOfBounds(Position $p): bool
    {
        if ($p->y < 0 || $p->y >= $this->rows || $p->x < 0 || $p->x >= $this->cols) return true;

        return false;
    }
}

class Position
{
    public function __construct(public int $y, public int $x) {}

    public function __toString(): string
    {
        return sprintf('[%s,%s]', $this->y, $this->x);
    }

    public function __toArray(): array
    {
        return [$y, $x];
    }

    static function getFromOffset(Position $p, array $v): Position
    {
        return new Position($p->y + $v[0], $p->x + $v[1]); 
    }

    static function getPositionKey(Position $p, string $prefix = ''): string
    {
        return ltrim(sprintf('%s_%d_%d', $prefix, $p->y, $p->x), '_');
    }
}

class Topo
{
    public function __construct(public int $h, public Position $p) {}
}

class Trail extends Topo
{
    public array $children;
    public bool $complete;
    public ?Trail $parent;
    public array $score_sheet;
    public int $score;

    public function __construct(public int $h, public Position $p, ?int $l = 1)
    {
        parent::__construct($h, $p);
        $this->children = array();
        $this->complete = false;
        $this->score = 0;
        $this->score_sheet = array();
    }

    public function completed(Position $p): void
    {
        $this->complete = true;
        if ($this->parent !== null) {
            $this->parent->completed($p);
        }

        if ($this->parent === null) {
            $key = $this->p . '---' . $p;
            if (!isset($this->score_sheet[$key])) {
                $this->score_sheet[$key] = 0;
                $this->score++;
            }
            $this->score_sheet[$key]++;
        }
    }

    public function __toString(): string
    {
        return sprintf('H: %d, %s | score: %d', $this->h, $this->p, $this->score);
    }

    public function build(): void
    {
        if ($this->h === 9) {
            $this->completed($this->p);
        } else {
            $next = Main::getMap()->getSiblings($this->p, $this->h + 1);
            foreach($next as $i => $p) {
                $this->children[$i] = new Trail($this->h + 1, $p);
                $this->children[$i]->parent = $this;
                $this->children[$i]->build();
            }
        }

    }
}

class HikingTrail
{
    public Trail $trailhead;

    public function __construct(Topo $ht)
    {
        $this->trailhead = new Trail($ht->h, $ht->p);
        $this->trailhead->parent = null;
    }

    public function buildTrail()
    {
        $this->trailhead->build();
    }
}

try {
    $default = './test.txt';
    $path = isset($argv[1]) ? $argv[1] : $default;

    $main = new Main($path);
    $main->run();
} catch (Throwable $e) {
    die(sprintf('Error (%d): %s%s%s', $e->getLine(), $e->getMessage(), PHP_EOL, $e->getTraceAsString()));
}
