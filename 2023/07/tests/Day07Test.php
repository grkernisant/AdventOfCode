<?php declare(strict_types=1);

define('TEST_MODE', 'TEST_MODE');

require_once (__DIR__ . '/../vendor/autoload.php');
require_once (__DIR__ . '/../puzzle07.php');

use PHPUnit\Framework\TestCase;

final class Day07Test extends TestCase
{
    public function getInput(string $path): array
    {
        return file(dirname(__FILE__) . '/' . $path);
    }

    public function testCanParseCamelPokerHands(): void
    {
        $hands = Main::parseCamelPokerHands($this->getInput('../test'));
        $this->assertNotEmpty($hands);
        $this->assertEquals(5, count($hands));

        foreach($hands as $hand) {
            $this->assertInstanceOf(CamelPokerHand::class, $hand);
            $this->assertInstanceOf(Hand::class, $hand);
            $this->assertEquals(5, count($hand->getCards()));
        }

        $winnings = Main::getCamelPokerWinnings($hands);
        $this->assertEquals(6440, $winnings);
    }

    public function testCanSubstituteJokers(): void
    {
        $hands = Main::parseCamelPokerHands($this->getInput('../test'), with_joker: true);
        $this->assertNotEmpty($hands);

        $winnings = Main::getCamelPokerWinnings($hands);
        $this->assertEquals(5905, $winnings);
    }
}