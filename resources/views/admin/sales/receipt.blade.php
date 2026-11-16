<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $sale->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100 font-mono text-xs py-6">

    <div class="max-w-sm mx-auto bg-white p-6 shadow-md rounded-lg space-y-4">
        
        <!-- Pharmacy Header -->
        <x-receipt-header />

        <hr class="border-dashed">

        <!-- Invoice Meta -->
        <div class="space-y-1">
            <p><span class="font-bold">Invoice #:</span> {{ $sale->invoice_number }}</p>
            <p><span class="font-bold">Date:</span> {{ $sale->created_at->format('d-M-Y h:i A') }}</p>
        </div>

        <hr class="border-dashed">

        <!-- Items Table -->
        <table class="w-full text-left">
            <thead>
                <tr class="border-b">
                    <th class="py-1">Item</th>
                    <th class="py-1 text-center">Qty</th>
                    <th class="py-1 text-right">Price</th>
                    <th class="py-1 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-dashed">
                @foreach($sale->items as $item)
                    <tr>
                        <td class="py-1.5 font-semibold">{{ $item->medicine->name }}</td>
                        <td class="py-1.5 text-center">{{ $item->quantity }}</td>
                        <td class="py-1.5 text-right">{{ $item->unit_price }}</td>
                        <td class="py-1.5 text-right">{{ $item->subtotal }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <hr class="border-dashed">

        <!-- Totals -->
        <div class="space-y-1 text-right">
            <p><span class="font-bold float-left">Subtotal:</span> PKR {{ number_format($sale->subtotal, 2) }}</p>
            <p><span class="font-bold float-left">Total Amount:</span> PKR {{ number_format($sale->total_amount, 2) }}</p>
            <p><span class="font-bold float-left">Paid Amount:</span> PKR {{ number_format($sale->paid_amount, 2) }}</p>
            <p><span class="font-bold float-left">Change:</span> PKR {{ number_format($sale->change_amount, 2) }}</p>
        </div>

        <hr class="border-dashed">

        <!-- Footer -->
        <div class="text-center text-gray-500 pt-2 space-y-1">
            <p>Thank you for your purchase!</p>
            <p class="text-[10px]">{{ $accountProfile?->receipt_footer ?? 'Software Developed By Pharmacy Team' }}</p>
        </div>

        <!-- Print Button -->
        <div class="no-print pt-4 text-center">
            <button onclick="window.print()" class="w-full bg-blue-600 text-white font-sans py-2 rounded font-bold shadow hover:bg-blue-700">Print Receipt</button>
        </div>

    </div>

</body>
</html>