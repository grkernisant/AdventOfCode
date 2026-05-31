package com.webnologies.grkernisant.aoc.aoc2024

interface Day {
    fun part1(input: List<String>): Any
    fun part2(input: List<String>): Any

    fun readInput(): List<String> {
        val name = this::class.simpleName?.lowercase()?.replaceFirstChar { it.uppercase() } ?: throw IllegalStateException("Anonymous class")
        return object {}.javaClass.getResourceAsStream("/2024/$name.txt")
            ?.bufferedReader()
            ?.readLines()
            ?: throw IllegalArgumentException("Cannot read input src/main/resources/2024/$name.txt")
    }
}