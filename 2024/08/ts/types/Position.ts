export interface Position {
  x: number,
  y: number
}

export const Pos2Num = (p: Position):number[] => {
  const n = [];
  n.push(p.x);
  n.push(p.y);
  return n;
}

export const Num2Pos = (n: number[]): Position => {
  if (n.length !== 2) throw new Error(`NUM2POS_ARGS_LENGTH_SHOULD_BE_2: ${n.length}`);

  return { x: n[0], y: n[1] };
}

export const Num2String = (n: number[]): string => {
  if (n.length !== 2) throw new Error(`NUM2STRING_ARGS_LENGTH_SHOULD_BE_2: ${n.length}`);

  return `(${n[0]},${n[1]})`;
}

const STRING_POSITION_REGEX = /\((\d+),(\d+)\)/;
export const Str2Pos = (str: string): Position => {
  const matches = str.match(STRING_POSITION_REGEX);
  if (matches === null) throw new Error(`UNSUPPORTED_STRING: ${str}`);

  return { x: Number(matches[1]), y: Number(matches[2]) } as Position;
}
