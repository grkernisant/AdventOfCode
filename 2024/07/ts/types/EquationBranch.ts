import type { EquationPath, Operand, Operator } from './index.ts';

export class EquationBranch {
  subtotal: Result | undefined;
  operand: Operand | undefined;
  children: EquationPath | undefined;

  constructor(operand?: Operand, subtotal?: Result) {
    this.subtotal = subtotal ?? null;
    this.operand = operand ?? null;
  }

  findResult(r: Result) {
    return this.children === undefined
      ? this.subtotal === r
      : this.children.add?.findResult(r) || this.children.multiply?.findResult(r) || this.children.concat?.findResult(r);
  }

  insert(operand: Operand, operators: Operator[]) {
    if (operators.length === 0) throw new Error(`NO_OPERATORS_DEFINED`);

    if (this.children !== undefined) {
      if (operators.includes('+')) this.children.add.insert(operand, operators);
      if (operators.includes('*')) this.children.multiply.insert(operand, operators);
      if (operators.includes('||')) this.children.concat.insert(operand, operators);
    }

    if (this.children === undefined) {
      const addition = this.subtotal !== null ? this.subtotal + operand : operand;
      const multiplication = this.subtotal !== null ? this.subtotal * operand : operand;
      const concat = this.subtotal !== null ? Number(`${this.subtotal}${operand}`) : operand;
      this.children = {};
      if (operators.includes('+')) this.children.add = new EquationBranch(operand, addition);
      if (operators.includes('*')) this.children.multiply = new EquationBranch(operand, multiplication);
      if (operators.includes('||')) this.children.concat = new EquationBranch(operand, concat);
    }

  }
}
