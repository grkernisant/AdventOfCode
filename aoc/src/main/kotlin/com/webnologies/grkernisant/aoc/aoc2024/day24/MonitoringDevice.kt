package com.webnologies.grkernisant.aoc.aoc2024.day24

data class MonitoringDevice(
    override val systemInit: MutableMap<String, Int> = mutableMapOf(),
    override val gateOps: MutableList<Gate> = mutableListOf(),
    var systemOutput: MutableMap<String, Int> = mutableMapOf(),
    var wireMap: MutableMap<String, MutableList<Int>> = mutableMapOf()
) : MonitoringDeviceInterface, WireContainerInterface {
    fun getSystemOutput(): Long {
        var so = ""
        systemOutput.keys.sorted().forEach { k ->
            so = "${systemOutput[k].toString()}$so"
        }

        return so.toLong(2)
    }

    override fun onWireDateValueChange(w: Wire) {
        if (w.value == null) return

        if (w.name.startsWith("z")) {
            systemOutput[w.name] = w.value!!
        }

        wireMap[w.name]?.forEach { index ->
            gateOps[index].updateInput(w)
            gateOps[index].runOp()
        }
    }

    fun run() {
        systemInit.entries.forEach { (name, value) ->
            val wire = Wire(name, value)
            onWireDateValueChange(wire)
        }
    }

    companion object {
        fun of(parser: MonitoringDeviceInterface): MonitoringDevice {
            val md = MonitoringDevice(
                systemInit = parser.systemInit,
                gateOps = parser.gateOps
            )
            md.gateOps.forEachIndexed { i, gate ->
                // broadcast
                gate.wireContainer = md

                // wire update
                val wire1 = gate.inputs.first.name
                val wire2 = gate.inputs.second.name
                if (md.wireMap[wire1] == null) md.wireMap[wire1] = mutableListOf()
                if (md.wireMap[wire2] == null) md.wireMap[wire2] = mutableListOf()

                md.wireMap[wire1]?.add(i)
                md.wireMap[wire2]?.add(i)
            }

            return md
        }
    }
}
