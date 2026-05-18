import type { PlantType } from './index.ts';

export interface PlantMetadata {
  hasFence: boolean,
  nbSides: number,
  isInnerPerimeter: boolean,
  isOuterPerimeter: boolean,
  positionKey: string,
  regionName: string,
}