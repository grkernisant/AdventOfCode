import { ANTENNA_REGEX, Num2Pos, Num2String, Pos2Num, Str2Pos } from './index.ts';
import type { AntennaFrequency, Antinode, Position } from './index.ts';

export class AntennaMap {
  antinodes: Set<string>;
  cols: number;
  frequencies: Map<AntennaFrequency, Set<number[]>>;
  rows: number;

  constructor(input: string) {
    this.frequencies = new Map<AntennaFrequency, Set<number[]>>();
    this.antinodes = new Set<string>();
    const lines = input
      .trim()
      .split('\n')
      .map((l, y) => {
        const matches = l.trim().matchAll(ANTENNA_REGEX);
        for (let match of matches) {
          if (!this.frequencies.has(match[0])) {
            this.frequencies.set(match[0], new Set<number[]>());
          }

          this.frequencies.get(match[0])?.add([match.index, y]);
        }

        return l.trim().split('');
      });


    this.rows = lines.length;
    this.cols = lines[0].length;
    this.setAntinodes();
  }

  getAntinode(p1: Position, p2: Position): Position | undefined {
    const a = { x: p2.x + (p2.x - p1.x), y: p2.y + (p2.y - p1.y) };
    return !this.outOfBounds(a.x, a.y) ? a : undefined;
  }

  getAntinodes(p1: Position, p2: Position): Position[] {
    const antinodes: Position[] = [];
    const aP1P2 = this.getAntinode(p1, p2);
    if (aP1P2) antinodes.push(aP1P2);
    const aP2P1 = this.getAntinode(p2, p1);
    if (aP2P1) antinodes.push(aP2P1);

    return antinodes;
  }

  setAntinodes() {
    this.frequencies.forEach((positions, freq) => {
      const arrPositions = Array.from(positions);
      if (arrPositions.length > 1) {
        const l = arrPositions.length;
        for(let n = 0; n <= l - 2; n++) {
          for(let m = n + 1; m <= l - 1; m++) {
            const aPositions = this.getAntinodes(
              Num2Pos(arrPositions[n]),
              Num2Pos(arrPositions[m])
            );
            aPositions.map((pos) => this.antinodes.add(Num2String(Pos2Num(pos))));
          }
        }
      }

    });
  }

  outOfBounds(x: number, y: number): boolean {
    return x < 0 || x >= this.cols || y < 0 || y >= this.rows;
  }

  toString(): string {
    const output = `${('.'.repeat(this.cols))}\n`
      .repeat(this.rows)
      .split('\n')
      .map(l => l.trim())
      .map(l => l.split(''));
    // antinodes
    this.antinodes.forEach((a) => {
      const pos = Str2Pos(a);
      output[pos.y][pos.x] = '#';
    });
    // frequencies
    this.frequencies.forEach((positions, freq) => {
      positions.forEach((num) => {
        const freqPost = Num2Pos(num);
        output[freqPost.y][freqPost.x] = freq;
      });
    });

    return output.reduce((acc, curr) => {
      acc = `${acc}\n${curr.join('')}`.trim();
      return acc;
    }, '');
  }
}