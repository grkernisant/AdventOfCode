package com.webnologies.grkernisant.aoc.aoc2024.day15

class Parser : ParserInterface {
    lateinit var map: List<List<MapTile>>
    lateinit var moves: List<RobotMove>
    lateinit var boxesPositions: List<Position>
    lateinit var robotPosition: Position

    constructor(input: List<String>) {
        this.parse(input)
    }

    override fun fetchBoxesPositions(): List<Position> { return this.boxesPositions }
    override fun fetchMapTile(): List<List<MapTile>> { return this.map }
    override fun fetchMoves(): List<RobotMove> { return this.moves }
    override fun fetchRobotPosition(): Position { return this.robotPosition }

    private fun parse(input: List<String>) {
        val parsedMap = mutableListOf<MutableList<MapTile>>()
        val parsedMoves = mutableListOf<RobotMove>()
        val parsedBoxesPositions = mutableListOf<Position>()
        lateinit var parsedRobotPositions: Position

        input.forEachIndexed { y, it ->
            val line = it.trim()
            val mapMatches = MapTile.REGEX.findAll(line)
            if (mapMatches.count() > 0) {
                parsedMap.add(
                    line.trim()
                        .split("")
                        .filter { it.isNotEmpty() }
                        .mapIndexed { x, c ->
                            if (c == "@") {
                                parsedRobotPositions = Position(x, y)
                            }

                            if (c == "O") {
                                parsedBoxesPositions+= Position(x, y)
                            }

                            MapTile.of(c.toCharArray()[0])
                        }
                        .toMutableList()
                )
            }

            val moveMatches = RobotMove.REGEX.findAll(line)
            if (moveMatches.count() > 0) {
                parsedMoves+=
                    line.trim()
                        .split("")
                        .filter { it.isNotEmpty() }
                        .map { c -> RobotMove.of(c.toCharArray()[0]) }
            }
        }

        this.map = parsedMap
        this.moves = parsedMoves
        this.boxesPositions = parsedBoxesPositions
        this.robotPosition = parsedRobotPositions
    }

    override fun toString(): String {
        val mapOutput = this.map.fold("") { acc, c ->
            acc + c.joinToString("") { c -> c.char.toString() } + "\n"
        }.trim()
        val movesOutput = this.moves.joinToString("") { m -> m.char.toString() }
        val output = buildString {
            append("Map:\n${mapOutput}\n\n")
            append("Boxes:\n${boxesPositions}\n")
            append("RobotPosition:\n${robotPosition}\n")
            append("Moves (${moves.size}):\n${movesOutput}\n")
        }

        return output
    }
}