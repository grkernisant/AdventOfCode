export type Direction = '>' | 'v' | '<' | '^';
export const DIRECTION_OFFSET = new Map<Direction, number[]>([
  ['>', [1, 0]],
  ['v', [0, 1]],
  ['<', [-1, 0]],
  ['^', [0, -1]],
]);
export const DIRECTION_ALL_OFFSET = new Map<Direction, number[]>([
  ['>', [1, 0]],
  ['v>', [1, 1]],
  ['v', [0, 1]],
  ['<v', [-1, 1]],
  ['<', [-1, 0]],
  ['<^', [-1, -1]],
  ['^', [0, -1]],
  ['^>', [1, -1]],
]);

const directionKeys = Array.from(DIRECTION_OFFSET.keys());
export const getDirectionIndex = (d: Direction): number => {
  if (DIRECTION_OFFSET.get(d) === undefined) throw new Error(`UNSUPPORTED_DIRECTION`);

  return directionKeys.findIndex(k => k === d);
}

export const getDirection = (i: number): Direction => {
  const index = (DIRECTION_OFFSET.size + i) % DIRECTION_OFFSET.size;
  return directionKeys[index];
}

export const getDirectionOffset = (i: number): number[] => {
  return DIRECTION_OFFSET.get(getDirection(i));
}

export const turnLeft = (d: Direction): Direction => {
  const currentIndex = getDirectionIndex(d);
  return getDirection(currentIndex - 1);
}

export const turnRight = (d: Direction): Direction => {
  const currentIndex = getDirectionIndex(d);
  return getDirection(currentIndex + 1);
}