<?php

require_once('./ObjectCompare.php');

class Main
{
    const GARDEN = 'garden';

    public Garden $garden;
    public Parser $parser;

    public function __construct(string $path)
    {
        $this->parser = new Parser($path);
        $this->garden = Garden::getInstance($this->parser->getInput());
    }

    public function run(): void
    {
        SharedResources::set(static::GARDEN, $this->garden);

        $this->garden->init();
    }
}

class Parser
{
    public array $input;

    public function __construct(public string $path)
    {
        if (!is_readable($path)) throw new Exception('Unreadable input');

        $this->input = file($path);
    }

    public function getInput(): array { return $this->input; }
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

class SharedResources
{
    private static array $resources = array();

    public static function get(string $resource_name)
    {
        return static::$resources[$resource_name] ?? null;
    }

    public static function set(string $resource_name, $resource): void
    {
        static::$resources[$resource_name] = $resource;
    }
}

class Garden
{
    private static $instance = null;

    public static array $_ROWS = array();
    public static array $_COLS = array();

    public static $plant_types = null;
    public static $plant_regex = null;

    public array $map;
    public array $regions;
    public int $cols;
    public int $rows;

    private function __construct(?array $map = null)
    {
        $this->map = array();
        $this->regions = array();
        $this->cols = 0;
        $this->rows = 0;

        if ($map !== null) {
            $regions = '';
            foreach($map as $y => $line) {
                $this->map[$y] = str_split(trim($line));
                foreach($this->map[$y] as $x => $p) {
                    $pos = new Position($y, $x);
                    $this->map[$y][$x] = new Plant((string) $p, $pos);
                    if (strpos($regions, $p) === false) $regions.= $p;
                }
            }
            $this->rows = count($this->map);
            $this->cols = count($this->map[0]);

            static::$plant_types = str_split($regions);
            sort(static::$plant_types);
        }
    }

    public function addRegions(array $regions): void
    {
        $added = false;
        foreach($regions as $r) { $added = $this->addRegion($r) || $added; }

        if ($added) {
            $this->delimitRegions();
        }
    }

    public function addRegion(Region $r): bool
    {
        if (!isset($this->regions[$r->getName()])) {
            $this->regions[$r->getName()] = $r;
            return true;
        }

        return false;
    }

    public function delimitRegions(): void
    {
        if (count($this->regions) === 0) return;

        $rk = array_keys($this->regions);
        $i = 0;
        do {
            if (($claim = $this->regions[$rk[$i]]->claimPlants()) === true) {
                $this->regions[$rk[$i]]->setArea();
                $this->regions[$rk[$i]]->setPerimeter();
                $i++;
            } else {
                // remove duplicates
                unset($this->regions[$rk[$i]]);
                array_splice($rk, $i, 1);
            }
        } while($i<count($rk));
    }

    public function findRegionsOnRow(int $y): array
    {
        $re = $this->getPlantRegex();
        $regions = array();
        $row = $this->getRowAsString($y);
        $from_col = 0;
        do {
            if(preg_match($re, substr($row, $from_col), $matches, PREG_OFFSET_CAPTURE)) {
                $r = new Region($this->map[$y][$from_col+$matches[0][1]]);
                /*$rn = $r->getName();
                // prepare for siblings search
                $range = range($matches[0][1], strlen($matches[0][0])-1);
                foreach($range as $x) { $this->map[$y][$x]->setRegionName($rn); }*/
                $from_col+= strlen($matches[0][0]);
                $regions[] = $r;
            } else {
                $from_col++;
            }
        } while($from_col < $this->cols);

        return $regions;
    }

    public function findAllPlantSiblings(): void
    {
        for($y = 0; $y < $this->rows; $y++) {
            for($x = 0; $x < $this->cols; $x++) {
                $this->map[$y][$x]->setSiblings();
            }
        }
    }

    public function findAllRegions(): void
    {
        // lets discover line by line
        $regions = array();
        for($y = 0; $y < $this->rows; $y++) {
            $row_regions = $this->findRegionsOnRow($y);
            if ($row_regions) array_splice($regions, count($regions), 0, $row_regions);
        }
        if ($regions) $this->addRegions($regions);
    }

    /*public function getColAsString(int $c): string | null
    {
        if ($c < 0 || $c > $this->cols) return null;

        if (isset(static::$_COLS[$c])) return static::$_COLS[$c];

        $col = '';
        for($y = 0; $y < $this->rows; $y++) $col.= $this->map[$y][$x];
        static::$_COLS[$c] = $col;

        return static::$_COLS[$c];
    }*/

    public static function getInstance(?array $map = null): Garden
    {
        if (static::$instance !== null) return static::$instance;

        static::$instance = new Garden($map);
        return static::$instance;
    }

    public function getPlant(Position $p): Plant | null
    {
        if ($this->outOfBounds($p)) return null;

        return $this->map[$p->y][$p->x];
    }

    public function getPlantRegex(): string
    {
        if (static::$plant_regex !== null) return static::$plant_regex;

        $or_plant = implode('|', static::$plant_types);
        $plant = sprintf('((?i)[%s])\1*', $or_plant);
        $plant_regex = sprintf('#%s#', $plant);
        Logger::debug('plant_regex', $plant_regex);

        static::$plant_regex = $plant_regex;
        return static::$plant_regex;
    }

    public function getPrice(): int
    {
        $price = array_reduce($this->regions, function($acc, $curr) {
            $acc+= $curr->getPrice();
            return $acc;
        }, 0);

        return $price;
    }

    public function getRowAsString(int $r): string | null
    {
        if ($r < 0 || $r > $this->rows) return null;

        if (isset(static::$_ROWS[$r])) return static::$_ROWS[$r];

        static::$_ROWS[$r] = implode('', $this->map[$r]);

        return static::$_ROWS[$r];
    }

    public function getRegion(Position $p): string | null
    {
        if ($this->outOfBounds($p)) return null;

        return $this->map[$p->y][$p->x];
    }

    public function init(): void
    {
        $this->findAllPlantSiblings();
        $this->findAllRegions();
        echo sprintf('The total price for fencing is: %d', $this->getPrice()), PHP_EOL;

        $this->setRegionSides();
    }

    public function outOfBounds(Position $p): bool
    {
        if ($p->y < 0 || $p->y >= $this->rows || $p->x < 0 || $p->x >= $this->cols) return true;

        return false;
    }

    public function setRegionSides(): void
    {
        $this->sortRegionPlants();

        foreach($this->regions as $k => $region) $region->setNbSides();
    }

    public function sortRegionPlants(): void
    {
        foreach($this->regions as $k => $region) {
            usort($region->plants, [Region::class, 'sortByCoords']);
        }
    }
}

class Region
{
    private int $area;
    private int $perimeter;
    public array $plants;
    public int $nb_sides;

    public function __construct(public Plant $hpic)
    {
        $this->plants = array();
        $this->area = 0;
        $this->perimeter = 0;
        $this->nb_sides = 0;
    }

    public function claimPlants(): bool
    {
        if ($this->hpic->getClaim() === true) return false;

        $cert = new Certificate(
            author: $this->getName(),
            signature: $this->getName() . (string) hrtime(true)
        );
        $this->hpic->applyCertificate($cert);
        array_push($this->plants, $this->hpic);
        array_splice($this->plants, 1, 0, $this->hpic->getSiblings($cert));

        return true;
    }

    public function getArea(): int
    {
        return $this->area;
    }

    public function getName(): string
    {
        return sprintf('%s%s', $this->hpic->name, $this->hpic->pos);
    }

    public function getPerimeter(): int
    {
        return $this->perimeter;
    }

    public function getPrice(): int
    {
        $price = $this->getArea() * $this->getPerimeter();
        echo sprintf(
            'Region: %s ( %d x %d ) = %d',
            $this->getName(),
            $this->getArea(), $this->getPerimeter(),
            $price
        ), PHP_EOL;

        return $price;
    }

    public function getNbSides(): int
    {
        return $this->nb_sides;
    }

    public function getPlantsPerRow(int $y): array
    {
        $plants_y = array_filter($this->plants, function($p) use ($y) {
            return $p->pos->y === $y;
        });

        return $plants_y;
    }

    public function setArea(): void
    {
        $this->area = count($this->plants);
    }

    public function setNbSides(): void
    {
        $min_y = reset($this->plants)->pos->y;
        $max_y = end($this->plants)->pos->y;

        // outer sides
        $outer_boundaries = array();
        $this->nb_sides = 3;
        $plants_row = $this->getPlantsPerRow($min_y);
        $outer_boundaries[] = (object) array('lower' => reset($plants_row), 'upper' => end($plants_row));
        $prev_min_x = reset($plants_row)->pos->x;
        $prev_max_x = end($plants_row)->pos->x;
        $i = $min_y + 1;
        while ($i <= $max_y) {
            $plants_row = $this->getPlantsPerRow($i);
            $outer_boundaries[] = (object) array('lower' => reset($plants_row), 'upper' => end($plants_row));
            $min_x = reset($plants_row)->pos->x;
            $max_x = end($plants_row)->pos->x;
            if ($min_x !== $prev_min_x) $this->nb_sides+= 2;
            if ($max_x !== $prev_max_x) $this->nb_sides+= 2;

            $prev_min_x = $min_x;
            $prev_max_x = $max_x;
            $i++;
        }
        $this->nb_sides++;
        echo sprintf('%s has %d outer sides', $this->hpic->getFullname(), $this->nb_sides), PHP_EOL;

        // inner sides
    }

    public function setPerimeter(): void
    {
        $this->perimeter = array_reduce($this->plants, function($acc, $curr) {
            $acc+= $curr->getBorderCount();
            return $acc;
        }, 0);
    }

    static public function sortByCoords(Plant $a, Plant $b): int
    {
        if ($a->pos->y < $b->pos->y) return -1;
        if ($a->pos->y > $b->pos->y) return 1;

        if ($a->pos->x < $b->pos->x) return -1;
        if ($a->pos->x > $b->pos->x) return 1;

        return 0;
    }
}

class Plant
{
    const SIDES = 4;

    public array $siblings;
    public string $fullname;
    public string $region_name;
    private string $password;
    private bool $claimed;
    public int $border_count;

    public function __construct(public string $name, public Position $pos)
    {
        $this->siblings = array();
        $this->password = '';
        $this->fullname = $name . $pos;
        $this->claimed = false;
        $this->setRegionName($name . $pos);
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function applyCertificate(Certificate $c): void
    {
        $this->setPassword($c->signature);
        $this->setRegionName($c->author);
        $this->setClaim(true);
    }

    public function findSiblings(?string $plant_type = null): array
    {
        $garden = SharedResources::get(Main::GARDEN);
        $positions = array_filter(
            array(
                'north' => Position::getFromOffset($this->pos, [-1, 0]),
                'east'  => Position::getFromOffset($this->pos, [0, 1]),
                'south' => Position::getFromOffset($this->pos, [1, 0]),
                'west'  => Position::getFromOffset($this->pos, [0, -1]),
            ),
            function($p) use ($garden) {
                return !$garden->outOfBounds($p);
            }
        );
        $plants = array_filter(array_map(function($p) use ($garden) {
            return $garden->getPlant($p);
        }, $positions));
        $siblings = array();
        if ($plant_type !== null && !empty($plants)) {
            $sc = new ScalarCondition(Operator::EQUAL_TO);
            $siblings = array_filter($plants, function($p) use ($sc, $plant_type) {
                return ObjectCompare::cmp($sc, $p->name, $plant_type);
            });
        }

        return $siblings;
    }

    public function getClaim(): bool
    {
        return $this->claimed;
    }

    public function getFullname()
    {
        return $this->getName() . $this->pos;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRegionName()
    {
        return $this->region_name;
    }

    public function getSiblings(?Certificate $c = null): array
    {
        if ($c === null) return $this->siblings;

        $siblings = array();
        foreach($this->siblings as $i => $s) {
            if ($this->siblings[$i]->getPassword() !== $c->signature) {
                $this->siblings[$i]->applyCertificate($c);
                array_push($siblings, $this->siblings[$i]);
                array_splice($siblings, count($siblings), 0, $this->siblings[$i]->getSiblings($c));
            }
        }

        return $siblings;
    }

    public function getBorderCount(): int
    {
        $this->border_count = static::SIDES - count($this->getSiblings());
        return $this->border_count;
    }

    public function setClaim(bool $c): void
    {
        $this->claimed = $c;
    }

    public function setPassword(string $pwd): void
    {
        $this->password = $pwd;
    }

    public function setRegionName(string $rn)
    {
        $this->region_name = $rn;
    }

    public function setSiblings()
    {
        $this->siblings = $this->findSiblings($this->name);
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

class Certificate
{
    public function __construct(public string $author, public string $signature) {}
}

try {
    $path = isset($argv[1]) ? $argv[1] : './test.txt';

    $main = new Main($path);
    $main->run();
} catch (Throwable $e) {
    die(sprintf('Error (%d): %s%s%s', $e->getLine(), $e->getMessage(), PHP_EOL, $e->getTraceAsString()));
}
