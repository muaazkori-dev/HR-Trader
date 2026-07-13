'use client';

import React from 'react';
import { useCart } from '@/context/CartContext';
import { useRouter } from 'next/navigation';
import { Plus, Minus } from 'lucide-react';

interface AddToCartButtonProps {
  product: {
    id: number;
    name: string;
    price: number;
    image: string;
    weight?: string;
    unit?: string;
  };
}

export const AddToCartButton: React.FC<AddToCartButtonProps> = ({ product }) => {
  const { cart, addToCart, updateQuantity } = useCart();
  const router = useRouter();

  const cartItem = cart.find((item) => item.id === product.id);

  const handleBuyNow = (e: React.MouseEvent) => {
    e.stopPropagation();
    e.preventDefault();
    if (!cartItem) {
      addToCart(product, 1);
    }
    router.push('/checkout');
  };

  return (
    <div className="flex items-center gap-2 w-full mt-1.5">
      {cartItem ? (
        <div className="flex items-center bg-slate-50 border border-slate-200 rounded-xl overflow-hidden shadow-inner flex-1 justify-between h-9">
          <button
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              e.preventDefault();
              updateQuantity(product.id, cartItem.quantity - 1);
            }}
            className="px-2.5 py-2 hover:bg-slate-100 text-slate-500 transition-colors h-full flex items-center justify-center"
            title="Decrease quantity"
          >
            <Minus className="w-3 h-3" />
          </button>
          <span className="text-xs font-mono font-bold text-slate-800">
            {cartItem.quantity}
          </span>
          <button
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              e.preventDefault();
              updateQuantity(product.id, cartItem.quantity + 1);
            }}
            className="px-2.5 py-2 hover:bg-slate-100 text-slate-700 transition-colors h-full flex items-center justify-center"
            title="Increase quantity"
          >
            <Plus className="w-3 h-3" />
          </button>
        </div>
      ) : (
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            e.preventDefault();
            addToCart(product, 1);
          }}
          className="flex-1 h-9 bg-slate-50 hover:bg-slate-100 text-slate-800 border border-slate-200 font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-1 shadow-sm"
        >
          <Plus className="w-3.5 h-3.5 text-slate-500" /> Add
        </button>
      )}

      <button
        type="button"
        onClick={handleBuyNow}
        className="flex-1 h-9 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-all flex items-center justify-center shadow-md active:scale-[0.98] whitespace-nowrap"
      >
        Buy Now
      </button>
    </div>
  );
};
