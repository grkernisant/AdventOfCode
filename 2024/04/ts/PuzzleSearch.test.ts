import { describe, it, expect } from 'vitest';
import { PuzzleSearch } from './types/PuzzleSearch.ts';

describe('Advent of Code 2024 Day 4', () => {
  const mockInputSmall = `..X...\n.SAMX.\n.A..A.\nXMAS.S\n.X....`;
  const mockInput = `MMMSXXMASM\nMSAMXMSMSA\nAMXSXMAAMM\nMSAMASMSMX\nXMASAMXAMM\nXXAMMXXAMA\nSMSMSASXSS\nSAXAMASAAA\nMAMMMXMMMM\nMXMXAXMASX`;

  it('parses a puzzle correctly', () => {
    const smallPS = new PuzzleSearch(mockInputSmall, "XMAS");
    expect(smallPS.cols).toBe(6);
    expect(smallPS.rows).toBe(5);
    expect(smallPS.puzzle[3][0]).toBe('X');
    expect(smallPS.solve()).toBe(4);

    const ps = new PuzzleSearch(mockInput, "XMAS");
    expect(ps.cols).toBe(10);
    expect(ps.rows).toBe(10);
    expect(ps.puzzle[3][0]).toBe('M');
    expect(ps.solve()).toBe(18);

    const psx = new PuzzleSearch(mockInput, "MAS");
    expect(psx.cols).toBe(10);
    expect(psx.rows).toBe(10);
    expect(psx.puzzle[3][0]).toBe('M');
    expect(psx.solveX()).toBe(9);
  });
});