import {
  REGULAR_BOX,
  WIDE_BOX,
  Num2String,
  Pos2String,
  Parser,
  Parser2x,
  Warehouse
} from './ts/types/index.ts';
import { readFileIfExists } from './ts/utils/File.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test';
const warehouseContent = await readFileIfExists(input);
const parser = new Parser(warehouseContent);
const warehouse = new Warehouse({
  map: parser.getMap(),
  moves: parser.getRobotMoves(),
  robot: parser.getRobotPosition(),
  boxSpecs: REGULAR_BOX,
});

// Part 1
warehouse.run();
console.log(`After robot moves GPS: ${warehouse.getBoxesGPS()}`);

// Part 2
const parser2x = new Parser(warehouseContent);
const p2x = Parser2x.factorize(parser2x);
const widerWarehouse = new Warehouse({
  map: p2x.getMap(),
  moves: p2x.getRobotMoves(),
  robot: p2x.getRobotPosition(),
  boxSpecs: WIDE_BOX,
});
widerWarehouse.run();
console.log(`After robot moves GPS: ${widerWarehouse.getBoxesGPS()}`);
console.log(widerWarehouse.print());