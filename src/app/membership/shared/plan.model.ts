export type PlanAccessScope = 'OPEN_GYM' | 'CLASSES' | 'BOTH';

export interface Plan {
  id: number;
  name: string;
  price: number;
  durationDays: number;
  accessScope: PlanAccessScope;
  maxMembers: number | null;
  isActive: boolean;
  currentMembersCount?: number;
}

export interface Subscription {
  id: number;
  planId: number;
  planName: string;
  memberId: number;
  memberEmail: string;
  memberFullName: string;
  status: 'PENDING' | 'ACTIVE' | 'EXPIRED' | 'CANCELLED';
  startDate: string;
  endDate: string;
  price: number;
  durationDays: number;
  accessScope: PlanAccessScope;
}

export interface CreatePlanRequest {
  name: string;
  price: number;
  durationDays: number;
  accessScope: PlanAccessScope;
  maxMembers?: number;
}

export interface UpdatePlanRequest {
  name?: string;
  price?: number;
  durationDays?: number;
  accessScope?: PlanAccessScope;
  maxMembers?: number;
  isActive?: boolean;
}