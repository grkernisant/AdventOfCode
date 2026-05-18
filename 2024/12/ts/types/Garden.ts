import {
  DIRECTION_ALL_OFFSET,
  DIRECTION_OFFSET,
  PLANT_TYPE_REGEX,
  Num2String,
  Plant2String,
  Region,
  String2Pos,
} from './index.ts';
import type { Plant, PlantMetadata } from './index.ts';
import { writeFileContent } from '../utils/File.ts';

const PLANT_NEIGHBORS_CACHE = new Map<string, Plant[]>();

export class Garden {
  cols: number;
  isNegative: boolean;
  map: Plant[][];
  name: string;
  plantsMetadata: Map<string, PlantMetadata>;
  rows: number;
  regions: Map<string, Region[]>;

  constructor(input: string, isNegative: boolean = false) {
    this.name = `Garden${Math.random()}`;
    this.isNegative = isNegative;
    this.map = input.trim().split('\n')
      .map((line, y) => {
        return line.trim()
          .split('')
          .map((pt, x) => { return { pt, x, y }; });
      });
    this.rows = this.map.length;
    this.cols = this.map[0].length;
    this.regions = new Map<string, Region[]>();
    this.plantsMetadata = new Map<string, PlantMetadata>();
    this.init();
  }

  discoverRegions() {
    this.map.map((line, y) => {
      const plantsOnRow = line.reduce((acc, curr) => acc + curr.pt, '');
      const matches = plantsOnRow.matchAll(PLANT_TYPE_REGEX);
      matches.forEach(m => {
        let i = m.index;
        const plantsToAdd = m[0].split('').reduce((acc, curr) => {
          acc.push({ pt: m[1], x: i, y });
          i++;

          return acc;
        }, []);

        if (!this.regions.has(m[1])) this.regions.set(m[1], []);

        let regionIndex = this.regions.get(m[1]).findIndex(region => {
          const plantY = y - 1;
          const plantX = m.index;
          let found = false;
          let i = 0;
          let l = m[0].length;
          let plantToFind: Plant;
          while(i < l && !found) {
            plantToFind = Plant2String({ pt: m[1], x: plantX + i, y: plantY });
            found = region.plants.find(p => Plant2String(p) === plantToFind) !== undefined;
            i++;
          }

          return found;
        });

        if (regionIndex === -1) {
          const r = new Region({ garden: this, pt: m[1] });
          this.regions.get(m[1]).push(r);
          regionIndex = this.regions.get(m[1]).length - 1;
        }
        this.regions.get(m[1])[regionIndex].addPlant(plantsToAdd); 
      });
    });
  }

  static factorizeFromNegativeSpace(cols: Number, rows: Number, plants: Plant[]): Garden {
    const negativeSpace = `${'N'.repeat(cols)}\n`.repeat(rows)
      .trim()
      .split('\n')
      .map(l => l.split(''));
    const input = plants
      .reduce((acc, curr) => {
        acc[curr.y][curr.x] = 'P';
        return acc;
      }, negativeSpace)
      .map(l => l.join(''))
      .join('\n');
    const ns = new Garden(input, true);
    return ns;
  }

  findRegionWithPlant(plant: Plant): Region | undefined {
    if (this.regions.size === 0) return undefined;
    if (!this.regions.has(plant.pt)) return undefined;

    return this.regions.get(plant.pt).find(r => r.findPlant(plant));
  }

  getBulkFencePrice(): number {
    let bulkFencePrice = 0;
    this.regions.forEach((r, pt) => {
      bulkFencePrice+= r.reduce((acc, curr) => {
        acc+= curr.bulkFencePrice;
        return acc;
      }, 0);
    });

    return bulkFencePrice;
  }

  getCol(x: number): Plant[] {
    let y = 0;
    const plants = [];
    while (y < this.rows) {
      plants.push(this.getPlant(x, y));
      y++;
    }

    return plants;
  }

  getFencePrice(): number {
    let fencePrice = 0;
    this.regions.forEach((r, pt) => {
      fencePrice+= r.reduce((acc, curr) => {
        acc+= curr.fencePrice;
        return acc;
      }, 0);
    });

    return fencePrice;
  }

  getPlant(x: number, y: number): Plant | undefined {
    if (this.outOfBounds(x, y)) return undefined;

    return this.map[y][x];
  }

  getPlantAllNeighbors(x: number, y: number): Plant[] | undefined {
    const plantNeighbrosCacheKey = `${this.name}\\${Num2String([x, y])}.all`;
    if (PLANT_NEIGHBORS_CACHE.has(plantNeighbrosCacheKey)) return PLANT_NEIGHBORS_CACHE.get(plantNeighbrosCacheKey);

    const plant = this.getPlant(x, y);
    if (plant === undefined) return undefined;

    const offsets = Array.from(DIRECTION_ALL_OFFSET.values());
    const neighbors: Plant[] = offsets.reduce((acc, curr) => {
      const dx = plant.x + curr[0];
      const dy = plant.y + curr[1];
      const n = this.getPlant(dx, dy);
      if (n !== undefined) acc.push(n);

      return acc;
    }, []);

    PLANT_NEIGHBORS_CACHE.set(plantNeighbrosCacheKey, neighbors);
    return neighbors;
  }

  getPlantMetadata(plantKey: string, metadataKey: string | undefined): unknown | undefined {
    const md = this.plantsMetadata.get(plantKey);
    if (md === undefined) return undefined;
    if (metadataKey === undefined) return md;

    return md[metadataKey];
  }

  getPlantNeighbors(x: number, y: number): Plant[] | undefined {
    const plantNeighbrosCacheKey = `${this.name}\\${Num2String([x, y])}`;
    if (PLANT_NEIGHBORS_CACHE.has(plantNeighbrosCacheKey)) return PLANT_NEIGHBORS_CACHE.get(plantNeighbrosCacheKey);

    const plant = this.getPlant(x, y);
    if (plant === undefined) return undefined;

    const offsets = Array.from(DIRECTION_OFFSET.values());
    const neighbors: Plant[] = offsets.reduce((acc, curr) => {
      const dx = plant.x + curr[0];
      const dy = plant.y + curr[1];
      const n = this.getPlant(dx, dy);
      if (n !== undefined) acc.push(n);

      return acc;
    }, []);

    PLANT_NEIGHBORS_CACHE.set(plantNeighbrosCacheKey, neighbors);
    return neighbors;
  }

  getRegion(regionName: region): Region | undefined {
    const regions = Array.from(this.regions.values()).flat();
    return regions.find(r => r.name === regionName);
  }

  getRow(y: number): Plant[] {
    let x = 0;
    const plants = [];
    while (x < this.cols) {
      plants.push(this.getPlant(x, y));
      x++;
    }

    return plants;
  }

  hasRegion(regionName: string): boolean {
    return this.getRegion(regionName) !== undefined;
  }

  init() {
    // parse map and create regions based on plant type
    this.discoverRegions();
    // merge regions if intersecting points or neighbor
    this.mergeRegions();
    // regions init
    this.initRegions();
  }

  initRegions() {
    this.regions.forEach((regs, pt) => regs.map(r => {
      r.init();
    }));

    if (this.isNegative) return;

    this.regions.forEach((regs, pt) => regs.map((r) => {
      r.postInit();
    }));
  }

  mergeRegions() {
    for (const [pt, r] of this.regions) {
      const l = r.length;
      if (l === 1) continue;
      if (this.isNegative && pt === 'N') continue;

      for (let i = 0; i < r.length - 1; i++) {
        if (r[i].plants.length === 0) continue;
        const plantsDst = r[i].getPlantsAndNeighborsSet();
        const plantsDstStr = plantsDst.join(', ');
        for (let j = r.length - 1; j > i; j--) {
          if (r[j].plants.length === 0) continue;
          const plantsSrc = r[j].getPlantsAndNeighborsSet();
          const plantsSrcStr = `${plantsSrc
            .join('|')
            .replaceAll('(', '\\(')
            .replaceAll(')', '\\)')}`;
          const plantsSrcRegex = new RegExp(plantsSrcStr);
          // found matching plant coord or neighbor position
          const matches = plantsDstStr.match(plantsSrcRegex);
          if (matches !== null) {
            plantsSrc.map(pStr => {
              const pPos = { pt: pt, ...String2Pos(pStr) };
              r[i].addPlant(pPos);
            });
            // will filter and purge after loop
            r[j].plants = [];
            r.splice(j, 1);
            i = -1;
            break;
          }
        }
      }
    }
  }

  outOfBounds(x: number, y: number): boolean {
    if(isNaN(x) || isNaN(y)) return true;

    return x < 0 || x >= this.cols || y < 0 || y >= this.rows;
  }

  setPlantMetadata(plantKey: string, metadataKey: string, metadataValue: Unknown) {
    if (!this.plantsMetadata.has(plantKey)) this.plantsMetadata.set(plantKey, {});

    const md = this.plantsMetadata.get(plantKey);
    md[metadataKey] = metadataValue;
    this.plantsMetadata.set(plantKey, md);
  }
}