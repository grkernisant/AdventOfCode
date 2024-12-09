<?php

class Parser
{
    const ANTENNA_REGEX = '/([a-zA-Z0-9])/';

    public Map $map;

    public function __construct(public array $lines)
    {
        $this->map = new Map();
        $cols = null;
        $rows = count($lines);
        foreach($lines as $y => $line) {
            $line = trim($line);
            if ($cols === null) $cols = strlen($line);
            if (preg_match_all(static::ANTENNA_REGEX, $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach($matches[0] as $match) {
                    $p = new Position($y, $match[1]);
                    $a = new Antenna($match[0], $p);
                    $this->map->addAntenna($a);
                }
            }
        }
        $this->map->rows = $rows;
        $this->map->cols = $cols ?? 0;
    }

    public function run()
    {
        $this->map->setAntiNodes(with_harmonics: false);
        echo sprintf('There are %d anti nodes on the map', $this->map->getNbAntiNodes()), str_repeat(PHP_EOL, 2);

        $this->map->setAntiNodes(with_harmonics: true);
        echo sprintf('There are %d anti nodes on the map', $this->map->getNbAntiNodes()), str_repeat(PHP_EOL, 2);

        echo $this->map, PHP_EOL;
    }
}

class Map
{
    const ANTENNA_KEY_PREFIX = 'ant_';

    public array $antennas;
    public array $anti_nodes;
    public int $rows;
    public int $cols;

    public function __construct()
    {
        $this->antennas = array();
        $this->anti_nodes = array();
    }

    public function __toString()
    {
        $output = '';
        for($j=0; $j<$this->rows; $j++) {
            $ans = array_filter($this->anti_nodes, function($an) use ($j) {
                return $an->pos->y === $j;
            });
            $line = str_repeat('.', $this->cols);
            foreach($ans as $an) {
                $line[$an->pos->x] = '#';
            }
            $output.= $line . PHP_EOL;
        }

        return $output;
    }

    public function addAntenna(Antenna $a): void
    {
        $key = static::getAntennaKey($a->char);
        if (!isset($this->antennas[$key])) $this->antennas[$key] = array();

        $this->antennas[$key][] = $a;
    }

    public function addAntiNode(Antenna $a, Antenna $b, bool $with_harmonics = false): void
    {
        $vector_xab = $b->pos->x - $a->pos->x;
        $vector_yab = $b->pos->y - $a->pos->y;

        $vector_xba = $a->pos->x - $b->pos->x;
        $vector_yba = $a->pos->y - $b->pos->y;

        $n = $with_harmonics ? 0 : 1;
        do {
            $xab = $b->pos->x + $n * $vector_xab;
            $yab = $b->pos->y + $n * $vector_yab;
            $p1  = new Position($yab, $xab);
            $in_bound = !$this->outOfBounds($p1);
            $key = static::getAntiNodeKey($p1);
            if ($in_bound && !isset($this->anti_nodes[$key])) {
                $this->anti_nodes[$key] = new AntiNode(for: $a->char, pos: $p1);
            }

            $n++;
        } while($in_bound && $with_harmonics);

        $n = $with_harmonics ? 0 : 1;
        do {
            $xba = $a->pos->x + $n * $vector_xba;
            $yba = $a->pos->y + $n * $vector_yba;
            $p2  = new Position($yba, $xba);
            $in_bound = !$this->outOfBounds($p2);
            $key = static::getAntiNodeKey($p2);
            if ($in_bound && !isset($this->anti_nodes[$key])) {
                $this->anti_nodes[$key] = new AntiNode(for: $a->char, pos: $p2);
            }

            $n++;
        } while($in_bound && $with_harmonics);
    }

    public function clearAntiNodes(): void
    {
        $this->anti_nodes = array();
    }

    public static function getAntennaKey(string $c): string
    {
        return static::ANTENNA_KEY_PREFIX . $c;
    }

    public static function getAntiNodeKey(Position $p): string
    {
        return sprintf('anti_node_%d_%d', $p->y, $p->x);
    }

    public function getNbAntiNodes(): int
    {
        return count($this->anti_nodes);
    }

    public function outOfBounds(Position $p): bool
    {
        if ($p->y < 0 || $p->y >= $this->rows) return true;
        if ($p->x < 0 || $p->x >= $this->cols) return true;

        return false;
    }

    public function setAntiNodes(bool $with_harmonics = false): void
    {
        $this->clearAntiNodes();
        foreach($this->antennas as $key => $antennas) {
            for($j=0; $j<count($antennas)-1; $j++) {
                $a = $antennas[$j];
                for($i=$j+1; $i<count($antennas); $i++) {
                    $b = $antennas[$i];
                    $this->addAntiNode($a, $b, $with_harmonics);
                }
            }
        }
    }
}

class Antenna
{
    public function __construct(public string $char, public Position $pos) {}

    public function __toString()
    {
        return sprintf('(%s: %s)', $this->char, $this->pos->__toString());
    }
}


class AntiNode
{
    public function __construct(public string $for, public Position $pos) {}
}

class Position
{
    public function __construct(public int $y, public int $x) {}

    public function __toString()
    {
        return sprintf('[%d, %d]', $this->y, $this->x);
    }
}

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