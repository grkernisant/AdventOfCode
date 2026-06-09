package com.webnologies.grkernisant.aoc.aoc2024.day20

import kotlin.math.abs

data class RaceTrack (
    override val cols: Int,
    override val end: PositionWithDistance,
    override val rows: Int,
    override val start: PositionWithDistance,
    override val track: List<List<MapTile>>
) : RaceTrackInterface {
    var cheatPositions: MutableMap<Int, MutableSet<String>> = mutableMapOf()
    var distances: MutableMap<String, Int> = mutableMapOf()
    var visited: MutableSet<String> = mutableSetOf()
    var unvisited: MutableSet<String> = mutableSetOf()

    private fun addNeighborsWithDirectionMap(
        pSrc: PositionWithDistance,
        pList: List<PositionWithDistance>,
        neighbors: MutableMap<Direction, MutableList<DirectionVector>>
    ) {
        pList.forEach { n ->
            val vector = DirectionVector.of(pSrc, n)
            val mt = getMapTile(n)
            if (vector != null &&  vector.distance == 1 && mt == MapTile.WALL) {
                if (!neighbors.contains(vector.direction)) neighbors[vector.direction] = mutableListOf()
                neighbors[vector.direction]?.add(vector)
            }

            if (vector != null &&  vector.distance == 2 && mt != MapTile.WALL) {
                if (!neighbors.contains(vector.direction)) neighbors[vector.direction] = mutableListOf()
                neighbors[vector.direction]?.add(vector)
            }
        }
    }

    fun getCheatCountOver(picoSeconds: Int): Int {
        var cc = 0
        cheatPositions.forEach { (picoSecondsSaved, cheatCodes) ->
            cc+= if (picoSecondsSaved >= picoSeconds) cheatCodes.size else 0
        }
        return cc
    }

    fun getCheatPositions(p: PositionWithDistance, cheatRule: CheatingCodeRule): List<DirectionVector> {
        return if (cheatRule == CheatingCodeRule.TWO_PICO_SEC)
            getCheatPositions2ps(p)
        else
            getCheatPositions20ps(p)
    }

    fun getCheatPositions2ps(p: PositionWithDistance): List<DirectionVector> {
        val n1 = getNeighbors(p, 1)
        if (n1.isEmpty()) return mutableListOf()
        val n2 = getNeighbors(p, 2)
        if (n2.isEmpty()) return mutableListOf()

        val neighborsMap: MutableMap<Direction, MutableList<DirectionVector>> = mutableMapOf()
        addNeighborsWithDirectionMap(p, n1, neighborsMap)
        addNeighborsWithDirectionMap(p, n2, neighborsMap)

        val cp: MutableList<DirectionVector> = mutableListOf()
        neighborsMap.forEach { (_, value) ->
            if (value.size == 2) {
                val sortedValue = value.sortedByDescending { it.distance }
                cp.add(sortedValue.first())
            }
        }

        return cp.toList()
    }

    fun getCheatPositions20ps(p: PositionWithDistance): List<DirectionVector> {
        val n1 = getCircularNeighbors(p, 2..20)
            .filter { n ->
                val mt = getMapTile(n) ?: return@filter false
                mt != MapTile.WALL
            }
        if (n1.isEmpty()) return emptyList()

        return n1.mapNotNull { n -> DirectionVector.of(p, n) }
    }

    fun getCircularNeighbors(p: PositionWithDistance, range: IntRange): List<PositionWithDistance> {
        if (outOfBounds(p)) return emptyList()

        val pList: MutableList<PositionWithDistance> = mutableListOf()
        val rMax = range.last
        val rStart = -1 * range.last
        val rSkip = Pair(-1 * range.first, range.first)
        for(y in rStart..rMax) {
            for(x in rStart..rMax) {
                val distance = abs(y) + abs(x)
                if (distance < rSkip.second || distance > rMax || outOfBounds(x, y)) continue
                pList.add(PositionWithDistance(x, y, distance))
            }
        }

        return pList.toList()
    }

    fun getMapTile(p: PositionWithDistance): MapTile? {
        if (outOfBounds(p)) return null
        return track[p.y][p.x]
    }

    fun getNeighbors(p: PositionWithDistance, offset: Int = 1, orthogonalFilter: Boolean? = true): List<PositionWithDistance> {
        if (outOfBounds(p)) return emptyList()

        return Direction.entries.mapNotNull { direction ->
            if (orthogonalFilter != null && direction.orthogonal != orthogonalFilter) return@mapNotNull null

            val pn = PositionWithDistance(
                x = p.x + offset * direction.offset[0],
                y = p.y + offset * direction.offset[1],
                distance = (p.distance ?: 0) + offset
            )
            if (outOfBounds(pn)) return@mapNotNull null
            pn
        }
    }

    fun getUnvisitedNeighbors(p: PositionWithDistance, offset: Int = 1): List<PositionWithDistance> {
        return getNeighbors(p, offset).filter { pn ->
            val mt = getMapTile(pn) ?: return@filter false
            mt != MapTile.WALL && !visited.contains(pn.toPositionKey())
        }
    }

    fun initStartVisit() {
        start.distance = 0
        if (visit(start)) {
            getUnvisitedNeighbors(start).forEach { unv ->
                unvisited.add(unv.toDistanceKey())
            }
        }
    }

    fun initKonamiCheatCodes() {
        visited.clear()
        unvisited.clear()
        unvisited.add(start.toDistanceKey())
    }

    fun run(cheatRule: CheatingCodeRule = CheatingCodeRule.TWO_PICO_SEC) {
        initStartVisit()
        setRaceTrackDistances()
        initKonamiCheatCodes()
        setKonamiCheatCodes(cheatRule)
    }

    fun setKonamiCheatCodes(cheatRule: CheatingCodeRule) {
        while (unvisited.isNotEmpty()) {
            val nextVisit: MutableSet<String> = mutableSetOf()
            unvisited.forEach { unv ->
                val p = PositionWithDistance.of(unv)
                visited.add(p.toPositionKey())
                val neighbors = getUnvisitedNeighbors(p)
                neighbors.forEach { neighbor ->
                    nextVisit.add(neighbor.toDistanceKey())
                }

                getCheatPositions(p, cheatRule).forEach { cp ->
                    val oldScore = distances[cp.start.toPositionKey()]
                    val newScore = distances[cp.end.toPositionKey()]
                    // it takes 2 seconds to teleport
                    val saved = if (newScore != null && oldScore != null) {
                        newScore - (oldScore + cp.distance)
                    } else null
                    if (saved != null && saved > 0) {
                        if (!cheatPositions.contains(saved)) cheatPositions[saved] = mutableSetOf()
                        cheatPositions[saved]?.add(cp.toVectorKey())
                    }
                }

            }
            unvisited.clear()

            nextVisit.forEach { nv -> unvisited.add(nv) }
        }
    }

    fun setRaceTrackDistances() {
        while (unvisited.isNotEmpty()) {
            val nextVisit: MutableSet<String> = mutableSetOf()
            unvisited.forEach { unv ->
                val p = PositionWithDistance.of(unv)
                if (visit(p)) {
                    val neighbors = getUnvisitedNeighbors(p)
                    neighbors.forEach { neighbor ->
                        nextVisit.add(neighbor.toDistanceKey())
                    }
                }
            }
            unvisited.clear()

            nextVisit.forEach { nv -> unvisited.add(nv) }
        }
    }

    fun outOfBounds(p: PositionInterface): Boolean = outOfBounds(p.x, p.y)
    fun outOfBounds(x: Int, y: Int): Boolean {
        return x !in 0..<cols || y !in 0..<rows
    }

    fun visit(p: PositionWithDistance): Boolean {
        val d = distances[p.toPositionKey()]
        if (d == null || p.distance != null && p.distance!! < d) {
            distances[p.toPositionKey()] = (p.distance ?: 0)
            visited.add(p.toPositionKey())

            return true
        }

        return false
    }

    companion object {
        fun of(ri: RaceTrackInterface): RaceTrack =
            RaceTrack(
                cols = ri.cols,
                end = ri.end,
                rows = ri.rows,
                start = ri.start,
                track = ri.track,
            )
    }
}