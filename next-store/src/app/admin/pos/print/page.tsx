'use client';

import React, { useState, useEffect, Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import { supabase } from '@/lib/supabase';

interface SaleItem {
  id: number;
  prod_name: string;
  prod_weight?: string;
  price: number;
  quantity: number;
}

interface Sale {
  id: number;
  created_at: string;
  payment_method: string;
  transaction_type: string;
  total_amount: number;
  cashier_name?: string;
}

function ReceiptContent() {
  const searchParams = useSearchParams();
  const saleId = searchParams.get('sale_id');

  const [sale, setSale] = useState<Sale | null>(null);
  const [items, setItems] = useState<SaleItem[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!saleId) return;

    const fetchReceiptData = async () => {
      try {
        // 1. Fetch sale detail
        const { data: saleData, error: saleErr } = await supabase
          .from('sales')
          .select(`
            id,
            created_at,
            payment_method,
            transaction_type,
            total_amount,
            cashier_id
          `)
          .eq('id', saleId)
          .single();

        if (saleErr) throw saleErr;

        let cashierName = 'System';
        if (saleData.cashier_id) {
          const { data: profileData } = await supabase
            .from('profiles')
            .select('name')
            .eq('id', saleData.cashier_id)
            .single();
          if (profileData?.name) cashierName = profileData.name;
        }

        // 2. Fetch sale items joined with products
        const { data: itemsData, error: itemsErr } = await supabase
          .from('sale_items')
          .select('id, quantity, price, product_id')
          .eq('sale_id', saleId);

        if (itemsErr) throw itemsErr;

        // Fetch product names for details
        const formattedItems: SaleItem[] = [];
        for (const item of itemsData) {
          const { data: prodData } = await supabase
            .from('products')
            .select('name, weight')
            .eq('id', item.product_id)
            .single();
          
          formattedItems.push({
            id: item.id,
            prod_name: prodData?.name || 'Unknown Product',
            prod_weight: prodData?.weight || '',
            price: Number(item.price),
            quantity: item.quantity
          });
        }

        setSale({
          id: saleData.id,
          created_at: saleData.created_at,
          payment_method: saleData.payment_method,
          transaction_type: saleData.transaction_type,
          total_amount: Number(saleData.total_amount),
          cashier_name: cashierName
        });
        setItems(formattedItems);

      } catch (err) {
        console.error('Error fetching receipt data:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchReceiptData();
  }, [saleId]);

  // Auto-trigger browser print dialog on load
  useEffect(() => {
    if (!loading && sale) {
      const timer = setTimeout(() => {
        window.print();
      }, 600);
      return () => clearTimeout(timer);
    }
  }, [loading, sale]);

  if (loading) {
    return <div className="text-center font-mono py-10 text-[11px] text-slate-500">Generating Thermal Invoice...</div>;
  }

  if (!sale) {
    return <div className="text-center font-mono py-10 text-[11px] text-rose-500">Error: Sale record not found.</div>;
  }

  // Pre-discount subtotal calculations
  const subtotal = items.reduce((sum, item) => sum + item.price * item.quantity, 0);
  const discountAmount = subtotal - sale.total_amount;
  const discountPercent = subtotal > 0 ? Math.round((discountAmount / subtotal) * 100) : 0;

  return (
    <div className="thermal-receipt-container">
      {/* CSS Reset inside page */}
      <style jsx global>{`
        @page {
          size: auto;
          margin: 0;
        }
        @media print {
          body {
            margin: 0;
            padding: 5px;
            width: 270px;
            background: #fff;
            color: #000;
          }
          .no-print {
            display: none !important;
          }
        }
        body {
          background-color: #fff;
          color: #000;
          margin: 0;
          padding: 8px;
          font-family: 'Courier New', Courier, monospace;
          font-size: 11px;
          width: 270px;
          text-align: left;
        }
      `}</style>

      {/* Header Info */}
      <div style={{ textAlign: 'center', marginBottom: '10px', lineHeight: '1.4' }}>
        <h3 style={{ margin: '0', fontSize: '15px', fontWeight: 'bold', textTransform: 'uppercase' }}>HR TRADERS</h3>
        <p style={{ margin: '2px 0', fontSize: '10px' }}>Main Bazaar, Lahore, Pakistan</p>
        <p style={{ margin: '2px 0', fontSize: '10px' }}>Ph: +92 333 7155323 | WhatsApp: 03337155323</p>
      </div>

      <div style={{ borderTop: '1px dashed #000', margin: '6px 0' }}></div>

      {/* Metadata */}
      <table style={{ width: '105%', fontSize: '10px', borderCollapse: 'collapse', marginBottom: '8px' }}>
        <tbody>
          <tr>
            <td><strong>Invoice ID:</strong></td>
            <td style={{ textAlign: 'right' }}>#HRT-POS-{sale.id.toString().padStart(6, '0')}</td>
          </tr>
          <tr>
            <td><strong>Date:</strong></td>
            <td style={{ textAlign: 'right' }}>{new Date(sale.created_at).toLocaleString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })}</td>
          </tr>
          <tr>
            <td><strong>Cashier:</strong></td>
            <td style={{ textAlign: 'right' }}>{sale.cashier_name || 'System'}</td>
          </tr>
          <tr>
            <td><strong>Type:</strong></td>
            <td style={{ textAlign: 'right' }}>{sale.transaction_type === 'POS' ? 'In-Store POS' : 'Online Delivery'}</td>
          </tr>
          <tr>
            <td><strong>Payment:</strong></td>
            <td style={{ textAlign: 'right' }}>{sale.payment_method}</td>
          </tr>
        </tbody>
      </table>

      <div style={{ borderTop: '1px dashed #000', margin: '6px 0' }}></div>

      {/* Items list */}
      <table style={{ width: '105%', fontSize: '10px', borderCollapse: 'collapse', marginBottom: '8px' }}>
        <thead>
          <tr style={{ borderBottom: '1px solid #000' }}>
            <th style={{ textAlign: 'left', paddingBottom: '3px' }}>Item (Weight)</th>
            <th style={{ textAlign: 'center', paddingBottom: '3px' }}>Qty</th>
            <th style={{ textAlign: 'right', paddingBottom: '3px' }}>Total</th>
          </tr>
        </thead>
        <tbody>
          {items.map((item) => (
            <tr key={item.id}>
              <td style={{ padding: '3px 0', verticalAlign: 'top', maxWidth: '140px', wordBreak: 'break-all' }}>
                {item.prod_name} {item.prod_weight ? `(${item.prod_weight})` : ''}
              </td>
              <td style={{ textAlign: 'center', padding: '3px 0', verticalAlign: 'top' }}>{item.quantity}</td>
              <td style={{ textAlign: 'right', padding: '3px 0', verticalAlign: 'top' }}>Rs. {(item.price * item.quantity).toFixed(0)}</td>
            </tr>
          ))}
        </tbody>
      </table>

      <div style={{ borderTop: '1px dashed #000', margin: '6px 0' }}></div>

      {/* Totals math */}
      <div style={{ marginLeft: 'auto', width: '80%', fontSize: '10px' }}>
        <table style={{ width: '105%', borderCollapse: 'collapse' }}>
          <tbody>
            <tr>
              <td>Subtotal:</td>
              <td style={{ textAlign: 'right' }}>Rs. {subtotal.toFixed(0)}</td>
            </tr>
            {discountAmount > 0 && (
              <tr>
                <td>Discount ({discountPercent}%):</td>
                <td style={{ textAlign: 'right' }}>-Rs. {discountAmount.toFixed(0)}</td>
              </tr>
            )}
            <tr style={{ fontWeight: 'bold', fontSize: '11px' }}>
              <td>Net Payable:</td>
              <td style={{ textAlign: 'right' }}>Rs. {sale.total_amount.toFixed(0)}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div style={{ borderTop: '1px dashed #000', margin: '8px 0' }}></div>

      {/* Receipt Footer */}
      <div style={{ textAlign: 'center', fontSize: '9px', lineHeight: '1.4' }}>
        <p style={{ margin: '2px 0' }}><strong>Thank you for shopping with us!</strong></p>
        <p style={{ margin: '2px 0' }}>Please review your items at the counter.</p>
        <p style={{ margin: '2px 0' }}>Developed by HR Traders Technical Desk</p>
      </div>

      {/* Close Receipt Window trigger */}
      <div className="no-print" style={{ marginTop: '20px', textAlign: 'center' }}>
        <button 
          onClick={() => window.close()} 
          style={{ padding: '6px 12px', fontSize: '10px', fontWeight: 'bold', background: '#e2e8f0', border: '1px solid #cbd5e1', borderRadius: '6px', cursor: 'pointer' }}
        >
          Close Window
        </button>
      </div>
    </div>
  );
}

export default function POSReceiptPrintPage() {
  return (
    <Suspense fallback={<div className="text-center font-mono py-10 text-[11px] text-slate-500">Loading thermal configurations...</div>}>
      <ReceiptContent />
    </Suspense>
  );
}
