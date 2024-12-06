<?php

$default = './test.txt';
$path = isset($argv[1]) ? $argv[1] : $default;
if (!is_readable($path)) die ("Could not open input file.\n");

$input = file_get_contents($path);

$a = new Board($input, 'XMAS');
echo $a->getAnswer(), ': ', $a->getScore(), PHP_EOL;

$b = new Board($input, 'MAS');
echo $b->getAnswer(), '(X): ', $b->getScoreX(), PHP_EOL;

class Board
{
    CONST HORIZONTAL_RIGHT = 'HORIZONTAL_RIGHT';
    CONST HORIZONTAL_LEFT  = 'HORIZONTAL_LEFT';
    CONST VERTICAL_DOWN    = 'VERTICAL_DOWN';
    CONST VERTICAL_UP      = 'VERTICAL_UP';

    CONST DIAGONAL_NE      = 'DIAGONAL_NE';
    CONST DIAGONAL_SE      = 'DIAGONAL_SE';
    CONST DIAGONAL_SW      = 'DIAGONAL_SW';
    CONST DIAGONAL_NW      = 'DIAGONAL_NW';

    CONST ALL_DIRECTIONS   = '__ALL';

    private int $cols;
    private int $rows;
    private array $board;
    private string $answer;
    private Solutions $solutions;

    public function __construct(string $input, $answer)
    {
        $this->solutions = new Solutions();

        $rows = explode(PHP_EOL, trim($input));
        $line = str_split(trim($rows[0]));
        $this->rows = count($rows);
        $this->cols = count($line);

        $this->board = array();
        foreach($rows as $y => $line) {
            $letters = str_split(trim($line));
            foreach($letters as $x => $char) {
                $this->board[$y][$x] = new Letter(char: $char, x: $x, y: $y);
            }
        }

        $this->answer = $answer;

        for($j = 0; $j<$this->rows; $j++) {
            for($i = 0; $i<$this->cols; $i++) {
                $this->findWords($j, $i, $answer);
            }
        }
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function getScore(): int
    {
        return $this->solutions->directions[Board::ALL_DIRECTIONS]->total;
    }

    public function getScoreX(): int
    {
        $score_x = 0;

        // match DIAGONAL_SE with DIAGONAL_NE
        $score_x+= $this->solutions->getDirectionXScore(self::DIAGONAL_SE, self::DIAGONAL_NE) ?? 0;

        // match DIAGONAL_SE with DIAGONAL_SW
        $score_x+= $this->solutions->getDirectionXScore(self::DIAGONAL_SE, self::DIAGONAL_SW) ?? 0;

        // match DIAGONAL_NW with DIAGONAL_NE
        $score_x+= $this->solutions->getDirectionXScore(self::DIAGONAL_NW, self::DIAGONAL_NE) ?? 0;

        // match DIAGONAL_NW with DIAGONAL_SW
        $score_x+= $this->solutions->getDirectionXScore(self::DIAGONAL_NW, self::DIAGONAL_SW) ?? 0;

        return $score_x;
    }

    /**
     * get all words at position $x, $y
     */
    public function findWords(int $y, int $x, string $answer): void
    {
        // bounding coordinates
        $max_incr = strlen($answer);
        $range_hor_pos = range($x, min($this->cols - 1, $x + $max_incr - 1));
        $range_hor_neg = range($x, max(0, $x - $max_incr + 1));
        $range_ver_pos = range($y, min($this->rows - 1, $y + $max_incr - 1));
        $range_ver_neg = range($y, max(0, $y - $max_incr + 1));

        // horizontal - left to right
        $w = '';
        $range = array(...$range_hor_pos);
        while(count($range) > 0) {
            $i = array_shift($range);
            $w.= $this->board[$y][$i]->getChar();
        }
        $this->validateWord(word: $w, x: $x, y: $y, direction: self::HORIZONTAL_RIGHT);

        // horizontal - right to left
        $w = '';
        $range = array(...$range_hor_neg);
        while(count($range) > 0) {
            $i = array_shift($range);
            $w.= $this->board[$y][$i]->getChar();
        }
        $this->validateWord(word: $w, x: $x, y: $y, direction: self::HORIZONTAL_LEFT);

        // vertical - top to bottom
        $w = '';
        $range = array(...$range_ver_pos);
        while(count($range) > 0) {
            $j = array_shift($range);
            $w.= $this->board[$j][$x]->getChar();
        }
        $this->validateWord(word: $w, x: $x, y: $y, direction: self::VERTICAL_DOWN);

        // vertical - bottom to top
        $w = '';
        $range = array(...$range_ver_neg);
        while(count($range) > 0) {
            $j = array_shift($range);
            $w.= $this->board[$j][$x]->getChar();
        }
        $this->validateWord(word: $w, x: $x, y: $y, direction: self::VERTICAL_UP);

        // diagonal north - east
        $w = '';
        $range_x = array(...$range_hor_pos);
        $range_y = array(...$range_ver_neg);
        $incr = min(count($range_x), count($range_y));
        for($n=0; $n<$incr; $n++) {
            $i = $range_x[$n];
            $j = $range_y[$n];
            $w.= $this->board[$j][$i]->getChar();
        }
        $this->validateWord(word: $w, x: $x, y: $y, direction: self::DIAGONAL_NE);

        // diagonal south - east
        $w = '';
        $range_x = array(...$range_hor_pos);
        $range_y = array(...$range_ver_pos);
        $incr = min(count($range_x), count($range_y));
        for($n=0; $n<$incr; $n++) {
            $i = $range_x[$n];
            $j = $range_y[$n];
            $w.= $this->board[$j][$i]->getChar();
        }
        $this->validateWord(word: $w, x: $x, y: $y, direction: self::DIAGONAL_SE);

        // diagonal south - west
        $w = '';
        $range_x = array(...$range_hor_neg);
        $range_y = array(...$range_ver_pos);
        $incr = min(count($range_x), count($range_y));
        for($n=0; $n<$incr; $n++) {
            $i = $range_x[$n];
            $j = $range_y[$n];
            $w.= $this->board[$j][$i]->getChar();
        }
        $this->validateWord(word: $w, x: $x, y: $y, direction: self::DIAGONAL_SW);

        // diagonal north - west
        $w = '';
        $range_x = array(...$range_hor_neg);
        $range_y = array(...$range_ver_neg);
        $incr = min(count($range_x), count($range_y));
        for($n=0; $n<$incr; $n++) {
            $i = $range_x[$n];
            $j = $range_y[$n];
            $w.= $this->board[$j][$i]->getChar();
        }
        $this->validateWord(word: $w, x: $x, y: $y, direction: self::DIAGONAL_NW);
    }

    private function validateWord(string $word, int $x, int $y, string $direction): bool
    {
        $valid = ($word === $this->answer);
        if ($valid) {
            $s = new Solution(solution: $this->answer, x: $x, y: $y, direction: $direction);
            $this->solutions->addSolution($s);
        }

        return $valid;
    }
}

class Letter
{
    public function __construct(private string $char, private int $x, private int $y) {}

    public function getChar(): string
    {
        return $this->char;
    }
}

class Solutions
{
    public array $directions;

    public function __construct()
    {
        $this->directions = array(
            Board::HORIZONTAL_LEFT => (object) array('solutions' => array(), 'total' => 0),
            Board::HORIZONTAL_RIGHT => (object) array('solutions' => array(), 'total' => 0),
            Board::VERTICAL_DOWN => (object) array('solutions' => array(), 'total' => 0),
            Board::VERTICAL_UP => (object) array('solutions' => array(), 'total' => 0),
            Board::DIAGONAL_NE => (object) array('solutions' => array(), 'total' => 0),
            Board::DIAGONAL_SE => (object) array('solutions' => array(), 'total' => 0),
            Board::DIAGONAL_SW => (object) array('solutions' => array(), 'total' => 0),
            Board::DIAGONAL_NW => (object) array('solutions' => array(), 'total' => 0),
            Board::ALL_DIRECTIONS => (object) array('solutions' => array(), 'total' => 0),
        );
    }

    public function addSolution(Solution $s): void
    {
        array_push($this->directions[$s->direction]->solutions, $s);
        $this->directions[$s->direction]->total++;

        array_push($this->directions[Board::ALL_DIRECTIONS]->solutions, $s);
        $this->directions[Board::ALL_DIRECTIONS]->total++;
    }

    public function getDirectionScore(?string $direction = null): int | null
    {
        if ($direction === null) return $this->directions[Board::ALL_DIRECTIONS]->total;

        if (isset($this->directions[$direction])) return $this->directions[$direction]->total;

        return null;
    }

    public function getDirectionSolutions(?string $direction = null): array | null
    {
        if ($direction === null) $direction = Board::ALL_DIRECTIONS;

        if (isset($this->directions[$direction])) return $this->directions[$direction]->solutions;

        return null;
    }

    public function getDirectionXScore(string $dir_1, string $dir_2): int | null
    {
        $score_dir_x = 0;
        $ne = $this->getDirectionSolutions(Board::DIAGONAL_NE);
        $se = $this->getDirectionSolutions(Board::DIAGONAL_SE);
        $sw = $this->getDirectionSolutions(Board::DIAGONAL_SW);
        $nw = $this->getDirectionSolutions(Board::DIAGONAL_NW);

        if ($dir_1 === Board::DIAGONAL_SE && $dir_2 === Board::DIAGONAL_NE) {
            // match DIAGONAL_SE with DIAGONAL_NE
            if ($se && $ne) {
                if (($offset = $this->getOffset($se[0]->solution))=== null) return 0;

                foreach($se as $se_sol) {
                    foreach($ne as $ne_sol) {
                        $score_dir_x+= ($ne_sol->x === $se_sol->x && $ne_sol->y === ($se_sol->y + $offset)) ? 1 : 0;
                    }
                }

                return $score_dir_x;
            }
        } else if ($dir_1 === Board::DIAGONAL_SE && $dir_2 === Board::DIAGONAL_SW) {
            // match DIAGONAL_SE with DIAGONAL_SW
            if ($se && $sw) {
                if (($offset = $this->getOffset($se[0]->solution))=== null) return 0;

                foreach($se as $se_sol)
                    foreach($sw as $sw_sol)
                        $score_dir_x+= ($sw_sol->x === ($se_sol->x + $offset) && $sw_sol->y === $se_sol->y) ? 1 : 0;

                return $score_dir_x;
            }
        } else if ($dir_1 === Board::DIAGONAL_NW && $dir_2 === Board::DIAGONAL_NE) {
            // match DIAGONAL_NW with DIAGONAL_NE
            if ($nw && $ne) {
                if (($offset = $this->getOffset($se[0]->solution))=== null) return 0;

                foreach($nw as $nw_sol)
                    foreach($ne as $ne_sol)
                        $score_dir_x+= ($ne_sol->x === ($nw_sol->x - $offset) && $ne_sol->y === $nw_sol->y) ? 1 : 0;

                return $score_dir_x;
            }
        } else if ($dir_1 === Board::DIAGONAL_NW && $dir_2 === Board::DIAGONAL_SW) {
            // match DIAGONAL_NW with DIAGONAL_SW
            if ($nw && $sw) {
                if (($offset = $this->getOffset($se[0]->solution))=== null) return 0;

                foreach($nw as $nw_sol)
                    foreach($sw as $sw_sol)
                        $score_dir_x+= ($sw_sol->x === $nw_sol->x && $sw_sol->y === ($nw_sol->y - $offset)) ? 1 : 0;

                return $score_dir_x;
            }
        }

        return null;
    }

    public function getOffset(string $solution): int | null
    {
        $offset = strlen($solution);
        if ($offset % 2 == 0) return null;

        return ceil($offset / 2);
    }
}

class Solution
{
    public function __construct(public string $solution, public int $x, public int $y, public string $direction) {}
}
