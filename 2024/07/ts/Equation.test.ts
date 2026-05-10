import { describe, it, expect } from 'vitest';
import { Equation } from './types/index.ts';

describe('Advent of Code 2024 Day 7', () => {
  const mockInput = `190: 10 19\n`
    + `3267: 81 40 27\n`
    + `83: 17 5\n`
    + `156: 15 6\n`
    + `7290: 6 8 6 15\n`
    + `161011: 16 10 13\n`
    + `192: 17 8 14\n`
    + `21037: 9 7 18 13\n`
    + `292: 11 6 16 20`;

  it('parses equations correctly', () => {
    const equations = mockInput
      .trim().split('\n').filter(Boolean)
      .map((it) => Equation.factory(it));
    expect(equations.length).toBe(9);
    expect(equations[0].result).toBe(190);
    expect(equations[0].operands.join(', ')).toBe('10, 19');
  });

  it('creates equations branches to solve equation with + and *', () => {
    const opAddMulti: Operator[] = ['+', '*'];
    const equations = mockInput
      .trim().split('\n').filter(Boolean)
      .map((it) => Equation.factory(it).solve(opAddMulti));
    expect(equations.filter((it) => it.solvable).length).toBe(3);
    expect(Equation.calibration(equations)).toBe(3749);
  });

  it('unsolvables equations may be solved using concatenation ||', () => {
    const opAddMulti: Operator[] = ['+', '*'];
    const opAll: Operator[] = ['+', '*', '||'];
    const equations = mockInput
      .trim().split('\n').filter(Boolean)
      .map((it) => Equation.factory(it).solve(opAddMulti));
    const unresolvedEquations = equations.filter((it) => !it.solvable);
    expect(unresolvedEquations.length).toBe(6);
    const solvedWithMixedOperators = unresolvedEquations
      .map(it => it.solve(opAll))
      .filter(it => it.solvable);
    expect(solvedWithMixedOperators.length).toBe(3);
    expect(equations.filter(it => it.solvable).length).toBe(6);
    expect(Equation.calibration(equations)).toBe(11387);
  });
});