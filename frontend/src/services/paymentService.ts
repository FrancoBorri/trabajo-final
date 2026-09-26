import api from "@/api/axios"
import {endpoints} from "@/api/endpoints";

export interface CreatePaymentResponse {
  message: string
  payment_id: number
  preference_id: string
  init_point: string
}

const paymentService = {
  async createPayment(appointmentId: number): Promise<CreatePaymentResponse> {
    const response = await api.post<CreatePaymentResponse>(
      endpoints.payments.create(appointmentId)
    )

    return response.data
  },
}

export default paymentService
