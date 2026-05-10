import { EquationBranch }  from './index.ts';
import type { Operand, Operator, Result } from './index.ts';

export const EQUATION_REGEX = /^(\d+):(?: (\d+))+$/;

export class Equation {
  operands: Operand[];
  result: Result;
  solvable: boolean;

  constructor(result: Result, operands: Operand[]) {
    this.result = result;
    this.operands = operands;
    this.solvable = false;
  }

  static calibration(equations: Equation[]): number {
    return equations.filter(it => it.solvable).reduce((acc, curr) => acc + curr.result, 0);
  }

  static factory(input: string): Equation {
    const matches = input.trim().match(EQUATION_REGEX);
    if (matches === null) throw new Error(`EQUATION_PARSE_ERROR: ${input.trim()}`);

    const result = Number(matches[1]);
    const operands = input
      .trim()
      .substring(matches[1].length + 1)
      .trim()
      .split(' ')
      .map((o) => Number(o)) as Operand[];
    return new Equation(result, operands);
  }

  solve(operators: Operator[]) {
    const eb = new EquationBranch();
    this.operands.map((op) => eb.insert(op, operators));
    this.solvable = eb.findResult(this.result);

    return this;
  }
}