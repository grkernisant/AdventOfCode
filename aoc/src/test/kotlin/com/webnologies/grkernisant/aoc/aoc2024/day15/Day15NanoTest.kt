package com.webnologies.grkernisant.aoc.aoc2024.day15

import com.webnologies.grkernisant.aoc.aoc2024.Day15Nano
import org.junit.jupiter.api.Assertions
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Test

class Day15NanoTest {
    val mockInput = Day15Nano.readInput()

    @Test
    @DisplayName("Parse an input correctly")
    fun parseInput() {
        val parser = Parser(mockInput)
        // how many rows?
        Assertions.assertEquals(7, parser.map.size)
        // how many cols?
        Assertions.assertEquals(7, parser.map[0].size)
        // Robot Position
        Assertions.assertEquals(5, parser.robotPosition.x)
        Assertions.assertEquals(3, parser.robotPosition.y)
        Assertions.assertEquals(MapTile.ROBOT, parser.map[3][5])
        // Floor
        Assertions.assertEquals(MapTile.FLOOR, parser.map[3][1])
        // Boxes
        Assertions.assertEquals(3, parser.boxesPositions.size)
        Assertions.assertEquals(MapTile.BOX, parser.map[3][3])
        Assertions.assertEquals(MapTile.BOX, parser.map[3][4])
        Assertions.assertEquals(MapTile.BOX, parser.map[4][3])
        // Moves
        Assertions.assertEquals(11, parser.moves.size)

        val parser2x = Parser2xWide.of(parser)
        // how many rows?
        Assertions.assertEquals(7, parser2x.map.size)
        // how many cols?
        Assertions.assertEquals(14, parser2x.map[0].size)
        // Robot Position
        Assertions.assertEquals(10, parser2x.robotPosition.x)
        Assertions.assertEquals(3, parser2x.robotPosition.y)
        Assertions.assertEquals(MapTile.ROBOT, parser2x.map[3][10])
        // Floor
        Assertions.assertEquals(MapTile.FLOOR, parser2x.map[3][2])
        // Boxes
        Assertions.assertEquals(3, parser2x.boxesPositions.size)
        Assertions.assertEquals(MapTile.BOX_2X_WIDE_LEFT, parser2x.map[3][6])
        Assertions.assertEquals(MapTile.BOX_2X_WIDE_LEFT, parser2x.map[3][8])
        Assertions.assertEquals(MapTile.BOX_2X_WIDE_LEFT, parser2x.map[4][6])
        // Moves
        Assertions.assertEquals(11, parser2x.moves.size)
    }

    @Test
    @DisplayName("Robot can move around and push larger boxes")
    fun robotPushesLargerBoxes() {
        val parser = Parser2xWide.of(Parser(mockInput))

        Assertions.assertEquals(10, parser.robotPosition.x)
        Assertions.assertEquals(3, parser.robotPosition.y)

        val warehouse = Warehouse.of(parser)
        val expectedRobotPositionAfterMove: List<Pair<RobotMove, Position>> = listOf(
            Pair(RobotMove.LEFT, Position(9, 3)),
            Pair(RobotMove.DOWN, Position(9, 4)),
            Pair(RobotMove.DOWN, Position(9, 5)),
            Pair(RobotMove.LEFT, Position(8, 5)),
            Pair(RobotMove.LEFT, Position(7, 5)),
            Pair(RobotMove.UP, Position(7, 4)),
            Pair(RobotMove.UP, Position(7, 4)),
            Pair(RobotMove.LEFT, Position(6, 4)),
            Pair(RobotMove.LEFT, Position(5, 4)),
            Pair(RobotMove.UP, Position(5, 3)),
            Pair(RobotMove.UP, Position(5, 2)),
        )
        expectedRobotPositionAfterMove.forEachIndexed{ i, pair ->
            warehouse.moveRobot()
            Assertions.assertEquals("$i: ${pair.first} ${pair.second}", "$i: ${pair.first} ${warehouse.robot}")
        }

        val expectedWarehouseMap = """
            ##############
            ##...[].##..##
            ##...@.[]...##
            ##....[]....##
            ##..........##
            ##..........##
            ##############
        """.trimIndent()
        Assertions.assertEquals(expectedWarehouseMap, warehouse.toString())
    }
}