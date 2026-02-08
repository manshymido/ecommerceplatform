import { useParams, Link, useNavigate } from 'react-router-dom';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQuery } from '@tanstack/react-query';
import { catalogApi } from '../api';
import { ErrorMessage } from '../components/ErrorMessage';
import { Input, Label, Button } from '../components/ui';
import { getApiErrorMessage } from '../utils/apiError';
import { useToastStore } from '../store/toastStore';
import {
  Star,
  ChevronLeft,
  Send,
  MessageSquare,
} from 'lucide-react';

const reviewSchema = z.object({
  rating: z.number().min(1).max(5),
  title: z.string().max(255).optional(),
  body: z.string().optional(),
});

type ReviewForm = z.infer<typeof reviewSchema>;

export function ReviewFormPage() {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();
  const toast = useToastStore((s) => s.add);

  const { data: productRes } = useQuery({
    queryKey: ['product', slug],
    queryFn: () => catalogApi.product(slug!),
    enabled: !!slug,
  });

  const submitReview = useMutation({
    mutationFn: (data: ReviewForm) =>
      catalogApi.createReview(slug!, {
        rating: data.rating,
        title: data.title || undefined,
        body: data.body || undefined,
      }),
    onSuccess: () => {
      toast('Review submitted successfully', 'success');
      navigate(`/products/${slug}`);
    },
  });

  const {
    register,
    handleSubmit,
    control,
    setValue,
    formState: { errors },
  } = useForm<ReviewForm>({
    resolver: zodResolver(reviewSchema),
    defaultValues: { rating: 5 },
  });

  const currentRating = useWatch({ control, name: 'rating', defaultValue: 5 }) ?? 5;
  const product = productRes;

  if (!slug) return null;

  return (
    <div className="min-h-screen">
      {/* Header */}
      <div className="bg-dark-50 border-b border-surface-border">
        <div className="container-app py-8 lg:py-12">
          <Link
            to={`/products/${slug}`}
            className="inline-flex items-center gap-2 text-text-secondary hover:text-accent transition-colors mb-6"
          >
            <ChevronLeft className="w-4 h-4" />
            Back to Product
          </Link>

          <div className="flex items-center gap-4">
            <div className="w-14 h-14 rounded-xl bg-accent/10 flex items-center justify-center">
              <MessageSquare className="w-7 h-7 text-accent" />
            </div>
            <div>
              <h1 className="heading-md text-text-primary">Write a Review</h1>
              {product && (
                <p className="text-text-secondary mt-1">
                  for <span className="text-accent font-medium">{product.name}</span>
                </p>
              )}
            </div>
          </div>
        </div>
      </div>

      <div className="container-app py-8 lg:py-12">
        <div className="max-w-xl mx-auto">
          <form
            onSubmit={handleSubmit((data) => submitReview.mutate(data))}
            className="card p-6 lg:p-8 space-y-6"
          >
            {/* Rating */}
            <div>
              <Label>Your Rating</Label>
              <div className="flex items-center gap-2 mt-2">
                {[1, 2, 3, 4, 5].map((n) => (
                  <button
                    key={n}
                    type="button"
                    onClick={() => setValue('rating', n)}
                    className="p-1 transition-transform hover:scale-110"
                    aria-label={`Rate ${n} out of 5`}
                  >
                    <Star
                      className={`w-8 h-8 ${
                        n <= currentRating
                          ? 'text-yellow-400 fill-yellow-400'
                          : 'text-dark-300 hover:text-dark-400'
                      }`}
                    />
                  </button>
                ))}
                <span className="ml-2 text-text-secondary text-sm">
                  {currentRating}/5
                </span>
              </div>
              {errors.rating && (
                <p className="text-status-danger text-sm mt-1">{errors.rating.message}</p>
              )}
              <input type="hidden" {...register('rating', { valueAsNumber: true })} />
            </div>

            {/* Title */}
            <div>
              <Label htmlFor="review-title">Review Title (optional)</Label>
              <Input
                id="review-title"
                type="text"
                {...register('title')}
                placeholder="Sum up your experience in a few words"
              />
            </div>

            {/* Body */}
            <div>
              <Label htmlFor="review-body">Your Review (optional)</Label>
              <textarea
                id="review-body"
                {...register('body')}
                rows={5}
                className="input min-h-[120px] resize-y"
                placeholder="Tell others what you think about this product..."
              />
            </div>

            {submitReview.isError && (
              <ErrorMessage
                message={getApiErrorMessage(submitReview.error, 'Failed to submit review')}
              />
            )}

            <div className="flex flex-col sm:flex-row gap-3 pt-2">
              <Link to={`/products/${slug}`} className="btn-secondary flex-1">
                Cancel
              </Link>
              <Button
                type="submit"
                variant="primary"
                disabled={submitReview.isPending}
                className="flex-1"
              >
                {submitReview.isPending ? (
                  'Submitting...'
                ) : (
                  <>
                    <Send className="w-4 h-4" />
                    Submit Review
                  </>
                )}
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
