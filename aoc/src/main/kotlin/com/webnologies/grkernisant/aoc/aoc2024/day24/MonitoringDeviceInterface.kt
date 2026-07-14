package com.webnologies.grkernisant.aoc.aoc2024.day24

interface MonitoringDeviceInterface {
    val systemInit: MutableMap<String, Int>
    val gateOps: MutableList<Gate>
}
