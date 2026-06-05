package com.webnologies.grkernisant.aoc.aoc2024.day18

import com.webnologies.grkernisant.aoc.aoc2024.Day18
import org.junit.jupiter.api.Test
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Assertions

class Day18Test {
    val mockInput = Day18.readInput()
    val mockEnv = Day18.readEnv()

    @Test
    @DisplayName("parses input and env correctly")
    fun parsesInputAndEnvCorrectly() {
        val parser = Parser(mockInput, mockEnv)

        Assertions.assertEquals(7, parser.cols)
        Assertions.assertEquals(7, parser.rows)
        Assertions.assertEquals(mockEnv["COLS"]?.toInt(), parser.cols)
        Assertions.assertEquals(mockEnv["ROWS"]?.toInt(), parser.rows)
        Assertions.assertEquals(Coords(5, 4), parser.bytes.first())
        Assertions.assertEquals(Coords(2, 0), parser.bytes.last())

        val expectedGridAfter12fallen = """
            ...#...
            ..#..#.
            ....#..
            ...#..#
            ..#..#.
            .#..#..
            #.#....
        """.trimIndent()
        val fallenBytes = mockEnv["FALLEN_BYTES"]?.toInt() ?: 0
        val ms = MemorySpace.of(parser)
        val actualGridAfter12fallen = ms.getGridAfterStr(fallenBytes)
        Assertions.assertEquals(expectedGridAfter12fallen, actualGridAfter12fallen)
    }

    @Test
    @DisplayName("Escape steps count")
    fun getEscapeStepsCount() {
        val parser = Parser(mockInput, mockEnv)
        val fallenBytes = mockEnv["FALLEN_BYTES"]?.toInt() ?: 0
        val ms = MemorySpace.of(parser)
        val escapeRoutes = ms.getEscapeRoute(fallenBytes)
        Assertions.assertEquals(22, escapeRoutes.first().size - 1)
    }

    @Test
    @DisplayName("Finds blocking escape tile")
    fun findBlockingEscapeTile() {
        val parser = Parser(mockInput, mockEnv)
        val fallenBytes = mockEnv["FALLEN_BYTES"]?.toInt() ?: 0
        val ms = MemorySpace.of(parser)
        val l = ms.bytes.size
        var n: Int = fallenBytes + 1
        var found = false
        do {
            val escapeRoutes = ms.getEscapeRoute(n)
            found = escapeRoutes.isNotEmpty()
            if (found) n++
        } while (n < l && found)
        Assertions.assertEquals("6,1", ms.bytes[n - 1].toCacheKey())
    }
}