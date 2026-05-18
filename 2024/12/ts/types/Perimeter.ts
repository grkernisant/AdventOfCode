import type { Position } from './index.ts';

export type PerimeterKey = string;

export interface Perimeter extends Position {
  key: PerimeterKey,
  nbSides: number,
}

const PERIMITER_REGEX = /^R([A-Z])\((\d+),(\d+)\)x(\d)$/;

export const String2Perimeter = (s: PerimeterKey): Perimeter | undefined => {
  const matches = s.match(PERIMITER_REGEX);
  if (matches === undefined) return undefined;

  return {
    key: matches[0],
    x: Number(matches[2]),
    y: Number(matches[3]),
    nbSides: Number(matches[4])
  } as Perimeter;
}

export const Perimeter2String = (p: Perimeter): string => { return p.key; }