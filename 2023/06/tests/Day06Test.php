<?php declare(strict_types=1);

define('TEST_MODE', 'TEST_MODE');

require_once(dirname(__FILE__) . '/../vendor/autoload.php');
require_once(dirname(__FILE__) . '/../puzzle06.php');

use PHPUnit\Framework\TestCase;

final class Day06Test extends TestCase
{
    public function getInput(string $path): array
    {
        return file(dirname(__FILE__) . '/' . $path);
    }

    public function testParseInputsCorrectly()
    {
        $racesWithKerning = Main::parseRaces($this->getInput('../test'));
        $this->assertEquals(3, count($racesWithKerning));

        $racesNoKerning = Main::parseRaces($this->getInput('../test'), with_kerning: false);
        $this->assertEquals(1, count($racesNoKerning));
    }

    public function testCalculatesBoatDistances()
    {
        $races = Main::parseRaces($this->getInput('../test'));

        $expectedResults = array(0, 6, 10, 12, 12, 10, 6, 0);
        $this->assertEquals(count($expectedResults), count($races[0]->results));

        foreach($races[0]->results as $bfp => $d) {
            $this->assertEquals($expectedResults[$bfp], $d);
        }
    }

    public function testCalculatesWaysToWinAndErrorMargin()
    {
        $expectedResults = array(4, 8, 9);
        $races = Main::parseRaces($this->getInput('../test'));
        foreach($races as $i => $r) {
            $this->assertEquals($expectedResults[$i], $r->ways_to_win);
        }

        $this->assertEquals(288, Main::getErrorMargin($races));
    }

    public function testCalculatesWaysToWinAndErrorMarginWithoutKerning()
    {
        $races = Main::parseRaces($this->getInput('../test'), false);
        $this->assertEquals(71530, $races[0]->time);
        $this->assertEquals(940200, $races[0]->distance);
        $this->assertEquals(71503, Main::getErrorMargin($races));
    }
}