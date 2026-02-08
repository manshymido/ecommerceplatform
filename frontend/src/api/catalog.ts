import { getData, getPaginated, postData } from './client';
import type { Product, Category, Brand, Review } from './types';

export interface ProductSuggestion {
  id: number;
  name: string;
  slug: string;
}

export const catalogApi = {
  products: (params?: {
    search?: string;
    category_id?: number;
    brand_id?: number;
    page?: number;
    per_page?: number;
  }) => getPaginated<Product[]>('/products', params),

  searchSuggestions: (q: string, limit = 10) =>
    getData<ProductSuggestion[]>('/products/suggestions', { params: { q, limit } }),

  product: (slug: string) => getData<Product>(`/products/${slug}`),
  categories: () => getData<Category[]>('/categories'),
  category: (slug: string) => getData<Category>(`/categories/${slug}`),
  brands: () => getData<Brand[]>('/brands'),
  reviews: (slug: string) => getData<Review[]>(`/products/${slug}/reviews`),
  createReview: (slug: string, data: { rating: number; title?: string; body?: string }) =>
    postData<Review>(`/products/${slug}/reviews`, data),
};
