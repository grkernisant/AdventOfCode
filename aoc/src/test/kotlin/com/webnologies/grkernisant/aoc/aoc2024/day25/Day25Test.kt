package com.webnologies.grkernisant.aoc.aoc2024.day25

import com.webnologies.grkernisant.aoc.aoc2024.Day25
import org.junit.jupiter.api.Assertions
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Test

class Day25Test {
    val mockInput = Day25.readInput()

    @Test
    @DisplayName("parses an input correctly")
    fun parseInput() {
        val parser = Parser(mockInput)
        Assertions.assertEquals(2, parser.locks.size)
        Assertions.assertEquals(3, parser.keys.size)

        val expectedLockHeights = listOf(
            listOf(0,5,3,4,3),
            listOf(1,2,0,5,3),
        )
        expectedLockHeights.forEachIndexed { i, height ->
            val heightStr = parser.locks[i].toPinCodeString(height)
            val pinCodeStr = parser.locks[i].toPinCodeString()
            Assertions.assertEquals(heightStr, pinCodeStr)
        }

        val expectedKeyHeights = listOf(
            listOf(5,0,2,1,3),
            listOf(4,3,4,0,2),
            listOf(3,0,2,0,1),
        )
        expectedKeyHeights.forEachIndexed { i, height ->
            val heightStr = parser.keys[i].toPinCodeString(height)
            val pinCodeStr = parser.keys[i].toPinCodeString()
            Assertions.assertEquals(heightStr, pinCodeStr)
        }
    }

    @Test
    @DisplayName("Find that 3 unique combination of key/lock fit")
    fun findUniqueKeyLockFitCount() {
        val parser = Parser(mockInput)
        Assertions.assertEquals(3, parser.getKeyLockFitCount())
    }
}