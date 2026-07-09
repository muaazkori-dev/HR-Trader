'use client';

import React, { useState } from 'react';
import { Star, Send, Award, Calendar } from 'lucide-react';
import { supabase } from '@/lib/supabase';
import { useRouter } from 'next/navigation';

interface Review {
  id: number;
  customer_name: string;
  rating: number;
  comment: string;
  created_at: string;
}

interface ProductReviewsProps {
  productId: number;
  initialReviews: Review[];
}

export const ProductReviews: React.FC<ProductReviewsProps> = ({ productId, initialReviews }) => {
  const router = useRouter();
  const [reviews, setReviews] = useState<Review[]>(initialReviews);
  const [name, setName] = useState('');
  const [rating, setRating] = useState(0);
  const [hoverRating, setHoverRating] = useState(0);
  const [comment, setComment] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState('');
  const [isError, setIsError] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim() || rating < 1 || rating > 5 || !comment.trim()) {
      setIsError(true);
      setMessage('Please enter your name, rating, and review comments.');
      return;
    }

    setSubmitting(true);
    setMessage('');
    setIsError(false);

    try {
      const { data, error } = await supabase
        .from('reviews')
        .insert([
          {
            product_id: productId,
            customer_name: name.trim(),
            rating,
            comment: comment.trim(),
          },
        ])
        .select()
        .single();

      if (error) {
        setIsError(true);
        setMessage('Failed to submit review: ' + error.message);
      } else if (data) {
        setName('');
        setRating(0);
        setComment('');
        setIsError(false);
        setMessage('Shukriya! Aap ka review submit ho gaya hai.');
        // Update local list
        setReviews([data as Review, ...reviews]);
        router.refresh();
      }
    } catch (err: any) {
      setIsError(true);
      setMessage('An error occurred during submission.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="space-y-6 text-left">
      <div className="flex items-center justify-between border-b border-slate-100 pb-3">
        <h3 className="font-extrabold text-slate-800 text-xs uppercase tracking-wider">
          Reviews & Feedback ({reviews.length})
        </h3>
      </div>

      {/* Review Success/Error Messages */}
      {message && (
        <div
          className={`p-4 rounded-xl text-xs font-semibold leading-relaxed border ${
            isError
              ? 'bg-rose-50 text-rose-700 border-rose-200'
              : 'bg-emerald-50 text-emerald-700 border-emerald-200'
          }`}
        >
          {message}
        </div>
      )}

      {/* Write a Review Block */}
      <div className="bg-slate-50 border border-slate-200/60 rounded-2xl p-5 space-y-4">
        <h4 className="text-[11px] font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
          <Award className="w-4 h-4 text-emerald-600" />
          Share Your Experience
        </h4>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {/* Name */}
            <div className="space-y-1">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                Your Name
              </label>
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
                placeholder="Enter your name"
                className="w-full px-4 py-2 bg-white border border-slate-350 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800"
              />
            </div>

            {/* Rating Stars */}
            <div className="space-y-1">
              <span className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                Your Rating
              </span>
              <div className="flex items-center gap-1.5 h-10">
                {[1, 2, 3, 4, 5].map((starValue) => {
                  const filled = starValue <= (hoverRating || rating);
                  return (
                    <button
                      key={starValue}
                      type="button"
                      onClick={() => setRating(starValue)}
                      onMouseEnter={() => setHoverRating(starValue)}
                      onMouseLeave={() => setHoverRating(0)}
                      className="text-slate-300 hover:scale-110 active:scale-95 transition-transform focus:outline-none"
                    >
                      <Star
                        className={`w-6 h-6 ${
                          filled ? 'text-amber-400 fill-amber-400' : 'text-slate-350'
                        }`}
                      />
                    </button>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Comment */}
          <div className="space-y-1">
            <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
              Your Review Comment
            </label>
            <textarea
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              required
              rows={3}
              placeholder="Tell others what you think about this product..."
              className="w-full px-4 py-2 bg-white border border-slate-350 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800 resize-none"
            />
          </div>

          <button
            type="submit"
            disabled={submitting}
            className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs transition-all flex items-center justify-center gap-1.5 shadow-md disabled:bg-slate-300 disabled:shadow-none"
          >
            <Send className="w-3.5 h-3.5" />
            {submitting ? 'Submitting Review...' : 'Submit Review'}
          </button>
        </form>
      </div>

      {/* Review List */}
      <div className="space-y-4">
        {reviews.length === 0 ? (
          <p className="text-xs text-slate-400 text-center py-6">
            No reviews posted yet. Be the first to share your review!
          </p>
        ) : (
          reviews.map((rev) => (
            <div
              key={rev.id}
              className="p-4 bg-white border border-slate-200/60 rounded-2xl space-y-2 shadow-sm text-left"
            >
              <div className="flex items-center justify-between">
                <div>
                  <h5 className="font-extrabold text-slate-800 text-xs">{rev.customer_name}</h5>
                  <div className="flex text-amber-400 mt-1 gap-0.5">
                    {Array.from({ length: 5 }).map((_, i) => (
                      <Star
                        key={i}
                        className={`w-3.5 h-3.5 ${
                          i < rev.rating ? 'fill-amber-405 text-amber-405' : 'text-slate-200'
                        }`}
                      />
                    ))}
                  </div>
                </div>
                <span className="text-[10px] text-slate-400 flex items-center gap-1">
                  <Calendar className="w-3 h-3" />
                  {new Date(rev.created_at).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                  })}
                </span>
              </div>
              <p className="text-slate-600 text-xs leading-relaxed">{rev.comment}</p>
            </div>
          ))
        )}
      </div>
    </div>
  );
};
