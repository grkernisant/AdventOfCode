package com.webnologies.grkernisant.aoc.aoc2024.day15

data class Parser2xWide(
    val boxesPositions: List<Position>,
    val map: List<List<MapTile>>,
    val moves: List<RobotMove>,
    val robotPosition: Position,
) : ParserInterface {
    override fun fetchBoxesPositions(): List<Position> { return this.boxesPositions }
    override fun fetchMapTile(): List<List<MapTile>> { return this.map }
    override fun fetchMoves(): List<RobotMove> { return this.moves }
    override fun fetchRobotPosition(): Position { return this.robotPosition }

    companion object {
        fun of(parser: Parser): Parser2xWide {
            val parsedMap = parser.map.fold(mutableListOf<MutableList<MapTile>>(), { accRow, row ->
                val parsedRow = row.fold(mutableListOf<MapTile>()) { accCol, col ->
                    when(col.char) {
                        '#' -> {
                            accCol.add(MapTile.WALL)
                            accCol.add(MapTile.WALL)
                        }
                        'O' -> {
                            accCol.add(MapTile.BOX_2X_WIDE_LEFT)
                            accCol.add(MapTile.BOX_2X_WIDE_RIGHT)
                        }
                        '.' -> {
                            accCol.add(MapTile.FLOOR)
                            accCol.add(MapTile.FLOOR)
                        }
                        '@' -> {
                            accCol.add(MapTile.ROBOT)
                            accCol.add(MapTile.FLOOR)
                        }
                    }
                    accCol
                }
                accRow+= parsedRow
                accRow
            })
            val parsedBoxesPositions = parser.boxesPositions.map { pb ->
                Position(x = 2 * pb.x, y = pb.y)
            }
            val parsedRobotPosition = Position(
                x = 2 * parser.robotPosition.x,
                y = parser.robotPosition.y
            )

            return Parser2xWide(
                boxesPositions = parsedBoxesPositions,
                map = parsedMap,
                moves = parser.moves.map { it },
                robotPosition = parsedRobotPosition,
            )
        }
    }
}
