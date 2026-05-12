import { describe, it, expect } from 'vitest';
import { DiskMap } from './types/index.ts';

describe('Advent of Code 2024 Day 9', () => {
  const mockInput = `2333133121414131402`;
  const mockUnzipped = `00...111...2...333.44.5555.6666.777.888899`;
  const mockSorted = `0099811188827773336446555566..............`;

  it('Parses a DiskMap correctly', () => {
    const dm = new DiskMap('12345');

    expect(dm.getFiles().length).toBe(3);
    expect(dm.getFiles()[0].blockSize).toBe(1);
    expect(dm.getFiles()[0].fileID).toBe(0);
    expect(dm.getFiles()[1].blockSize).toBe(3);
    expect(dm.getFiles()[1].fileID).toBe(1);
    expect(dm.getFiles()[2].blockSize).toBe(5);
    expect(dm.getFiles()[2].fileID).toBe(2);

    expect(dm.getFreeSpace().length).toBe(2);
    expect(dm.getFreeSpace()[0].blockSize).toBe(2);
    expect(dm.getFreeSpace()[1].blockSize).toBe(4);
  });

  it('Defrags a DiskMap correctly', () => {
    const dm = new DiskMap(mockInput);
    expect(dm.toString()).toBe(mockUnzipped);
    dm.defrag();
    expect(dm.toString()).toBe(mockSorted);
  });

  it('Get checksum correctly', () => {
    const dm = new DiskMap(mockInput);
    const cs = dm.defrag().getDiskChecksum();
    expect(cs).toBe(1928);
  });

  it('Moves whole files correctly', () => {
    const dm = new DiskMap(mockInput);
    const cs = dm.moveFiles().getDiskChecksum();
    expect(cs).toBe(2858);
  });

  /*** extras ***/
  it('Swaps spaces correctly', () => {
    const dm = new DiskMap('12345');
    expect(dm.swapBlock(1, 3).toString()).toBe('0....111..22222');
  });

  it('Swaps files correctly', () => {
    const dm = new DiskMap('12345');
    expect(dm.swapBlock(0, 4).toString()).toBe('22222..111....0');
  });

  it('Swaps files forward', () => {
    const dm1 = new DiskMap('12345');
    expect(dm1.swapBlock(0, 3).toString()).toBe('...1110...22222');

    const dm2 = new DiskMap('82345');
    expect(dm2.swapBlock(0, 3).toString()).toBe('0000..111000022222');

  });

  it('Merge spaces correctly', () => {
    const dm1 = new DiskMap('12345');
    expect(dm1.mergeSpaces(1, 3).toString()).toBe('0111......22222');

    const dm2 = new DiskMap('12345');
    expect(dm2.mergeSpaces(3, 1).toString()).toBe('0......11122222');
  });
});