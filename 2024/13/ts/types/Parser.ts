import { BUTTON_REGEX, PRIZE_REGEX, Machine } from './index.ts';

export const PRIZE_ADJUSTMENT = 10000000000000;
export const UNLIMITED_HITS = 0;

export class Parser {
  machines: Machine[];
  maxHits: number;

  constructor(input: string, maxHits: number = 100) {
    // <= 0 for unlimited hits
    this.maxHits = !isNaN(maxHits) && maxHits >= 0 ? maxHits : 0;
    this.machines = [];
    this.init(input);
  }

  getInput(): Machine[] {
    return this.machines;
  }

  getTokens(): number {
    return this.machines.reduce((acc, curr) => {
      if (curr.solvable) acc+= curr.cost;
      return acc;
    }, 0);
  }

  init(input: string) {
    this.parseMachines(input);
    this.machines.map(m => m.solve());
  }

  parseMachines(input: string) {
    const lines = input.trim().split('\n');
    const l = lines.length;
    let i: number = 0;
    let j: number;
    let buffer: Machine = new Machine();

    while (i < l) {
      const line = lines[i].trim();
      if (line === '') {
        i++;
        continue;
      }

      j = i % 4;
      if (j < 2) {
        // clear buffer
        if (j === 0) buffer = new Machine();
        const matches = line.match(BUTTON_REGEX);
        if (matches) {
          const x = Number(matches[2]);
          const y = Number(matches[3]);
          if (j === 0) buffer.setButtonA({ x, y });
          else buffer.setButtonB({ x, y });
        }
      }

      if (j === 2) {
        const matches = line.match(PRIZE_REGEX);
        if (matches) {
          buffer.setPrize({
            x: Number(matches[1]),
            y: Number(matches[2])
          });
          buffer.setMaxHits(this.maxHits);

          if (buffer.isDefined()) this.machines.push(buffer);
        }
      }

      i++;
    }
  }
}