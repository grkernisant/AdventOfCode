package com.webnologies.grkernisant.aoc.aoc2024.day20

interface RaceTrackInterface {
    val cols: Int
    val end: PositionWithDistance
    val rows: Int
    val start: PositionWithDistance
    val track: List<List<MapTile>>
}