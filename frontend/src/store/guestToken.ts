const STORAGE_KEY = 'guest_cart_token';

function generateToken(): string {
  return crypto.randomUUID();
}

export function getGuestToken(): string {
  try {
    let token = localStorage.getItem(STORAGE_KEY);
    if (!token) {
      token = generateToken();
      localStorage.setItem(STORAGE_KEY, token);
    }
    return token;
  } catch {
    return generateToken();
  }
}

