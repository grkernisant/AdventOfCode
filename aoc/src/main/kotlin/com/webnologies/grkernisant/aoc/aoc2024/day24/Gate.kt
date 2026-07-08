package com.webnologies.grkernisant.aoc.aoc2024.day24

data class Gate(
    var inputs: Pair<Wire, Wire>,
    val output: Wire,
    val gateOperator: GateOperator,
    var ready: Boolean = false,
    var wireContainer: WireContainerInterface? = null
) {
    fun updateInput(w: Wire) {
        if (ready) return

        if (inputs.first.name == w.name && inputs.first.value == null) {
            inputs.first.value = w.value
        }

        if (inputs.second.name == w.name && inputs.second.value == null) {
            inputs.second.value = w.value
        }

        if (inputs.first.value != null && inputs.second.value != null) {
            ready = true
//            runOp()
        }
    }

    fun runOp() {
        if (!ready) return
        if (output.value != null) return

        when (gateOperator) {
            GateOperator.AND -> output.value = inputs.first.value!!.and(inputs.second.value!!)
            GateOperator.OR -> output.value = inputs.first.value!!.or(inputs.second.value!!)
            GateOperator.XOR -> output.value = inputs.first.value!!.xor(inputs.second.value!!)
        }

        wireContainer?.onWireDateValueChange(output)
    }

    override fun toString(): String =
        "${inputs.first} ${gateOperator.name} ${inputs.second} -> ${output.value}"
}
