package com.webnologies.grkernisant.aoc.aoc2024.day16

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json
import kotlin.math.abs

@Serializable
data class Maze(
    val cols: Int,
    val end: Position,
    val maze: List<List<MazeTile>>,
    val rows: Int,
    val start: Position,
) {
    var distances: MutableMap<String, MutableMap<Direction, Int>> = mutableMapOf()
    var bestScores: MutableMap<String, Int> = mutableMapOf()
    var visited: MutableSet<String> = mutableSetOf()
    var unvisited: MutableSet<String> = mutableSetOf()

    fun cacheAs(key: String) {
        CACHE[key] = Json.encodeToString(this)
    }

    fun findBestPathsBackwards(filter: PositionWithDirectionFilter): List<String> {
        val result = this.findVisitedKeys(filter)
        if (result.isEmpty()) return emptyList()

        return result.flatMap { r ->
            try {
                val p = PositionWithDirection.of(r)
                val currentPath = p.toKey()
                val prev = if (p.prev != "") Position.of(p.prev) else null

                if (prev == null || p.score == 0) return@flatMap listOf(currentPath)

                val prevResultFromStraight = this.findBestPathsBackwards(PositionWithDirectionFilter(
                    pos = prev,
                    score = p.safeScore() - 1,
                    dir = p.d
                ))

                val prevResultFromLeft = this.findBestPathsBackwards(PositionWithDirectionFilter(
                    pos = prev,
                    score = p.safeScore() - 1001,
                    dir = p.d.rotateRight()
                ))

                val prevResultFromRight = this.findBestPathsBackwards(PositionWithDirectionFilter(
                    pos = prev,
                    score = p.safeScore() - 1001,
                    dir = p.d.rotateLeft()
                ))

                val allPreviousPaths: List<String> = prevResultFromLeft + prevResultFromStraight + prevResultFromRight
                return@flatMap allPreviousPaths.map { prevPath -> "$prevPath;$currentPath" }
            } catch(_: IllegalArgumentException) {
                return emptyList()
            }
        }
    }

    fun findBestPathsForward(from: PositionWithDirection, maxScore: Int): List<String> {
        val currentPath = listOf(from.toKey())
        if (from.safeScore() >= maxScore) return currentPath

        val neighbors = this.getNeighborsFrom(from)
            .filter { n -> this.bestScores[n.toDirectionKey()] == n.score }
        if (neighbors.isEmpty()) return currentPath

        val bestPaths = neighbors.flatMap { neighbor ->
            this.findBestPathsForward(neighbor, maxScore)
        }
        return bestPaths.map { bp ->
            (currentPath + bp).joinToString(";")
        }
    }

    fun findVisitedKeys(filter: PositionWithDirectionFilter): List<String> {
        if (filter.pos != null && this.outOfBounds(filter.pos)) return emptyList()

        return this.visited.toList().filter{ v ->
            try {
                val p = PositionWithDirection.of(v)
                var cond = true

                // score
                if (filter.score != null) cond = p.score == filter.score
                if (!cond) return@filter false

                // position
                if (filter.pos != null) cond = p.toKey() == filter.pos.toKey()
                if (!cond) return@filter false

                // direction
                if (filter.dir != null) cond = p.d.facing == filter.dir.facing
                if (!cond) return@filter false

                true
            } catch(_: IllegalArgumentException) {
                return@filter false
            }
        }
    }

    fun getDistanceEnd(): Int? {
        val endTileKey = this.getTile(this.end)?.pos?.toKey() ?: return null
        val distance = this.distances[endTileKey] ?: return null
        return distance.entries.toList().minOf { it.value }
    }

    fun getNbOnBestPaths(forward: Boolean = true): Int {
        this.getDistanceEnd() ?: return 0
        return if (forward)
            this.getNbOnBestPathsForward()
        else
            this.getNbOnBestPathsBackwards()
    }

    private fun getNbOnBestPathsBackwards(): Int {
        val distanceEnd = this.getDistanceEnd() ?: return 0
        val bestPaths = this.findBestPathsBackwards(PositionWithDirectionFilter(
            pos = this.end,
            score = distanceEnd,
        ))
        return this.getUniqueTilesCount(bestPaths)
    }

    private fun getNbOnBestPathsForward(): Int {
        val distanceEnd = this.getDistanceEnd() ?: return 0
        val startTile = PositionWithDirection(
            x = this.start.x,
            y = this.start.y,
            d = Direction.RIGHT,
            score = 0,
        )
        val bestPaths = this.findBestPathsForward(startTile, distanceEnd)
            .filter{ bp -> bp.endsWith(this.end.toKey()) }
        return this.getUniqueTilesCount(bestPaths)
    }

    fun getNeighborsFrom(p: PositionWithDirection): List<PositionWithDirection> {
        val neighbors: List<PositionWithDirection> = listOf(
            p.d.getPositionWithDirection(p, 0),
            p.d.getPositionWithDirection(p, -1),
            p.d.getPositionWithDirection(p, 1),
        ).filter { p ->
            !this.outOfBounds(p) &&
            this.getTile(p)?.tile != Tile.WALL
        }

        return neighbors
    }

    fun getTile(p: PositionInterface): MazeTile? = this.getTile(p.x, p.y)
    fun getTile(x: Int, y: Int): MazeTile? {
        if (this.outOfBounds(x, y)) return null

        return this.maze[y][x]
    }

    fun getUniqueTilesCount(bestPaths: List<String>): Int {
        if (bestPaths.isEmpty()) return 0

        val uniqueMazeTiles = bestPaths.fold(mutableSetOf<String>()) { acc, curr ->
            curr.trim()
                .split(';')
                .filter { it.isNotEmpty() }
                .forEach{ p -> acc.add(p) }
            acc
        }
        return uniqueMazeTiles.size
    }

    fun getUnvisitedNeighborsFrom(p: PositionWithDirection): List<PositionWithDirection> {
        val neighbors = this.getNeighborsFrom(p).filter { p ->
            (!this.bestScores.contains(p.toDirectionKey()) || p.safeScore() < this.bestScores[p.toDirectionKey()]!!)
        }

        return neighbors
    }

    fun initExplore() {
        val startPosition = PositionWithDirection(
            x = start.x,
            y = start.y,
            d = Direction.RIGHT,
            score = 0,
            prev = start.toKey()
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

    fun outOfBounds(p: Position): Boolean = this.outOfBounds(p.x, p.y)
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
                return@fold if (acc == -1 || unvisitedPosition.safeScore() < acc) {
                    unvisitedPosition.safeScore()
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
                val possibleScore = pos.safeScore() + shortestDistance + turnCost

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
        if (bestScore != -1 && p.safeScore() > bestScore) return
        this.bestScores[directionKey] = p.safeScore()

        if (!this.distances.containsKey(positionKey)) {
            this.distances[positionKey] = mutableMapOf()
        }

        val bestDistance = this.distances[positionKey]?.get(p.d) ?: -1
        if (bestDistance == -1 || p.safeScore() < bestDistance) {
            this.distances[positionKey]!![p.d] = p.safeScore()
        }
    }

    companion object {
        val CACHE = mutableMapOf<String, String>()

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
