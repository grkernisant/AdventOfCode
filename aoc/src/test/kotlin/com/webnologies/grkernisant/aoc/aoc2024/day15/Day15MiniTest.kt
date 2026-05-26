package com.webnologies.grkernisant.aoc.aoc2024.day15

import com.webnologies.grkernisant.aoc.aoc2024.Day15Mini
import org.junit.jupiter.api.Assertions
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Test

class Day15MiniTest {
    val mockInput = Day15Mini.readInput()

    @Test
    @DisplayName("Parse an input correctly")
    fun parseInput() {
        val parser = Parser(mockInput)
        // how many rows?
        Assertions.assertEquals(8, parser.map.size)
        // how many cols?
        Assertions.assertEquals(8, parser.map[0].size)
        // Robot Position
        Assertions.assertEquals(2, parser.robotPosition.x)
        Assertions.assertEquals(2, parser.robotPosition.y)
        Assertions.assertEquals(MapTile.ROBOT, parser.map[2][2])
        // Floor
        Assertions.assertEquals(MapTile.FLOOR, parser.map[2][3])
        // Boxes
        Assertions.assertEquals(6, parser.boxesPositions.size)
        Assertions.assertEquals(MapTile.BOX, parser.map[2][4])
        // Moves
        Assertions.assertEquals(15, parser.moves.size)
    }

    @Test
    @DisplayName("Loads a warehouse correctly")
    fun parseAndLoadWarehouse() {
        val parser = Parser(mockInput)
        val warehouse = Warehouse.of(parser)
        // Dimensions
        Assertions.assertEquals(8, warehouse.cols)
        Assertions.assertEquals(8, warehouse.rows)
        // Robot Moves
        Assertions.assertEquals(RobotMove.LEFT, warehouse.getMoveIntent())
        Assertions.assertEquals(RobotMove.UP, warehouse.getMoveIntent())
        Assertions.assertEquals(RobotMove.UP, warehouse.getMoveIntent())
        Assertions.assertEquals(RobotMove.RIGHT, warehouse.getMoveIntent())
        Assertions.assertEquals(RobotMove.RIGHT, warehouse.getMoveIntent())
        Assertions.assertEquals(RobotMove.RIGHT, warehouse.getMoveIntent())
        Assertions.assertEquals(RobotMove.DOWN, warehouse.getMoveIntent())
    }

    @Test
    @DisplayName("Robot moves sequentially and pushes boxes around")
    fun robotMovesAndPushesBoxes() {
        val parser = Parser(mockInput)
        val warehouse = Warehouse.of(parser)
        val expectedRobotPositions: List<Pair<RobotMove, Position>> = listOf(
            RobotMove.LEFT to Position(2, 2),
            RobotMove.UP to Position(2, 1),
            RobotMove.UP to Position(2, 1),
            RobotMove.RIGHT to Position(3, 1),
            RobotMove.RIGHT to Position(4, 1),
            RobotMove.RIGHT to Position(4, 1),
            RobotMove.DOWN to Position(4, 2),
            RobotMove.DOWN to Position(4, 2),
            RobotMove.LEFT to Position(3, 2),
            RobotMove.DOWN to Position(3, 3),
            RobotMove.RIGHT to Position(4, 3),
            RobotMove.RIGHT to Position(5, 3),
            RobotMove.DOWN to Position(5, 4),
            RobotMove.LEFT to Position(4, 4),
            RobotMove.LEFT to Position(4, 4),
        )

        expectedRobotPositions.forEach { pair ->
            warehouse.moveRobot()
            Assertions.assertEquals(pair.second, warehouse.robot)
        }
    }

    @Test
    @DisplayName("Robot moves in loop and pushes boxes around")
    fun warehouseRunsRobotInLoop() {
        val parser = Parser(mockInput)
        val warehouse = Warehouse.of(parser)
        val expectedRobotFinalPosition: String = """
            ########
            #....OO#
            ##.....#
            #.....O#
            #.#O@..#
            #...O..#
            #...O..#
            ########
        """.trimIndent()

        warehouse.runRobot()
        Assertions.assertEquals(expectedRobotFinalPosition, "$warehouse")
    }

    @Test
    @DisplayName("Warehouse GPS is 2028")
    fun warehouseGoodPositionSystem() {
        val parser = Parser(mockInput)
        val warehouse = Warehouse.of(parser)
        val expectedGPS: Int = 2028

        warehouse.runRobot()
        Assertions.assertEquals(expectedGPS, warehouse.getGPS())
    }
}