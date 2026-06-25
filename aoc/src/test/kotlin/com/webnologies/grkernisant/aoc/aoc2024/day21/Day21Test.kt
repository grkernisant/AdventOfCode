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
        val expectedComplexity = listOf<String>(
            "68 * 29",
            "60 * 980",
            "68 * 179",
            "64 * 456",
            "64 * 379",
        )
        val parser = Parser(mockInput)
        val ship = Ship.of(parser)
        ship.init()
        val complexity = ship.getComplexity()
        complexity.forEachIndexed { index, comp ->
            Assertions.assertEquals(expectedComplexity[index], "${comp.first} * ${comp.second}")
        }

        Assertions.assertEquals(126384, ship.getComplexitySum())
    }
}
