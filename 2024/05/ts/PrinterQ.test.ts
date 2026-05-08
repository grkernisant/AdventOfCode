import { describe, it, expect } from 'vitest';
import { Printer } from './types/PrinterQ.ts';

describe('Advent of Code 2024 Day 5', () => {
  const mockInput = `47|53\n` +
    `97|13\n` +
    `97|61\n` +
    `97|47\n` +
    `75|29\n` +
    `61|13\n` +
    `75|53\n` +
    `29|13\n` +
    `97|29\n` +
    `53|29\n` +
    `61|53\n` +
    `97|53\n` +
    `61|29\n` +
    `47|13\n` +
    `75|47\n` +
    `97|75\n` +
    `47|61\n` +
    `75|61\n` +
    `47|29\n` +
    `75|13\n` +
    `53|13\n` +
    `\n` +
    `75,47,61,53,29\n` +
    `75,97,47,61,53\n`;

  it('parses a printer correctly', () => {
    const prt = new Printer(mockInput);

    expect(prt.pageOrderRules.length).toBe(21);
    expect(prt.printQueues.length).toBe(2);
    expect(prt.printQueues[1].validSort).toBe(false);
    expect(prt.printQueues[0].pages.length).toBe(5);
    expect(prt.printQueues[0].validSort).toBe(true);
    expect(prt.printQueues[0].middle).toBe(61);
  });
});