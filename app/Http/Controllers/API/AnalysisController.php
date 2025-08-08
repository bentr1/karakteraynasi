amespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Analysis;
use App\Models\Photo;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class AnalysisController extends Controller
{
    // ... (index, downloadPdf, kredi/reklam fonksiyonları aynı kalacak)
    public function __construct() { /* ... */ }
    public function index() { /* ... */ }
    public function downloadPdf($id) { /* ... */ }
    public function startAnalysisWithCredit(Request $request) { /* ... */ }
    public function startAnalysisWithAd(Request $request) { /* ... */ }
    public function addCredits(Request $request) { /* ... */ }

    /**
     * Ortak Analiz Fonksiyonu (YENİ YÖNTEMLE GÜNCELLENDİ)
     */
    private function performAnalysis(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'front_profile' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'side_profile' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $frontPhoto = $request->file('front_profile');
            
            // YENİ: Harici API'ye göndererek yüz özelliklerini al
            $detectedFeatures = $this->getRealFeaturesFromApi($frontPhoto);
            
            if (isset($detectedFeatures['error'])) {
                 return response()->json(['error' => 'Yüz analizi başarısız: ' . $detectedFeatures['error']], 500);
            }

            $featuresString = implode(', ', $detectedFeatures['features']);

            $prompt = "Sen, 1902 tarihli 'Vaught's Practical Character Reader' adlı kitaptaki fizyonomi ve frenoloji ilkelerine hakim bir karakter analistisin. Sana verilen yüz özelliklerine dayanarak, bu kitaptaki anlatım dilini ve tarzını kullanarak Türkçe bir karakter analizi oluştur. Analizin başında, sonuçların bilimsel olmadığını ve sadece eğlence amaçlı tarihi bir yoruma dayandığını belirten kısa bir not ekle. Analiz edilecek özellikler şunlar: " . $featuresString;

            // ... (Gemini'ye istek atma ve veritabanına kaydetme kodları aynı)
            $geminiApiKey = env('GEMINI_API_KEY');
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$geminiApiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]);
            $analysisText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'Analiz sonucu alınamadı.';
            
            $user = Auth::user();
            $analysis = new Analysis();
            $analysis->user_id = $user->id;
            $analysis->analysis_text = $analysisText;
            $analysis->detected_features = json_encode($detectedFeatures['features']);
            $analysis->save();
            
            // Fotoğrafı kaydetme (bu kısım aynı kalıyor)
            $frontPhotoPath = $frontPhoto->store('profiles', 'public');
            Photo::create([
                'analysis_id' => $analysis->id,
                'photo_url' => $frontPhotoPath,
                'profile_type' => 'front'
            ]);

            return response()->json(['message' => 'Analiz başarıyla tamamlandı.', 'analysis' => $analysis], 201);

        } catch (Exception $e) {
            return response()->json(['error' => 'Analiz sırasında bir hata oluştu.', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * YENİ FONKSİYON: Resmi Face++ API'sine gönderir ve özellikleri alır.
     */
    private function getRealFeaturesFromApi($imageFile)
    {
        $apiKey = env('FACEPLUSPLUS_API_KEY');
        $apiSecret = env('FACEPLUSPLUS_API_SECRET');

        if (!$apiKey || !$apiSecret) {
            return ['error' => 'Face++ API anahtarları yapılandırılmamış.'];
        }

        $response = Http::asMultipart()
            ->post('https://api-us.faceplusplus.com/facepp/v3/detect', [
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'image_file' => $imageFile,
                'return_attributes' => 'gender,age,ethnicity,emotion,beauty,skinstatus'
            ]);

        if ($response->failed() || empty($response->json()['faces'])) {
            return ['error' => 'Harici API ile yüz algılanamadı veya bir hata oluştu.'];
        }

        $faceData = $response->json()['faces'][0]['attributes'];
        
        // API'den gelen veriyi bizim istediğimiz metin formatına çeviriyoruz
        $features = [];
        $features[] = $faceData['gender']['value'] == 'Male' ? 'erkek' : 'kadın';
        $features[] = 'yaklaşık ' . $faceData['age']['value'] . ' yaşında';
        $features[] = 'ten rengi ' . $faceData['ethnicity']['value'];
        
        // En baskın duyguyu bul
        $emotions = (array)$faceData['emotion'];
        arsort($emotions); // Duyguları en yüksekten düşüğe sırala
        $dominantEmotion = array_key_first($emotions);
        $features[] = 'baskın duygusu ' . $dominantEmotion;

        return ['features' => $features];
    }
}
