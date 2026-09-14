<?php

declare(strict_types=1);

define('TEST_MODE', 'TEST_MODE');

require_once (__DIR__ . '/../vendor/autoload.php');
require_once (__DIR__ . '/../puzzle09.php');

use PHPUnit\Framework\TestCase;

class Day09Test extends TestCase
{
    public function getInput(string $path): array
    {
        return file(dirname(__FILE__) . '/' . $path, FILE_IGNORE_NEW_LINES);
    }

    public function testCanPredictNextNumber(): void
    {
        $rl = new ReportLine(array(0,3,6,9,12,15));
        $this->assertEquals(18, $rl->next_number);

        $rl = new ReportLine(array(1,3,6,10,15,21));
        $this->assertEquals(28, $rl->next_number);

        $rl = new ReportLine(array(10,13,16,21,30,45));
        $this->assertEquals(68, $rl->next_number);
    }

    public function testCanParseInputAndPredict(): void
    {
        $expectedResults = (object) array(
            'next' => array(18, 28, 68),
            'prev' => array(-3, 0, 5),
        );
        $parser = new Parser(dirname(__FILE__) . '/../test');
        $reports = Main::parseReports($parser->getInput());
        foreach($reports as $i => $r) {
            $this->assertEquals($expectedResults->next[$i], $r->next_number);
            $this->assertEquals($expectedResults->prev[$i], $r->prev_number);
        }

        $this->assertEquals(
            array_sum($expectedResults->next),
            Main::getReportLinesNextNumbersSum($reports)
        );

        $this->assertEquals(
            array_sum($expectedResults->prev),
            Main::getReportLinesPrevNumbersSum($reports)
        );
    }
}