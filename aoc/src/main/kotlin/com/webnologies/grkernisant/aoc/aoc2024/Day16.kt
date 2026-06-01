package com.webnologies.grkernisant.aoc.aoc2024

import com.webnologies.grkernisant.aoc.aoc2024.day16.Parser
import com.webnologies.grkernisant.aoc.aoc2024.day16.Maze
import kotlinx.serialization.json.Json

object Day16 : Day {
    override fun part1(input: List<String>): Any {
        val parser = Parser(input)
        val maze = Maze.of(parser)
        maze.initExplore()
        val distanceEnd = maze.getDistanceEnd() ?: 0
        maze.cacheAs("part1")
        return distanceEnd
    }

    override fun part2(input: List<String>): Any {
        val cached = Maze.CACHE["part1"]

        if (cached != null) {
            val maze = Json.decodeFromString<Maze>(cached)
            return maze.getNbOnBestPaths()
        }

        return 0
    }
}