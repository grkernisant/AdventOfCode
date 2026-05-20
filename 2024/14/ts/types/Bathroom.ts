import type { Position, Robot } from './index.ts';
import { sortPositionY } from './index.ts';

export interface BathroomOption {
  c: number,
  r: number
}

export class Bathroom {
  cols: number;
  robots: Robot[];
  rows: number;
  quadrantBlindX: number;
  quadrantBlindY: number;

  constructor(options: BathroomOption = { c: 11, r: 7 }) {
    this.cols = options.c;
    this.rows = options.r;
    this.robots = [];
    this.quadrantBlindX = Math.floor(this.cols / 2);
    this.quadrantBlindY = Math.floor(this.rows / 2);
  }

  addRobots(robots: Robot[]) {
    this.robots.splice(robots.length, 0, ...robots);
  }

  parseXmasTree(positions: Position[]): boolean {
    // look for triangular formation
    const posX = positions.filter(p => p.x === this.quadrantBlindX);
    const l = posX.length;
    let found = false;
    let n = 0;
    let nextRows_3: Position[];
    let nextRows_5: Position[];
    let nextRows_7: Position[];
    while (n < l && !found) {
      nextRows_3 = positions.filter(p => {
        return (p.y === posX[n].y + 1) &&
        p.x >= posX[n].x - 1 &&
        p.x <= posX[n].x + 1
      });

      nextRows_5 = positions.filter(p => {
        return (p.y === posX[n].y + 2) &&
        p.x >= posX[n].x - 2 &&
        p.x <= posX[n].x + 2
      });

      nextRows_7 = positions.filter(p => {
        return (p.y === posX[n].y + 3) &&
        p.x >= posX[n].x - 3 &&
        p.x <= posX[n].x + 3
      });

      found = nextRows_3.length === 3 && nextRows_5.length === 5 && nextRows_7.length === 7;
      n++;
    }

    return found;
  }

  getEasterEgg(): number {
    // when are all the robots grouped?
    const isGrouped = 10;
    let t = 0;
    let pos: Position[];
    let aligned: Position[];
    const maxGroupedAligned: number[] = `00`.repeat(this.cols).split('').map(x => Number(x));
    while (t < 2 * this.cols) {
      pos = this.getRobotsPositionAfter(t);
      let nb = 0;
      for (let x = 0; x < this.cols; x++) {
        aligned = pos.filter(p => p.x === x);
        nb+= aligned.length >= isGrouped ? aligned.length : 0;
        /*const l = aligned.length;
        if (stats.maxAligned[x] <= l) {
          stats.maxAligned[x] = l;
          stats.t[x] = t;
          if (!stats.freq.has(l)) stats.freq.set(l, 0);
          stats.freq.set(l, stats.freq.get(l) + 1);
        }*/
      }
      maxGroupedAligned[t] = nb;
      t++;
    }
    const maxAligned = Math.max(...maxGroupedAligned);
    const Occurance_1 = maxGroupedAligned.findIndex(nb => nb === maxAligned);
    // when does it happen again?
    const Occurance_2 = (Occurance_1 + 1) + maxGroupedAligned.slice(Occurance_1 + 1).findIndex(nb => nb === maxAligned);
    // increment
    const deltaT = Occurance_2 - Occurance_1;
    let found: boolean;
    t = Occurance_1;
    do {
      pos = this.getRobotsPositionAfter(t);
      found = this.parseXmasTree(pos);
      if (!found) t+= deltaT;
    } while (!found);

    return t;
  }

  getRobotPositionAfter(r: Robot, sec: number = 0): Position {
    const x = (r.pos.x + r.acc.x * sec) % this.cols;
    const y = (r.pos.y + r.acc.y * sec) % this.rows;
    return {
      x: x >= 0 ? Math.abs(x) : x + this.cols,
      y: y >= 0 ? Math.abs(y) : y + this.rows,
    };
  }

  getRobotsPositionAfter(sec: number): Position[] {
    return this.robots.map(r => this.getRobotPositionAfter(r, sec));
  }

  getQuandrantCounts(positions: Position[]): number[] {
    const quadrant = positions.reduce((acc, p) => {
      const qIndex = this.getQuandrantIndex(p);
      if (qIndex !== undefined && !isNaN(qIndex)) {
        if (!acc.has(qIndex)) acc.set(qIndex, []);

        acc.get(qIndex).push(p);
      }

      return acc;
    }, new Map<number, Position[]>());


    return [
      quadrant.get(1)?.length ?? 0,
      quadrant.get(2)?.length ?? 0,
      quadrant.get(3)?.length ?? 0,
      quadrant.get(4)?.length ?? 0,
    ];
  }

  getQuandrantIndex(p: Position):  number | undefined {
    if (p.x === this.quadrantBlindX || p.y === this.quadrantBlindY) return undefined;

    if (p.y < this.quadrantBlindY) {
      return p.x < this.quadrantBlindX ? 1 : 2;
    }

    return p.x < this.quadrantBlindX ? 3 : 4;
  }

  getSafetyFactor(quadrantCounts: number[]): number {
    if (quadrantCounts.length === 0) return 0;

    return quadrantCounts.reduce((acc, curr) => acc * curr, 1);
  }

  print(pos: Position[]): string {
    const blank = `${`.`.repeat(this.cols)}\n`
      .repeat(this.rows).trim()
      .split('\n')
      .map(line => line.trim().split(''));
    const bathroom = pos.reduce((acc, curr) => {
        acc[curr.y][curr.x] = '#';
        return acc;
      }, blank );


    return bathroom
      .map(cols => `${cols.join('')}`)
      .join('\n');
  }
}