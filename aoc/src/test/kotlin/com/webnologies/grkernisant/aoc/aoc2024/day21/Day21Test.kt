package com.webnologies.grkernisant.aoc.aoc2024.day21

import com.webnologies.grkernisant.aoc.aoc2024.Day21
import org.junit.jupiter.api.Assertions
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Test

class Day21Test {
    val mockInput = Day21.readInput()

    @Test
    @DisplayName("Parses list of keycodes to process")
    fun parseKeyCodesToProcess() {
        val parser = Parser(mockInput)
        Assertions.assertEquals(5, parser.codes.size)

        val expectedCodes = listOf("029A", "980A", "179A", "456A", "379A")
        expectedCodes.forEachIndexed { index, expectedCode ->
            Assertions.assertEquals(expectedCode, parser.toKeyCodeString()[index])
        }
    }

    @Test
    @DisplayName("Processes a keycode and its complexity")
    fun processKeyCodesComplexity() {
        val expectedLengths = mapOf(
            "029A" to 68L,
            "980A" to 60L,
            "179A" to 68L,
            "456A" to 64L,
            "379A" to 64L,
        )
        val parser = Parser(mockInput)
        val ship = Ship.of(parser)
        ship.init()
        expectedLengths.forEach { (code, expectedLength) ->
            Assertions.assertEquals(expectedLength, ship.shortestSequenceLengths[code], "Length mismatch for $code")
        }

        Assertions.assertEquals(126384L, ship.getComplexitySum())
    }
}
