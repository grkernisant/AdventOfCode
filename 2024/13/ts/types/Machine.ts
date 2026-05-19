import type {
  Button,
  Prize
} from './index.ts';

const costA = 3;
const costB = 1;

interface MachineParams {
  butA?: Button;
  butB?: Button;
  p?: Prize;
  maxHits?: number;
};

export class Machine {
  buttonA?: Button;
  buttonB?: Button;
  cost: number;
  hitA: number;
  hitB: number;
  maxHits: number;
  prize?: Prize;
  solvable: boolean;

  constructor(params: MachineParams = {}) {
    this.buttonA = params.butA;
    this.buttonB = params.butB;
    this.maxHits = (!isNaN(params.maxHits) && Number(params.maxHits) > 0)
      ? Number(params.maxHits)
      : 0;
    this.prize = params.p;
    this.solvable = false;
  }

  isDefined(): boolean {
    return this.buttonA !== undefined && this.buttonB !== undefined && this.prize !== undefined;
  }

  reset() {
    this.cost = undefined;
    this.hitA = undefined;
    this.hitB = undefined;
    this.solvable = false;
  }

  setButtonA(ba: Button) { this.buttonA = ba; }
  setButtonB(bb: Button) { this.buttonB = bb; }
  setMaxHits(mh: number) { this.maxHits = mh; }
  setPrize(p: Prize) {
    this.prize = p;
  }

  solve() {
    this.solveHitB();
    this.solveHitA();

    if (this.hitB !== parseInt(this.hitB) || this.hitB < 0 || !this.validateMaxHits(this.hitB)) return;
    if (this.hitA !== parseInt(this.hitA) || this.hitA < 0 || !this.validateMaxHits(this.hitA)) return;

    this.solvable = true;
    this.cost = costA * this.hitA + costB * this.hitB;
  }

  solveHitA() {
    this.hitA = (this.prize.x - (this.hitB * this.buttonB.x)) / this.buttonA.x;
  }

  solveHitB() {
    this.hitB = ( (this.prize.y * this.buttonA.x) - (this.prize.x * this.buttonA.y) ) /
      ( this.buttonB.y * this.buttonA.x - this.buttonB.x * this.buttonA.y );
  }

  validateMaxHits(nbHits: number): boolean {
    if (this.maxHits <= 0) return true;

    return nbHits < this.maxHits;
  }
}