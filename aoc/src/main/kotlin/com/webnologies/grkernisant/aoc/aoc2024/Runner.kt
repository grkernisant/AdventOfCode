package com.webnologies.grkernisant.aoc.aoc2024

import kotlin.system.measureTimeMillis

fun main() {
    val day = Day24

    val input = day.readInput()
    println("--- ${day::class.simpleName} ---")

    val p1Time = measureTimeMillis {
        println("Part 1: ${day.part1(input)}")
    }
    println("Elapsed time: ${p1Time}ms\n")

    val p2Time = measureTimeMillis {
        println("Part 2: ${day.part2(input)}")
    }
    println("Elapsed time: ${p2Time}ms")
}
