package com.webnologies.grkernisant.aoc.aoc2024.day15

import kotlin.collections.forEach

typealias BoxIndexesPerAxisMap = MutableMap<Int, MutableSet<Int>>

data class Warehouse(
    val surface: MutableList<MutableList<MapTile>>,
    var robot: Position,
    val moves: MutableList<RobotMove>,
    val boxes: MutableList<Position>
) {
    val rows: Int = surface.size
    val cols: Int = surface[0].size
    var canPushBoxCache: MutableMap<String, Boolean> = mutableMapOf()

    private fun canGroupMove(groupToMove: BoxIndexesPerAxisMap, dir: RobotMove): Boolean {
        if (groupToMove.isNotEmpty()) {
            var validateGroupMove: Pair<Int, Int> = Pair(0, 0)
            groupToMove.entries.forEach { (_, boxIndexes) ->
                boxIndexes.forEach { boxIndex ->
                    val neighbors = this.getBoxSideFacing(boxIndex, dir)
                    validateGroupMove = Pair(
                        validateGroupMove.first + neighbors.size,
                        validateGroupMove.second
                    )
                    neighbors.forEach { np ->
                        val boxVision = this.getVisionFrom(np, dir)
                        val nextFloor = boxVision.indexOf(MapTile.FLOOR)
                        if (nextFloor == -1) return false

                        val nextWall = boxVision.indexOf(MapTile.WALL)
                        if (nextFloor < nextWall) {
                            validateGroupMove = Pair(
                                validateGroupMove.first,
                                validateGroupMove.second + 1
                            )
                        } else return false
                    }
                }
            }

            return validateGroupMove.first == validateGroupMove.second
        }

        return true
    }

    private fun canRobotMove(dir: RobotMove): Boolean {
        val robotVision = this.getVisionFrom(p = this.robot, dir)
        val nextFloor = robotVision.indexOf(MapTile.FLOOR)
        if (nextFloor == -1) return false

        val nextWall = robotVision.indexOf(MapTile.WALL)
        return nextFloor < nextWall
    }

      private fun clearCaches() {
        canPushBoxCache = mutableMapOf()
    }

    private fun findBoxAtPosition(p: Position): Int {
        if (!this.hasBox(p)) return -1

        return when(this.getTile(p)) {
            MapTile.BOX,
            MapTile.BOX_2X_WIDE_LEFT -> this.boxes.indexOf(p)
            MapTile.BOX_2X_WIDE_RIGHT -> this.boxes.indexOf(p.offset(RobotMove.LEFT))
            else -> -1
        }
    }

    private fun getBoxesToMove(p: Position, dir: RobotMove): BoxIndexesPerAxisMap {
        val boxes: BoxIndexesPerAxisMap = mutableMapOf()
        val boxIndex = this.findBoxAtPosition(p)
        if (boxIndex == -1) return boxes

        val box = this.boxes[boxIndex]
        val key: Char = when(dir) {
            RobotMove.LEFT,
            RobotMove.RIGHT -> 'x'
            RobotMove.UP,
            RobotMove.DOWN -> 'y'
        }
        val mapKey: Int = if (key == 'x') box.x else box.y
        boxes[mapKey] = mutableSetOf(boxIndex)
        val neighbors = this.getBoxNeighborPositionsFacing(boxIndex, dir)
        neighbors.forEach { n ->
            val neighborBoxesToMove = this.getBoxesToMove(n, dir)
            neighborBoxesToMove.entries.forEach { (k, value) ->
                if (value.isNotEmpty()) {
                    if (!boxes.containsKey(k)) boxes[k] = mutableSetOf()
                    value.forEach { v ->
                        boxes[k]?.add(v)
                    }
                }
            }
        }

        return boxes
    }

    private fun getBoxNeighborPositionsFacing(index: Int, dir: RobotMove): List<Position> {
        try {
            val pos = mutableListOf<Position>()
            val box = this.boxes[index]
            val tile = this.getTile(box)
            return when (dir) {
                RobotMove.LEFT -> {
                    for(y in 0..< tile.area.height) {
                        pos.add(Position(box.x - 1, box.y + y))
                    }
                    pos
                }
                RobotMove.UP -> {
                    for(x in 0..< tile.area.width) {
                        pos.add(Position(box.x + x, box.y - 1))
                    }
                    pos
                }
                RobotMove.RIGHT -> {
                    for(y in 0..< tile.area.height) {
                        pos.add(Position(box.x + tile.area.width, box.y + y))
                    }
                    pos
                }
                RobotMove.DOWN -> {
                    for(x in 0..< tile.area.width) {
                        pos.add(Position(box.x + x, box.y + tile.area.height))
                    }
                    pos
                }
            }.filter { p -> !this.outOfBounds(p.x, p.y) || this.findBoxAtPosition(p) == -1 }
        } catch (e: IndexOutOfBoundsException) {
            println("Warning: Out of bounds, column $index does not exist\n${e.message}")
            return emptyList()
        } catch (e: Error) {
            println("Unexpected error: ${e.message}")
            return emptyList()
        }
    }

    private fun getBoxSideFacing(index: Int, dir: RobotMove): List<Position> {
        try {
            val pos = mutableListOf<Position>()
            val box = this.boxes[index]
            val tile = this.getTile(box)
            return when (dir) {
                RobotMove.LEFT -> {
                    for(y in 0..< tile.area.height) {
                        pos.add(Position(box.x, box.y + y))
                    }
                    pos
                }
                RobotMove.UP -> {
                    for(x in 0..< tile.area.width) {
                        pos.add(Position(box.x + x, box.y))
                    }
                    pos
                }
                RobotMove.RIGHT -> {
                    for(y in 0..< tile.area.height) {
                        pos.add(Position(box.x + tile.area.width - 1, box.y + y))
                    }
                    pos
                }
                RobotMove.DOWN -> {
                    for(x in 0..< tile.area.width) {
                        pos.add(Position(box.x + x, box.y + tile.area.height - 1))
                    }
                    pos
                }
            }
        } catch (e: IndexOutOfBoundsException) {
            println("Warning: Out of bounds, column $index does not exist\n${e.message}")
            return emptyList()
        } catch (e: Error) {
            println("Unexpected error: ${e.message}")
            return emptyList()
        }
    }

    fun getCol(c: Int): List<MapTile> {
        if (this.outOfBounds(c, 0)) throw IllegalStateException("Out of bounds, column $c does not exist")

        return this.surface.fold(mutableListOf()) { acc, curr ->
            acc+= curr[c]
            acc
        }
    }

    fun getGPS(): Int {
        return this.boxes.fold(0) { acc, position ->
            acc + 100 * position.y + position.x
        }
    }

    fun getMoveIntent(): RobotMove {
        return this.moves.removeFirst()
    }

    private fun getVisionFrom(p: Position, dir: RobotMove): List<MapTile> {
        val currentTile = this.getTile(p)
        return when(dir) {
            RobotMove.LEFT -> this.getRow(p.y)
                .slice(IntRange(0, p.x - 1))
                .reversed()
            RobotMove.RIGHT -> this.getRow(p.y)
                .slice(IntRange(p.x + 1, this.cols - 1))
            RobotMove.UP -> this.getCol(p.x)
                .slice(IntRange(0, p.y - 1))
                .reversed()
            RobotMove.DOWN -> this.getCol(p.x)
                .slice(IntRange(p.y + 1, this.rows - 1))
        }
    }

    fun getRow(r: Int): List<MapTile> {
        if (this.outOfBounds(0, r)) throw IllegalStateException("Out of bounds, row $r does not exist")

        return this.surface[r]
    }

    private fun getTile(x: Int, y: Int): MapTile {
        if (this.outOfBounds(x, y)) throw IllegalArgumentException("Out of bounds tile($x, $y) does not exist)")

        return this.surface[y][x]
    }
    private fun getTile(p: Position): MapTile { return this.getTile(p.x, p.y) }

    private fun hasBox(p: Position): Boolean {
        return when (this.getTile(p)) {
            MapTile.BOX,
            MapTile.BOX_2X_WIDE_LEFT,
            MapTile.BOX_2X_WIDE_RIGHT -> true
            else -> false
        }
    }

    private fun hasFloor(p: Position): Boolean {
        return this.getTile(p) == MapTile.FLOOR
    }

    private fun hasWall(p: Position): Boolean {
        return this.getTile(p) == MapTile.WALL
    }

    private fun initTurn() {
        this.clearCaches()
    }

    fun moveRobot() {
        this.initTurn()
        val moveIntent = getMoveIntent()
        if(this.canRobotMove(moveIntent)) {
            this.moveRobotTo(moveIntent)
        }
    }

    private fun moveBoxTo(index: Int, dir: RobotMove): Boolean {
        try {
            val nextTilePos = this.boxes[index].offset(dir)
            // current tile
            val currTile = this.getTile(this.boxes[index])
            val currPos = Position(this.boxes[index].x, this.boxes[index].y)
            val copy: MutableList<MutableList<MapTile>> = mutableListOf()
            // clear map
            for (y in 0..< currTile.area.height) {
                copy.add(mutableListOf())
                for (x in 0..< currTile.area.width) {
                    copy[y].add(MapTile.of(this.surface[currPos.y + y][currPos.x + x].char))
                    this.surface[currPos.y + y][currPos.x + x] = MapTile.FLOOR
                }
            }
            // move box
            for (y in 0..< currTile.area.height) {
                for (x in 0..< currTile.area.width) {
                    this.surface[nextTilePos.y + y][nextTilePos.x + x] = copy[y][x]
                }
            }
            // update box position
            this.boxes[index] = nextTilePos
            return true
        } catch (e: IndexOutOfBoundsException) {
            println("Warning: Out of bounds, column $index does not exist\n${e.message}")
            return false
        } catch (e: Error) {
            println("Unexpected error: ${e.message}")
            return false
        }
    }

    private fun moveRobotTo(dir: RobotMove) {
        val nextTilePos = this.robot.offset(dir)
        if (this.hasWall(nextTilePos)) return
        // found boxes to move?
        if (this.hasBox(nextTilePos)) {
            val groupToMove = this.getBoxesToMove(nextTilePos, dir)
            if (groupToMove.isNotEmpty() && this.canGroupMove(groupToMove, dir)) {
                val keys = when (dir) {
                    RobotMove.LEFT,
                    RobotMove.UP -> groupToMove.keys.sorted()
                    RobotMove.RIGHT,
                    RobotMove.DOWN -> groupToMove.keys.sortedDescending()
                }
                keys.forEach { k ->
                    val boxIndexes = groupToMove[k]!!.map { this.boxes[it] }
                    val boxIndexesSorted = when (dir) {
                        RobotMove.LEFT -> boxIndexes.sortedWith { a, b -> Position.sortPositionByX(a, b) }
                        RobotMove.UP -> boxIndexes.sortedWith { a, b -> Position.sortPositionByY(a, b) }
                        RobotMove.RIGHT -> boxIndexes.sortedWith { a, b -> Position.sortPositionByX(a, b) }.reversed()
                        RobotMove.DOWN -> boxIndexes.sortedWith { a, b -> Position.sortPositionByY(a, b) }.reversed()
                    }
                    boxIndexesSorted.forEach {
                        val boxIndex = this.findBoxAtPosition(it)
                        this.moveBoxTo(boxIndex, dir)
                    }
                }
            } else return
        }

        // move robot
        if (this.hasFloor(nextTilePos)) {
            this.surface[this.robot.y][this.robot.x] = MapTile.FLOOR
            this.surface[nextTilePos.y][nextTilePos.x] = MapTile.ROBOT
            this.robot = nextTilePos
        }
    }

    private fun outOfBounds(x: Int, y: Int): Boolean {
        return x < 0 || x >= this.cols || y < 0 || y >= this.rows
    }

    fun runRobot() {
        while(this.moves.isNotEmpty()) {
            this.moveRobot()
        }
    }

    override fun toString(): String {
        return this.surface.fold("") { acc, c ->
            acc + c.joinToString("") { c -> c.char.toString() } + "\n"
        }.trim()
    }

    companion object {
        fun of(parser: ParserInterface): Warehouse =
            Warehouse(
                surface = parser.fetchMapTile().map { row ->
                    row.toMutableList()
                }.toMutableList(),
                robot = Position(x = parser.fetchRobotPosition().x, y = parser.fetchRobotPosition().y),
                moves = parser.fetchMoves().toMutableList(),
                boxes = parser.fetchBoxesPositions().map{ bp -> Position(bp.x, bp.y) }.toMutableList()
            )
    }
}
