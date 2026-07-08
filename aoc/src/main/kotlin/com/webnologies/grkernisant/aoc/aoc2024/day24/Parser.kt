package com.webnologies.grkernisant.aoc.aoc2024.day24

import com.webnologies.grkernisant.aoc.aoc2024.day24.Wire.Companion.WIRE_REGEX

class Parser : MonitoringDeviceInterface {
    override val systemInit: MutableMap<String, Int> = mutableMapOf()
    override val gateOps: MutableList<Gate> = mutableListOf()

    constructor(input: List<String>) {
        val initRegex = Regex("^($WIRE_REGEX): ([01])$")
        val gateOpRegex = Regex("^($WIRE_REGEX) (AND|XOR|OR) ($WIRE_REGEX) -> ($WIRE_REGEX)$")
        input.forEach { line ->
            val initLine = initRegex.matchEntire(line.trim())
            val opLine = gateOpRegex.matchEntire(line.trim())
            if (initLine != null) {
                systemInit[initLine.groups[1]!!.value] = initLine.groups[2]!!.value.toInt()
            }

            if (opLine != null) {
                val g = Gate(
                    inputs = Pair(Wire(opLine.groups[1]!!.value), Wire(opLine.groups[3]!!.value)),
                    gateOperator = GateOperator.of(opLine.groups[2]!!.value),
                    output = Wire(opLine.groups[4]!!.value)
                )
                gateOps.add(g)
            }
        }
    }
}