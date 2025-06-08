<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['booking.user', 'booking.hotel']);
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        
        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $payments = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'payment_method' => 'required|in:credit_card,bank_transfer,e_wallet,virtual_account,cash',
            'amount' => 'required|numeric|min:0',
            'payment_details' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if booking already has payment
        $booking = Booking::findOrFail($request->booking_id);
        if ($booking->payment) {
            return response()->json(['error' => 'Booking already has payment'], 422);
        }

        // Validate amount matches booking total
        if ($request->amount != $booking->total_price) {
            return response()->json(['error' => 'Payment amount does not match booking total'], 422);
        }

        $payment = Payment::create([
            'booking_id' => $request->booking_id,
            'payment_method' => $request->payment_method,
            'amount' => $request->amount,
            'status' => 'pending',
            'payment_details' => $request->payment_details
        ]);

        return response()->json($payment->load('booking'), 201);
    }

    public function show($id)
    {
        $payment = Payment::with(['booking.user', 'booking.hotel'])->findOrFail($id);
        return response()->json($payment);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'payment_method' => 'sometimes|required|in:credit_card,bank_transfer,e_wallet,virtual_account,cash',
            'payment_details' => 'nullable|array',
            'transaction_id' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Only allow updates if payment is still pending
        if ($payment->status !== 'pending') {
            return response()->json(['error' => 'Cannot update non-pending payment'], 422);
        }

        $payment->update($request->all());
        return response()->json($payment);
    }

    public function markAsPaid(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($payment->status !== 'pending') {
            return response()->json(['error' => 'Payment is not pending'], 422);
        }

        $payment->markAsPaid($request->transaction_id);
        
        // Auto-confirm booking when payment is successful
        $payment->booking->update([
            'status' => 'confirmed',
            'confirmed_at' => now()
        ]);

        return response()->json($payment->load('booking'));
    }

    public function markAsFailed($id)
    {
        $payment = Payment::findOrFail($id);
        
        if ($payment->status !== 'pending') {
            return response()->json(['error' => 'Payment is not pending'], 422);
        }

        $payment->markAsFailed();
        return response()->json($payment);
    }

    public function markAsRefunded($id)
    {
        $payment = Payment::findOrFail($id);
        
        if ($payment->status !== 'paid') {
            return response()->json(['error' => 'Payment is not paid'], 422);
        }

        $payment->markAsRefunded();
        
        // Cancel the booking if payment is refunded
        $payment->booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        return response()->json($payment->load('booking'));
    }
}