import { describe, it, expect } from 'vitest';
import { Bathroom, Parser } from './types/index.ts';

describe('Advent of Code 2024 Day 14', () => {
  const mockInput = `p=0,4 v=3,-3\n`
    + `p=6,3 v=-1,-3\n`
    + `p=10,3 v=-1,2\n`
    + `p=2,0 v=2,-1\n`
    + `p=0,0 v=1,3\n`
    + `p=3,0 v=-2,-2\n`
    + `p=7,6 v=-1,-3\n`
    + `p=3,0 v=-1,-2\n`
    + `p=9,3 v=2,3\n`
    + `p=7,3 v=-1,2\n`
    + `p=2,4 v=2,-3\n`
    + `p=9,5 v=-3,-3`;

  it ('Can parse robots and their speeds', () => {
    const p = new Parser(mockInput);
    expect(p.robots.length).toBe(12);

    const b = new Bathroom();
    b.addRobots(p.getInput());
    expect(b.robots.length).toBe(12);

    const posAfter100 = b.getRobotsPositionAfter(100);
    const quandrantCounts = b.getQuandrantCounts(posAfter100);
    expect(quandrantCounts.join(',')).toBe('1,3,4,1');
    expect(b.getSafetyFactor(quandrantCounts)).toBe(12);
  });
});