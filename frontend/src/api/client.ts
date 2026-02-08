import axios, { type AxiosRequestConfig, type InternalAxiosRequestConfig } from 'axios';
import { useAuthStore } from '../store/authStore';
import { getGuestToken } from '../store/guestToken';
import type { ApiData, ApiPaginated } from './types';

const baseURL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api';

export const apiClient = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

apiClient.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  const url = config.url ?? '';
  const isCartOrCheckout =
    url.includes('/cart') || url.includes('/checkout');
  if (isCartOrCheckout) {
    const guestToken = getGuestToken();
    if (guestToken) {
      config.headers['X-Guest-Token'] = guestToken;
    }
  }
  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout();
      const path = window.location.pathname;
      if (!path.startsWith('/login') && !path.startsWith('/register')) {
        window.location.href = '/login?redirect=' + encodeURIComponent(path);
      }
    }
    return Promise.reject(error);
  }
);

/** Unwrap axios response body, then { data: T } -> T. Avoids repeating .then(r => r.data) and .data. */
export function getData<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
  return apiClient.get<ApiData<T>>(url, config).then((res) => res.data.data);
}
export function getPaginated<T>(url: string, params?: AxiosRequestConfig['params']): Promise<ApiPaginated<T>> {
  return apiClient.get<ApiPaginated<T>>(url, { params }).then((res) => res.data);
}
export function postData<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
  return apiClient.post<ApiData<T>>(url, data, config).then((res) => res.data.data);
}
export function putData<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
  return apiClient.put<ApiData<T>>(url, data, config).then((res) => res.data.data);
}
export function patchData<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
  return apiClient.patch<ApiData<T>>(url, data, config).then((res) => res.data.data);
}
export function deleteNoContent(url: string, config?: AxiosRequestConfig): Promise<void> {
  return apiClient.delete(url, config).then(() => undefined);
}
/** For DELETEs that return { data: T } (e.g. cart/wishlist). */
export function deleteData<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
  return apiClient.delete<ApiData<T>>(url, config).then((res) => res.data.data);
}

type CrudPaginated<T> = {
  list: (params?: { page?: number; per_page?: number }) => Promise<ApiPaginated<T[]>>;
  get: (id: number) => Promise<T>;
  create: (data: Partial<T> | Record<string, unknown>) => Promise<T>;
  update: (id: number, data: Partial<T> | Record<string, unknown>) => Promise<T>;
  delete: (id: number) => Promise<void>;
};
type CrudNonPaginated<T> = {
  list: () => Promise<T[]>;
  get: (id: number) => Promise<T>;
  create: (data: Partial<T> | Record<string, unknown>) => Promise<T>;
  update: (id: number, data: Partial<T> | Record<string, unknown>) => Promise<T>;
  delete: (id: number) => Promise<void>;
};

/** CRUD helpers for admin resources; reduces repeated list/get/create/update/delete. */
export function createCrud<T>(basePath: string, opts?: { listPaginated?: false }): CrudNonPaginated<T>;
export function createCrud<T>(basePath: string, opts: { listPaginated: true }): CrudPaginated<T>;
export function createCrud<T>(
  basePath: string,
  opts?: { listPaginated?: boolean }
): CrudNonPaginated<T> | CrudPaginated<T> {
  const get = (id: number) => getData<T>(`${basePath}/${id}`);
  const create = (data: Partial<T> | Record<string, unknown>) => postData<T>(basePath, data);
  const update = (id: number, data: Partial<T> | Record<string, unknown>) =>
    putData<T>(`${basePath}/${id}`, data);
  const del = (id: number) => deleteNoContent(`${basePath}/${id}`);
  if (opts?.listPaginated === true) {
    return {
      list: (params?: { page?: number; per_page?: number }) => getPaginated<T[]>(basePath, params),
      get,
      create,
      update,
      delete: del,
    } as CrudPaginated<T>;
  }
  return {
    list: () => getData<T[]>(basePath),
    get,
    create,
    update,
    delete: del,
  } as CrudNonPaginated<T>;
}
