/**
 * k6 load test: GET /api/orders (authenticated).
 * Run: k6 run -e BASE_URL=http://localhost:8000 -e AUTH_TOKEN=your-token load-tests/orders.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const AUTH_TOKEN = __ENV.AUTH_TOKEN || '';

export const options = {
  stages: [
    { duration: '30s', target: 5 },
    { duration: '1m', target: 15 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<400'],
    http_req_failed: ['rate<0.01'],
  },
};

export default function () {
  if (!AUTH_TOKEN) { sleep(1); return; }

  const res = http.get(BASE_URL + '/api/orders', {
    headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + AUTH_TOKEN },
  });
  check(res, { 'orders status 200': (r) => r.status === 200 });
  sleep(0.5);
}
