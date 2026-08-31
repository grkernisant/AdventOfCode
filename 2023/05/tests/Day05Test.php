<?php declare(strict_types=1);

define('TEST_MODE', 'TEST_MODE');

require_once(dirname(__FILE__) . '/../vendor/autoload.php');
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
            $mapped_id = $almanac->convertTo(reset($vals), (string) reset($keys), (string) end($keys));
            $this->assertEquals($mapped_id, end($vals));
        }
    }

    public function testFindsLowestLocationNumber(): void
    {
        $almanac = Almanac::factorize($this->getInput('../test'));
        $this->assertEquals($almanac->getLowestLocationNumber(), 35);
    }

    public function testGetSeedsAsListOrRange(): void
    {
        $nb_seeds = 0;
        $almanac1 = Almanac::factorize($this->getInput('../test'));
        foreach($almanac1->getSeeds() as $seed) $nb_seeds++;
        $this->assertEquals($nb_seeds, 4);

        $nb_seeds = 0;
        $almanac2 = Almanac::factorize($this->getInput('../test'), SeedMode::AsRange);
        foreach($almanac2->getSeeds() as $seed) $nb_seeds++;
        $this->assertEquals($nb_seeds, 27);
    }

    public function testAlamanacHasSeed(): void
    {
        $expectedSeeds = array(79, 14, 55, 13);
        $almanac1 = Almanac::factorize($this->getInput('../test'));
        $almanac2 = Almanac::factorize($this->getInput('../test'), SeedMode::AsRange);

        foreach($expectedSeeds as $i => $s) {
            $this->assertTrue($almanac1->isSeed($s), sprintf("Expected Almanac1 %d to be a seed", $s));
            if ($i % 2 === 0) {
                $this->assertTrue($almanac2->isSeed($s), sprintf("Expected Almanac2 %d to be a seed", $s));
            }

            $this->assertFalse($almanac1->isSeed($s+10), sprintf("Expected Almanac1 %d not to be a seed", $s + 10));
            if ($i % 2 === 0) {
                $this->assertTrue($almanac2->isSeed($s+10), sprintf("Expected Almanac2 %d to be a seed", $s + 10));
            }
        }
    }

    public function testCanRevertFromSoilToSeed(): void
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
            $this->assertEquals($almanac->revertFrom($v, 'seed', 'soil'), $k);
        }
    }

    public function testCanRevertFromSeedToLocation(): void
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
                $mapped_id = $almanac->revertFrom($vals[$i], $keys[$i-1], $keys[$i]);
                $this->assertEquals($mapped_id, $vals[$i-1]);
            }
            // automatic conversion
            $mapped_id = $almanac->revertFrom(end($vals), (string) reset($keys), (string) end($keys));
            $this->assertEquals($mapped_id, reset($vals));
        }
    }

    public function testFindLowestLocationNumberAsSeedRange() {
        $alm1 = Almanac::factorize($this->getInput('../test'), SeedMode::AsList);
        $this->assertEquals(35, $alm1->getLowestLocationNumber());

        $alm2 = Almanac::factorize($this->getInput('../test'), SeedMode::AsRange);
        $this->assertEquals(46, $alm2->getLowestLocationNumber());
    }
}