import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { catalogApi } from '../api';
import { EmptyState } from '../components/EmptyState';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { ProductCard } from '../components/ProductCard';
import { useQueryWithUI } from '../hooks/useQueryWithUI';
import { SEO } from '../components/SEO';
import {
  ArrowRight,
  Zap,
  Shield,
  Gift,
  Star,
  ChevronLeft,
  ChevronRight,
  Sparkles,
  Gamepad2,
  CreditCard,
  Clock,
} from 'lucide-react';

const HERO_SLIDES = [
  {
    title: 'Power Up Your Gaming',
    subtitle: 'Get instant access to digital credits, game keys, and premium content for your favorite platforms.',
    cta: 'Shop Now',
    ctaSecondary: 'View Deals',
    to: '/products',
    image: null,
    gradient: 'from-accent/20 via-dark to-dark',
  },
  {
    title: 'Exclusive Offers',
    subtitle: 'Save big on top gaming brands. Limited time deals on Razer Gold, Steam, PlayStation, and more.',
    cta: 'See Offers',
    ctaSecondary: 'Learn More',
    to: '/products',
    image: null,
    gradient: 'from-blue-500/20 via-dark to-dark',
  },
  {
    title: 'Instant Delivery',
    subtitle: 'No waiting. Get your digital codes delivered instantly to your email. Start gaming in seconds.',
    cta: 'Get Started',
    ctaSecondary: 'How It Works',
    to: '/products',
    image: null,
    gradient: 'from-purple-500/20 via-dark to-dark',
  },
];

const FEATURES = [
  {
    icon: Zap,
    title: 'Instant Delivery',
    description: 'Get your digital products delivered to your email within seconds of purchase.',
  },
  {
    icon: Shield,
    title: 'Secure Payment',
    description: '256-bit SSL encryption and multiple payment options for your peace of mind.',
  },
  {
    icon: Gift,
    title: 'Best Prices',
    description: 'Competitive pricing and regular promotions to give you the best value.',
  },
  {
    icon: Clock,
    title: '24/7 Support',
    description: 'Our dedicated support team is always ready to help you with any questions.',
  },
];

const BRANDS = ['Steam', 'PlayStation', 'Xbox', 'Nintendo', 'Epic Games', 'EA Sports'];

export function HomePage() {
  const [slideIndex, setSlideIndex] = useState(0);
  const [isAutoPlaying, setIsAutoPlaying] = useState(true);

  const { data: productsRes, render, isLoading } = useQueryWithUI({
    queryKey: ['products-featured'],
    queryFn: () => catalogApi.products({ per_page: 8, page: 1 }),
    fallbackMessage: 'Failed to load content',
  });

  const { data: categoriesRes } = useQuery({
    queryKey: ['categories'],
    queryFn: () => catalogApi.categories(),
  });

  // Auto-advance slides
  useEffect(() => {
    if (!isAutoPlaying) return;
    const timer = setInterval(() => {
      setSlideIndex((i) => (i + 1) % HERO_SLIDES.length);
    }, 6000);
    return () => clearInterval(timer);
  }, [isAutoPlaying]);

  const goToSlide = (index: number) => {
    setSlideIndex(index);
    setIsAutoPlaying(false);
    setTimeout(() => setIsAutoPlaying(true), 10000);
  };

  const nextSlide = () => goToSlide((slideIndex + 1) % HERO_SLIDES.length);
  const prevSlide = () => goToSlide((slideIndex - 1 + HERO_SLIDES.length) % HERO_SLIDES.length);

  const ui = render();
  if (ui) return ui;

  const products = productsRes?.data ?? [];
  const categories = (categoriesRes ?? []) as { id: number; name: string; slug: string }[];

  return (
    <div className="animate-fade-in">
      <SEO
        title="Home"
        description="Your trusted destination for digital products and gaming credits. Get instant access to Razer Gold, Steam, PlayStation, Xbox and more."
      />

      {/* Hero Section */}
      <section className="relative min-h-[600px] lg:min-h-[700px] overflow-hidden hero-bg">
        {/* Background effects */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-accent/10 rounded-full blur-[100px] animate-float" />
          <div className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-[100px] animate-float animate-delay-300" />
        </div>

        {/* Grid pattern overlay */}
        <div className="hero-grid-overlay absolute inset-0 opacity-[0.03]" />

        <div className="container-app relative z-10">
          <div className="flex flex-col lg:flex-row items-center min-h-[600px] lg:min-h-[700px] py-16 lg:py-0">
            {/* Content */}
            <div className="flex-1 text-center lg:text-left">
              {HERO_SLIDES.map((slide, i) => (
                <div
                  key={i}
                  className={`transition-all duration-700 ${
                    i === slideIndex
                      ? 'opacity-100 translate-y-0'
                      : 'opacity-0 translate-y-8 absolute pointer-events-none'
                  }`}
                >
                  <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-accent/10 border border-accent/30 text-accent text-sm font-medium mb-6">
                    <Sparkles className="w-4 h-4" />
                    Digital Gaming Store
                  </div>
                  <h1 className="heading-xl text-text-primary mb-6">
                    {slide.title.split(' ').map((word, wi) => (
                      <span key={wi}>
                        {wi === slide.title.split(' ').length - 1 ? (
                          <span className="text-gradient glow-text">{word}</span>
                        ) : (
                          word + ' '
                        )}
                      </span>
                    ))}
                  </h1>
                  <p className="text-lg lg:text-xl text-text-secondary max-w-xl mx-auto lg:mx-0 mb-8">
                    {slide.subtitle}
                  </p>
                  <div className="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <Link to={slide.to} className="btn-primary btn-lg group">
                      {slide.cta}
                      <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                    </Link>
                    <Link to={slide.to} className="btn-secondary btn-lg">
                      {slide.ctaSecondary}
                    </Link>
                  </div>
                </div>
              ))}

              {/* Slide navigation */}
              <div className="flex items-center justify-center lg:justify-start gap-4 mt-12">
                <button
                  type="button"
                  aria-label="Previous slide"
                  onClick={prevSlide}
                  className="w-10 h-10 rounded-full border border-surface-border bg-surface-card/50 flex items-center justify-center text-text-secondary hover:text-accent hover:border-accent/50 transition-colors"
                >
                  <ChevronLeft className="w-5 h-5" />
                </button>
                <div className="flex items-center gap-2">
                  {HERO_SLIDES.map((_, i) => (
                    <button
                      key={i}
                      type="button"
                      aria-label={`Go to slide ${i + 1}`}
                      aria-current={i === slideIndex ? 'true' : undefined}
                      onClick={() => goToSlide(i)}
                      className={`h-2 rounded-full transition-all duration-300 ${
                        i === slideIndex
                          ? 'w-8 bg-accent shadow-glow-sm'
                          : 'w-2 bg-dark-300 hover:bg-dark-400'
                      }`}
                    />
                  ))}
                </div>
                <button
                  type="button"
                  aria-label="Next slide"
                  onClick={nextSlide}
                  className="w-10 h-10 rounded-full border border-surface-border bg-surface-card/50 flex items-center justify-center text-text-secondary hover:text-accent hover:border-accent/50 transition-colors"
                >
                  <ChevronRight className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Hero visual */}
            <div className="flex-1 hidden lg:flex items-center justify-center relative">
              <div className="relative w-80 h-80">
                {/* Animated rings */}
                <div className="absolute inset-0 rounded-full border border-accent/20 animate-glow-pulse" />
                <div className="absolute inset-4 rounded-full border border-accent/30 animate-glow-pulse animate-delay-200" />
                <div className="absolute inset-8 rounded-full border border-accent/40 animate-glow-pulse animate-delay-400" />
                {/* Center icon */}
                <div className="absolute inset-12 rounded-full bg-accent/10 backdrop-blur-sm flex items-center justify-center">
                  <Gamepad2 className="w-24 h-24 text-accent animate-float" />
                </div>
                {/* Floating elements */}
                <div className="absolute -top-4 -right-4 w-16 h-16 rounded-xl bg-surface-card border border-surface-border shadow-card flex items-center justify-center animate-float">
                  <CreditCard className="w-8 h-8 text-accent" />
                </div>
                <div className="absolute -bottom-4 -left-4 w-16 h-16 rounded-xl bg-surface-card border border-surface-border shadow-card flex items-center justify-center animate-float animate-delay-300">
                  <Zap className="w-8 h-8 text-yellow-400" />
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Bottom gradient fade */}
        <div className="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-dark to-transparent" />
      </section>

      {/* Features Section */}
      <section className="py-16 lg:py-20 bg-dark-50">
        <div className="container-app">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {FEATURES.map((feature, i) => (
              <div
                key={feature.title}
                className={`card p-6 text-center group hover:border-accent/30 transition-all animate-fade-in-up ${['stagger-0', 'stagger-100', 'stagger-200', 'stagger-300'][i]}`}
              >
                <div className="w-14 h-14 mx-auto rounded-2xl bg-accent/10 flex items-center justify-center mb-4 group-hover:bg-accent/20 group-hover:shadow-glow-sm transition-all">
                  <feature.icon className="w-7 h-7 text-accent" />
                </div>
                <h3 className="font-semibold text-text-primary mb-2">{feature.title}</h3>
                <p className="text-sm text-text-muted">{feature.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Categories Section */}
      {categories.length > 0 && (
        <section className="py-16 lg:py-20">
          <div className="container-app">
            <div className="flex items-end justify-between gap-4 mb-8">
              <div>
                <h2 className="heading-md text-text-primary">Shop by Category</h2>
                <p className="text-text-secondary mt-2">Find exactly what you're looking for</p>
              </div>
              <Link to="/products" className="btn-ghost group hidden sm:inline-flex">
                View All
                <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
              </Link>
            </div>
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
              {categories.slice(0, 6).map((category, i) => (
                <Link
                  key={category.id}
                  to={`/products?category=${category.id}`}
                  className="card-hover p-6 text-center group animate-fade-in-up"
                  style={{ animationDelay: `${i * 50}ms` }}
                >
                  <div className="w-12 h-12 mx-auto rounded-xl bg-accent/10 flex items-center justify-center mb-3 group-hover:bg-accent/20 group-hover:shadow-glow-sm transition-all">
                    <Gamepad2 className="w-6 h-6 text-accent" />
                  </div>
                  <h3 className="font-medium text-text-primary group-hover:text-accent transition-colors">
                    {category.name}
                  </h3>
                </Link>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Featured Products */}
      <section className="py-16 lg:py-20 bg-dark-50">
        <div className="container-app">
          <div className="flex items-end justify-between gap-4 mb-8">
            <div>
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-accent/10 text-accent text-xs font-medium mb-3">
                <Star className="w-3.5 h-3.5 fill-accent" />
                Featured
              </div>
              <h2 className="heading-md text-text-primary">Popular Products</h2>
              <p className="text-text-secondary mt-2">Discover our most popular digital products</p>
            </div>
            <Link to="/products" className="btn-secondary group hidden sm:inline-flex">
              Browse All
              <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
            </Link>
          </div>

          {isLoading ? (
            <div className="flex justify-center py-20">
              <LoadingSpinner />
            </div>
          ) : products.length === 0 ? (
            <div className="card p-12 text-center">
              <EmptyState
                message="No products yet"
                description="Check back soon for amazing deals!"
                icon={<Gamepad2 className="w-8 h-8 text-text-muted" />}
                className="!p-0"
              />
            </div>
          ) : (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
              {products.map((product, i) => (
                <div
                  key={product.id}
                  className={`animate-fade-in-up ${['stagger-0', 'stagger-50', 'stagger-100', 'stagger-150', 'stagger-200', 'stagger-250', 'stagger-300', 'stagger-350'][i % 8]}`}
                >
                  <ProductCard
                    id={product.id}
                    slug={product.slug}
                    name={product.name}
                    description={product.description}
                    main_image_url={product.main_image_url}
                    variantId={product.variants?.[0]?.id}
                    isNew={i < 2}
                  />
                </div>
              ))}
            </div>
          )}

          <div className="text-center mt-10 sm:hidden">
            <Link to="/products" className="btn-primary">
              View All Products
              <ArrowRight className="w-4 h-4" />
            </Link>
          </div>
        </div>
      </section>

      {/* Brands Section */}
      <section className="py-16 lg:py-20">
        <div className="container-app">
          <div className="text-center mb-10">
            <h2 className="heading-md text-text-primary">Trusted Brands</h2>
            <p className="text-text-secondary mt-2">We partner with the biggest names in gaming</p>
          </div>
          <div className="flex flex-wrap items-center justify-center gap-8 lg:gap-12">
            {BRANDS.map((brand) => (
              <div
                key={brand}
                className="px-6 py-4 rounded-xl bg-surface-card border border-surface-border text-text-muted font-bold text-lg hover:text-accent hover:border-accent/30 transition-all cursor-default"
              >
                {brand}
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Newsletter Section */}
      <section className="py-16 lg:py-20 bg-dark-50">
        <div className="container-app">
          <div className="card p-8 lg:p-12 text-center relative overflow-hidden">
            {/* Background glow */}
            <div className="absolute inset-0 bg-gradient-to-r from-accent/5 via-transparent to-accent/5" />
            <div className="absolute top-0 left-1/2 -translate-x-1/2 w-96 h-96 bg-accent/10 rounded-full blur-[100px]" />

            <div className="relative">
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-accent/10 text-accent text-xs font-medium mb-4">
                <Gift className="w-3.5 h-3.5" />
                Get 10% Off
              </div>
              <h2 className="heading-md text-text-primary mb-4">
                Subscribe for Exclusive Deals
              </h2>
              <p className="text-text-secondary max-w-lg mx-auto mb-8">
                Join our newsletter and get 10% off your first order plus exclusive access to new products and special promotions.
              </p>
              <form className="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                <input
                  type="email"
                  placeholder="Enter your email"
                  className="input flex-1"
                />
                <button type="submit" className="btn-primary whitespace-nowrap">
                  Subscribe
                  <ArrowRight className="w-4 h-4" />
                </button>
              </form>
              <p className="text-xs text-text-muted mt-4">
                By subscribing, you agree to our Privacy Policy. Unsubscribe anytime.
              </p>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
