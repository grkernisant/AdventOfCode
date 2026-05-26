package com.webnologies.grkernisant.aoc.aoc2024.day15

interface ParserInterface {
    fun fetchBoxesPositions(): List<Position>
    fun fetchMapTile(): List<List<MapTile>>
    fun fetchMoves(): List<RobotMove>
    fun fetchRobotPosition(): Position
}