package com.webnologies.grkernisant.aoc.aoc2024.day15

import com.webnologies.grkernisant.aoc.aoc2024.Day15
import org.junit.jupiter.api.Assertions
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Test

class Day15Test {
    val mockInput = Day15.readInput()

    @Test
    @DisplayName("Parse an input correctly")
    fun parseInput() {
        val parser = Parser(mockInput)
        // how many rows?
        Assertions.assertEquals(10, parser.surface.size)
        // how many cols?
        Assertions.assertEquals(10, parser.surface[0].size)
        // Robot Position
        Assertions.assertEquals(4, parser.robotPosition.x)
        Assertions.assertEquals(4, parser.robotPosition.y)
        Assertions.assertEquals(MapTile.ROBOT, parser.surface[4][4])
        // Floor
        Assertions.assertEquals(MapTile.FLOOR, parser.surface[4][5])
        // Boxes
        Assertions.assertEquals(21, parser.boxesPositions.size)
        Assertions.assertEquals(MapTile.BOX, parser.surface[1][3])
        Assertions.assertEquals(MapTile.BOX, parser.surface[4][3])
        // Wall
        Assertions.assertEquals(MapTile.WALL, parser.surface[0][0])
        Assertions.assertEquals(MapTile.WALL, parser.surface[5][2])
        // Moves
        Assertions.assertEquals(700, parser.moves.size)
    }

    @Test
    @DisplayName("Warehouse GPS is 10092")
    fun warehouseGoodPositionSystem() {
        val parser = Parser(mockInput)
        val warehouse = Warehouse.of(parser)
        val expectedGPS: Int = 10092

        warehouse.runRobot()
        Assertions.assertEquals(expectedGPS, warehouse.getGPS())
    }

    @Test
    @DisplayName("Parses a larger map correctly and GPS is 9021")
    fun parseLargerMap() {
        val largeWarehouseExpectedMap = """
            ####################
            ##....[]....[]..[]##
            ##............[]..##
            ##..[][]....[]..[]##
            ##....[]@.....[]..##
            ##[]##....[]......##
            ##[]....[]....[]..##
            ##..[][]..[]..[][]##
            ##........[]......##
            ####################
        """.trimIndent()
        val largerWarehouseExpectedOutputMap = """
            ####################
            ##[].......[].[][]##
            ##[]...........[].##
            ##[]........[][][]##
            ##[]......[]....[]##
            ##..##......[]....##
            ##..[]............##
            ##..@......[].[][]##
            ##......[][]..[]..##
            ####################
        """.trimIndent()
        val parser = Parser2xWide.of(Parser(mockInput))
        val warehouse = Warehouse.of(parser)

        Assertions.assertEquals(largeWarehouseExpectedMap, warehouse.toString())

        warehouse.runRobot()
        Assertions.assertEquals(largerWarehouseExpectedOutputMap, warehouse.toString())

        val expectedGPS: Int = 9021
        Assertions.assertEquals(expectedGPS, warehouse.getGPS())
    }
}