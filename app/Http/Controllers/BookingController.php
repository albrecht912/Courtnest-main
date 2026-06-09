<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function create()
    {
        $courts = Court::all(); 
        
        $bookedSlots = Booking::where('booking_date', '>=', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->get()
            ->map(function($slot) {
                return [
                    'court_id' => $slot->court_id,
                    'booking_date' => Carbon::parse($slot->booking_date)->format('Y-m-d'),
                    'start_hour' => (int)Carbon::parse($slot->start_time)->format('H'),
                    'duration' => (int)$slot->duration
                ];
            });
        
        return view('bookings.create', compact('courts', 'bookedSlots'));
    }

    public function store(Request $request)
    {
        $request->validate([
    'court_id' => 'required|integer|exists:courts,id',
    'booking_date' => 'required|date|after_or_equal:today|before_or_equal:' . now()->addMonths(3)->toDateString(),
    'start_time' => 'required|date_format:H:i:s|in:08:00:00,09:00:00,10:00:00,11:00:00,12:00:00,13:00:00,14:00:00,15:00:00,16:00:00,17:00:00,18:00:00,19:00:00,20:00:00,21:00:00,22:00:00',
    'duration' => 'required|integer|min:1|max:3',
]); // Enhanced for input validation//

        $duration = (int)$request->duration;
        $newStart = Carbon::parse($request->start_time);
        $newEnd = (clone $newStart)->addHours($duration);

        if ($request->booking_date == now()->toDateString()) {
            if ($newStart->lt(now())) {
                return back()->withErrors(['error' => 'You cannot book a time slot that has already passed today.'])->withInput();
            }
        }

        $overlap = Booking::where('court_id', $request->court_id)
            ->where('booking_date', $request->booking_date)
            ->where('status', '!=', 'cancelled')
            ->get() 
            ->filter(function ($existing) use ($newStart, $newEnd) {
                $existingStart = Carbon::parse($existing->start_time);
                $existingEnd = (clone $existingStart)->addHours((int)$existing->duration);
                return ($newStart->lt($existingEnd) && $newEnd->gt($existingStart));
            })->isNotEmpty();

        if ($overlap) {
            return back()->withErrors(['error' => 'Occupied! The court is reserved during this time range.'])->withInput();
        }

        $court = Court::findOrFail($request->court_id);

        $tempBooking = [
            'court_id' => $request->court_id,
            'court_name' => $court->name,
            'sport_type' => $court->sport_type,
            'booking_date' => $request->booking_date,
            'start_time' => $newStart->toTimeString(),
            'duration' => $duration,
            'total_price' => $court->price_per_hour * $duration,
        ];

        session(['pending_booking' => $tempBooking]);

        return redirect()->route('bookings.checkout');
    }

    public function checkout()
    {
        $booking = session('pending_booking');

        if (!$booking) {
            return redirect()->route('bookings.create')->withErrors(['error' => 'No pending booking found.']);
        }
        
        $booking = (object) $booking;

        return view('bookings.checkout', compact('booking'));
    }

    public function pay()
    {
        $data = session('pending_booking');

        if (!$data) {
            return redirect()->route('bookings.create')->withErrors(['error' => 'Payment session expired.']);
        }

        Booking::create([
            'user_id' => Auth::id(),
            'court_id' => $data['court_id'],
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'duration' => $data['duration'],
            'total_price' => $data['total_price'],
            'status' => 'upcoming',
        ]);

        session()->forget('pending_booking');

        return redirect()->route('dashboard')->with('success', 'Payment Successful! Your court is secured.');
    }

    public function cancel(Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'Booking has been cancelled.');
    }
}
