export interface Position {
  x: number,
  y: number
}

const POSITION_REGEX = /^\((\d+),(\d+)\)$/;

export const Num2Pos = (n: number[]): Position => {
  return { x: n[0], y: n[1] };
}

export const Num2String = (n: number[]): string => {
  return `(${n[0]},${n[1]})`;
}

export const Pos2String = (p: Position): string => {
  return `(${p.x},${p.y})`;
}

export const Pos2Num = (p: Position): number[] => {
  return [p.x, p.y];
}

export const String2Num = (s: string): number[] | undefined => {
  const pos = String2Pos(s);
  if (pos === undefined) return undefined;

  return [pos.x, pos.y];
}

export const String2Pos = (s: string): Position | undefined => {
  const matches = s.match(POSITION_REGEX);
  if (matches !== undefined) {
    return { x: Number(matches[1]), y: Number(matches[2]) };
  }

  return undefined;
}

export const sortPositionByX = (a: Position, b: Position): number => {
  const delta = a.x - b.x;
  if (delta !== 0) return delta;

  return a.y - b.y;
}

export const sortPositionByY = (a: Position, b: Position): number => {
  const delta = a.y - b.y;
  if (delta !== 0) return delta;

  return a.x - b.x;
}