import { EquationBranch } from './index.ts';

export interface EquationPath {
  add: EquationBranch | undefined;
  multiply: EquationBranch | undefined;
  concat: EquationBranch | undefined;
}