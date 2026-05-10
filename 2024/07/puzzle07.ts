import { Equation } from './ts/types/index.ts';
import { readFileIfExists } from './ts/utils/File.ts';
import type { Operator } from './ts/types/index.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test.txt';
const equationsContent = await readFileIfExists(input);
const opAddMulti: Operator[] = ['+', '*'];
const equations = (equationsContent ?? '')
  .trim().split('\n').filter(Boolean)
  .map((it) => Equation.factory(it).solve(opAddMulti));

// part 1
console.log(`The calibration is: ${Equation.calibration(equations)}`);
console.log(`${equations.filter(it => it.solvable).length} out of ${equations.length} equations were solved`);

// part 2
const opAll: Operator[] = ['+', '*', '||'];
const unresolvedEquations = equations
  .filter((it) => !it.solvable)
  .map(it => it.solve(opAll));
console.log(`The calibration is: ${Equation.calibration(equations)}`);
