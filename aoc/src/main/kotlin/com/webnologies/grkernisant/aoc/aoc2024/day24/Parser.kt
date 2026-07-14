package com.webnologies.grkernisant.aoc.aoc2024.day24

import com.webnologies.grkernisant.aoc.aoc2024.day24.Wire.Companion.WIRE_REGEX

class Parser : MonitoringDeviceInterface {
    override val systemInit: MutableMap<String, Int> = mutableMapOf()
    override val gateOps: MutableList<Gate> = mutableListOf()
    val swaps: Map<String, String>

    constructor(input: List<String>, swapInputs: Map<String, String> = mapOf()) {
        val converted = mutableMapOf<String, String>()
        swaps = swapInputs
        swaps.entries.forEach { (k, v) ->
            converted[k] = v
            converted[v] = k
        }
        val initRegex = Regex("^($WIRE_REGEX): ([01])$")
        val gateOpRegex = Regex("^($WIRE_REGEX) (AND|XOR|OR) ($WIRE_REGEX) -> ($WIRE_REGEX)$")
        input.forEach { line ->
            val initLine = initRegex.matchEntire(line.trim())
            val opLine = gateOpRegex.matchEntire(line.trim())
            if (initLine != null) {
                systemInit[initLine.groups[1]!!.value] = initLine.groups[2]!!.value.toInt()
            }

            if (opLine != null) {
                val outputName = converted[opLine.groups[4]!!.value] ?: opLine.groups[4]!!.value
                val g = Gate(
                    inputs = Pair(Wire(opLine.groups[1]!!.value), Wire(opLine.groups[3]!!.value)),
                    gateOperator = GateOperator.of(opLine.groups[2]!!.value),
                    output = Wire(outputName)
                )
                gateOps.add(g)
            }
        }
    }

    fun getSortedSwaps(): String {
        val inputs = mutableSetOf<String>()
        swaps.entries.forEach { (k, v) ->
            inputs.add(k)
            inputs.add(v)
        }

        return inputs.sorted().joinToString(",")
    }
}