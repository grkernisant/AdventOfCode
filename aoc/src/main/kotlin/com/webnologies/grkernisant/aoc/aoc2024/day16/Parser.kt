package com.webnologies.grkernisant.aoc.aoc2024.day16

class Parser : ParserInterface {
    var cols: Int = 0
    lateinit var end: Position
    var maze: List<List<MazeTile>>
    lateinit var start: Position
    var rows: Int = 0

    constructor(input: List<String>) {
        this.maze = input.mapIndexed { y, row ->
            row.trim().split("")
                .filter { it.isNotEmpty() }
                .mapIndexed { x, col ->
                    if (col == "E") this.end = Position(x, y)
                    if (col == "S") this.start = Position(x, y)

                    MazeTile(
                        Tile.of(col.toCharArray()[0]),
                        Position(x, y)
                    )
                }
        }

        this.rows = this.maze.size
        this.cols = this.maze[0].size
    }

    override fun fetchCols(): Int { return this.cols }
    override fun fetchEnd(): Position { return this.end }
    override fun fetchMazeTile(): List<List<MazeTile>> { return this.maze }
    override fun fetchRows(): Int { return this.rows }
    override fun fetchStart(): Position { return this.start }
}
