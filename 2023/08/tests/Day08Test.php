<?php

declare(strict_types=1);

require_once (__DIR__ . '/../vendor/autoload.php');
require_once (__DIR__ . '/../puzzle08.php');

use PHPUnit\Framework\TestCase;

class Day08Test extends TestCase
{
    public function getInput($path): array
    {
        return file(dirname(__FILE__) . '/' . $path);
    }

    public function testCanParseInput(): void
    {
        $input = $this->getInput('../test');
        $dm1 = DesertMap::fromStringArray($input);

        $this->assertEquals(2, count($dm1->instructions));
        $this->assertEquals('RIGHT', $dm1->instructions[0]->name);
        $this->assertEquals('LEFT', $dm1->instructions[1]->name);
    }

    public function testCanRunDesertMap(): void
    {
        $input = $this->getInput('../test');
        $dm1 = DesertMap::fromStringArray($input);

        $this->assertEquals(2, $dm1->run());

        $input_llr = <<<INSTRUCTIONS
        LLR

        AAA = (BBB, BBB)
        BBB = (AAA, ZZZ)
        ZZZ = (ZZZ, ZZZ)
        INSTRUCTIONS;
        $dm2 = DesertMap::fromStringArray(explode("\n", $input_llr));
        $this->assertEquals(6, $dm2->run());
    }

    public function testCanRunDesertMapConcurrent(): void
    {
        $input_lr = <<<INSTRUCTIONS
        LR

        11A = (11B, XXX)
        11B = (XXX, 11Z)
        11Z = (11B, XXX)
        22A = (22B, XXX)
        22B = (22C, 22C)
        22C = (22Z, 22Z)
        22Z = (22B, 22B)
        XXX = (XXX, XXX)
        INSTRUCTIONS;
        $dm = DesertMap::fromStringArray(explode("\n", $input_lr));

        $this->assertEquals(6, $dm->run(RunMode::CONCURRENT));
    }
}