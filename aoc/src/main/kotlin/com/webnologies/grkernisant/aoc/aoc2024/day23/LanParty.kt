package com.webnologies.grkernisant.aoc.aoc2024.day23

import kotlin.math.max
import kotlin.math.min

class LanParty {
    val computers: MutableMap<String, PC> = mutableMapOf()
    val networks: MutableSet<String> = mutableSetOf()
    val localAreaNetworks: MutableSet<String> = mutableSetOf()
    var networksInitialised: Boolean

    constructor(input: List<String>, connectNow: Boolean = false) {
        networksInitialised = connectNow
        input.forEach { line ->
            val pcNames = line.trim()
                .split("-")
                .let {
                    Pair(it[0], it[1])
                }
            val pc1 = computers.getOrPut(pcNames.first) { PC(pcNames.first) }
            val pc2 = computers.getOrPut(pcNames.second) { PC(pcNames.second) }
            pc1.addConnection(pc2)
            pc2.addConnection(pc1)
            if (connectNow) addConnection(pcNames)
        }
    }

    fun addConnection(conn: Pair<String, String>) {
        val networksFiltered = networks.filter { nw ->
            val indexFirst = nw.indexOf(conn.first)
            val indexSecond = nw.indexOf(conn.second)
            indexFirst >-1 || indexSecond > -1
        }

        if (networks.isEmpty() || networksFiltered.isEmpty()) {
            networks.add("${conn.first},${conn.second}")
            return
        }

        val newSets = mutableSetOf<String>()
        networksFiltered.forEach { nw ->
            val indexFirst = nw.indexOf(conn.first)
            val indexSecond = nw.indexOf(conn.second)

            if (indexFirst > -1 && indexSecond > -1) {
                val indexMin = min(indexFirst, indexSecond)
                val indexMax = max(indexFirst, indexSecond)
                val parts = nw
                    .substring(indexMin, indexMax + 2)
                    .split(',')
                    .filter { it.isNotEmpty() }
                    .sorted()
                    .toSet()
                addLan(parts)
            } else if (indexFirst > -1 || indexSecond > -1) {
                newSets.add("${conn.first},${conn.second}")
                if (nw.startsWith(conn.first)) {
                    newSets.add("${conn.second},$nw")
                } else if (nw.endsWith(conn.first)) {
                    newSets.add("$nw,${conn.second}")
                } else if (nw.startsWith(conn.second)) {
                    newSets.add("${conn.first},$nw")
                } else if (nw.endsWith(conn.second)) {
                    newSets.add("$nw,${conn.first}")
                } else if (indexFirst != -1) {
                    newSets.add("${nw.substring(0, indexFirst)}${conn.first},${conn.second}")
                } else {
                    newSets.add("${nw.substring(0, indexSecond)}${conn.second},${conn.first}")
                }
            }
        }
        newSets.forEach { ns -> networks.add(ns) }
    }

    fun addLan(names: Set<String>) {
        val newSet = names.toList().sorted().joinToString(",") { it }
        localAreaNetworks.add(newSet)
    }

    fun checkInitialised(size: Int) {
        if (networksInitialised) return

        computers.entries.forEach { (name, pc) ->
            findComputer(pc, name, size)
        }
    }

    fun filterContainsNamesStartWith(nw: List<String>, startWith: String): List<String> {
        return nw.filter { it.startsWith(startWith) || it.indexOf(",$startWith") >= 0 }
    }

    fun findComputer(
        pc: PC,
        search: String,
        maxDepth: Int,
        depth: Int = 1,
        path: Set<String> = setOf()
    ) {
        val newPath = path union setOf(pc.name)
        if (pc.connections.contains(search) && depth == maxDepth) {
            addLan(newPath)
            return
        }

        if (depth >= maxDepth) return

        pc.connections.forEach { name ->
            val connectedTo = computers[name]
            if (connectedTo != null && !path.contains(name)) {
                findComputer(connectedTo, search, maxDepth, depth + 1, newPath)
            }
        }
    }

    fun getLANofSize(size: Int): List<String> {
        checkInitialised(size)

        val lanSize = 2 * size + size - 1
        return localAreaNetworks
            .filter { lan -> lan.length == lanSize }
            .sorted()
    }

    fun getMaxConnections(): Int {
        var nb = 0
        computers.entries.forEach { (name, pc) ->
            nb = max(nb, pc.connections.size)
        }

        return nb
    }
}