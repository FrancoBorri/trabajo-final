export type Role = "admin" | "professional" | "client"
export type AppointmentStatus = "pending" | "confirmed" | "completed" | "cancelled"
export type PaymentStatus = "pending" | "approved" | "rejected" | "refunded"

export interface User {
  id: string
  name: string
  lastName: string
  email: string
  role: Role
  phone?: string
  specialty?: string
  description?: string
  avatar?: string
  created_at: string
  updated_at: string
}



export interface Professional {
  id: string
  userId: string
  name: string
  lastName: string
  user?: {
    name?: string
    lastName?: string
    last_name?: string
  }
  email: string
  specialty: string
  avatar?: string
  description?: string
  phone: string
  services?: Array<string | number> | Service[]
}

export interface Client {
  id: string
  userId: string
  name: string
  lastName: string
  email: string
  phone: string
  avatar?: string
  created_at: string
  updated_at: string
}

export interface Service {
  id: string
  title: string
  description: string
  price: number
  duration: number // minutes
  professional_id?: string | number
  created_at: string
  updated_at: string
}

export interface Appointment {
  id: number;
  user_id: number;
  professional_id: number;
  service_id: number;
  date: string;
  time: string;
  status: AppointmentStatus;
  notes: string | null;
  professional: Professional;
  service: Service;
  client?: User;
  user?: User;
  payment?: Payment | null;
  created_at: string
  updated_at: string
}

export interface Availability {
  id: string
  professional_id: string
  day_week: number
  time_start: string
  time_end: string
  created_at: string
  updated_at: string
}

export interface ClinicalHistory {
  id: string
  user_id: number
  chief_complaint: string
  medical_history: string
  initial_assessment: string
  clinical_impression: string
  therapeutic_goals: string
  treatment_plans: string
  notes: string
  created_at: string;
  updated_at: string;
}

export interface ClinicalSession {
  id: string
  clinical_history_id: string
  appointment_id: string
  session_number: string
  topic: string
  evolution: string
  observation: string
  created_at: string;
  updated_at: string;
}

export interface Payment {
  id: number
  appointment_id: number
  user_id: number
  amount: number
  status: PaymentStatus
  mercadopago_payment_id: string | null
  mercadopago_preference_id: string | null
  created_at: string
  updated_at: string
}
