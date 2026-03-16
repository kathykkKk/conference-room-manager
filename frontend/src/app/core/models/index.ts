export interface BusinessPark {
  id: string;
  name: string;
  address: string;
  city: string;
}

export interface Equipment {
  id: string;
  name: string;
  category: string;
}

export interface HallPhoto {
  id: string;
  hall_id: string;
  url: string;
}

export interface HallEquipment {
  id: string;
  equipment_id?: string;
  name: string;
  category: string;
  quantity?: number;
  pivot?: {
    quantity: number;
  };
}

export interface PricingRule {
  id: string;
  hall_id: string;
  price_per_hour: string;
  priority: number;
  apply_from_date: string;
  apply_until_date: string | null;
  weekdays: string;
  time_from: string;
  time_to: string;
}

export interface Hall {
  id: string;
  business_park_id: string;
  name: string;
  capacity: number;
  area_sq_m: number;
  description: string;
  status: 'available' | 'unavailable';
  created_at: string;
  updated_at: string;
  business_park?: BusinessPark;
  photos?: HallPhoto[];
  equipment?: HallEquipment[];
  pricing_rules?: PricingRule[];
  price_per_hour?: number;
  current_price?: number;
}

export interface Client {
  id: string;
  client_type: 'individual' | 'company';
  full_name: string;
  email: string;
  phone: string;
  company_name?: string;
}

export interface Booking {
  id: string;
  hall_id: string;
  client_id: string;
  start_datetime: string;
  end_datetime: string;
  total_price: number;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
  created_at: string;
  updated_at: string;
  hall?: Hall;
  client?: Client;
}

export interface Payment {
  id: string;
  booking_id: string;
  amount: number;
  payment_status: 'pending' | 'paid' | 'failed';
}

export interface HallFiltersData {
  business_parks: BusinessPark[];
  equipment?: Equipment[];
  equipment_categories?: string[];
  cities?: string[];
}
