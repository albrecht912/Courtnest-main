<x-app-layout>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        ::-webkit-calendar-picker-indicator {
            filter: invert(38%) sepia(24%) saturate(1200%) hue-rotate(160deg) brightness(105%) contrast(95%);
            cursor: pointer;
        }

        option:disabled {
            color: #94a3b8 !important;
            background-color: #f8fafc !important;
            font-style: italic;
        }

        /* Smooth transition for price pop-in */
        #price-preview {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
    </style>

    <div class="min-h-screen bg-[#F8FAFC] font-sans selection:bg-[#22C55E] selection:text-white py-12">

        <div class="max-w-[1200px] mx-auto px-4 sm:px-8 lg:px-12">

            <div class="relative overflow-hidden rounded-[32px] border border-black/5 bg-white shadow-[0_30px_100px_rgba(15,23,42,0.08)]">
                
                <div class="absolute -top-24 -right-24 w-[400px] h-[400px] rounded-full bg-blue-500/5 blur-[80px]"></div>
                <div class="absolute -bottom-24 -left-24 w-[400px] h-[400px] rounded-full bg-[#22C55E]/5 blur-[80px]"></div>

                <div class="relative p-8 sm:p-12">

                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8 mb-12">
                        <div class="space-y-2">
                            <span class="text-[#22C55E] font-black uppercase tracking-[0.2em] text-[22px]">Reservations</span>
                            <h2 class="text-4xl sm:text-5xl font-black tracking-tight text-[#0b1320]">
                                Book Your <span class="text-[#16a34a]">Arena</span>
                            </h2>
                        </div>

                        <div id="price-preview" class="hidden transform scale-95 opacity-0 lg:scale-100 lg:opacity-100">
                            <div class="bg-[#0b1320] p-6 rounded-2xl shadow-2xl border border-white/5 min-w-[240px] relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-[#22C55E]/10 rounded-full blur-2xl"></div>
                                <p class="text-white text-[20px] font-black uppercase tracking-widest mb-1">Total :</p>
                                <div id="total-price-tag" class="text-white text-2xl font-black tracking-tighter">RM 0.00</div>
                                <div class="mt-2 flex items-center gap-2">
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="mb-8 rounded-2xl border-l-4 border-red-500 bg-red-50 p-5">
                            <div class="flex items-center gap-3 mb-2">
                                <i class="bi bi-exclamation-octagon-fill text-red-500"></i>                            </div>
                            <ul class="text-sm font-semibold text-red-600/80 space-y-1 ml-7 list-disc">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div id="booking-data-bridge" data-booked="{{ json_encode($bookedSlots, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"></div>

                    <form action="{{ route('bookings.store') }}" method="POST" id="bookingForm" class="space-y-10">
                        @csrf

                        <div class="space-y-4">
                            <label class="flex items-center gap-2 text-[#0b1320] font-black uppercase text-[12px] tracking-widest">
                                <i class="bi bi-trophy-fill text-[#22C55E]"></i>
                                Select Arena
                            </label>
                            <div class="relative group">
                                <select name="court_id" id="court_id" required
                                    class="w-full rounded-2xl bg-slate-100 border-none px-6 py-5 font-bold text-[#0b1320] appearance-none shadow-sm focus:ring-4 focus:ring-[#22C55E]/10 transition-all cursor-pointer hover:bg-slate-100">
                                    <option value="" disabled selected>Choose a court...</option>
                                    @foreach($courts as $court)
                                        <option value="{{ $court->id }}" data-price="{{ $court->price_per_hour }}">
                                            {{ $court->name }} — RM{{ number_format($court->price_per_hour, 0) }}/hr
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="space-y-4">
                                <label class="flex items-center gap-2 text-[#0b1320] font-black uppercase text-[12px] tracking-widest">
                                    <i class="bi bi-calendar3 text-[#22C55E]"></i>
                                    Date
                                </label>
                                <input type="date" name="booking_date" id="booking_date" required min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}"
                                    class="w-full rounded-2xl bg-slate-100 border-none px-6 py-5 font-bold text-[#0b1320] shadow-sm focus:ring-4 focus:ring-[#22C55E]/10 transition-all">
                            </div>

                            <div class="space-y-4">
                                <label class="flex items-center gap-2 text-[#0b1320] font-black uppercase text-[12px] tracking-widest">
                                    <i class="bi bi-hourglass-split text-[#22C55E]"></i>
                                    Duration
                                </label>
                                <div class="relative">
                                    <select name="duration" id="duration" required
                                        class="w-full rounded-2xl bg-slate-100 border-none px-6 py-5 font-bold text-[#0b1320] appearance-none shadow-sm focus:ring-4 focus:ring-[#22C55E]/10 transition-all cursor-pointer">
                                        <option value="1">1 Hour Session</option>
                                        <option value="2">2 Hours Session</option>
                                        <option value="3">3 Hours Session</option>
                                    </select>
                                    <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <label class="flex items-center gap-2 text-[#0b1320] font-black uppercase text-[12px] tracking-widest">
                                    <i class="bi bi-clock-fill text-[#22C55E]"></i>
                                    Start Time
                                </label>
                                <div class="relative">
                                    <select name="start_time" id="start_time" required
                                        class="w-full rounded-2xl bg-slate-100 border-none px-6 py-5 font-bold text-[#0b1320] appearance-none shadow-sm focus:ring-4 focus:ring-[#22C55E]/10 transition-all cursor-pointer">
                                        <option value="" disabled selected>Select time</option>
                                        @for ($hour = 8; $hour <= 22; $hour++)
                                            <option value="{{ sprintf('%02d:00:00', $hour) }}">
                                                {{ date('h:i A', strtotime("$hour:00")) }}
                                            </option>
                                        @endfor
                                    </select>
                                    <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="relative pt-4">
                            <div class="absolute inset-0 bg-[#22C55E]/20 blur-2xl rounded-full scale-90"></div>
                            <button type="submit"
                                class="relative w-full rounded-2xl py-6 font-black uppercase tracking-[0.25em] text-xs
                                       bg-[#22C55E] hover:bg-[#16a34a] text-[#0b1320]
                                       shadow-[0_20px_40px_rgba(34,197,94,0.1)]
                                       transition-all duration-300 transform hover:translate-y-[-2px] active:translate-y-0">
                                Confirm Booking Now
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const courtSelect = document.getElementById('court_id');
            const dateInput = document.getElementById('booking_date');
            const timeSelect = document.getElementById('start_time');
            const durationSelect = document.getElementById('duration');
            const bridge = document.getElementById('booking-data-bridge');
            const pricePreview = document.getElementById('price-preview');
            const totalPriceTag = document.getElementById('total-price-tag');

            const bookedSlots = JSON.parse(bridge.getAttribute('data-booked') || '[]');

            function updateUI() {
                const selectedCourt = courtSelect.value;
                const selectedDate = dateInput.value;
                const selectedDuration = parseInt(durationSelect.value);
                const options = timeSelect.options;

                const courtOption = courtSelect.options[courtSelect.selectedIndex];
                if (selectedCourt) {
                    const price = parseFloat(courtOption.getAttribute('data-price'));
                    totalPriceTag.innerText = `RM ${(price * selectedDuration).toFixed(2)}`;
                    
                    // Improved Animation trigger
                    pricePreview.classList.remove('hidden', 'opacity-0', 'scale-95');
                    pricePreview.classList.add('opacity-100', 'scale-100');
                }

                const now = new Date();
                const todayStr = now.toLocaleDateString('en-CA');
                const currentHour = now.getHours();

                for (let i = 0; i < options.length; i++) {
                    const option = options[i];
                    if (option.value === "") continue;
                    const optionHour = parseInt(option.value.split(':')[0]);
                    
                    const isPast = (selectedDate === todayStr && optionHour <= currentHour);
                    const isOccupied = bookedSlots.some(slot => {
                        if (slot.court_id != selectedCourt || slot.booking_date !== selectedDate) return false;
                        const endHour = parseInt(slot.start_hour) + parseInt(slot.duration);
                        return (optionHour >= slot.start_hour && optionHour < endHour);
                    });

                    if (isPast || isOccupied) {
                        option.disabled = true;
                        if (!option.text.includes('(')) {
                            option.text += isPast ? ' (Passed)' : ' (Occupied)';
                        }
                    } else {
                        option.disabled = false;
                        option.text = option.text.replace(' (Passed)', '').replace(' (Occupied)', '');
                    }
                }
            }

            courtSelect.addEventListener('change', updateUI);
            dateInput.addEventListener('change', updateUI);
            durationSelect.addEventListener('change', updateUI);
            updateUI();
        });
    </script>
</x-app-layout>