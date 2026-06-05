package com.webnologies.grkernisant.aoc.aoc2024.day18

import kotlin.collections.slice

data class MemorySpace(
    override val bytes: List<Coords>,
    override val cols: Int,
    override val grid: List<List<MemoryTile>>,
    override val rows: Int,
    val escape: MutableList<String> = mutableListOf(),
    val gridCache: MutableMap<String, List<List<MemoryTile>>> = mutableMapOf(),
    val visited: MutableSet<String> = mutableSetOf(),
    val unvisited: MutableMap<String, Int> = mutableMapOf(),
) : MemorySpaceInterface {
    fun clearCaches() {
        escape.clear()
        visited.clear()
        unvisited.clear()
        gridCache.clear()
    }

    fun findBlockingEscapeTile(from: Int): String {
        val l = bytes.size
        var n: Int = from + 1
        var found: Boolean
        do {
            val escapeRoutes = getEscapeRoute(n)
            found = escapeRoutes.isNotEmpty()
            if (found) n++
        } while (n < l && found)

        return if (!found) bytes[n-1].toCacheKey() else ""
    }

    fun findPathFrom(src: Coords, dst: Coords, t: Int): List<List<String>> {
        val path = mutableSetOf<String>()
        path.add(src.toCacheKey())
        if (src == dst) return listOf(path.toList())

        val mt = getMemoryTile(src, t)
        val fastestNeighbors = (mt?.neighbors ?: emptyList())
            .filter { n ->
                getMemoryTile(n, t)?.score == (mt?.score ?: 0) - 1
            }
        if (fastestNeighbors.isEmpty()) return listOf()

        val paths = fastestNeighbors.flatMap { n ->
            val path = mutableSetOf<String>()
            val last = dst.toCacheKey()
            path.add(src.toCacheKey())
            val nl = findPathFrom(n, dst, t).map { nPath ->
                var addedLast = false
                nPath.forEach { np ->
                    if (!addedLast) {
                        path.add(np)
                        if (np == last) addedLast = true
                    }
                }
                path.toList()
            }

            nl
        }

        return paths
    }

    fun getEscapeRoute(t: Int): List<List<String>> {
        clearCaches()
        resetTilesScoresAndNeighbors(t)
        setDistances(t)
        return getScorePathsSinglePass(t)
    }

    fun getGridAfter(t: Int, withPathTiles: Boolean = false, forcedRefresh: Boolean = false): List<List<MemoryTile>> {
        val cacheKey = getGridAfterCacheKey(t, withPathTiles)
        if (!forcedRefresh && gridCache.contains(cacheKey)) return gridCache[cacheKey]!!

        val grid = List(this.rows) {
            MutableList(this.cols) { MemoryTile(MemoryType.FREE) }
        }

        val fallenBytes = if (t >= this.bytes.size) bytes else bytes.slice(0..<t)
        fallenBytes.forEach { fByte ->
            if (!outOfBounds(fByte)) {
                grid[fByte.y][fByte.x].mt = MemoryType.CORRUPTED
            }
        }

        if (!withPathTiles) {
            gridCache[cacheKey] = grid
            return grid
        }

        return grid
    }

    fun getGridAfterCacheKey(t: Int, withCorrupted: Boolean): String =
        "$t-$withCorrupted"

    fun getGridAfterStr(t: Int): String {
        return getGridAfter(t).joinToString("\n") { row ->
            row.joinToString ("") { it.mt.c.toString() }
        }
    }

    fun getMemoryTile(c: Coords, t: Int): MemoryTile? {
        if (outOfBounds(c)) return null

        return getGridAfter(t)[c.y][c.x]
    }

    fun getNeighbors(c: Coords, t: Int): List<Coords> {
        if (outOfBounds(c)) return emptyList()

        val neighbors = Direction.entries.map { d ->
            Coords(c.x + d.offset[0], c.y + d.offset[1])
        }
        return neighbors.filter { n ->
            if (outOfBounds(n)) return@filter false
            if (isMemoryTileCorrupted(n, t)) return@filter false

            true
        }
    }

    fun getScorePathsRecursive(t: Int): List<List<String>> {
        val start = Coords(0, 0)
        val exit = Coords(this.cols - 1, this.rows - 1)
        val scoredPaths = findPathFrom(exit, start, t)
            .filter { path ->
                path.first() == exit.toCacheKey() && path.last() == start.toCacheKey()
            }
            .sortedBy { it.size }
        return scoredPaths
    }

    fun getScorePathsSinglePass(t: Int): List<List<String>> {
        val start = Coords(0, 0)
        val exit = Coords(this.cols - 1, this.rows - 1)

        val exitTile = getMemoryTile(exit, t)
        if (exitTile == null || exitTile.score == -1) {
            return emptyList()
        }

        val path = mutableListOf<String>()
        var current = exit
        path.add(current.toCacheKey())

        while (current != start) {
            val currentTile = getMemoryTile(current, t) ?: break
            val currentScore = currentTile.score
            val next = currentTile.neighbors.firstOrNull { neighbor ->
                getMemoryTile(neighbor, t)?.score == currentScore - 1
            } ?: break
            current = next
            path.add(current.toCacheKey())
        }

        if (current != start) {
            return emptyList()
        }

        path.reverse()
        return listOf(path)
    }

    fun getUnvisitedNeighbors(c: Coords, t: Int = 0): List<Coords> {
        if (outOfBounds(c)) return emptyList()

        val neighbors = getMemoryTile(c, t)?.neighbors ?: emptyList()
        if (neighbors.isEmpty()) return emptyList()

        return neighbors.filter { n -> !visited.contains(n.toCacheKey(t)) }
    }

    fun isMemoryTileCorrupted(c: Coords, t: Int = 0): Boolean {
        val grid = getGridAfter(t)
        if (outOfBounds(c)) throw IllegalArgumentException("$c is out of bounds")
        return grid[c.y][c.x].mt == MemoryType.CORRUPTED
    }

    fun outOfBounds(c: Coords): Boolean = outOfBounds(c.x, c.y)
    fun outOfBounds(x: Int, y: Int): Boolean {
        return x < 0 || x >= this.cols || y < 0 || y >= this.rows
    }

    fun setDistances(t: Int) {
        // mark start tile with a 0 score
        val start = Coords(0, 0)
        setMemoryTileScore(start, t, 0)
        visited.add(start.toCacheKey())
        val neighbors = getUnvisitedNeighbors(start, t)
        // explore map via each unvisited neighbors
        neighbors.forEach { n ->
            // unvisited get score + 1
            unvisited[n.toCacheKey(t)] = 1
        }
        setTileAsSafe(t)
    }

    fun setMemoryTileScore(c: Coords, t: Int, score: Int): Boolean {
        if (outOfBounds(c)) return false

        val cacheKey = getGridAfterCacheKey(t, withCorrupted = false)
        if (gridCache[cacheKey] == null || gridCache[cacheKey]?.get(c.y) == null) return false

        val isMemoryTile = gridCache[cacheKey]!![c.y][c.x].mt == MemoryType.FREE
        val initialiseScore = gridCache[cacheKey]!![c.y][c.x].score == -1
        val updateScore = gridCache[cacheKey]!![c.y][c.x].score > score
        if (isMemoryTile && (initialiseScore || updateScore)) {
            gridCache[cacheKey]!![c.y][c.x].score = score
            return true
        }

        return false
    }

    fun setTileAsSafe(t: Int) {
        val nextVisits = mutableMapOf<String, Int>()
        unvisited.forEach { (unv, score) ->
            visited.add(unv)
            val c = Coords.of(unv)
            if (setMemoryTileScore(c, t, score)) {
                getMemoryTile(c, t)?.neighbors?.forEach { n ->
                    nextVisits[n.toCacheKey(t)] = score + 1
                }
            }
        }
        unvisited.clear()

        if (nextVisits.isNotEmpty()) {
            nextVisits.forEach { (key, score) ->
                unvisited[key] = score
            }
            nextVisits.clear()
            setTileAsSafe(t)
        }
    }

    fun resetTilesScoresAndNeighbors(t: Int) {
        // get corrupted tiles at t
        val grid = getGridAfter(t)
        for(y in 0..< this.rows) {
            for(x in 0..< this.cols) {
                // reset tile score
                grid[y][x].score = -1
                if (grid[y][x].mt == MemoryType.FREE) {
                    val currentCoords = Coords(x, y)
                    // reset valid neighbors at t
                    getNeighbors(currentCoords, t).forEach { neighbor ->
                        grid[y][x].neighbors.add(neighbor)
                    }
                }
            }
        }
    }

    companion object {
        fun of (msi: MemorySpaceInterface): MemorySpace =
            MemorySpace(
                bytes = msi.bytes,
                cols = msi.cols,
                grid = msi.grid,
                rows = msi.rows,
            )
    }
}
