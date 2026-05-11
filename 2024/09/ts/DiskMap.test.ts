import { describe, it, expect } from 'vitest';
import { DiskMap } from './types/index.ts';

describe('Advent of Code 2024 Day 9', () => {
  const mockInput = `2333133121414131402`;
  const mockUnzipped = `00...111...2...333.44.5555.6666.777.888899`;
  const mockSorted = `0099811188827773336446555566..............`;

  it('Parses and unzips a DiskMap correctly', () => {
    const dm = new DiskMap(mockInput);
    expect(dm.unzipped).toBe(mockUnzipped);
  });

  it('Sorts a DiskMap correctly', () => {
    const dm = new DiskMap(mockInput);
    dm.defrag();
    expect(dm.unzipped).toBe(mockSorted);
    expect(dm.getDiskChecksum()).toBe(1928);
  });
});