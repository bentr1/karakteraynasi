<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction; // YORUM: Transaction modelini import et.

class PaymentController extends Controller
{
    /**
     * Mobil uygulamadan gelen satın alma işlemini işler.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function purchaseCredits(Request $request)
    {
        $request->validate([
            'receipt' => 'required|string',
            'platform' => 'required|in:google,apple',
            'productId' => 'required|string',
        ]);

        $user = Auth::user();
        $platform = $request->input('platform');
        $receipt = $request->input('receipt');
        $productId = $request->input('productId');

        // YORUM: Burada Google/Apple sunucularıyla receipt doğrulaması yapılmalıdır.
        $isValidPurchase = true; // SİMÜLASYON

        if (!$isValidPurchase) {
            return response()->json(['error' => 'Geçersiz satın alma bilgisi.'], 422);
        }

        $creditsToAdd = 0;
        if ($productId === 'credit_1') {
            $creditsToAdd = 1;
        } elseif ($productId === 'credit_5') {
            $creditsToAdd = 5;
        }

        if ($creditsToAdd > 0) {
            $user->increment('credits', $creditsToAdd);

            // YORUM: İşlemi loglamak için transaction kaydı oluştur.
            Transaction::create([
                'user_id' => $user->id,
                'platform' => $platform,
                'receipt' => $receipt,
                'product_id' => $productId,
                'credits_added' => $creditsToAdd,
            ]);

            return response()->json([
                'message' => 'Krediler başarıyla eklendi.',
                'new_credit_balance' => $user->credits,
            ]);
        }

        return response()->json(['error' => 'Geçersiz ürün IDsi.'], 400);
    }
}
