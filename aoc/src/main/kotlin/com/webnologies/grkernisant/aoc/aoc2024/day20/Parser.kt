package com.webnologies.grkernisant.aoc.aoc2024.day20

import kotlin.properties.Delegates

class Parser : RaceTrackInterface{
    override var cols by Delegates.notNull<Int>()
    override lateinit var end: PositionWithDistance
    override var rows by Delegates.notNull<Int>()
    override lateinit var start: PositionWithDistance
    override var track: List<List<MapTile>>

    constructor(input: List<String>) {
        track = input.mapIndexed { y, line ->
            line.trim()
                .split("")
                .filter { it.isNotEmpty() }
                .mapIndexed { x, it ->
                    if (it == "S") start = PositionWithDistance(x, y)
                    if (it == "E") end = PositionWithDistance(x, y)

                    MapTile.of(it.toCharArray()[0])
                }
        }

        rows = track.size
        cols = track[0].size
    }
}
