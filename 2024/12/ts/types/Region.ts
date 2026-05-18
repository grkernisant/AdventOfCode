import {
  DIRECTION_OFFSET,
  PLANT_TYPE_REGEX,
  getDirection,
  getDirectionIndex,
  getDirectionOffset,
  getMd5Hash,
  Garden,
  Num2String ,
  Plant2String,
  Pos2String,
  Pos2Num,
  sortPositionByX,
  sortPositionByY,
  String2Num,
  String2Perimeter,
  String2Plant,
  String2Pos,
  turnRight
} from './index.ts';
import type {
  BoundingBox,
  Gardener,
  Plant,
  Perimeter,
  PerimeterKey,
  PlantMetadata,
  PlantType,
  Position
} from './index.ts';

export class Region {
  area: number;
  boundingBox: BoundingBox;
  bulkFencePrice: number;
  exploreNext: Set<string>;
  fence: Set<PerimeterKey>;
  fencePrice: number;
  fenceStr: string;
  garden: Garden;
  innerFenceSides: number;
  name: string;
  neighbors: Set<string>;
  outerSides: number;
  perimiter: number;
  plants: Plant[];
  pt: PlantType;

  constructor({garden: garden, pt: pt}: {garden: Garden, pt: PlantType}) {
    this.garden = garden;
    this.pt = pt;
    this.exploreNext = new Set<string>();
    this.fence = new Set<PerimeterKey>();
    this.plants = [];
    this.neighbors = new Set<string>();
  }

  addFence(pt: PlantType, pl: Plant) {
    this.fence.add(this.getPerimeterKey(pt, pl));
  }

  addPlant(plant: Plant | Plant[]) {
    if (!Array.isArray(plant)) plant = [plant];

    const plantsToAdd = plant.filter(p => this.findPlant(p) === undefined);
    if (plantsToAdd.length > 0) {
      // this.plants.splice(this.plants.length > 0 ? this.plants.length - 1 : 0, 0, ...plantsToAdd);
      this.plants.splice(this.plants.length, 0, ...plantsToAdd);
    }
  }

  buildFenceStr(plants: Plant[]): string {
    return `|${this.getRegionName(plants).replaceAll(";", "|")}|`;
  }

  calculateArea(): number { return this.plants.length; }

  calculateBulkFencePrice() {
    if (isNaN(this.area)) this.area = this.calculateArea();
    const sides = this.getSides();
    this.setBulkFencePrice(this.area * sides);
  }

  calculateFencePrice() {
    this.area = this.calculateArea();
    this.perimeter = this.calculatePerimeter();
    this.setFencePrice(this.area * this.perimeter);
  }

  calculatePerimeter(): number {
    const perimeter = this.plants
      .reduce((acc, p) => {
        const n = this.garden.getPlantNeighbors(p.x, p.y);
        const nbSame = n.reduce((acc, curr) => {
          if (curr.pt === p.pt) acc++;
          return acc;
        }, 0);
        const plantMetaKey = Pos2String(p);
        const nbSides = 4 - nbSame;
        this.setPlantMetadata(plantMetaKey, 'nbSides', nbSides);
        this.setPlantMetadata(plantMetaKey, 'hasFence', nbSides !== 0);
        acc+= nbSides;
        return acc;
      }, 0);

    return perimeter;
  }

  discoverInnerRegions(): string[] {
    const regionName = this.getPlantMetadata(Pos2String(this.plants[0]), 'regionName') ?? undefined;
    if (regionName === undefined) throw new Error(`UNKNOWN_REGION: ${Pos2String(this.plant[0])}`);
    const bb = this.getBoundingBox();
    const dx = bb.br.x - bb.tl.x;
    const dy = bb.br.y - bb.tl.y;
    // too small to contain any region
    if (dx < 2 || dy < 2) {
      return [];
    }

    // analyse each row
    // find other plants within the boundaries
    const others = new Map<string, Plant[]>;
    const plantsPerRow = this.plants
      .sort(sortPositionByY)
      .reduce((acc, curr) => {
        if (!acc.has(curr.y)) acc.set(curr.y, []);
        acc.get(curr.y).push(curr);
        return acc;
    }, new Map<number, Plant[]>());
    for(let y = bb.tl.y; y <= bb.br.y; y++) {
      const plantRow = plantsPerRow.get(y);
      const xMin = plantRow[0].x;
      const xMax = plantRow[plantRow.length-1].x;
      const gardenRow = this.garden.getRow(y);
      for(let x = xMin; x <= xMax; x++) {
        if (gardenRow[x].pt !== this.pt) {
          const md = this.getPlantMetadata(Pos2String(gardenRow[x]));
          if (!others.has(md.regionName)) others.set(md.regionName, []);
          others.get(md.regionName).push(gardenRow[x]);
        }
      }
    }

    others.forEach((otherPlants, name) => {
      const possibleInnerRegionName = this.getRegionName(otherPlants.sort(sortPositionByX));
      const otherRegion = this.garden.getRegion(possibleInnerRegionName);
      if (possibleInnerRegionName !== regionName && otherRegion !== undefined) {
        const perimeter = otherPlants.filter(p => {
          const md = this.getPlantMetadata(Pos2String(p));
          return md.isOuterPerimeter === true && md.hasFence === true;
        });
        const nbDelimiterNeighbors = perimeter.reduce((acc, curr) => {
          const isRegionNeighbor = this.plantHasRegionNeighbor(curr, regionName);
          acc+= isRegionNeighbor ? 1 : 0;
          return acc;
        }, 0);
        if (perimeter.length > 0 && perimeter.length === nbDelimiterNeighbors) {
          this.neighbors.add(possibleInnerRegionName);
        }
      }
    });

    return Array.from(this.neighbors);
  }

  discoverInnerFenceSides(): number {
    if (this.innerFenceSides !== undefined) return this.innerFenceSides;

    if (this.plants.length === 1) {
      this.innerFenceSides = 0;
      return 0;
    }

    if (this.plants.length > 1) {
      const plants = this.discoverInnerRegions().reduce((acc, curr) => {
        const region = this.garden.getRegion(curr);
        acc.splice(acc.length, 0, ...region.plants);
        return acc;
      }, []);

      if (plants.length === 0) {
        this.innerFenceSides = 0;
        return 0;  
      }

      plants.sort(sortPositionByX);
      const xMin = plants[0].x;
      const xMax = plants[plants.length-1].x;
      plants.sort(sortPositionByY);
      const yMin = plants[0].y;
      const yMax = plants[plants.length-1].y;

      const plantsTranslated = plants.map(p => {
        return { pt: p.pt, x: p.x - xMin, y: p.y - yMin };
      });
      // create a new garden with the inner regions and count their outer sides.
      const g = Garden.factorizeFromNegativeSpace(xMax - xMin + 1, yMax - yMin + 1, plantsTranslated);
      this.innerFenceSides = (g.regions.get('P') ?? []).reduce((acc, curr) => acc + curr.outerSides, 0);

      return this.innerFenceSides;
    }

    // length = 0
    return 0;
  }

  discoverPerimeterSides(): number {
    if (this.outerSides !== undefined) return this.outerSides;

    if (this.plants.length === 1) {
      this.setPlantMetadata(Pos2String(this.plants[0]), 'isInnerPerimeter', false);
      this.setPlantMetadata(Pos2String(this.plants[0]), 'isOuterPerimeter', true);
      this.addFence(this.pt, this.plants[0]);
      this.outerSides = 4;
      return 4;
    }

    // starting point
    const bb = this.getBoundingBox();
    const pos = this.plants
      .sort(sortPositionByY)
      .filter(p => p.y === bb.tl.y)
      .sort(sortPositionByX)[0];
    let gardener: Gardener = {
      dir: '>',
      x: pos.x,
      y: pos.y
    };
    const gardenerNextPosition = this.getGardenerPosition(gardener, 0) ?? this.getGardenerPosition(gardener, 1);
    const startPositionKey = this.getGardenerPositionKey(gardenerNextPosition);
    this.setPlantMetadata(Pos2String(gardener), 'isOuterPerimeter', true);
    this.addFence(this.pt, gardener);

    let iteration = -1;
    let nbSides = gardenerNextPosition.dir === '>' ? 1 : 0;
    let discover = true;
    let currentPositionKey: string;
    while (discover) {
      iteration++;
      // is left in region?
      const gardenerTurningLeft = this.getGardenerPosition(gardener, -1);
      const gardenerForward = this.getGardenerPosition(gardener, 0);
      const gardenerTurningRight = this.getGardenerPosition(gardener, 1);

      if (gardenerTurningLeft !== undefined) {
        gardener = {...gardenerTurningLeft};
        nbSides++;
      } else if (gardenerForward !== undefined) {
        gardener = {...gardenerForward};
      } else if (gardenerTurningRight !== undefined) {
        gardener = {...gardenerTurningRight};
        nbSides++;
      } else {
        const currentDir = gardener.dir;
        gardener.dir = turnRight(currentDir);
        nbSides++;
      }

      this.setPlantMetadata(Pos2String(gardener), 'isOuterPerimeter', true);
      this.addFence(this.pt, gardener);
      currentPositionKey = this.getGardenerPositionKey(gardener);
      discover = nbSides < 4 ? true : currentPositionKey !== startPositionKey;
    }

    this.outerSides = nbSides - 1;
    return this.outerSides;
  }

  findPlant(plant: Plant): Plant | undefined {
    return this.plants.find(p => p.pt === plant.pt && p.x === plant.x && p.y === plant.y);
  }

  getBoundingBox(): BoundingBox {
    if (this.boundingBox !== undefined) return this.boundingBox;

    const sortByX = this.plants.sort(sortPositionByX);
    const xMin = sortByX[0].x;
    const xMax = sortByX[sortByX.length-1].x;
    const sortByY = this.plants.sort(sortPositionByY);
    const yMin = sortByY[0].y;
    const yMax = sortByY[sortByY.length-1].y;

    this.boundingBox = {
      tl: { x: xMin, y: yMin },
      br: { x: xMax, y: yMax }
    };
    return this.boundingBox;
  }

  getFenceStr(): string | undefined {
    return this.fenceStr;
  }

  getGardenerPosition(g: Gardener, dir: Direction | number = 0, inRegion: boolean = true): Gardener | undefined {
    // -1 turn left, 0 forward, +1 turn right, -2/+2 back
    // const d: = 
    if (isNaN(dir) && DIRECTION_OFFSET.get(dir) === undefined) throw new Error(`UNSUPPORTED_DIRECTION`);
    if (!isNaN(dir) && Math.abs(dir) > 2) throw new Error(`UNSUPPORTED_DIRECTION`);

    if (!isNaN(dir)) dir = parseInt(dir);
    const newDirection = isNaN(dir)
      ? getDirectionIndex(dir)
      : (getDirectionIndex(g.dir) + dir) % DIRECTION_OFFSET.size;
    const offset = getDirectionOffset(newDirection);
    const dx = g.x + offset[0];
    const dy = g.y + offset[1];
    const newPosition = { dir: getDirection(newDirection), x: dx, y: dy };

    if (!inRegion) {
      return this.garden.outOfBounds(dx, dy) ? newPosition : undefined;
    }

    return this.findPlant({ pt: this.pt, ...newPosition }) ? newPosition : undefined;
  }

  getGardenerPositionKey(g: Gardener): string {
    return `(x: ${g.x}, y: ${g.y}, dir: ${g.dir})`;
  }

  getPerimeterKey(pt: PlantType, p: Position): string {
    const nbSides = this.getPlantMetadata(Pos2String(p), 'nbSides') ?? 0;
    return `R${pt}${Pos2String(p)}x${nbSides}`;
  }

  getPlantMetadata(plantKey: string, metadataKey: string | undefined) {
    return this.garden.getPlantMetadata(plantKey, metadataKey);
  }

  getPlantsAndNeighborsSet(): string[] {
    const plantsPositions = new Set<string>();
    const offsets = Array.from(DIRECTION_OFFSET.values());
    this.plants.map(p => {
      plantsPositions.add(Pos2String(p));
      offsets.map(o => {
        const dx = p.x + o[0];
        const dy = p.y + o[1];
        const neighbor = this.garden.getPlant(dx, dy);
        if (neighbor !== undefined && neighbor.pt === p.pt) {
          plantsPositions.add(Pos2String(neighbor));
        }
      });
    });

    return Array.from(plantsPositions);
  }

  getRegionName(plants: Plant[]): string {
    const names = plants.reduce((acc, curr) => {
        acc.push(curr);
        return acc;
      }, [])
      .sort(sortPositionByX)
      .map(p => Plant2String(p))
      .join(';');
    return names;
    // return getMd5Hash(names);
  }

  getSides(): number {
    return this.discoverPerimeterSides() + this.discoverInnerFenceSides();
  }

  init() {
    this.initMetadata();
    this.calculateFencePrice();
    this.discoverPerimeterSides();
    this.setOuterFence();
    this.tagInnerSides();
  }

  initMetadata() {
    this.name = this.getRegionName(this.plants);
    this.plants.map(p => {
      const plantMetaKey = Pos2String(p);
      this.setPlantMetadata(plantMetaKey, 'positionKey', plantMetaKey);
      this.setPlantMetadata(plantMetaKey, 'regionName', this.name);
    });
  }

  insideBoundingBox(p: Position): boolean {
    const bb = this.getBoundingBox();
    return p.x >= bb.tl.x && px.x <= bb.br.x && p.y >= bb.tl.y && p.y <= bb.br.y;
  }

  plantHasRegionNeighbor(plant: Plant, regionName: string): boolean {
    const md = this.getPlantMetadata(Pos2String(plant));
    if (md.hasFence === true) {
      const neighbors = this.garden.getPlantAllNeighbors(plant.x, plant.y);
      const stats = neighbors.reduce((acc, curr) => {
        const nMetadata = this.getPlantMetadata(Pos2String(curr));
        const nRegion = nMetadata.regionName === regionName;
        const sameRegion = plant.pt === curr.pt && nMetadata.regionName === md.regionName;
        acc.reg+= nRegion ? 1 : 0;
        acc.nb+= (sameRegion || nRegion) ? 1 : 0;
        return acc;
      }, { nb:0, reg: 0 });
      return stats.nb === 8 && stats.reg > 0;
    }

    return false;
  }

  postInit() {
    this.discoverInnerFenceSides();
    this.calculateBulkFencePrice();
  }

  setBulkFencePrice(bp: number) {
    this.bulkFencePrice = bp;
  }

  setFencePrice(p: number) {
    this.fencePrice = p;
  }

  setOuterFence() {
    if (this.fenceStr === undefined && this.fence.size > 0) {
      const fence = Array.from(this.fence).map(f => {
        return { ...String2Perimeter(f), pt: this.pt };
      });
      this.fenceStr = this.buildFenceStr(fence);
    }
  }

  setPlantMetadata(plantKey: string, metadataKey: string, metadataValue: Unknown) {
    this.garden.setPlantMetadata(plantKey, metadataKey, metadataValue);
  }

  tagInnerSides() {
    if (this.plants.length === 1) return;

    this.plants.map(p => {
      const plantMetaKey = Pos2String(p);
      const md = this.getPlantMetadata(plantMetaKey);
      
      if (md.hasFence === true && md.isOuterPerimeter === undefined) {
        this.setPlantMetadata(plantMetaKey, 'isInnerPerimeter', true);
        this.setPlantMetadata(plantMetaKey, 'isOuterPerimeter', false);
      }
    });
  }

  toString(): string {
    const main = `Region(pt: ${this.pt}, plants: ${this.plants.length}, area: , perimiter:, price: )`;
    const plants = this.plants.length > 0
      ? this.plants.map(p => `  { pt:${p.pt}, x:${p.x}, y:${p.y} }`).join('   \n')
      : '';
    return `${main}\n${plants}`.trim();
  }
}