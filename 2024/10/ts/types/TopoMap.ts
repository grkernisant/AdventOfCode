import { DIRECTION_OFFSET, TRAIL_HEAD_START, Pos2String, TrailHead } from './index.ts';
import type { Altitude, Position, Topo, TopoScore } from './index.ts';

export class TopoMap {
  cols: number;
	topomap: Topo[][];
  theads: TrailHead[];
  rows: number;

	constructor(input: string) {
    this.theads = [];
    this.topomap = input
      .trim()
      .split('\n')
      .map((l, y) => {
        return l
          .trim()
          .split('')
          .map((c, x) => {
            const topo = !isNaN(c)
              ? { type: 'trail', h: Number(c), x, y }
              : { type: 'ground', h: c, x, y }
            if (topo.type === 'trail' && topo.h === TRAIL_HEAD_START) {
              this.theads.push(new TrailHead({map: this, x, y}));
            }

            return topo;
          });
      });

    this.rows = this.topomap.length;
    this.cols = this.topomap[0].length;

    this.init();
	}

  findNextPaths(x: number, y: number, h: Altitude): Topo[] {
    return Array.from(DIRECTION_OFFSET.values())
      .map(v => {
        const dx = x + v[0];
        const dy = y + v[1];

        return [dx, dy];
      })
      .filter(pos => {
        const tv = this.getTopoValue(pos[0], pos[1]);
        return tv !== undefined &&
          tv.type === 'trail' &&
          tv.h === (h + 1)
      });
  }

  getRatings(): TopoRating {
    // per trails
    const firstPass = this.theads.reduce((acc, curr) => {
      if (curr.ratings.size > 0) {
        curr.ratings.forEach((v, k) => {
          acc.trails.set(k, v.size);
          acc.total+= v.size;
        });
      }

      return acc;
    }, { trails: new Map<string, number>(), total: 0 });

    // per trails heads
    const secondPass = {
      details: new Map<string, number[]>(),
      subtotals: new Map<string, number>(),
      total: firstPass.total
    };
    firstPass.trails.forEach((v, k) => {
      const keys = k.split('-');
      secondPass.details.set(keys[0], [...(secondPass.details.get(keys[0]) ?? []), v]);
      secondPass.subtotals.set(keys[0], (secondPass.subtotals.get(keys[0]) ?? 0) + v);
    });

    return secondPass;
  }

  getScore(): TopoScore {
    return this.theads.reduce((acc, curr) => {
      if (curr.score > 0) {
        acc.subtotals.set(Pos2String({x: curr.x, y: curr.y}), curr.score);
        acc.total+= curr.score;
      }

      return acc;
    }, { subtotals: new Map<string, number>(), total: 0 });
  }

  getTopoValue(x: number, y: number): Topo | undefined {
    if (isNaN(x) || isNaN(y)) throw new Error(`BAD_REQUEST_TOPO_VALUE_NAN: x:${x} y:${y}`);
    if (this.outOfBounds(x, y)) return undefined;

    return this.topomap[y][x];
  }

  init() {
    this.theads.map(th => th.init());
  }

  outOfBounds(x: number, y: number): boolean {
    return x < 0 || x >= this.cols || y < 0 || y >= this.rows;
  }

}