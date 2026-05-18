import type { PlantType, Position } from './index.ts';

export const Plant2String = (p: Plant): string => {
  return `${p.pt}(${p.x},${p.y})`;
}

const PLANT_REGEX = /^([A-Z])\((\d+),(\d+)\)$/;
export const String2Plant = (str: string): Plant => {
  const matches = str.match(PLANT_REGEX);
  return {
    pt: matches[1],
    x: Number(matches[2]),
    y: Number(matches[3])
  };
}

export interface Plant extends Position {
  pt: PlantType
}