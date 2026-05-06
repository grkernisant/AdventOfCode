export const MUL_REGEX = /mul\((\d{1,3}),(\d{1,3})\)/g;
export const DONT_DELIMITER = `don't()`;
export const DO_DELIMITER = `do()`;
export interface Mul {
	operand1: number,
	operand2: number,
}