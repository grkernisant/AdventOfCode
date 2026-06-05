package com.webnologies.grkernisant.aoc.aoc2024

interface DayTest {
    fun getInputName(): String {
        return this::class.simpleName?.lowercase()?.replaceFirstChar { it.uppercase() } ?: throw IllegalStateException("Anonymous class")
    }

    fun readEnv(): Map<String, String> {
        val name = getInputName()
        val envFile = object {}.javaClass.getResourceAsStream("/2024/$name.env")
            ?.bufferedReader()
            ?.readLines()
            ?: emptyList()

        return envFile.fold(mutableMapOf<String, String>()) { acc, curr ->
            val matches = ENV_REGEX.matchEntire(curr)
            if (matches != null) acc[matches.groupValues[1]] = matches.groupValues[2]
            acc
        }
    }

    fun readInput(): List<String> {
        val name = getInputName()
        return object {}.javaClass.getResourceAsStream("/2024/$name.txt")
            ?.bufferedReader()
            ?.readLines()
            ?: throw IllegalArgumentException("Cannot read input src/test/resources/2024/$name.txt")
    }
}