export interface MazeMapInterface {
  cols : number,
  end: Position,
  maze: MazeTile[][],
  rows : number,
  start: Position,
}