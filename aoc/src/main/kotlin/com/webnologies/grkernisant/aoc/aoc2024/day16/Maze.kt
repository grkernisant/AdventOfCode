package com.webnologies.grkernisant.aoc.aoc2024.day16

import kotlin.math.abs

data class Maze(
    val cols: Int,
    val end: Position,
    val maze: List<List<MazeTile>>,
    val rows: Int,
    val start: Position,
) {
    var distances: MutableMap<String, Int> = mutableMapOf()
    var bestScores: MutableMap<String, Int> = mutableMapOf()
    var visited: MutableSet<String> = mutableSetOf()
    var unvisited: MutableSet<String> = mutableSetOf()

    fun getDistanceEnd(): Int? {
        val endTileKey = this.getTile(this.end)?.pos?.toKey() ?: return null
        val distance = this.distances[endTileKey] ?: return null
        return distance
    }

    fun getTile(p: PositionWithDirection): MazeTile? = this.getTile(p.x, p.y)
    fun getTile(p: Position): MazeTile? = this.getTile(p.x, p.y)
    fun getTile(x: Int, y: Int): MazeTile? {
        if (this.outOfBounds(x, y)) return null

        return this.maze[y][x]
    }

    fun getUnvisitedNeighborsFrom(p: PositionWithDirection): List<PositionWithDirection> {
        val neighbors: List<PositionWithDirection> = listOf(
            p.d.getPositionWithDirection(p, -1),
            p.d.getPositionWithDirection(p, 0),
            p.d.getPositionWithDirection(p, 1),
        ).filter { p ->
            !this.outOfBounds(p) &&
            this.getTile(p)?.tile != Tile.WALL &&
            (!this.bestScores.contains(p.toDirectionKey()) || p.score < this.bestScores[p.toDirectionKey()]!!)
        }

        return neighbors
    }

    fun initExplore() {
        val startPosition = PositionWithDirection(
            x = start.x,
            y = start.y,
            d = Direction.RIGHT,
            score = 0
        )
        this.visit(startPosition)

        val neighbors = this.getUnvisitedNeighborsFrom(startPosition)
        neighbors.forEach { neighbor ->
            this.unvisited.add(neighbor.toScoreKey())
        }

        this.explore()
    }

    fun explore() {
        val nextVisit: MutableSet<String> = mutableSetOf()
        while(this.unvisited.isNotEmpty() && !this.stopExplore()) {
            nextVisit.clear()
            this.unvisited.forEach { unvKey ->
                val p = PositionWithDirection.of(unvKey)
                this.visit(p)

                this.getUnvisitedNeighborsFrom(p).forEach { neighbor ->
                    nextVisit.add(neighbor.toScoreKey())
                }
            }

            this.unvisited.clear()
            nextVisit.forEach { nv -> this.unvisited.add(nv) }
        }
    }

    fun outOfBounds(p: PositionWithDirection): Boolean = this.outOfBounds(p.x, p.y)
    fun outOfBounds(x: Int, y: Int): Boolean {
        return !(x in 0..< this.cols && y in 0 ..<this.rows)
    }

    fun stopExplore(): Boolean {
        val endDistance = this.getDistanceEnd()
        if (endDistance != null) {
            // scores too high?
            val minDistance = this.unvisited.toList().fold(-1) { acc, curr ->
                val unvisitedPosition = PositionWithDirection.of(curr)
                return@fold if (acc == -1 || unvisitedPosition.score < acc) {
                    unvisitedPosition.score
                } else {
                    acc
                }
            }
            if (minDistance > endDistance) return true

            // impossible to reach lower score?
            val stillPossible = this.unvisited.toList().filter { unv ->
                val pos = PositionWithDirection.of(unv)
                if (pos.toKey() == this.end.toKey()) return@filter true

                val deltaX = abs(this.end.x - pos.x)
                val deltaY = abs(this.end.y - pos.y)
                val shortestDistance = deltaX + deltaY
                val isAlignedStraightFromLeft = pos.d === Direction.LEFT && pos.x > this.end.x && deltaY == 0
                val isAlignedStraightFromRight = pos.d === Direction.RIGHT && pos.x < this.end.x && deltaY == 0
                val isAlignedStraightFromUp = pos.d === Direction.UP && pos.y < this.end.y && deltaX == 0
                val isAlignedStraightFromDown = pos.d === Direction.DOWN && pos.y > this.end.y && deltaX == 0
                val isAlignedStraight = isAlignedStraightFromLeft ||
                        isAlignedStraightFromRight ||
                        isAlignedStraightFromUp ||
                        isAlignedStraightFromDown
                val xorDelta = (deltaX == 0) xor (deltaY == 0)
                val onlyOneTurnFromHorizontal = (pos.d === Direction.LEFT || pos.d === Direction.RIGHT) && xorDelta
                val onlyOneTurnFromVertical = (pos.d === Direction.UP || pos.d === Direction.DOWN) && xorDelta
                val onlyOneTurn = onlyOneTurnFromHorizontal || onlyOneTurnFromVertical
                val turnCost = if (isAlignedStraight)  {
                    0
                } else if (onlyOneTurn) {
                    1000
                } else {
                    2000
                }
                val possibleScore = pos.score + shortestDistance + turnCost

                possibleScore <= endDistance
            }

            if (stillPossible.isEmpty()) return true
        }

        return false
    }

    fun visit(p: PositionWithDirection) {
        val scoreKey = p.toScoreKey()
        val directionKey = p.toDirectionKey()
        val positionKey = p.toKey()

        this.visited.add(scoreKey)

        val bestScore = this.bestScores[directionKey] ?: -1
        if (bestScore != -1 && p.score > bestScore) return
        this.bestScores[directionKey] = p.score

        val bestDistance = this.distances[positionKey] ?: -1
        if (bestDistance == -1 || p.score < bestDistance) {
            this.distances[positionKey] = p.score
        }
    }

    companion object {
        fun of(p: ParserInterface): Maze =
            Maze(
                cols = p.fetchCols(),
                end = p.fetchEnd(),
                maze = p.fetchMazeTile().map { row ->
                    row.map { col -> col }
                },
                rows = p.fetchRows(),
                start = p.fetchStart(),
            )
    }
}
