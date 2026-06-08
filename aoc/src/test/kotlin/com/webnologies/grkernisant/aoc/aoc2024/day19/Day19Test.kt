package com.webnologies.grkernisant.aoc.aoc2024.day19

import com.webnologies.grkernisant.aoc.aoc2024.Day19
import org.junit.jupiter.api.Assertions
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Test

class Day19Test {
    val mockInput = Day19.readInput()

    @Test
    @DisplayName("Parses an input correctly")
    fun parsesAnInput() {
        val parser = Parser(mockInput)
        val onsen = Onsen.of(parser)

        Assertions.assertEquals(8, onsen.patterns.size)
        Assertions.assertEquals(8, parser.towels.size)
    }

    @Test
    @DisplayName("Counts possible designs")
    fun towelDesignCount() {
        val parser = Parser(mockInput)
        val onsen = Onsen.of(parser)

        Assertions.assertEquals(16UL, onsen.getCombinationCount())
    }
}