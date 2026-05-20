import { ROBOT_REGEX } from './index.ts';
import type { Robot } from './index.ts';

export class Parser {
  robots: Robot[];

  constructor(input: string) {
    this.robots = [];

    input.trim().split(`\n`)
      .map(line => {
        const matches = line.trim().match(ROBOT_REGEX);
        if (matches) {
          this.robots.push({
            pos: { x: Number(matches[1]), y: Number(matches[2]) },
            acc: { x: Number(matches[3]), y: Number(matches[4]) }
          });
        }
      });
  }

  getInput(): Robot[] { return this.robots; }
}