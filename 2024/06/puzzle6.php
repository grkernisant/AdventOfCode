<?php

$default = './test.txt';
$path = isset($argv[1]) ? $argv[1] : $default;

if (!is_readable($path)) Throw new Exception('Unreadable input');
$lines = file($path);

class Board
{
    const LANDMARK = '#';
    const LOOP_TRAP = 'O';
    const FREE_SPACE = '.';
    const VISITED_SPACE = 'X';

    const CONNECTION_ALL = '+';
    const CONNECTION_NORTH_SOUTH = '|';
    const CONNECTION_EAST_WEST = '-';

    public array $positions;
    public array $loops;
    public array $loop_traps;
    public array $unnecessary_loop_traps;

    public function __construct(public int $rows, public int $cols) {
        $this->positions = array();
        $this->loops = array();
        $this->loop_traps = array();
        $this->unnecessary_loop_traps = array();
    }

    public function addLoop(Position $p): bool
    {
        $cache_key = Position::getPositionKey($p, 'loop');
        if (isset($this->loops[$cache_key])) return false;

        $this->loops[$cache_key] = $p->toCoordinates();
        return true;
    }

    public function addLoopTrap(Position $p, Direction $d): bool
    {
        $cache_key = Position::getPositionKey($p, 'loop_trap');
        if (isset($this->loop_traps[$cache_key])) return false;

        $this->loop_traps[$cache_key] = (object) array(
            'position' => $p->toCoordinates(),
            'direction' => $d,
        );
        return true;
    }

    public function checkLoopAt(array $history, array $crossroads, array $guards_vision, int $first_loop_index): void
    {
        $self = $this;
        $crossroads = array_filter($crossroads, function($cr, $index) use($self, $history, $guards_vision, $first_loop_index) {
            if ($index <= $first_loop_index) return false;

            $spotted = $cr->findIndex($guards_vision);
            if ($spotted) return false;

            // cache exists?
            $cache_key = Position::getPositionKey($cr, 'loop_trap');
            if (isset($self->loop_traps[$cache_key])) return false;

            $turn_right = Direction::turnRightFrom($cr->getDirection());

            // can we turn right at the previous position?
            $p = $history[$index-1];
            $forced_road = $self->getPositionsFrom($p, $turn_right);
            if (
                !empty($forced_road) &&
                ($already_visited = $self->filterGuardHasVisited($forced_road, true)) &&
                count($forced_road) === count($already_visited) &&
                $cr->findIndex($self->unnecessary_loop_traps) === null
            ) {
                $self->addLoopTrap($cr, $turn_right);
                /*// blacklist loop trap ahead
                $road_ahead = $self->getPositionsFrom($cr, $cr->getDirection());
                array_splice($road_ahead, count($road_ahead)-1, 1);
                if (!empty($road_ahead)) {
                    array_splice(
                        $self->unnecessary_loop_traps,
                        count($self->unnecessary_loop_traps),
                        0,
                        $road_ahead
                    );
                }*/
                return true;
            }

            // is the point ahead loop trap?
            $p = isset($history[$index+1]) ? $history[$index+1] : null;
            $next_ahead = Direction::getNextPosition($self, $cr, $cr->getDirection());
            if ($p && $next_ahead && Position::isEqual($p, $next_ahead)) {
                $forced_road = $self->getPositionsFrom($cr, $turn_right);
                if (
                    !empty($forced_road) &&
                    ($already_visited = $self->filterGuardHasVisited($forced_road, true)) &&
                    count($forced_road) === count($already_visited) &&
                    $next_ahead->findIndex($self->unnecessary_loop_traps) === null
                ) {
                    $self->addLoopTrap($next_ahead, $turn_right);
                    // blacklist loop trap ahead
                    /*$road_ahead = $self->getPositionsFrom($next_ahead, $cr->getDirection());
                    array_splice($road_ahead, count($road_ahead)-1, 1);
                    if (!empty($road_ahead)) {
                        array_splice(
                            $self->unnecessary_loop_traps,
                            count($self->unnecessary_loop_traps),
                            0,
                            $road_ahead
                        );
                    }*/
                    return true;
                }
            }

            return false;
        }, ARRAY_FILTER_USE_BOTH);
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

    public function filterGuardHasVisited(array $positions, bool $visited): array
    {
        if (count($positions) === 0) return array();

        return array_filter(
            $positions,
            function($p) use ($visited) {
                return $p->visited === $visited;
            }
        );
    }

    public function getPosition(Position $p): Position | null
    {
        if ($p->y < 0 || $p->y >= $this->rows) return null;
        if ($p->y < 0 || $p->y >= $this->rows) return null;

        return $this->positions[$p->y][$p->x] ?? null;
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

    public function clearPositionConnections(Position $p): void
    {
        $this->getPosition($p)->resetConnections();
    }

    public function updatePositionConnections(Position $p)
    {
        $siblings = array(
            'north' => $this->getPosition(Position::fromOffset($p, [-1, 0])),
            'east'  => $this->getPosition(Position::fromOffset($p, [0, 1])),
            'south' => $this->getPosition(Position::fromOffset($p, [1, 0])),
            'west'  => $this->getPosition(Position::fromOffset($p, [0, -1])),
        );

        $visited_siblings = array_filter($siblings, fn($s) => $s?->visited);
        foreach($visited_siblings as $vs) {
            $this->getPosition($p)->addConnection($vs, 'guard_visited');
        }
    }
}

class Position
{
    public Direction $direction;
    public array $connections;
    public object $connection_types;

    public function __construct(public int $y, public int $x, public bool $landmark = false, public bool $visited = false, public $loop_trap = false)
    {
        $this->resetConnections();
    }

    public function __toString(): string
    {
        if ($this->landmark) return Board::LANDMARK;
        if ($this->loop_trap) return Board::LOOP_TRAP;

        if ($this->connection_types->north_south || $this->connection_types->east_west) {
            if ($this->connection_types->north_south && $this->connection_types->east_west)
                return Board::CONNECTION_ALL;

            if ($this->connection_types->north_south)
                return Board::CONNECTION_NORTH_SOUTH;

            return Board::CONNECTION_EAST_WEST;
        }

        if ($this->visited) return Board::VISITED_SPACE;

        return Board::FREE_SPACE;
    }

    public function __toDebug(): string
    {
        return sprintf('[%d, %d]', $this->y, $this->x);
    }

    public function getDirection() { return $this->direction; }
    public function setDirection(Direction $d) { $this->direction = $d; }

    public function addConnection(Position $p, string $prefix = ''): bool
    {
        $k = self::getPositionKey($p, $prefix);
        $added = false;
        if (!isset($this->connections[$k])) {
            $this->connections[$k] = $p;
            $added = true;
        }

        $diff = $this->getDiff($p);
        switch (serialize($diff)) {
            case Direction::$SIBLING_NORTH:
            case Direction::$SIBLING_SOUTH:
                if (!$this->connection_types->north_south) {
                    $this->connection_types->north_south = true;
                    $added = true;    
                }
                break;

            case Direction::$SIBLING_EAST:
            case Direction::$SIBLING_WEST:
                if (!$this->connection_types->east_west) {
                    $this->connection_types->east_west = true;
                    $added = true;
                }
                break;
        }

        return $added;
    }

    public function resetConnections(): void
    {
        $this->connections = array();
        $this->connection_types = (object) array(
            'north_south' => false,
            'east_west' => false
        );
    }

    public function findIndex(array $collection): array | null
    {
        $indexes = array();
        $keys = array_keys($collection);
        foreach($keys as $k) {
            if (static::isEqual($collection[$k], $this)) {
                array_push($indexes, $k);
            }
        }

        return $indexes ?: null;
    }

    public function getDiff(Position $p): array
    {
        return [$p->y - $this->y, $p->x - $this->x];
    }

    static public function getPositionKey(Position $p, string $prefix = ''): string
    {
        return trim(sprintf('%s_%d_%d', $prefix, $p->y, $p->x), '_ ');
    }

    static public function isEqual(Position $p1, Position $p2): bool
    {
        return $p1->y === $p2->y && $p1->x === $p2->x;
    }

    static public function fromCoordinates(array $coords): Position
    {
        return new Position(x: $coords[1], y: $coords[0]);
    }

    static public function fromOffset(Position $p, array $offset): Position
    {
        return new Position(x: $p->x + $offset[1], y: $p->y + $offset[0]);
    }

    public function toCoordinates(): array
    {
        return [$this->y, $this->x];
    }
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

    const TURN_90_LEFT  = 'TURN_90_LEFT';
    const TURN_90_RIGHT = 'TURN_90_RIGHT';

    static public $SIBLING_NORTH = '';
    static public $SIBLING_EAST  = '';
    static public $SIBLING_SOUTH = '';
    static public $SIBLING_WEST  = '';

    static public $cache = array();

    public string $facing;

    private function __construct(string $d)
    {
        $this->facing = $d;
    }

    static public function getNextPosition(Board $b, Position $p, Direction $d): Position | null
    {
        switch($d->facing) {
            case static::NORTH: return $b->positions[$p->y-1][$p->x] ?? null;
            case static::EAST:  return $b->positions[$p->y][$p->x+1] ?? null;
            case static::SOUTH: return $b->positions[$p->y+1][$p->x] ?? null;
            case static::WEST:  return $b->positions[$p->y][$p->x-1] ?? null;
        }

        return null;
    }

    static public function from(string $d): Direction | null
    {
        switch ($d) {
            case static::NORTH:
            case static::EAST:
            case static::SOUTH:
            case static::WEST:
                $cache_key = 'direction_' . $d;
                if (isset(static::$cache[$cache_key])) return static::$cache[$cache_key];

                $direction = new Direction($d);
                static::$cache[$cache_key] = $direction;

                return $direction;
            break;

            default:
                Throw new Exception(sprintf('UNAVAILABLE_DIRECTION %s', $d));
            break;
        }

        return null;
    }

    static public function turnLeftFrom(Direction $d): Direction | null
    {
        if ($d->facing === static::NORTH) return Direction::from(static::WEST);
        if ($d->facing === static::WEST) return Direction::from(static::SOUTH);
        if ($d->facing === static::SOUTH) return Direction::from(static::EAST);
        if ($d->facing === static::EAST) return Direction::from(static::NORTH);

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

    static public function uTurnFrom(Direction $d): Direction | null
    {
        if ($d->facing === static::NORTH) return Direction::from(static::SOUTH);
        if ($d->facing === static::EAST) return Direction::from(static::WEST);
        if ($d->facing === static::SOUTH) return Direction::from(static::NORTH);
        if ($d->facing === static::WEST) return Direction::from(static::EAST);

        return null;
    }

    static public function initSiblings(): void
    {
        if (static::$SIBLING_NORTH === '') {
            static::$SIBLING_NORTH = serialize([-1, 0]);
            static::$SIBLING_EAST  = serialize([0, 1]);
            static::$SIBLING_SOUTH = serialize([1, 0]);
            static::$SIBLING_WEST  = serialize([0, -1]);
        }
    }
}

class Parser
{
    public int $cols;
    public int $rows;
    public Board $board;
    public Guard $guard;
    public string $initial_direction;
    public Position $initial_guard;
    public array $history;

    public function __construct(array $lines)
    {
        $this->rows = count($lines);
        foreach($lines as $y => $line) {
            $this->map($y, $line);
        }

        $this->history = array();
    }

    public function __toString(): string
    {
        $output = '';
        for($j = 0; $j<$this->board->rows; $j++) {
            for($i = 0; $i<$this->board->cols; $i++) {
                $position = $this->board->positions[$j][$i];
                $is_landmark = $position->landmark;
                $has_visited = $position->visited;
                $is_trap  = $position->loop_trap;
                $is_guard = $this->isGuardPosition($position);
                $initial  = $this->isInitialGuardPosition($position); 
                $output.= ($is_guard || $is_trap)
                    ? ($is_trap ? Board::LOOP_TRAP : $this->guard->direction->facing)
                    : (
                        $is_landmark
                        ? Board::LANDMARK
                        : (
                            $has_visited
                            ? ($initial ? $this->initial_direction : $this->board->getPosition($position)->__toString())
                            : Board::FREE_SPACE
                        )
                    );
            }
            $output.= PHP_EOL;
        }

        return trim($output);
    }

    public function displayPart1Answer(int $nb_places, $nb_turns): void
    {
        echo sprintf('The guard has visited %d places in %d turns and took %d steps', $nb_places, $nb_turns, count($this->history)), PHP_EOL;
    }

    public function displayPart2Answer(): void
    {
        echo $this, str_repeat(PHP_EOL, 3);
        echo sprintf('We think there are %d safe possible traps', count($this->board->loop_traps)), PHP_EOL;
    }

    public function filterPositionCrossroads(array $positions): array
    {
        if (count($positions) === 0) return array();

        return array_filter(
            $positions,
            fn($p) => $p->connection_types->north_south && $p->connection_types->east_west
        );
    }

    public function guardExplores(): array {
        $gp = $this->guard->getPosition();
        $gd = $this->guard->getDirection();
        return $this->board->getPositionsFrom($gp, $gd);
    }

    public function isGuardPosition(Position $p): bool
    {
        return $p->y === $this->guard->position->y && $p->x === $this->guard->position->x;
    }

    public function isInitialGuardPosition(Position $p): bool
    {
        return $p->y === $this->initial_guard->y && $p->x === $this->initial_guard->x;
    }

    public function markAsVisited(array $positions): int
    {
        $nb_updated = 0;
        $gd = $this->guard->getDirection();
        foreach($positions as $p) {
            if ($this->board->getPosition($p)->visited === false) {
                $this->board->getPosition($p)->visited = true;
                $this->board->getPosition($p)->setDirection($gd);
                $nb_updated++;
            } else {
                // found a loop
                $this->board->addLoop($p);
            }
            $this->history[] = $this->board->getPosition($p);
        }

        return $nb_updated;
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
            if ($start) {
                $this->guard = new Guard($p, Direction::from($v), 0);
                $this->initial_direction = $v;
                $this->initial_guard = $p;
            }
        }
    }

    public function run(): void
    {
        $continue = true;
        $nb_positions = 0;
        $turn = 0;

        // part 1
        try {
            do {
                if ($doras_map = $this->guardExplores()) {
                    // remove double discovery
                    if ($turn > 0) array_shift($doras_map);
                    // mark map as visited
                    $nb_positions+= $this->markAsVisited($doras_map);
                    // move guard
                    $goes_to = end($doras_map);
                    $this->guard->setPosition($goes_to);
                    if (!$this->board->isBorder($goes_to)) {
                        $this->turnGuard(Direction::TURN_90_RIGHT);
                    } else {
                        $continue = false;
                    }
                } else {
                    Throw new Exception('Guard is stuck!');
                }
                $turn++;
            } while ($continue);
        } catch(Throwable $e) {
            echo sprintf('Error: %s, at line(%d)%s%s', $e->getMessage(), $e->getLine(), PHP_EOL, $e->getTraceAsString()), PHP_EOL;
        }
        
        $this->displayPart1Answer($nb_positions, $turn);

        // part 2
        try {
            if ($this->board->loops) {
                Direction::initSiblings();
                // update connections
                foreach($this->history as $h) {
                    $this->board->clearPositionConnections($h);
                    $this->board->updatePositionConnections($h);
                }
                // lets find the crossroads (keys are preserved)
                // lets filter after the first loop
                // and not in the guard first field of view
                // turning right before the crossroad should be a familiar path
                // does the next position after the crossroad create a loop?
                $first_loop = Position::fromCoordinates(reset($this->board->loops))->findIndex($this->history);
                $this->guard->setPosition(
                    $this->history[0],
                    $this->history[0]->getDirection()
                );
                $doras_map = $this->guardExplores();
                $crossroads = $this->filterPositionCrossroads($this->history);
                $hk = array_keys($this->history);
                $crossroads[end($hk)] = end($this->history);
                $this->board->checkLoopAt(
                    history: $this->history,
                    crossroads: $crossroads,
                    guards_vision:$doras_map,
                    first_loop_index: reset($first_loop)
                );
            }
         } catch(Throwable $e) {
            echo sprintf('Error: %s, at line(%d)%s%s', $e->getMessage(), $e->getLine(), PHP_EOL, $e->getTraceAsString()), PHP_EOL;
        }

        foreach($this->board->loop_traps as $lt) {
            $p = Position::fromCoordinates($lt->position);
            $this->board->getPosition($p)->loop_trap = true;
        }

        $this->displayPart2Answer();
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