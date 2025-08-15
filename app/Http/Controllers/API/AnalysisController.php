<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Analysis;
use App\Models\Photo;
use Barryvdh\DomPDF\Facade\Pdf;

class AnalysisController extends Controller
{
    /**
     * Kredi kullanarak resimleri analiz eder.
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'photo1' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'photo2' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = Auth::user();

        // YORUM: Kullanıcının kredisi olup olmadığını kontrol et.
        if ($user->credits < 1) {
            return response()->json(['error' => 'Yetersiz kredi', 'code' => 'NO_CREDITS'], 402);
        }

        // YORUM: Krediyi düşür.
        $user->decrement('credits');

        return $this->processAnalysis($request, $user);
    }

    /**
     * Reklam izleyerek resimleri analiz eder.
     */
    public function analyzeWithAd(Request $request)
    {
        $request->validate([
            'photo1' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'photo2' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = Auth::user();
        
        // YORUM: Burada reklam izlendiğine dair bir doğrulama mekanizması eklenebilir,
        // ancak şimdilik direkt analize izin veriyoruz.
        return $this->processAnalysis($request, $user);
    }

    /**
     * Analiz işlemini gerçekleştiren özel fonksiyon.
     */
    private function processAnalysis(Request $request, $user)
    {
        try {
            // YORUM: Fotoğrafları public diskine kaydet ve URL'lerini al.
            $path1 = $request->file('photo1')->store('photos', 'public');
            $path2 = $request->file('photo2')->store('photos', 'public');

            // YORUM: PDF içeriğini al. Gerçek senaryoda bu PDF'ten çıkarılan metin veya kurallar olmalıdır.
            // Şimdilik PDF'in ana fikrini temsil eden bir metin kullanıyoruz.
            $pdfContent = "Vaught's Practical Character Reader kitabına göre bir insanın karakterini yüz hatlarından analiz et. Özellikle alın, burun, çene, gözler ve dudak yapısına odaklan. İki fotoğraf verilecek: biri önden, diğeri yandan. Bu iki fotoğrafı karşılaştırarak detaylı bir karakter analizi sun.";

            // YORUM: Gemini API'sine gönderilecek prompt'u hazırla.
            $prompt = $pdfContent . "\n\nİşte analiz edilecek kişinin iki fotoğrafı. Bu bilgilere dayanarak kapsamlı bir karakter analizi yap.";

            // YORUM: Gemini Vision API'sine istek gönder.
            // .env dosyanıza GEMINI_API_KEY'inizi eklemeyi unutmayın.
            $apiKey = env('GEMINI_API_KEY');
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-vision:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $request->file('photo1')->getMimeType(),
                                    'data' => base64_encode(file_get_contents($request->file('photo1')))
                                ]
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => $request->file('photo2')->getMimeType(),
                                    'data' => base64_encode(file_get_contents($request->file('photo2')))
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

            if (!$response->successful() || !isset($response->json()['candidates'][0]['content']['parts'][0]['text'])) {
                 // YORUM: Hata durumunda yüklenen fotoğrafları sil.
                Storage::disk('public')->delete([$path1, $path2]);
                return response()->json(['error' => 'Yapay zeka analizi sırasında bir hata oluştu.'], 500);
            }

            $analysisText = $response->json()['candidates'][0]['content']['parts'][0]['text'];

            // YORUM: Analizi veritabanına kaydet.
            $analysis = Analysis::create([
                'user_id' => $user->id,
                'analysis_text' => $analysisText,
                'detected_features' => json_encode([]), // Gemini'den gelen ek veriler buraya eklenebilir.
            ]);

            // YORUM: Fotoğrafları veritabanına kaydet.
            Photo::create([
                'analysis_id' => $analysis->id,
                'photo_url' => Storage::url($path1),
                'profile_type' => 'front',
            ]);

            Photo::create([
                'analysis_id' => $analysis->id,
                'photo_url' => Storage::url($path2),
                'profile_type' => 'side',
            ]);

            return response()->json(['analysis' => $analysis->load('photos')]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Sunucu hatası: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Kullanıcının geçmiş analizlerini listeler.
     */
    public function getAnalysisHistory()
    {
        $user = Auth::user();
        $analyses = $user->analyses()->with('photos')->orderBy('created_at', 'desc')->get();
        return response()->json($analyses);
    }
    
    /**
     * Belirtilen analizi PDF olarak indirir.
     */
    public function downloadPdf($id)
    {
        $analysis = Analysis::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        
        $pdf = Pdf::loadView('pdf.analysis', ['analysis' => $analysis]);
        
        return $pdf->download('karakter-analizi-'.$id.'.pdf');
    }

    /**
     * Analiz metnini sese dönüştürür ve stream eder.
     */
    public function getAnalysisAudio($id)
    {
        $analysis = Analysis::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        
        // YORUM: Google Text-to-Speech API entegrasyonu
        // .env dosyanıza GOOGLE_TTS_API_KEY'inizi eklemeyi unutmayın.
        $apiKey = env('GOOGLE_TTS_API_KEY');
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://texttospeech.googleapis.com/v1/text:synthesize?key={$apiKey}", [
            'input' => [
                'text' => $analysis->analysis_text
            ],
            'voice' => [
                'languageCode' => 'tr-TR',
                'name' => 'tr-TR-Wavenet-A', // Kadın sesi
                'ssmlGender' => 'FEMALE'
            ],
            'audioConfig' => [
                'audioEncoding' => 'MP3'
            ]
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Seslendirme sırasında bir hata oluştu.'], 500);
        }

        $audioContent = base64_decode($response->json()['audioContent']);

        return response($audioContent, 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'inline; filename="analysis-'.$id.'.mp3"',
        ]);
    }
}
