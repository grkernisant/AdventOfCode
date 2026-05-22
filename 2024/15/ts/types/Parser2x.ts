import { Parser } from './index.ts';

export class Parser2x extends Parser {
  constructor(input?: string) {
    super(input);
  }

  static factorize(p: Parser): Parser2x {
    const p2x = new Parser2x();

    p2x.map = p.map.reduce((accRow, row) => {
      const newRow = row.reduce((accCol, col) => {
        switch(col) {
          case '#':
            accCol.push('#'); accCol.push('#');
            break;

          case 'O':
            accCol.push('['); accCol.push(']');
            break;

          case '.':
            accCol.push('.'); accCol.push('.');
            break;

          case '@':
            accCol.push('@'); accCol.push('.');
            break;

          default: break;
        }

        return accCol;
      }, []);
      accRow.push(newRow);

      return accRow;
    }, []);

    p2x.robotPosition = {
      x: 2 * p.robotPosition.x,
      y: p.robotPosition.y
    };

    p2x.robotMoves = [...p.robotMoves];
    return p2x;
  }
}