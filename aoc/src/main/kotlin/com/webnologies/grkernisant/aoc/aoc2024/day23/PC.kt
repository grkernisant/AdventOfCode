package com.webnologies.grkernisant.aoc.aoc2024.day23

data class PC(val name: String) {
    val connections: MutableSet<String> = mutableSetOf()

    fun addConnection(connection: PC) {
        connections.add(connection.name)
    }
}
