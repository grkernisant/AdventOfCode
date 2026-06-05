package com.webnologies.grkernisant.aoc.aoc2024.day18

interface MemorySpaceInterface {
    val bytes: List<Coords>
    val cols: Int
    val grid: List<List<MemoryTile>>
    val rows: Int
}