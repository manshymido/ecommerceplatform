import { apiClient } from './client';
import type { LoginResponse } from './types';

export const authApi = {
  login: (email: string, password: string) =>
    apiClient.post<LoginResponse>('/login', { email, password }).then((r) => r.data),
  register: (name: string, email: string, password: string, passwordConfirmation: string) =>
    apiClient
      .post<LoginResponse>('/register', {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })
      .then((r) => r.data),
  me: () => apiClient.get<{ user: LoginResponse['user'] }>('/user').then((r) => r.data),
  logout: () => apiClient.post<{ message?: string }>('/logout').then((r) => r.data),
};
