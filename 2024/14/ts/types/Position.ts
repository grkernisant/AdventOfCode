export interface Position {
  x: number,
  y: number,
}

export const sortPositionY = (p1: Position, p2: Position) => {
  return p1.x - p2.x;
}
