<?php declare(strict_types=1);

define('TEST_MODE', 'TEST_MODE');

require_once(dirname(__FILE__) . '/../puzzle05.php');

use PHPUnit\Framework\TestCase;

final class Day05Test extends TestCase
{
    public function getInput(string $path): array
    {
        return file(dirname(__FILE__) . '/' . $path);
    }

    public function testCanParseAlmanacCorrectly(): void
    {
        $almanac = Almanac::factorize($this->getInput('../test'));

        $this->assertEquals(count($almanac->seeds), 4);
        $expectedSeeds = array(79, 14, 55, 13);
        foreach($almanac->seeds as $i => $seed_id) {
            $this->assertEquals($seed_id, $expectedSeeds[$i]);
        }

        $this->assertEquals(count($almanac->transforms), 7);
        $expectedMap = array(
            'seed' => 'soil',
            'soil' => 'fertilizer',
            'fertilizer' => 'water',
            'water' => 'light',
            'light' => 'temperature',
            'temperature' => 'humidity',
            'humidity' => 'location'
        );
        foreach($almanac->transforms as $k => $v) {
            $this->assertEquals($v, $expectedMap[$k]);
        }
    }

    public function testCanConvertFromSeedToSoil(): void
    {
        $almanac = Almanac::factorize($this->getInput('../test'));
        $expectedSeedsToSoil = array(
            79 => 81,
            14 => 14,
            55 => 57,
            13 => 13
        );
        foreach($expectedSeedsToSoil as $k => $v) {
            $this->assertEquals($almanac->convertTo($k, 'seed', 'soil'), $v);
        }
    }

    public function testCanConvertFromSeedToLocation(): void
    {
        $almanac = Almanac::factorize($this->getInput('../test'));
        $expectedResults = array(
            array(
                'seed' => 79,
                'soil' => 81,
                'fertilizer' => 81,
                'water' => 81,
                'light' => 74,
                'temperature' => 78,
                'humidity' => 78,
                'location' => 82
            ),
            array(
                'seed' => 14,
                'soil' => 14,
                'fertilizer' => 53,
                'water' => 49,
                'light' => 42,
                'temperature' => 42,
                'humidity' => 43,
                'location' => 43
            ),
            array(
                'seed' => 55,
                'soil' => 57,
                'fertilizer' => 57,
                'water' => 53,
                'light' => 46,
                'temperature' => 82,
                'humidity' => 82,
                'location' => 86
            ),
            array(
                'seed' => 13,
                'soil' => 13,
                'fertilizer' => 52,
                'water' => 41,
                'light' => 34,
                'temperature' => 34,
                'humidity' => 35,
                'location' => 35
            )
        );
        foreach($expectedResults as $result) {
            $keys = array_keys($result);
            $vals = array_values($result);
            // step by step
            for ($i = 1; $i < count($keys); $i++) {
                $mapped_id = $almanac->convertTo($vals[$i-1], $keys[$i-1], $keys[$i]);
                $this->assertEquals($mapped_id, $vals[$i]);
            }
            // automatic conversion
            $mapped_id = $almanac->convertTo(reset($vals), reset($keys), end($keys));
            $this->assertEquals($mapped_id, end($vals));
        }
    }

    public function testFindsLowestLocationNumber(): void
    {
        $almanac = Almanac::factorize($this->getInput('../test'));
        $this->assertEquals($almanac->getLowestLocationNumber(), 35);
    }
}