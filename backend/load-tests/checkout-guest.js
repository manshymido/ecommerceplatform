/**
 * k6 load test: Guest checkout flow (get cart, add item, checkout).
 * Run: k6 run -e BASE_URL=http://localhost:8000 load-tests/checkout-guest.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export const options = {
  stages: [
    { duration: '20s', target: 5 },
    { duration: '40s', target: 10 },
    { duration: '20s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<800'],
    http_req_failed: ['rate<0.05'],
  },
};

function getGuestToken() {
  const res = http.get(BASE_URL + '/api/cart');
  if (res.status !== 200) return null;
  const body = JSON.parse(res.body);
  return body.data && body.data.guest_token ? body.data.guest_token : null;
}

function getFirstVariantId() {
  const res = http.get(BASE_URL + '/api/products?per_page=1');
  if (res.status !== 200) return null;
  const body = JSON.parse(res.body);
  const product = body.data && body.data[0];
  if (!product || !product.variants || !product.variants.length) return null;
  return product.variants[0].id;
}

export default function () {
  const guestToken = getGuestToken();
  if (!guestToken) { sleep(1); return; }
  const variantId = getFirstVariantId();
  if (!variantId) { sleep(1); return; }
  const addRes = http.post(
    BASE_URL + '/api/cart/items',
    JSON.stringify({ product_variant_id: variantId, quantity: 1 }),
    { headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Guest-Token': guestToken } }
  );
  check(addRes, { 'add to cart 200': (r) => r.status === 200 });
  sleep(0.2);
  const checkoutPayload = JSON.stringify({
    email: 'guest-loadtest@example.com',
    shipping_address: { first_name: 'Guest', last_name: 'User', line1: '123 Test St', city: 'Test City', postal_code: '12345', country_code: 'US' },
    billing_address: { first_name: 'Guest', last_name: 'User', line1: '123 Test St', city: 'Test City', postal_code: '12345', country_code: 'US' },
  });
  const checkoutRes = http.post(
    BASE_URL + '/api/checkout',
    checkoutPayload,
    { headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Guest-Token': guestToken } }
  );
  check(checkoutRes, { 'checkout 201 or 422': (r) => r.status === 201 || r.status === 422 });
  sleep(1);
}
