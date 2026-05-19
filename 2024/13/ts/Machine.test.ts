import { describe, it, expect } from 'vitest';
import { PRIZE_ADJUSTMENT, Parser } from './types/index.ts';

describe('Advent of Code 2024 Day 13', () => {
  const mockInput = `Button A: X+94, Y+34\n`
    + `Button B: X+22, Y+67\n`
    + `Prize: X=8400, Y=5400\n\n`

    + `Button A: X+26, Y+66\n`
    + `Button B: X+67, Y+21\n`
    + `Prize: X=12748, Y=12176\n\n`

    + `Button A: X+17, Y+86\n`
    + `Button B: X+84, Y+37\n`
    + `Prize: X=7870, Y=6450\n\n`

    + `Button A: X+69, Y+23\n`
    + `Button B: X+27, Y+71\n`
    + `Prize: X=18641, Y=10279`;

  it('Parses a Claw Machine list successfully', () => {
    const parser = new Parser(mockInput);
    expect(parser.machines.length).toBe(4);
  });

  it('It solve a Claw Machine correctly', () => {
    const parser = new Parser(mockInput);
    expect(parser.machines[1].solvable).toBe(false);
    expect(parser.machines[3].solvable).toBe(false);

    expect(parser.machines[0].solvable).toBe(true);
    expect(parser.machines[2].solvable).toBe(true);

    expect(parser.machines[0].hitA).toBe(80);
    expect(parser.machines[0].hitB).toBe(40);
    expect(parser.machines[0].cost).toBe(280);

    expect(parser.machines[2].hitA).toBe(38);
    expect(parser.machines[2].hitB).toBe(86);
    expect(parser.machines[2].cost).toBe(200);
  });

  it('It solves how many tokens to spend correctly', () => {
    const parser = new Parser(mockInput);
    expect(parser.getTokens()).toBe(480);
  });

  it('It solve a Claw Machine correctly after prize correction', () => {
    const parser = new Parser(mockInput);
    parser.machines.map(m => {
      const newPrize = {
       x: m.prize.x + PRIZE_ADJUSTMENT,
       y: m.prize.y + PRIZE_ADJUSTMENT,
      };
      m.setPrize(newPrize);
      m.reset();
      m.setMaxHits(0);
      m.solve();
    });

    expect(parser.machines[1].solvable).toBe(true);
    expect(parser.machines[3].solvable).toBe(true);

    expect(parser.machines[0].solvable).toBe(false);
    expect(parser.machines[2].solvable).toBe(false);
  });
});