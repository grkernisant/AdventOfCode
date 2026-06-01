package com.webnologies.grkernisant.aoc.aoc2024.day16

import com.webnologies.grkernisant.aoc.aoc2024.Day16B
import org.junit.jupiter.api.Assertions
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Test

class Day16BTest {
    val mockInput = Day16B.readInput()

    @Test
    @DisplayName("parses an input correctly")
    fun parseInput() {
        val parser = Parser(mockInput)
        Assertions.assertEquals(Position(1, 15), parser.fetchStart())
        Assertions.assertEquals(Position(15, 1), parser.fetchEnd())

        val maze = Maze.of(parser)
        Assertions.assertEquals(Position(1, 15), maze.start)
        Assertions.assertEquals(Tile.START, maze.getTile(1, 15)?.tile)
        Assertions.assertEquals(Position(15, 1), maze.end)
        Assertions.assertEquals(Tile.END, maze.getTile(15, 1)?.tile)

        maze.initExplore()
        Assertions.assertEquals(11048, maze.getDistanceEnd())
    }

    @Test
    @DisplayName("counts accurate nb of best path places")
    fun findNbBestPaths() {
        val parser = Parser(mockInput)
        val maze = Maze.of(parser)
        maze.initExplore()

        Assertions.assertEquals(64, maze.getNbOnBestPaths())
    }
}