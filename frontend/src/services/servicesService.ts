import api from "@/api/axios";
import {endpoints} from "@/api/endpoints";
import type { Service } from "@/types";

const getServiceItems = (value: unknown): Record<string, unknown>[] => {
  if (Array.isArray(value)) {
    return value.filter(
      (item): item is Record<string, unknown> =>
        Boolean(item) && typeof item === "object"
    )
  }

  if (!value || typeof value !== "object") {
    return []
  }

  const object = value as Record<string, unknown>

  if ("id" in object) {
    return [object]
  }

  for (const key of ["data", "services", "service"]) {
    const items = getServiceItems(object[key])

    if (items.length) {
      return items
    }
  }

  return []
}

const normalizeService = (value: Record<string, unknown>): Service => {
  const professional =
    value.professional && typeof value.professional === "object"
      ? value.professional as Record<string, unknown>
      : undefined

  return {
    ...value,
    id: String(value.id),
    title: String(value.title ?? value.name ?? ""),
    description: String(value.description ?? ""),
    price: Number(value.price ?? 0),
    duration: Number(value.duration ?? value.duration_minutes ?? 0),
    professional_id: [
      value.professional_id,
      value.professionalId,
      professional?.id
    ].find(
      (professionalId): professionalId is string | number =>
        typeof professionalId === "string" || typeof professionalId === "number"
    ),
    created_at: String(value.created_at ?? ""),
    updated_at: String(value.updated_at ?? "")
  }
}

class ServiceService {

  async getAll(): Promise<Service[]> {

    const response = await api.get(
      endpoints.services.all
    );

    return getServiceItems(response.data).map(normalizeService);
  }


  async getById(id: number | string): Promise<Service> {

    const response = await api.get(
      endpoints.services.detail(id)
    );

    const [service] = getServiceItems(response.data)
    return normalizeService(service ?? response.data);
  }


  async create(
    data: Omit<Service, "id" | "created_at" | "updated_at">
  ): Promise<Service> {

    const response = await api.post(
      endpoints.services.all,
      data
    );

    const [service] = getServiceItems(response.data)
    return normalizeService({
      ...(service ?? {}),
      ...data,
      id: service?.id ?? `local-${Date.now()}`
    });
  }


  async update(
    id: number | string,
    data: Partial<Service>
  ): Promise<Service> {

    const response = await api.put(
      endpoints.services.detail(id),
      data
    );

    return response.data;
  }


  async delete(id: number | string): Promise<void> {

    await api.delete(
      endpoints.services.detail(id)
    );

  }

}


export default new ServiceService();
