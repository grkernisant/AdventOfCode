package com.webnologies.grkernisant.aoc.aoc2024.day21

import kotlin.math.abs

data class Ship (
    override val codes: List<KeyCode>,
) : KeyCodesInterface {
    private val numericCoords = mapOf(
        '7' to Pair(0, 0), '8' to Pair(1, 0), '9' to Pair(2, 0),
        '4' to Pair(0, 1), '5' to Pair(1, 1), '6' to Pair(2, 1),
        '1' to Pair(0, 2), '2' to Pair(1, 2), '3' to Pair(2, 2),
        '0' to Pair(1, 3), 'A' to Pair(2, 3)
    )
    private val numericGap = Pair(0, 3)
    private val directionalCoords = mapOf(
        '^' to Pair(1, 0), 'A' to Pair(2, 0),
        '<' to Pair(0, 1), 'v' to Pair(1, 1), '>' to Pair(2, 1)
    )
    private val directionalGap = Pair(0, 0)
    var shortestSequenceLengths: MutableMap<String, Long> = mutableMapOf()
    private val vPathScore = mutableMapOf<CacheKey, Long>()

    fun getComplexitySum(): Long {
        return shortestSequenceLengths.entries.fold(0L) { acc, entry ->
            val numPart = entry.key.substring(0, 3).toLong()
            acc + entry.value * numPart
        }
    }

    private fun getCost(start: Char, end: Char, level: Int, depth: Int): Long {
        val key = CacheKey(start, end, level)
        if (vPathScore.containsKey(key)) {
            return vPathScore[key]!!
        }

        if (level == 0) {
            return 1L
        }

        val isNumeric = (level == depth)
        val paths = getValidPaths(start, end, isNumeric)

        var minCost = Long.MAX_VALUE
        for (path in paths) {
            var pathCost = 0L
            var current = 'A'
            for (char in path) {
                pathCost += getCost(current, char, level - 1, depth)
                current = char
            }
            if (pathCost < minCost) {
                minCost = pathCost
            }
        }

        vPathScore[key] = minCost
        return minCost
    }

    private fun getSequenceLength(codeStr: String, depth: Int): Long {
        var totalCost = 0L
        var current = 'A'
        for (char in codeStr) {
            totalCost += getCost(current, char, depth, depth)
            current = char
        }
        return totalCost
    }

    private fun getValidPaths(startChar: Char, endChar: Char, isNumeric: Boolean): List<String> {
        if (startChar == endChar) return listOf("A")
        val coords = if (isNumeric) numericCoords else directionalCoords
        val gap = if (isNumeric) numericGap else directionalGap
        val start = coords[startChar] ?: return emptyList()
        val end = coords[endChar] ?: return emptyList()

        val dx = end.first - start.first
        val dy = end.second - start.second

        val hChar = if (dx > 0) '>' else '<'
        val vChar = if (dy > 0) 'v' else '^'

        val hCount = abs(dx)
        val vCount = abs(dy)

        val results = mutableListOf<String>()

        fun permute(currentPath: String, hRemaining: Int, vRemaining: Int, curX: Int, curY: Int) {
            if (curX == gap.first && curY == gap.second) {
                return
            }
            if (hRemaining == 0 && vRemaining == 0) {
                results.add(currentPath + "A")
                return
            }
            if (hRemaining > 0) {
                val nextX = if (dx > 0) curX + 1 else curX - 1
                permute(currentPath + hChar, hRemaining - 1, vRemaining, nextX, curY)
            }
            if (vRemaining > 0) {
                val nextY = if (dy > 0) curY + 1 else curY - 1
                permute(currentPath + vChar, hRemaining, vRemaining - 1, curX, nextY)
            }
        }

        permute("", hCount, vCount, start.first, start.second)
        return results
    }

    fun init(depth: Int = 3) {
        if (codes.isEmpty()) return

        codes.forEach { code ->
            val codeStr = code.toString()
            shortestSequenceLengths[codeStr] = getSequenceLength(codeStr, depth)
        }
    }

    companion object {
        fun of(k: KeyCodesInterface): Ship = Ship(codes = k.codes)
    }
}
