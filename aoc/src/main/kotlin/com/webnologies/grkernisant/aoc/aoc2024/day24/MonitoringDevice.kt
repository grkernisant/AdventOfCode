package com.webnologies.grkernisant.aoc.aoc2024.day24

data class MonitoringDevice(
    override val systemInit: MutableMap<String, Int> = mutableMapOf(),
    override val gateOps: MutableList<Gate> = mutableListOf(),
    var systemOutput: MutableMap<String, Int> = mutableMapOf(),
    var wireValues: MutableMap<String, Int> = mutableMapOf(),
    var wireMap: MutableMap<String, MutableList<Int>> = mutableMapOf(),
    var wires: MutableMap<String, Wire> = mutableMapOf(),
    var hasRun: Boolean = false,
) : MonitoringDeviceInterface, WireContainerInterface {
    fun findGateByWireName(wireName: String): Gate? {
        val gate = gateOps.filter { g -> g.output.name == wireName }
        return if (gate.isNotEmpty()) gate[0] else null
    }

    /**
     * Addition in binary is the result of an XOR operation
     * However we need to account for a potential carry over from the previous byte
     * in which case the value would be positive from an AND operation
     *
     * The wires are not tangled as long as we can evaluate that the current byte
     * respect the above stated rules
     *
     * SUCCESS:
     * with X' and Y' being the previous byte values
     * when X' AND Y' are true we have a CO' carry over value
     *   Z = (X XOR Y) XOR CO'
     * than Z00 = X00 XOR Y00 is a valid equation to start with
     */
    fun findWrongWires(): List<String> {
        run()

        val carryOverMap: MutableMap<String, String> = mutableMapOf()
        val perByteSumMap: MutableMap<String, String> = mutableMapOf()
        val relatedGates: MutableMap<String, MutableList<String>> = mutableMapOf()
        val wrongOutputs: MutableSet<String> = mutableSetOf()
        val untilIndex = getBinarySize()
        var r = 0
        while (r < untilIndex) {
            val xCurr = getParameterIndex(r, "x")
            val yCurr = getParameterIndex(r, "y")
            val zCurr = getParameterIndex(r)
            val zPrev = getParameterIndex(r - 1)
            val zGate = findGateByWireName(zCurr) ?: throw IllegalArgumentException("Missing equation $zCurr")
            relatedGates[zCurr] = mutableListOf()

            // current carry value
            val zCurrCarry = gateOps.find { g -> g.checkOpWithStrings(Pair(xCurr, yCurr), GateOperator.AND) }
            val isVariable = zGate.inputs.first.name.startsWith("x")
                .or(zGate.inputs.first.name.startsWith("y"))
            val eq1 = if (!isVariable) findGateByWireName(zGate.inputs.first.name) else null
            val eq2 = if (!isVariable) findGateByWireName(zGate.inputs.second.name) else null

            if (r > 0) {
                // Z = (X XOR Y) XOR CO'
                val isEq1XOR = eq1 != null && eq1.checkOpWithStrings(Pair(xCurr, yCurr), GateOperator.XOR)
                val isEq2XOR = eq2 != null && eq2.checkOpWithStrings(Pair(xCurr, yCurr), GateOperator.XOR)
                if (isEq1XOR) perByteSumMap[zCurr] = eq1.output.name
                if (isEq2XOR) perByteSumMap[zCurr] = eq2.output.name

                val isEq1Carry = eq1 != null && isCarryOverGate(eq1, r, perByteSumMap, carryOverMap)
                val isEq2Carry = eq2 != null && isCarryOverGate(eq2, r, perByteSumMap, carryOverMap)
                if (isEq1Carry) carryOverMap[zPrev] = eq1.output.name
                if (isEq2Carry) carryOverMap[zPrev] = eq2.output.name

                val isValid = if (r < untilIndex - 1) {
                    isEq1XOR.xor(isEq2XOR) && isEq1Carry.xor(isEq2Carry)
                } else {
                    isCarryOverGate(zGate, r, perByteSumMap, carryOverMap)
                }

                if (isValid && eq1 != null && eq2 != null) {
                    val elements = listOf(
                        zGate.inputs.first.name,
                        zGate.inputs.second.name,
                        eq1.inputs.first.name,
                        eq1.inputs.second.name,
                        eq2.inputs.first.name,
                        eq2.inputs.second.name,
                    ).filter { e -> !(e.startsWith("x") || e.startsWith("y")) }
                    relatedGates[zCurr]?.addAll(elements)
                } else {
                    wrongOutputs.add(zGate.output.name)
                }
            }

            if (r == 0) {
                // current byte XOR check
                if (zGate.checkOpWithStrings(Pair(xCurr, yCurr), GateOperator.XOR)) {
                    relatedGates[zCurr]?.add(zGate.output.name)
                } else {
                    wrongOutputs.add(zGate.output.name)
                }

                if (zCurrCarry != null) {
                    relatedGates[zCurr]?.add(zCurrCarry.output.name)
                    carryOverMap[zCurr] = zCurrCarry.output.name
                } else {
                    wrongOutputs.add(zGate.output.name)
                }
            }

            if (wrongOutputs.isNotEmpty()) break
            r++
        }

        return wrongOutputs.toList()
    }

    fun getBinarySize(): Int {
        return systemInit.keys.filter { it.startsWith("x") }.size + 1
    }

    fun getFullEquationFor(
        name: String,
        indent: String = ""
    ): String {
        val gate = findGateByWireName(name) ?: return ""
        val newIndent = "  $indent"
        val subEquation = mutableListOf<String>()
        subEquation.add(getFullEquationFor(gate.inputs.first.name, newIndent))
        subEquation.add(getFullEquationFor(gate.inputs.second.name, newIndent))

        val newFullEquation: String = buildString {
            append("$indent${"$name: ${gate.toEquationOutput(false)}"}\n")
            append(subEquation.filter { it.isNotBlank() }.joinToString("\n") { it })
        }

        return newFullEquation
    }

    fun getParameterIndex(index: Int, prefix: String = "z"): String {
        // we are assuming 3 letter wire names
        val formattedIndex = if (index < 10) "0$index" else index.toString()
        return "$prefix$formattedIndex"
    }

    fun getSystemOutput(): Long {
        var so = ""
        systemOutput.keys.sorted().forEach { k ->
            so = "${systemOutput[k].toString()}$so"
        }

        return so.toLong(2)
    }

    fun getSumOfXY(): Pair<String, Long> {
        val x = getWireStartsWith("x").toLong(2)
        val y = getWireStartsWith("y").toLong(2)
        val zz = getWireStartsWith("z")
            .split("")
            .filter { it.isNotEmpty() }
        val xy = (x + y).toString(2)
            .let {
                if (it.length < zz.size) {
                    return@let "0".repeat(zz.size - it.length) + it
                }

                if (it.length > zz.size) {
                    // truncate bits
                    return@let it.substring(it.length - zz.size)
                }

                it
            }

        return Pair(xy, xy.toLong(2))
    }

    fun getWireStartsWith(s: String): String {
        var wo = ""
        val keysFiltered = wireValues.keys.sorted().filter { k -> k.startsWith(s) }
        keysFiltered.forEach { k ->
            wo = "${wireValues[k]}$wo"
        }

        return wo
    }

    /**
     * Carry over needs to hold the AND operation for the previous byte (previous bytes were all 0)
     * OR (Sum the previous bytes with their carry)
     * hence an AND operation between previous XOR and previous carry
     * */
    private fun isCarryOverGate(
        gate: Gate,
        byte: Int,
        perByteSumMap: Map<String, String>,
        carryOverMap: Map<String, String>
    ): Boolean {
        if (byte <= 0) return false

        // XNN AND YNN -> previousByteCarry
        val zPrev1 = getParameterIndex(byte - 1, "z")
        if (gate.output.name == carryOverMap[zPrev1]) return true

        if (byte < 2 || gate.gateOperator != GateOperator.OR) return false

        val xPrev1 = getParameterIndex(byte - 1, "x")
        val yPrev1 = getParameterIndex(byte - 1, "y")
        val zPrev2 = getParameterIndex(byte - 2, "z")
        val zCarryPrev1 = gateOps.find { gate ->
            gate.checkOpWithStrings(Pair(xPrev1, yPrev1), GateOperator.AND)
        } ?: throw IllegalArgumentException("Carry Equation NOT FOUND for $xPrev1 AND $yPrev1")

        val eq1 = findGateByWireName(gate.inputs.first.name)
        val eq2 = findGateByWireName(gate.inputs.second.name)

        // previous Byte Sum AND previous byte carry
        // (XNN XOR YNN) AND (XMM AND XMM) with M = N - 1
        val isEq1CarryPrev1 = eq1 != null && zCarryPrev1.checkOp(eq1.inputs, GateOperator.AND)
        val isEq2CarryPrev1 = eq2 != null && zCarryPrev1.checkOp(eq2.inputs, GateOperator.AND)
        if (isEq1CarryPrev1.xor(isEq2CarryPrev1)) {
            val eqCarrySumPrev2 = if (isEq1CarryPrev1) eq2 else eq1
            if (eqCarrySumPrev2 != null && perByteSumMap[zPrev1] != null && carryOverMap[zPrev2] != null) {
                val byteXOR = Wire(perByteSumMap[zPrev1]!!)
                val carry =  Wire(carryOverMap[zPrev2]!!)
                val wirePair = Pair(byteXOR, carry)
                if (eqCarrySumPrev2.checkOp(wirePair, GateOperator.AND)) {
                    return true
                }
            }
        }

        return false
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

        wireValues[w.name] = w.value!!
    }

    fun run() {
        if (hasRun) return

        systemInit.entries.forEach { (name, value) ->
            val wire = Wire(name, value)
            onWireDateValueChange(wire)
        }

        hasRun = true
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

                // wires
                md.wires.getOrPut(
                    wire1
                ) { Wire(gate.inputs.first.name) }
                md.wires.getOrPut(
                    wire2
                ) { Wire(gate.inputs.second.name) }
                val output = md.wires.getOrPut(
                    gate.output.name
                ) { gate.output }
                output.affectedBy.addAll(setOf(wire1, wire2))
            }

            return md
        }
    }
}
