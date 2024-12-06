<?php

$default = './test.txt';
$path = isset($argv[1]) ? $argv[1] : $default;

if (!is_readable($path)) Throw new Exception('Unreadable input');
$lines = file($path);

class Board
{
    const LANDMARK = '#';
    const FREE_SPACE = '.';
    const VISITED_SPACE = 'M';

    public array $positions;

    public function __construct(public int $rows, public int $cols) {
        $this->positions = array();
    }

    public function isBorder(Position $p): bool
    {
        return
            $p->y === 0 ||
            $p->y === ($this->rows - 1) ||
            $p->x === 0 ||
            $p->x === ($this->cols - 1);
    }

    public function isLandmark(Position $p): bool
    {
        return $p->landmark;
    }

    public function hasVisited(Position $p): bool
    {
        return $p->visited;
    }

    public function getPositionsFrom(Position $from, Direction $dir): array {
        $possibilities = array();

        switch ($dir->facing) {
            case Direction::NORTH:
                $j = $from->y;
                while ($j>-1 && !$this->positions[$j][$from->x]->landmark) {
                    array_push($possibilities, $this->positions[$j][$from->x]);
                    $j--;
                }
            break;

            case Direction::EAST:
                $i = $from->x;
                while ($i<$this->cols && !$this->positions[$from->y][$i]->landmark) {
                    array_push($possibilities, $this->positions[$from->y][$i]);
                    $i++;
                }
            break;

            case Direction::SOUTH:
                $j = $from->y;
                while ($j<$this->rows && !$this->positions[$j][$from->x]->landmark) {
                    array_push($possibilities, $this->positions[$j][$from->x]);
                    $j++;
                }
            break;

            case Direction::WEST:
                $i = $from->x;
                while ($i>-1 && !$this->positions[$from->y][$i]->landmark) {
                    array_push($possibilities, $this->positions[$from->y][$i]);
                    $i--;
                }
            break;
        }

        return $possibilities;
    }

    public function setPosition(Position $p): void {
        $this->positions[$p->y][$p->x] = $p;
    }
}

class Position
{
    public function __construct(public int $y, public int $x, public bool $landmark = false, public bool $visited = false) {}
}

class Guard
{
    public function __construct(public Position $position, public Direction $direction) {}

    public function getDirection(): Direction { return $this->direction; }

    public function setDirection(Direction $d): void { $this->direction = $d; }

    public function getPosition(): Position { return $this->position; }

    public function setPosition(Position $p, Direction $d = null): void {
        $this->position = $p;
        if ($d !== null) $this->setDirection($d);
    }
}

class Direction
{
    const NORTH = '^';
    const WEST  = '<';
    const SOUTH = 'v';
    const EAST  = '>';

    const TURN_90_RIGHT = 'TURN_90_RIGHT';

    static public $cache = array();

    public string $facing;

    private function __construct(string $d)
    {
        $this->facing = $d;
    }

    static public function from(string $d): Direction | null
    {
        switch ($d) {
            case static::NORTH:
            case static::EAST:
            case static::SOUTH:
            case static::WEST:
                $direction = new Direction($d);
                return $direction;
            break;

            default:
                Throw new Exception(sprintf('UNAVAILABLE_DIRECTION %s', $d));
            break;
        }

        return null;
    }

    static public function turnRightFrom(Direction $d): Direction | null
    {

        if ($d->facing === static::NORTH) return Direction::from(static::EAST);
        if ($d->facing === static::EAST) return Direction::from(static::SOUTH);
        if ($d->facing === static::SOUTH) return Direction::from(static::WEST);
        if ($d->facing === static::WEST) return Direction::from(static::NORTH);

        return null;
    }
}

class Parser
{
    public int $cols;
    public int $rows;
    public Board $board;
    public Guard $guard;

    public function __construct(array $lines)
    {
        $this->rows = count($lines);
        foreach($lines as $y => $line) {
            $this->map($y, $line);
        }
    }

    public function __toString(): string
    {
        $output = '';
        for($j = 0; $j<$this->board->rows; $j++) {
            for($i = 0; $i<$this->board->cols; $i++) {
                $is_landmark = $this->board->isLandmark($this->board->positions[$j][$i]);
                $has_visited = $this->board->hasVisited($this->board->positions[$j][$i]);
                $output.= ($j !== $this->guard->position->y || $i !== $this->guard->position->x)
                    ? ($is_landmark ? Board::LANDMARK : ($has_visited ? Board::VISITED_SPACE : Board::FREE_SPACE))
                    : $this->guard->direction->facing;
            }
            $output.= PHP_EOL;
        }

        return trim($output);
    }

    public function display(int $nb_places, $nb_turns): void
    {
        echo sprintf('The guard has visited %d places in %d turns', $nb_places, $nb_turns), PHP_EOL;
    }

    public function guardExplores(): array {
        $gp = $this->guard->getPosition();
        $gd = $this->guard->getDirection();
        return $this->board->getPositionsFrom($gp, $gd);
    }

    public function guardVisited(array $positions, bool $dry_run = false): int
    {
        $updated = 0;
        foreach($positions as $p) {
            if ($this->board->positions[$p->y][$p->x]->visited === false) {
                if (!$dry_run) $this->board->positions[$p->y][$p->x]->visited = true;
                $updated++;
            }
        }

        return $updated;
    }

    public function map(int $y, string $line): void
    {
        $line = trim($line);
        if (!isset($this->cols)) {
            $this->cols = strlen($line);
            $this->board = new Board($this->rows, $this->cols);
        }

        $vision = str_split($line);
        foreach($vision as $x => $v) {
            $landmark   = $v === Board::LANDMARK;
            $free_space = ($v === Board::FREE_SPACE) || !$landmark;
            $start      = $v !== Board::FREE_SPACE && $free_space;

            $p = new Position($y, $x, $landmark);
            $this->board->setPosition($p);
            if ($start) $this->guard = new Guard($p, Direction::from($v));
        }
    }

    public function moveGuardTo(Position $p): void
    {
        // move guard
        $this->guard->setPosition($p);
    }

    public function run(): void
    {
        $continue = true;
        $nb_positions = 0;
        $turn = 0;
        do {
            $turn++;
            if ($doras_map = $this->guardExplores()) {
                // has the guard visited some positions before?
                $nb_updates = $this->guardVisited($doras_map, dry_run: true);
                $nb_visited = count($doras_map) - $nb_updates;
                if ($nb_visited) {
                    echo sprintf('The guard has already visited %d places out of %d', $nb_visited, count($doras_map)), PHP_EOL;
                }
                // move guard
                $goes_to = end($doras_map);
                $this->moveGuardTo($goes_to);
                // mark map as visited
                $nb_positions+= $this->guardVisited($doras_map);
                if (!$this->board->isBorder($goes_to)) {
                    $this->turnGuard(Direction::TURN_90_RIGHT);
                } else {
                    $continue = false;
                }
            } else {
                Throw new Exception('Guard is stuck');
            }
        } while ($continue);

        echo $this, str_repeat(PHP_EOL, 3);
        $this->display($nb_positions, $turn);
    }

    public function turnGuard(string $turn): void
    {
        if ($turn === Direction::TURN_90_RIGHT) {
            // turn guard
            $d = Direction::turnRightFrom($this->guard->getDirection());
            $this->guard->setDirection($d);
        }
    }
}

$parser = new Parser($lines);
$parser->run();