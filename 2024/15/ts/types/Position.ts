export interface Position {
  x: number,
  y: number
}

const POSITION_STRING_REGEX = /^\((\d+),(\d+)\)$/;

export const Num2String = (n: number[]): string => `(${n[0]},${n[1]})`;
export const Pos2String = (p: Position): string => `(${p.x},${p.y})`;
export const String2Pos = (s: string): Position => {
  const matches = s.match(POSITION_STRING_REGEX);
  if (matches === null) throw new Error(`INVALID_STRING_POSITION: ${s}`);
  return { x: Number(matches[1]), y: Number(matches[2]) };
}
