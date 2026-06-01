package com.webnologies.grkernisant.aoc.aoc2024.day16

import com.webnologies.grkernisant.aoc.aoc2024.Day16
import org.junit.jupiter.api.Assertions
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Test

class Day16Test {
    val mockInput = Day16.readInput()

    @Test
    @DisplayName("parses an input correctly")
    fun parseInput() {
        val parser = Parser(mockInput)
        Assertions.assertEquals(Position(1, 13), parser.fetchStart())
        Assertions.assertEquals(Position(13, 1), parser.fetchEnd())

        val maze = Maze.of(parser)
        Assertions.assertEquals(Position(1, 13), maze.start)
        Assertions.assertEquals(Tile.START, maze.getTile(1, 13)?.tile)
        Assertions.assertEquals(Position(13, 1), maze.end)
        Assertions.assertEquals(Tile.END, maze.getTile(13, 1)?.tile)

        maze.initExplore()
        Assertions.assertEquals(7036, maze.getDistanceEnd())
    }
}