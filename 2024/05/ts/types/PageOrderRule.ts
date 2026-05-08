import type { PageNumber } from './PageNumber.ts';

export const PAGE_ORDER_RULE_REGEX = /^(\d+)\|(\d+)$/;

export interface PageOrderRule {
	pageX: PageNumber,
	pageY: PageNumber
}