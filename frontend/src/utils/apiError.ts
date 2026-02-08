/** API error shape (axios + Laravel validation). */
interface ApiErrorData {
  message?: string;
  errors?: Record<string, string[]>;
}

/** Extract user-facing message from API/axios errors. */
export function getApiErrorMessage(
  error: unknown,
  fallback: string = 'Something went wrong'
): string {
  const err = error as { response?: { data?: ApiErrorData }; message?: string } | null;
  const msg = err?.response?.data?.message ?? err?.message ?? fallback;
  const validation = getValidationMessage(err);
  return validation ? `${msg}. ${validation}` : msg;
}

/** Build a single line from Laravel validation errors (e.g. "name: Required. slug: Already taken."). */
export function getValidationMessage(error: unknown): string {
  const data = (error as { response?: { data?: ApiErrorData } } | null)?.response?.data;
  const errors = data?.errors;
  if (!errors || typeof errors !== 'object') return '';
  const parts: string[] = [];
  for (const [field, messages] of Object.entries(errors)) {
    if (Array.isArray(messages) && messages.length > 0) {
      parts.push(`${field}: ${messages[0]}`);
    }
  }
  return parts.join('. ');
}
