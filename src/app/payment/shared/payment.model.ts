export type PaymentMethod = 'ONLINE' | 'CASH';

export interface PaymentMember {
  id: number;
  email: string;
  fullName: string;
}

export interface Payment {
  id: number;
  amount: number;
  method: PaymentMethod;
  paidAt: string;
  processedBy?: string | null;
  plan?: string | null;
  planName?: string | null;
  member?: PaymentMember;
}

export interface RevenueSummary {
  onlineRevenue: number;
  cashRevenue: number;
  totalRevenue: number;
  onlineCount: number;
  cashCount: number;
  totalCount: number;
}

export interface CheckoutRequest {
  subscriptionId: number;
  cardNumber?: string;
  cardHolder?: string;
  expiry?: string;
  cvv?: string;
}

export interface CheckoutResponse {
  message: string;
  payment: {
    id: number;
    amount: number;
    method: PaymentMethod;
    paidAt: string;
  };
  subscription: {
    id: number;
    plan: string;
    status: string;
    startDate: string;
    endDate: string;
  };
}

export interface CashPaymentRequest {
  memberId: number;
  subscriptionId: number;
  amount?: number;
}

export interface CashPaymentResponse {
  message: string;
  payment: {
    id: number;
    amount: number;
    method: PaymentMethod;
    processedBy: string;
    paidAt: string;
  };
  subscription: {
    id: number;
    plan: string;
    status: string;
    startDate: string;
    endDate: string;
  };
}

export interface MemberSubscriptionItem {
  id: number;
  planName: string;
  price: number;
  durationDays: number;
  status: 'PENDING' | 'ACTIVE' | 'EXPIRED' | 'CANCELLED';
  startDate?: string;
  endDate?: string;
}
