'use client';

import React from 'react';
import { useCart } from '@/context/CartContext';
import { Plus, Minus, ShoppingCart } from 'lucide-react';

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

  const cartItem = cart.find((item) => item.id === product.id);

  if (cartItem) {
    return (
      <div className="flex items-center bg-emerald-50 border border-emerald-200 rounded-xl overflow-hidden shadow-inner">
        <button
          onClick={() => updateQuantity(product.id, cartItem.quantity - 1)}
          className="p-2 hover:bg-emerald-100/50 text-emerald-700 transition-colors"
          title="Decrease quantity"
        >
          <Minus className="w-3.5 h-3.5" />
        </button>
        <span className="px-3 text-xs font-mono font-bold text-emerald-800 min-w-[24px] text-center">
          {cartItem.quantity}
        </span>
        <button
          onClick={() => updateQuantity(product.id, cartItem.quantity + 1)}
          className="p-2 hover:bg-emerald-100/50 text-emerald-700 transition-colors"
          title="Increase quantity"
        >
          <Plus className="w-3.5 h-3.5" />
        </button>
      </div>
    );
  }

  return (
    <button
      onClick={() => addToCart(product, 1)}
      className="p-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-all shadow-md active:scale-95 flex items-center justify-center"
      title="Add to Cart"
    >
      <ShoppingCart className="w-4 h-4" />
    </button>
  );
};
