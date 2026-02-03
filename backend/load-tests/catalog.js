/**
 * k6 load test: Catalog browsing (public endpoints).
 * Run: k6 run -e BASE_URL=http://localhost:8000 load-tests/catalog.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export const options = {
  stages: [
    { duration: '30s', target: 10 },
    { duration: '1m', target: 20 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],
    http_req_failed: ['rate<0.01'],
  },
};

export default function () {
  const res = http.get(BASE_URL + '/api/products?per_page=15');
  check(res, { 'products status 200': (r) => r.status === 200 });
  sleep(0.5);
  const catRes = http.get(BASE_URL + '/api/categories');
  check(catRes, { 'categories status 200': (r) => r.status === 200 });
  sleep(0.3);
  const brandRes = http.get(BASE_URL + '/api/brands');
  check(brandRes, { 'brands status 200': (r) => r.status === 200 });
  sleep(0.5);
}
